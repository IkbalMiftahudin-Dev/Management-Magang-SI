@extends('layouts.auth-shell')

@section('title', $pageTitle . ' — SI RPL UPI')

@section('content')
    <div class="flex min-h-screen flex-col lg:flex-row">
        <aside class="flex w-full flex-col justify-between border-b border-slate-200/80 bg-white p-6 lg:w-72 lg:border-r lg:border-b-0">
            <div>
                <div class="mb-8 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0101B7] text-sm font-bold text-white">
                        SI
                    </div>
                    <div>
                        <p class="text-sm font-bold text-[#0101B7]">SI RPL UPI</p>
                        <p class="text-[11px] font-medium tracking-widest text-slate-500 uppercase">Portal Magang</p>
                    </div>
                </div>

                <p class="mb-3 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">Menu Utama</p>
                <nav class="space-y-1.5">
                    <a
                        href="{{ route($user->role->dashboardRoute()) }}"
                        class="flex items-center gap-3 rounded-full bg-[#0101B7] px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
                    >
                        <span>{{ $pageTitle }}</span>
                    </a>
                </nav>
            </div>

            <div class="mt-8 space-y-4">
                <div class="rounded-2xl bg-[#F2F7FF] p-4">
                    <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#FFD166] text-xs font-bold text-slate-900">
                        SSO
                    </div>
                    <p class="text-sm font-semibold text-slate-900">Terintegrasi SSO</p>
                    <p class="mt-1 text-xs text-slate-600">
                        Data akademik tersinkron otomatis dengan SACIBIRU.
                    </p>
                    <p class="mt-2 flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Tersinkron
                    </p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 p-6 lg:p-10">
            <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $pageTitle }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ $pageDescription }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-right">
                        <p class="text-[11px] text-slate-400">Role</p>
                        <p class="text-xs font-semibold text-slate-800">{{ $user->role->label() }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#0101B7] text-xs font-bold text-white">
                            {{ $user->initials() }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $user->role->identifierLabel() }} {{ $user->identifier_number ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <section class="grid gap-6 md:grid-cols-2">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="text-base font-semibold text-slate-900">Status Sesi SSO SACIBIRU</h2>
                    <dl class="mt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">SSO Subject (sub)</dt>
                            <dd class="font-mono text-xs text-slate-800">{{ $user->sso_sub }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Email</dt>
                            <dd class="font-medium text-slate-800">{{ $user->email }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Role SSO</dt>
                            <dd class="font-semibold text-[#0101B7]">{{ $user->role->value }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Token Berlaku Hingga</dt>
                            <dd class="text-slate-800">
                                {{ $ssoSession?->access_token_expires_at?->format('d M Y H:i:s') ?? '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="text-base font-semibold text-slate-900">Hak Akses Aktif (RBAC Tahap 1)</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($permissions as $permission)
                            <span class="rounded-full bg-[#F2F7FF] px-3 py-1 text-xs font-medium text-[#0101B7]">
                                {{ $permission }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>
    </div>
@endsection
