@props([
    'title' => 'SI RPL UPI — Portal Magang',
])

@php
    use App\Enums\UserRole;
    use App\Models\Logbook;
    use App\Models\PendataanMagang;

    $user = auth()->user();
    $role = $user?->role;

    $logbookCount = 0;
    if ($role === UserRole::Mahasiswa && $user?->mahasiswa !== null) {
        $latestPendataanId = PendataanMagang::query()
            ->where('mahasiswa_id', $user->mahasiswa->id)
            ->latest('id')
            ->value('id');

        if ($latestPendataanId !== null) {
            $logbookCount = Logbook::query()
                ->where('pendataan_magang_id', $latestPendataanId)
                ->count();
        }
    }

    $navItems = match ($role) {
        UserRole::Mahasiswa => [
            ['label' => 'Beranda', 'route' => 'mahasiswa.beranda', 'active' => request()->routeIs('mahasiswa.beranda', 'mahasiswa.lowongan.*'), 'icon' => 'grid'],
            ['label' => 'Pendataan Magang', 'route' => 'mahasiswa.pendataan.show', 'active' => request()->routeIs('mahasiswa.pendataan.*'), 'icon' => 'briefcase'],
            ['label' => 'Logbook Kegiatan', 'route' => 'mahasiswa.logbook.index', 'active' => request()->routeIs('mahasiswa.logbook.*'), 'icon' => 'book', 'badge' => $logbookCount],
            ['label' => 'Berkas Akhir', 'route' => 'mahasiswa.berkas-akhir.show', 'active' => request()->routeIs('mahasiswa.berkas-akhir.*'), 'icon' => 'document'],
            ['label' => 'SK & Nilai', 'route' => 'mahasiswa.hasil.index', 'active' => request()->routeIs('mahasiswa.hasil.*'), 'icon' => 'award'],
        ],
        UserRole::Dosen => [
            ['label' => 'Penilaian & SK', 'route' => 'dosen.penilaian.index', 'active' => request()->routeIs('dosen.*'), 'icon' => 'clipboard'],
        ],
        UserRole::StaffKaprodi => [
            ['label' => 'Kelola Lowongan', 'route' => 'staff.lowongan.index', 'active' => request()->routeIs('staff.lowongan.*'), 'icon' => 'building'],
            ['label' => 'Verifikasi & Penilaian', 'route' => 'staff.verifikasi.index', 'active' => request()->routeIs('staff.verifikasi.*'), 'icon' => 'clipboard'],
        ],
        UserRole::Kaprodi => [
            ['label' => 'Kelola Lowongan', 'route' => 'kaprodi.lowongan.index', 'active' => request()->routeIs('kaprodi.lowongan.*'), 'icon' => 'building'],
            ['label' => 'Verifikasi & Penilaian', 'route' => 'kaprodi.verifikasi.index', 'active' => request()->routeIs('kaprodi.verifikasi.*'), 'icon' => 'clipboard'],
        ],
        UserRole::Admin => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'grid'],
            ['label' => 'Kelola Lowongan', 'route' => 'admin.lowongan.index', 'active' => request()->routeIs('admin.lowongan.*'), 'icon' => 'building'],
            ['label' => 'Mata Kuliah & Rubrik', 'route' => 'admin.mata-kuliah.index', 'active' => request()->routeIs('admin.mata-kuliah.*'), 'icon' => 'book'],
            ['label' => 'Audit Trail', 'route' => 'admin.audit-logs.index', 'active' => request()->routeIs('admin.audit-logs.*'), 'icon' => 'shield'],
        ],
        default => [],
    };
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — SI RPL UPI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=lexend:500,600,700|plus-jakarta-sans:400,500,600,700" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-[#F2F7FF] text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen max-w-[1480px] gap-6 p-4 lg:p-6">
        {{-- Mobile Sidebar Backdrop --}}
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/40 backdrop-blur-xs lg:hidden" onclick="togglePortalSidebar(false)"></div>

        {{-- Floating Rounded Sidebar --}}
        <aside id="portal-sidebar" class="fixed inset-y-4 left-4 z-50 flex w-[264px] -translate-x-[120%] flex-col justify-between rounded-3xl bg-white p-5 shadow-[0_10px_35px_rgba(1,1,183,0.06)] ring-1 ring-slate-100 transition-transform duration-200 lg:sticky lg:top-6 lg:h-[calc(100vh-3rem)] lg:translate-x-0">
            <div class="overflow-y-auto">
                {{-- Brand Logo --}}
                <div class="mb-7 flex items-center justify-between px-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <svg class="h-9 w-9 shrink-0" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M11 9L20 18L11 27" stroke="#FFD166" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M19 13L28 22L19 31" stroke="#0101B7" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div>
                            <p class="font-heading text-base font-bold leading-tight tracking-tight text-[#0101B7]">SI RPL UPI</p>
                            <p class="text-[10px] font-semibold tracking-[0.18em] text-slate-400 uppercase">Portal Magang</p>
                        </div>
                    </a>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden" onclick="togglePortalSidebar(false)" aria-label="Tutup menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Navigation --}}
                <p class="mb-3 px-2 text-[11px] font-semibold tracking-[0.14em] text-slate-400 uppercase">Menu Utama</p>
                <nav class="space-y-1.5">
                    @foreach ($navItems as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            class="group flex items-center justify-between rounded-full px-4 py-3 text-sm font-medium transition-all {{ $item['active'] ? 'bg-[#0101B7] font-semibold text-white shadow-[0_8px_20px_rgba(1,1,183,0.28)]' : 'text-slate-600 hover:bg-[#F2F7FF] hover:text-[#0101B7]' }}"
                        >
                            <span class="flex items-center gap-3">
                                @switch($item['icon'])
                                    @case('grid')
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                                        @break
                                    @case('briefcase')
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                                        @break
                                    @case('book')
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                        @break
                                    @case('document')
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        @break
                                    @case('award')
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="8" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg>
                                        @break
                                    @case('building')
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        @break
                                    @case('clipboard')
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        @break
                                    @default
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                @endswitch
                                <span>{{ $item['label'] }}</span>
                            </span>

                            @if (isset($item['badge']) && $item['badge'] > 0)
                                <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#FFD166] px-1.5 text-[11px] font-bold text-slate-900">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Bottom SSO Widget + Logout --}}
            <div class="mt-6 space-y-3 pt-4">
                <div class="relative rounded-2xl bg-[#F2F7FF] p-4 pt-6">
                    <div class="absolute -top-4 left-4 flex h-9 w-9 items-center justify-center rounded-xl bg-[#FFD166] text-[#0101B7] shadow-xs">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                        </svg>
                    </div>
                    <p class="font-heading text-xs font-semibold text-slate-900">Terintegrasi SSO</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                        Data akademik tersinkron otomatis dengan Modul Utama.
                    </p>
                    <p class="mt-2.5 flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Tersinkron
                    </p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Workspace --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Topbar --}}
            <header class="mb-6 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-700 shadow-xs ring-1 ring-slate-200/70 lg:hidden"
                        onclick="togglePortalSidebar(true)"
                        aria-label="Buka menu"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="hidden sm:block">
                        @isset($breadcrumb)
                            {{ $breadcrumb }}
                        @endisset
                    </div>
                </div>

                @if ($user !== null)
                    <div class="flex items-center gap-3">
                        <div class="hidden rounded-2xl bg-white px-4 py-1.5 text-left shadow-xs ring-1 ring-slate-200/60 sm:block">
                            <p class="text-[10px] font-medium text-slate-400">Role</p>
                            <p class="text-xs font-semibold text-slate-800">{{ $user->role->label() }}</p>
                        </div>

                        <div class="flex items-center gap-2.5 pl-1">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0101B7] font-heading text-xs font-bold text-white shadow-xs">
                                {{ $user->initials() }}
                            </div>
                            <div class="hidden text-left md:block">
                                <p class="text-sm font-semibold leading-tight text-slate-900">{{ $user->name }}</p>
                                <p class="text-[11px] font-medium text-slate-400">
                                    {{ $user->role->identifierLabel() }} {{ $user->identifier_number ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </header>

            {{-- Page Content --}}
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-ui.toast />

    <script>
        function togglePortalSidebar(open) {
            const sidebar = document.getElementById('portal-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (!sidebar || !backdrop) {
                return;
            }
            if (open) {
                sidebar.classList.remove('-translate-x-[120%]');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-[120%]');
                backdrop.classList.add('hidden');
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
