<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SiaClient;

class FonnteWebhookController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    public function handle(Request $r)
    {
        Log::info('[FONNTE] WEBHOOK_VERSION=2026-ADMIN-GURU-DEVICE-FINAL');

        $payload = $r->all();

        Log::info('[FONNTE] Webhook hit', [
            'ip' => $r->ip(),
            'method' => $r->method(),
            'payload' => $payload,
        ]);

        // Abaikan status-only callback
        if (
            isset($payload['state']) &&
            !isset($payload['message']) &&
            !isset($payload['pesan']) &&
            !isset($payload['sender']) &&
            !isset($payload['pengirim'])
        ) {
            Log::info('[FONNTE] ignored=status-only', [
                'state' => $payload['state'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'ignored' => 'status',
            ]);
        }

        $msg = trim((string) ($payload['message'] ?? $payload['pesan'] ?? ''));
        $sender = $this->normalizeMsisdn($payload['sender'] ?? $payload['pengirim'] ?? '');
        $msgId = $payload['id'] ?? $payload['message_id'] ?? null;

        $isQuick = isset($payload['quick']) && filter_var($payload['quick'], FILTER_VALIDATE_BOOLEAN);
        $deviceNumber = $this->extractDeviceNumber($payload);

        $adminDeviceNumber = config('services.fonnte.admin_device_number', '6285601820651');
        $guruDeviceNumber = config('services.fonnte.guru_device_number', '6283190007144');

        $adminFallbackId = (int) config('services.fonnte.default_admin_user_id', 1);

        $guruFallbackId = config('services.fonnte.default_guru_user_id');
        $guruFallbackId = $guruFallbackId !== null ? (int) $guruFallbackId : null;

        if ($msg === '' || $sender === '') {
            Log::warning('[FONNTE] Empty message or sender', [
                'msg' => $msg,
                'sender' => $sender,
                'raw_sender' => $payload['sender'] ?? $payload['pengirim'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'ignored' => true,
            ]);
        }

        // quick=true biasanya callback pesan keluar dari device/system
        // abaikan, karena pesan keluar sudah disimpan controller
        if ($isQuick) {
            Log::info('[FONNTE] ignored=outgoing-quick-callback', [
                'device' => $deviceNumber,
                'message' => $msg,
                'external_id' => $msgId,
            ]);

            return response()->json([
                'ok' => true,
                'ignored' => 'outgoing_quick',
            ]);
        }

        $deviceRole = $this->resolveDeviceRole(
            $deviceNumber,
            $adminDeviceNumber,
            $guruDeviceNumber
        );

        $parentId = $this->matchParentByPhone($sender);

        if (!$parentId) {
            Log::warning('[FONNTE] No parent matched', [
                'sender' => $sender,
                'device_number' => $deviceNumber,
                'device_role' => $deviceRole,
                'message' => $msg,
            ]);

            return response()->json([
                'ok' => true,
                'unmapped' => true,
            ]);
        }

        $assignedToUserId = null;
        $thread = null;

        /*
        |--------------------------------------------------------------------------
        | PRIORITAS 1:
        | cari thread aktif yang SUDAH ADA untuk parent ini berdasarkan jalur device
        |--------------------------------------------------------------------------
        */
        if ($deviceRole === 'guru') {
            $thread = DB::table('chat_threads as t')
                ->join('users as u', 'u.id', '=', 't.assigned_to_user_id')
                ->where('t.owner_parent_id', $parentId)
                ->whereIn('t.status', ['open', 'pending'])
                ->whereIn('u.role', ['walkel', 'guru'])
                ->orderByDesc('t.last_message_at')
                ->orderByDesc('t.id')
                ->select('t.*')
                ->first();

            if ($thread) {
                $assignedToUserId = (int) $thread->assigned_to_user_id;
            } else {
                $assignedToUserId = $guruFallbackId;
            }
        } elseif ($deviceRole === 'admin') {
            $thread = DB::table('chat_threads as t')
                ->join('users as u', 'u.id', '=', 't.assigned_to_user_id')
                ->where('t.owner_parent_id', $parentId)
                ->whereIn('t.status', ['open', 'pending'])
                ->where('u.role', 'admin')
                ->orderByDesc('t.last_message_at')
                ->orderByDesc('t.id')
                ->select('t.*')
                ->first();

            if ($thread) {
                $assignedToUserId = (int) $thread->assigned_to_user_id;
            } else {
                $assignedToUserId = $adminFallbackId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PRIORITAS 2:
        | kalau belum ada thread hasil filter role/device, coba cari thread aktif
        | parent + assignee yang sudah ditentukan
        |--------------------------------------------------------------------------
        */
        if (!$thread && $assignedToUserId) {
            $thread = DB::table('chat_threads')
                ->where('owner_parent_id', $parentId)
                ->where('assigned_to_user_id', $assignedToUserId)
                ->whereIn('status', ['open', 'pending'])
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | PRIORITAS 3:
        | fallback terakhir: ambil thread aktif terbaru parent ini
        |--------------------------------------------------------------------------
        */
        if (!$thread) {
            $thread = DB::table('chat_threads')
                ->where('owner_parent_id', $parentId)
                ->whereIn('status', ['open', 'pending'])
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->first();

            if ($thread && !$assignedToUserId) {
                $assignedToUserId = $thread->assigned_to_user_id ?: null;
            }
        }

        if (!$thread) {
            $threadId = DB::table('chat_threads')->insertGetId([
                'owner_parent_id' => $parentId,
                'assigned_to_user_id' => $assignedToUserId,
                'status' => 'open',
                'last_channel' => 'whatsapp',
                'last_message_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $threadId = (int) $thread->id;

            DB::table('chat_threads')
                ->where('id', $threadId)
                ->update([
                    'assigned_to_user_id' => $thread->assigned_to_user_id ?: $assignedToUserId,
                    'last_channel' => 'whatsapp',
                    'last_message_at' => now(),
                    'status' => 'open',
                    'updated_at' => now(),
                ]);
        }

        DB::table('chat_messages')->insert([
            'thread_id' => $threadId,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'sender_type' => 'parent',
            'sender_id' => $parentId,
            'message_type' => 'teks',
            'message_status' => 'diterima',
            'body' => $msg,
            'external_id' => $msgId,
            'delivered_at' => null,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('[FONNTE] parent inbound stored', [
            'thread_id' => $threadId,
            'parent_id' => $parentId,
            'assigned_to_user_id' => $assignedToUserId,
            'device_number' => $deviceNumber,
            'device_role' => $deviceRole,
            'body' => $msg,
        ]);

        return response()->json(['ok' => true]);
    }

    private function normalizeMsisdn(?string $s): string
    {
        $d = preg_replace('/\D+/', '', (string) $s);
        if ($d === '') {
            return '';
        }

        if (str_starts_with($d, '0')) {
            return '62' . substr($d, 1);
        }

        if (str_starts_with($d, '8')) {
            return '62' . $d;
        }

        return $d;
    }

    private function extractDeviceNumber(array $payload): ?string
    {
        $candidates = [
            $payload['device'] ?? null,
            $payload['device_number'] ?? null,
            $payload['number'] ?? null,
            $payload['from'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeMsisdn($candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function resolveDeviceRole(?string $deviceNumber, string $adminDeviceNumber, string $guruDeviceNumber): string
    {
        if ($deviceNumber && $deviceNumber === $this->normalizeMsisdn($guruDeviceNumber)) {
            return 'guru';
        }

        if ($deviceNumber && $deviceNumber === $this->normalizeMsisdn($adminDeviceNumber)) {
            return 'admin';
        }

        return 'admin';
    }

    private function matchParentByPhone(string $sender): ?int
    {
        $ortuUsers = DB::table('users')
            ->where('role', 'ortu')
            ->whereNotNull('sia_user_id')
            ->select('id', 'sia_user_id')
            ->get();

        foreach ($ortuUsers as $u) {
            $detail = $this->fetchSiswaDetailByNis($u->sia_user_id);
            if (!$detail) {
                continue;
            }

            $ayah = $this->normalizeMsisdn($detail['no_hp_ayah'] ?? '');
            $ibu = $this->normalizeMsisdn($detail['no_hp_ibu'] ?? '');

            if ($ayah !== '' && $ayah === $sender) {
                Log::info('[FONNTE] matched_by=ayah', [
                    'sender' => $sender,
                    'user_id' => (int) $u->id,
                    'nis' => $u->sia_user_id,
                ]);

                return (int) $u->id;
            }

            if ($ibu !== '' && $ibu === $sender) {
                Log::info('[FONNTE] matched_by=ibu', [
                    'sender' => $sender,
                    'user_id' => (int) $u->id,
                    'nis' => $u->sia_user_id,
                ]);

                return (int) $u->id;
            }
        }

        return null;
    }

    private function fetchSiswaDetailByNis(string $nis): ?array
    {
        $basic = $this->sia->getSiswaByNis($nis);

        if (!$basic || ($basic['status'] ?? false) !== true) {
            Log::warning('[FONNTE] gagal getSiswaByNis', [
                'nis' => $nis,
                'raw' => $basic,
            ]);
            return null;
        }

        $id = $basic['data']['id'] ?? null;
        if (!$id) {
            return null;
        }

        $detail = $this->sia->masterSiswaDetail($id);

        if (!$detail || ($detail['status'] ?? false) !== true) {
            Log::warning('[FONNTE] gagal masterSiswaDetail', [
                'nis' => $nis,
                'id' => $id,
                'raw' => $detail,
            ]);
            return null;
        }

        return $detail['data'] ?? null;
    }
}