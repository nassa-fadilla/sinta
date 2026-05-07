@extends('ortu.layout')
@section('title', 'Riwayat Chat')

@section('content')
  <div class="space-y-6">
    <section
      class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

      {{-- HEADER --}}
      <div class="border-b border-slate-200 px-4 py-5 sm:px-5 md:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex items-start gap-4">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm sm:h-14 sm:w-14">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 10h.01M12 10h.01M16 10h.01M5 18l2.5-2.5H18a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6A2 2 0 0 0 4 7v9l1 2" />
              </svg>
            </div>

            <div class="min-w-0">
              <h1 class="text-xl font-semibold tracking-tight text-slate-800 sm:text-2xl">
                Riwayat Chat
              </h1>
              <p class="mt-1 text-sm text-slate-500">
                Lihat riwayat percakapan Anda dengan pihak sekolah.
              </p>
              @if($siswa)
                <p class="mt-1 text-xs text-slate-400">
                  Siswa:
                  <span class="font-medium text-slate-700">{{ $siswa->nama }}</span>
                  • NIS {{ $siswa->nis }}
                </p>
              @endif
            </div>
          </div>

          <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
            <form method="GET" action="{{ route('ortu.chat.index') }}" id="chatSearchForm"
              class="w-full sm:flex-1 lg:w-[16rem]">
              <div
                class="relative flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-100">
                <span class="text-slate-400">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M10 4a6 6 0 0 1 4.472 9.966l3.281 3.28a1 1 0 0 1-1.414 1.415l-3.28-3.281A6 6 0 1 1 10 4z" />
                  </svg>
                </span>

                <input type="text" name="q" value="{{ $q }}" placeholder="Cari tujuan / siswa..."
                  class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:ring-0"
                  autocomplete="off" />

                <span id="searchLoader" class="hidden">
                  <div class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                  </div>
                </span>
              </div>
            </form>

            <a href="{{ route('ortu.chat.create') }}"
              class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
              <span class="text-lg leading-none">+</span>
              Kirim Pesan
            </a>
          </div>
        </div>
      </div>

      @if(session('ok'))
        <div
          class="auto-dismiss-alert border-b border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 opacity-100 transition-all duration-500 ease-in-out sm:px-5 md:px-6">
          {{ session('ok') }}
        </div>
      @endif

      @if(session('status'))
        <div
          class="auto-dismiss-alert border-b border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 opacity-100 transition-all duration-500 ease-in-out sm:px-5 md:px-6">
          {{ session('status') }}
        </div>
      @endif

      @if($errors->any())
        <div class="border-b border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 sm:px-5 md:px-6">
          <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- CHAT LAYOUT --}}
      <div class="overflow-hidden bg-white">
        <div class="flex flex-col md:flex-row md:h-[calc(100vh-210px)] md:min-h-[500px] md:max-h-[620px]">

          {{-- SIDEBAR KIRI --}}
          <aside
            class="flex w-full shrink-0 flex-col overflow-hidden border-b border-slate-200 bg-white md:h-full md:w-[310px] md:border-b-0 md:border-r lg:w-[340px]">

            <div class="shrink-0 border-b border-slate-200 bg-white px-4 py-3">
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                    Daftar Percakapan
                  </p>
                  <p class="mt-1 text-xs text-slate-500">
                    Total thread:
                    <span class="font-semibold text-blue-600">{{ $threads->count() }}</span>
                  </p>
                </div>

                <span
                  class="inline-flex shrink-0 items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                  {{ $threads->count() }} chat
                </span>
              </div>
            </div>

            <div class="max-h-[280px] overflow-y-auto md:min-h-0 md:max-h-none md:flex-1">
              @if($threads->isEmpty())
                <div class="px-4 py-8 text-center">
                  <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 10h.01M12 10h.01M16 10h.01M5 18l2.5-2.5H18a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6A2 2 0 0 0 4 7v9l1 2" />
                    </svg>
                  </div>
                  <p class="mt-3 text-sm font-medium text-slate-600">Belum ada percakapan</p>
                  <p class="mt-1 text-xs text-slate-400">
                    Chat dengan pihak sekolah akan muncul di panel ini.
                  </p>
                </div>
              @else
                <ul class="divide-y divide-slate-100 text-sm">
                  @foreach($threads as $t)
                    @php
                      $assignee = $t->assignee;
                      $namaTujuan = $assignee->name ?? 'Pihak Sekolah';
                      $roleLabel = $t->assignee_role_detail ?? 'Pihak Sekolah';

                      $lastAt = $t->last_message_at ?? $t->updated_at ?? null;
                      $lastAtText = $lastAt
                        ? \Carbon\Carbon::parse($lastAt)->timezone('Asia/Jakarta')->format('d M H:i')
                        : '-';

                      $lastBody = $t->last_message_body ?? '—';
                      $isActive = $activeThread && (int) $activeThread->id === (int) $t->id;
                      $unreadCount = (int) ($t->unread_count ?? 0);

                      $initial = \Illuminate\Support\Str::of($namaTujuan)
                        ->trim()
                        ->explode(' ')
                        ->map(fn($p) => mb_substr($p, 0, 1))
                        ->take(2)
                        ->implode('');

                      if (!$initial) {
                        $initial = 'PS';
                      }
                    @endphp

                    <li>
                      <a href="{{ route('ortu.chat.show', $t->id) }}"
                        class="group flex items-start gap-3 px-4 py-3 transition {{ $isActive ? 'bg-blue-50/70' : 'hover:bg-slate-50' }}">

                        <div
                          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700 shadow-sm">
                          {{ $initial }}
                        </div>

                        <div class="min-w-0 flex-1">
                          <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                              <p class="truncate text-[13px] font-semibold text-slate-800">
                                {{ $namaTujuan }}
                              </p>
                              <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $roleLabel }}
                              </p>
                            </div>

                            <div class="flex shrink-0 flex-col items-end gap-1">
                              <span class="text-[11px] text-slate-400">
                                {{ $lastAtText }}
                              </span>

                              @if($unreadCount > 0)
                                <span
                                  class="inline-flex h-6 min-w-[24px] items-center justify-center rounded-full bg-blue-500 px-1.5 text-[10px] font-semibold leading-none text-white shadow-sm">
                                  {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                              @endif
                            </div>
                          </div>

                          <div class="mt-1.5">
                            <p class="truncate text-xs text-slate-400">
                              {{ \Illuminate\Support\Str::limit($lastBody, 34) }}
                            </p>
                          </div>
                        </div>
                      </a>
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          </aside>

          {{-- PANEL KANAN --}}
          <section class="flex min-h-[420px] flex-1 flex-col bg-white md:min-h-0 md:h-full">
            @if($activeThread)
              @php
                $assignee = $activeThread->assignee;
                $roleLabel = $activeThread->assignee_role_detail ?? 'Pihak Sekolah';

                $status = $activeThread->status ?? 'open';
                $statusLabel = match ($status) {
                  'open' => 'Open',
                  'pending' => 'Pending',
                  'resolved' => 'Resolved',
                  default => ucfirst($status),
                };

                $activeInitial = \Illuminate\Support\Str::of($assignee->name ?? 'Pihak Sekolah')
                  ->trim()
                  ->explode(' ')
                  ->map(fn($p) => mb_substr($p, 0, 1))
                  ->take(2)
                  ->implode('');

                if (!$activeInitial) {
                  $activeInitial = 'PS';
                }
              @endphp

              {{-- HEADER PERCAKAPAN --}}
              <div class="shrink-0 border-b border-slate-200 bg-white px-4 py-3 md:px-5">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex min-w-0 items-center gap-3">
                    <div
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700 shadow-sm">
                      {{ $activeInitial }}
                    </div>

                    <div class="min-w-0">
                      <div class="truncate text-sm font-semibold text-slate-800 md:text-[15px]">
                        {{ $assignee->name ?? 'Pihak Sekolah' }}
                      </div>
                      <div class="mt-0.5 truncate text-xs text-slate-500">
                        {{ $roleLabel }}
                      </div>
                    </div>
                  </div>

                  <div class="flex shrink-0 items-center gap-2">
                    <span
                      class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-[11px] font-medium text-blue-700 ring-1 ring-blue-200">
                      {{ $statusLabel }}
                    </span>
                  </div>
                </div>
              </div>

              {{-- ISI CHAT --}}
              <div id="chat-body" class="min-h-0 flex-1 overflow-y-auto bg-white px-3 py-4 sm:px-4 md:px-5">
                <div class="space-y-3">
                  @forelse($messages as $m)
                    @php
                      $isParent = $m->sender_type === 'parent';
                      $who = $isParent ? 'Anda' : ($m->sender?->name ?? ucfirst($m->sender_type ?? 'Petugas'));
                      $align = $isParent ? 'justify-end' : 'justify-start';

                      $bubble = $isParent
                        ? 'bg-blue-500 text-white rounded-br-md shadow-[0_10px_24px_rgba(59,130,246,0.18)]'
                        : 'bg-slate-50 text-slate-800 rounded-bl-md border border-slate-200 shadow-[0_8px_20px_rgba(15,23,42,0.05)]';

                      $time = \Carbon\Carbon::parse($m->created_at)
                        ->timezone('Asia/Jakarta')
                        ->locale('id')
                        ->translatedFormat('d M Y, H:i');

                      $statusMsg = $m->message_status ?? null;
                    @endphp

                    <div class="flex {{ $align }} chat-message-item" data-message-id="{{ $m->id }}">
                      <div class="max-w-[92%] sm:max-w-[85%] md:max-w-[76%]">
                        <div class="mb-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                          <span>{{ $who }}</span>
                          <span>•</span>
                          <span>{{ $m->channel }}</span>
                          <span>•</span>
                          <span>{{ $time }} WIB</span>

                          @if($statusMsg)
                            <span
                              class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-slate-600 ring-1 ring-slate-200">
                              {{ ucfirst($statusMsg) }}
                            </span>
                          @endif
                        </div>

                        <div class="rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed sm:px-4 {{ $bubble }}">
                          {!! nl2br(e($m->body)) !!}
                        </div>
                      </div>
                    </div>
                  @empty
                    <p id="chat-empty-state" class="pt-10 text-center text-sm italic text-slate-400">
                      Belum ada pesan di percakapan ini.
                    </p>
                  @endforelse
                </div>
              </div>

              {{-- FORM KIRIM --}}
              <div class="shrink-0 border-t border-slate-200 bg-white p-3 md:p-4">
                @if(($activeThread->status ?? 'open') === 'resolved')
                  <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
                    Percakapan ini sudah diselesaikan. Silakan buat percakapan baru jika ingin mengirim pesan lagi.
                  </div>
                @else
                  <form action="{{ route('ortu.chat.send', $activeThread->id) }}" method="POST"
                    class="flex items-end gap-2 md:gap-3" id="replyForm">
                    @csrf
                    <div class="flex-1">
                      <textarea name="message" rows="1"
                        class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-sm transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100"
                        placeholder="Tulis pesan..." required>{{ old('message') }}</textarea>
                    </div>
                    <button type="submit"
                      class="inline-flex h-[46px] shrink-0 items-center justify-center rounded-2xl bg-blue-500 px-4 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md md:px-5">
                      Kirim
                    </button>
                  </form>
                @endif
              </div>
            @else
              <div class="flex flex-1 flex-col items-center justify-center bg-white px-6 py-10 text-center">
                <div
                  class="mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-100 text-blue-600 shadow-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 10h.01M12 10h.01M16 10h.01M5 18l2.5-2.5H18a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6A2 2 0 0 0 4 7v9l1 2" />
                  </svg>
                </div>

                <h2 class="text-base font-semibold text-slate-700">
                  Pilih percakapan di sebelah kiri
                </h2>
                <p class="mt-1 max-w-md text-sm text-slate-500">
                  Klik salah satu percakapan untuk membuka detail chat, atau tekan
                  <span class="font-medium text-blue-600">Kirim Pesan</span> untuk memulai percakapan baru.
                </p>
              </div>
            @endif
          </section>
        </div>
      </div>
    </section>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const searchInput = document.querySelector('#chatSearchForm input[name="q"]');
      const searchForm = document.getElementById('chatSearchForm');
      const loader = document.getElementById('searchLoader');
      const chatBody = document.getElementById('chat-body');
      const replyForm = document.getElementById('replyForm');
      const emptyState = document.getElementById('chat-empty-state');

      let searchTypingTimer;
      let isSubmitting = false;
      let pollInterval = null;
      let pollInFlight = false;

      document.querySelectorAll('.auto-dismiss-alert').forEach((alert) => {
        setTimeout(() => {
          alert.classList.add('opacity-0', '-translate-y-1');
          alert.classList.remove('opacity-100');

          setTimeout(() => {
            alert.remove();
          }, 550);
        }, 5000);
      });

      searchInput?.addEventListener('input', () => {
        loader.classList.remove('hidden');
        clearTimeout(searchTypingTimer);
        searchTypingTimer = setTimeout(() => {
          searchForm.submit();
        }, 600);
      });

      searchForm?.addEventListener('submit', () => {
        loader.classList.remove('hidden');
      });

      function scrollToBottom(force = false) {
        if (!chatBody) return;

        const distanceFromBottom = chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight;

        if (force || distanceFromBottom < 140) {
          chatBody.scrollTo({
            top: chatBody.scrollHeight,
            behavior: force ? 'auto' : 'smooth'
          });
        }
      }

      function getLastMessageId() {
        if (!chatBody) return 0;

        const items = chatBody.querySelectorAll('.chat-message-item[data-message-id]');
        if (!items.length) return 0;

        return Math.max(...Array.from(items).map(el => parseInt(el.dataset.messageId || '0', 10)));
      }

      function messageExists(id) {
        if (!chatBody) return false;

        return !!chatBody.querySelector(`.chat-message-item[data-message-id="${id}"]`);
      }

      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
      }

      function nl2br(text) {
        return escapeHtml(text).replace(/\n/g, '<br>');
      }

      function capitalize(text) {
        const value = String(text || '');
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
      }

      function renderMessageBubble(msg) {
        const isOutgoing = !!msg.is_outgoing;
        const align = isOutgoing ? 'justify-end' : 'justify-start';
        const bubble = isOutgoing
          ? 'bg-blue-500 text-white rounded-br-md shadow-[0_10px_24px_rgba(59,130,246,0.18)]'
          : 'bg-slate-50 text-slate-800 rounded-bl-md border border-slate-200 shadow-[0_8px_20px_rgba(15,23,42,0.05)]';

        const statusBadge = msg.message_status
          ? `<span class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-slate-600 ring-1 ring-slate-200">${escapeHtml(capitalize(msg.message_status))}</span>`
          : '';

        return `
              <div class="flex ${align} chat-message-item" data-message-id="${msg.id}">
                <div class="max-w-[92%] sm:max-w-[85%] md:max-w-[76%]">
                  <div class="mb-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                    <span>${escapeHtml(msg.who || '-')}</span>
                    <span>•</span>
                    <span>${escapeHtml(msg.channel || '-')}</span>
                    <span>•</span>
                    <span>${escapeHtml(msg.time || '-')}</span>
                    ${statusBadge}
                  </div>

                  <div class="rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed sm:px-4 ${bubble}">
                    ${nl2br(msg.body || '')}
                  </div>
                </div>
              </div>
            `;
      }

      async function fetchNewMessages() {
        const activeThreadId = @json($activeThread->id ?? null);

        if (!chatBody || !activeThreadId) return;
        if (document.visibilityState !== 'visible') return;
        if (!navigator.onLine) return;
        if (isSubmitting) return;
        if (pollInFlight) return;

        pollInFlight = true;

        const lastId = getLastMessageId();

        try {
          const url = `{{ route('ortu.chat.fetchNewMessages', ['thread' => '__ID__']) }}`
            .replace('__ID__', activeThreadId) + `?after_id=${lastId}`;

          const response = await fetch(url, {
            method: 'GET',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
            credentials: 'same-origin',
            cache: 'no-store'
          });

          if (!response.ok) {
            pollInFlight = false;
            return;
          }

          const result = await response.json();

          if (!result.success || !Array.isArray(result.messages) || result.messages.length === 0) {
            pollInFlight = false;
            return;
          }

          const distanceFromBottom = chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight;
          const shouldAutoScroll = distanceFromBottom < 160;

          let hasNew = false;

          result.messages.forEach(msg => {
            if (!msg || !msg.id || messageExists(msg.id)) return;

            if (emptyState) {
              emptyState.remove();
            }

            chatBody.insertAdjacentHTML('beforeend', renderMessageBubble(msg));
            hasNew = true;
          });

          if (hasNew && shouldAutoScroll) {
            scrollToBottom();
          }
        } catch (error) {
          console.error('Gagal mengambil pesan baru:', error);
        } finally {
          pollInFlight = false;
        }
      }

      if (replyForm) {
        replyForm.addEventListener('submit', function () {
          isSubmitting = true;
        });
      }

      if (chatBody) {
        scrollToBottom(true);
        pollInterval = setInterval(fetchNewMessages, 2500);
      }

      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
          fetchNewMessages();
        }
      });

      window.addEventListener('online', function () {
        fetchNewMessages();
      });
    });
  </script>
@endsection