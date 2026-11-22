@extends('layouts.auth-shell')

@section('title', 'Keluar dari Portal Magang — SI RPL UPI')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200/70">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#0101B7] font-bold text-white">
                    SI
                </div>
                <div>
                    <p class="text-sm font-bold tracking-wide text-[#0101B7]">SI RPL UPI</p>
                    <p class="text-xs font-medium tracking-widest text-slate-500 uppercase">Portal Magang · Modul 5</p>
                </div>
            </div>

            <div class="mb-6 rounded-2xl bg-[#F2F7FF] p-4">
                <h1 class="text-xl font-semibold text-slate-900">Anda telah keluar</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    Sesi lokal Modul Magang/SI dan sesi SSO SACIBIRU Anda telah diakhiri dengan aman.
                </p>
            </div>

            <div class="flex flex-col gap-3">
                <a
                    href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-900"
                >
                    Masuk Kembali lewat SSO SACIBIRU
                </a>

                @if (! empty($ssoMenuUrl))
                    <a
                        href="{{ $ssoMenuUrl }}"
                        class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Kembali ke Menu Utama SSO
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
