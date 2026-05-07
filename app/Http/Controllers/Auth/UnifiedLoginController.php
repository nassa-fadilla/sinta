<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SiaClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UnifiedLoginController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /**
     * Tampilkan halaman login terpadu.
     */
    public function showLoginForm(Request $request)
    {
        $captcha = $this->refreshCaptcha($request);

        return view('auth.login', [
            'captchaA' => $captcha['angka_pertama'],
            'captchaB' => $captcha['angka_kedua'],
        ]);
    }

    /**
     * Proses login terpadu:
     * - Admin: email + password
     * - Kepsek / Wali Kelas: NUPTK + tanggal lahir
     * - Ortu: NIS + tanggal lahir siswa
     */
    public function login(Request $request)
    {
        $data = $request->validate(
            [
                'identity' => ['required', 'string'],
                'credential' => ['required', 'string'],
                'captcha_answer' => ['required', 'numeric'],
            ],
            [
                'captcha_answer.required' => 'Captcha wajib diisi.',
                'captcha_answer.numeric' => 'Jawaban captcha harus berupa angka.',
            ],
            [
                'identity' => 'Email / NUPTK / NIS',
                'credential' => 'Password / Tanggal Lahir',
                'captcha_answer' => 'Captcha',
            ]
        );

        $identity = trim($data['identity']);
        $credential = trim($data['credential']);
        $remember = $request->boolean('remember');

        /*
        |--------------------------------------------------------------------------
        | VALIDASI CAPTCHA
        |--------------------------------------------------------------------------
        */
        $captchaJawabanSession = (int) $request->session()->get('login_captcha_jawaban', -1);
        $captchaJawabanInput = (int) $data['captcha_answer'];

        if ($captchaJawabanSession < 0 || $captchaJawabanInput !== $captchaJawabanSession) {
            $this->refreshCaptcha($request);

            throw ValidationException::withMessages([
                'captcha_answer' => 'Jawaban captcha salah. Silakan coba lagi.',
            ])->redirectTo(route('login'));
        }

        /*
        |--------------------------------------------------------------------------
        | 1) LOGIN ADMIN (EMAIL)
        |--------------------------------------------------------------------------
        */
        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $user = $this->attemptAdminLogin($request, $identity, $credential, $remember);

            if (!$user) {
                $this->refreshCaptcha($request);

                throw ValidationException::withMessages([
                    'identity' => 'Email atau kata sandi salah.',
                ])->redirectTo(route('login'));
            }

            $request->session()->regenerate();
            $this->refreshCaptcha($request);

            return $this->redirectByRole($user);
        }

        /*
        |--------------------------------------------------------------------------
        | 2) LOGIN KEPSEK / WALI KELAS (NUPTK)
        |--------------------------------------------------------------------------
        */
        $user = $this->attemptGuruLogin($identity, $credential);

        if ($user) {
            Auth::login($user, $remember);
            $request->session()->regenerate();
            $this->refreshCaptcha($request);

            return $this->redirectByRole($user);
        }

        /*
        |--------------------------------------------------------------------------
        | 3) LOGIN ORANG TUA (NIS)
        |--------------------------------------------------------------------------
        */
        $user = $this->attemptOrtuLogin($identity, $credential);

        if ($user) {
            Auth::login($user, $remember);
            $request->session()->regenerate();
            $this->refreshCaptcha($request);

            return $this->redirectByRole($user)
                ->with('ok', 'Selamat datang, ' . $user->name);
        }

        /*
        |--------------------------------------------------------------------------
        | 4) GAGAL LOGIN
        |--------------------------------------------------------------------------
        */
        $this->refreshCaptcha($request);

        throw ValidationException::withMessages([
            'identity' => 'Kredensial tidak cocok atau akun tidak ditemukan.',
        ])->redirectTo(route('login'));
    }

    /**
     * Logout terpadu.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Login admin lokal SINTA.
     */
    private function attemptAdminLogin(Request $request, string $email, string $password, bool $remember = false): ?User
    {
        if (
            !Auth::attempt([
                'email' => $email,
                'password' => $password,
            ], $remember)
        ) {
            return null;
        }

        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return null;
        }

        return $user;
    }

    /**
     * Login kepsek / wali kelas berdasarkan NUPTK + tanggal lahir.
     * Semua data SIA diambil via API, bukan model lokal.
     */
    private function attemptGuruLogin(string $nuptk, string $credential): ?User
    {
        $nuptk = trim($nuptk);

        if ($nuptk === '') {
            return null;
        }

        $plainDob = $this->normalizeDateToYmd($credential);
        if (!$plainDob) {
            return null;
        }

        $guruRes = $this->sia->getGuruByNuptk($nuptk);

        if (($guruRes['status'] ?? false) !== true || empty($guruRes['data'])) {
            return null;
        }

        $guru = is_array($guruRes['data']) ? $guruRes['data'] : [];
        if (empty($guru)) {
            return null;
        }

        $dobSia = $this->normalizeDateToYmd((string) ($guru['tanggal_lahir'] ?? ''));
        if (!$dobSia || $dobSia !== $plainDob) {
            return null;
        }

        $resolvedRole = $this->resolveGuruFunctionalRole($guru);

        if (!$resolvedRole) {
            return null;
        }

        $candidateEmails = [];

        if (!empty($guru['email'])) {
            $candidateEmails[] = trim((string) $guru['email']);
        }

        if (!empty($guru['nuptk'])) {
            $candidateEmails[] = trim((string) $guru['nuptk']) . '@sinta.local';
        }

        if (!empty($guru['nip'])) {
            $candidateEmails[] = trim((string) $guru['nip']) . '@sinta.local';
        }

        $candidateEmails = array_values(array_unique(array_filter($candidateEmails)));

        $user = null;

        if (!empty($candidateEmails)) {
            $user = User::query()
                ->whereIn('email', $candidateEmails)
                ->first();
        }

        if (!$user && !empty($guru['nuptk'])) {
            $user = User::query()
                ->where('sia_user_id', (string) $guru['nuptk'])
                ->first();
        }

        if (!$user && !empty($guru['nip'])) {
            $user = User::query()
                ->where('sia_user_id', (string) $guru['nip'])
                ->first();
        }

        if (!$user) {
            $email = !empty($guru['email'])
                ? trim((string) $guru['email'])
                : ((string) ($guru['nuptk'] ?? $guru['nip'] ?? ('guru' . ($guru['id'] ?? Str::random(4)))) . '@sinta.local');

            if (User::query()->where('email', $email)->exists()) {
                $base = !empty($guru['nuptk'])
                    ? trim((string) $guru['nuptk'])
                    : (!empty($guru['nip']) ? trim((string) $guru['nip']) : 'guru');

                $email = $this->uniqueLocalEmail($base, 'sinta.local');
            }

            $user = User::create([
                'name' => (string) ($guru['nama'] ?? 'Guru'),
                'email' => $email,
                'password' => Hash::make($plainDob),
                'role' => $resolvedRole,
                'sia_user_id' => (string) ($guru['nuptk'] ?? $guru['nip'] ?? ''),
            ]);
        } else {
            if (!Hash::check($plainDob, $user->password)) {
                $user->password = Hash::make($plainDob);
                $user->save();
            }
        }

        $updates = [];

        $siaUserId = (string) ($guru['nuptk'] ?? $guru['nip'] ?? '');
        if ($siaUserId !== '' && $user->sia_user_id !== $siaUserId) {
            $updates['sia_user_id'] = $siaUserId;
        }

        if (!empty($guru['nama']) && $user->name !== $guru['nama']) {
            $updates['name'] = $guru['nama'];
        }

        if (!empty($guru['email']) && $user->email !== $guru['email']) {
            $emailDipakaiUserLain = User::query()
                ->where('email', $guru['email'])
                ->where('id', '!=', $user->id)
                ->exists();

            if (!$emailDipakaiUserLain) {
                $updates['email'] = $guru['email'];
            }
        }

        if ($user->role !== $resolvedRole) {
            $updates['role'] = $resolvedRole;
        }

        if (!empty($updates)) {
            $user->update($updates);
        }

        return $user->fresh();
    }

    /**
     * Login orang tua berdasarkan NIS + tanggal lahir siswa.
     */
    private function attemptOrtuLogin(string $nisInput, string $credential): ?User
    {
        $nisInput = trim($nisInput);
        $dob = $this->normalizeDateToYmd($credential);

        if ($nisInput === '' || !$dob) {
            return null;
        }

        $apiResponse = $this->sia->getSiswaByNis($nisInput);

        if (($apiResponse['status'] ?? false) !== true || empty($apiResponse['data'])) {
            return null;
        }

        $siswaData = is_array($apiResponse['data']) ? $apiResponse['data'] : [];
        if (empty($siswaData)) {
            return null;
        }

        $tlSia = $this->normalizeDateToYmd((string) ($siswaData['tanggal_lahir'] ?? ''));
        if (!$tlSia || $tlSia !== $dob) {
            return null;
        }

        $nis = (string) ($siswaData['nis'] ?? '');
        $siswaId = $siswaData['id'] ?? null;

        if ($nis === '' || !$siswaId) {
            return null;
        }

        $emailBase = $nis;
        $defaultEmail = $emailBase . '@ortu.local';

        $user = User::query()
            ->where('role', 'ortu')
            ->where('sia_user_id', $nis)
            ->first();

        if (!$user) {
            $user = User::query()
                ->where('role', 'ortu')
                ->where('siswa_id', $siswaId)
                ->first();
        }

        if ($user && empty($user->sia_user_id)) {
            $user->sia_user_id = $nis;

            if (is_null($user->siswa_id)) {
                $user->siswa_id = $siswaId;
            }

            $user->save();
        }

        if (!$user) {
            $email = $defaultEmail;

            if (User::query()->where('email', $email)->exists()) {
                $email = $this->uniqueEmailForParent($emailBase, (int) $siswaId);
            }

            $user = User::create([
                'sia_user_id' => $nis,
                'siswa_id' => $siswaId,
                'name' => (string) ($siswaData['nama'] ?? ('Ortu ' . $nis)),
                'email' => $email,
                'password' => bcrypt(Str::random(16)),
                'role' => 'ortu',
            ]);
        } else {
            $updates = [];

            if ($user->role !== 'ortu') {
                $updates['role'] = 'ortu';
            }

            if (!empty($siswaData['nama']) && $user->name !== $siswaData['nama']) {
                $updates['name'] = $siswaData['nama'];
            }

            if (empty($user->sia_user_id)) {
                $updates['sia_user_id'] = $nis;
            }

            if (is_null($user->siswa_id)) {
                $updates['siswa_id'] = $siswaId;
            }

            if (!empty($updates)) {
                $user->update($updates);
            }
        }

        return $user->fresh();
    }

    /**
     * Menentukan role guru final berdasarkan API SIA.
     */
    private function resolveGuruFunctionalRole(array $guru): ?string
    {
        $kepsekNuptk = env('KEPSEK_NUPTK', '43265128');

        if (!empty($guru['nuptk']) && (string) $guru['nuptk'] === (string) $kepsekNuptk) {
            return 'kepsek';
        }

        $guruId = $guru['id'] ?? null;
        if (!$guruId) {
            return null;
        }

        $rombelRes = $this->sia->masterRombel(null, [
            'guru_id' => $guruId,
            'aktif' => 1,
        ]);

        $rombelList = is_array($rombelRes['data'] ?? null) ? $rombelRes['data'] : [];

        if (!empty($rombelList)) {
            return 'walkel';
        }

        return null;
    }

    /**
     * Redirect dashboard berdasarkan role.
     */
    private function redirectByRole(User $user)
    {
        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'kepsek' => redirect()->route('kepsek.dashboard'),
            'walkel' => redirect()->route('guru.dashboard'),
            'ortu' => redirect()->route('ortu.dashboard'),
            default => redirect('/'),
        };
    }

    /**
     * Normalisasi input tanggal lahir ke format Y-m-d.
     */
    private function normalizeDateToYmd(string $rawDate): ?string
    {
        $rawDate = trim($rawDate);

        if ($rawDate === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $rawDate)->format('Y-m-d');
        } catch (\Throwable $e) {
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d');
        } catch (\Throwable $e) {
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $rawDate)->format('Y-m-d');
        } catch (\Throwable $e) {
        }

        try {
            return Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Throwable $e) {
        }

        return null;
    }

    /**
     * Generate email unik untuk ortu jika email default sudah dipakai.
     */
    private function uniqueEmailForParent(string $base, int $siswaId): string
    {
        $candidate = "{$base}.{$siswaId}@ortu.local";

        if (!User::query()->where('email', $candidate)->exists()) {
            return $candidate;
        }

        $i = 2;

        do {
            $candidate = "{$base}.{$siswaId}.{$i}@ortu.local";
            $i++;
        } while (User::query()->where('email', $candidate)->exists());

        return $candidate;
    }

    /**
     * Generate email lokal unik untuk user non-ortu.
     */
    private function uniqueLocalEmail(string $base, string $domain = 'sinta.local'): string
    {
        $base = preg_replace('/[^a-zA-Z0-9._-]/', '', $base) ?: 'user';
        $candidate = "{$base}@{$domain}";

        if (!User::query()->where('email', $candidate)->exists()) {
            return $candidate;
        }

        $i = 2;

        do {
            $candidate = "{$base}.{$i}@{$domain}";
            $i++;
        } while (User::query()->where('email', $candidate)->exists());

        return $candidate;
    }

    /**
     * Generate captcha penjumlahan sederhana.
     */
    private function generateCaptcha(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);

        return [
            'angka_pertama' => $a,
            'angka_kedua' => $b,
            'jawaban' => $a + $b,
        ];
    }

    /**
     * Refresh captcha ke session dan kembalikan nilainya.
     */
    private function refreshCaptcha(Request $request): array
    {
        $captcha = $this->generateCaptcha();

        $request->session()->put('login_captcha_jawaban', $captcha['jawaban']);

        return $captcha;
    }
}