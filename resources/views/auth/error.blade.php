@extends('layouts.auth-shell')

@section('title', ($title ?? 'Autentikasi SSO') . ' — SI RPL UPI')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200/70">
            <div class="mb-4 inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                HTTP {{ $status ?? 400 }}
            </div>

            <h1 class="text-xl font-semibold text-slate-900">{{ $title ?? 'Autentikasi SSO Gagal' }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                {{ $message ?? 'Terjadi kendala saat memproses autentikasi SSO SACIBIRU.' }}
            </p>

            <div class="mt-6 flex flex-col gap-3">
                <a
                    href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-900"
                >
                    Coba Masuk Lagi
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
