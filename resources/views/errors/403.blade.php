@extends('layouts.auth-shell')

@section('title', '403 Akses Ditolak — SI RPL UPI')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200/70">
            <div class="mb-4 inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">
                403 · Akses Ditolak
            </div>

            <h1 class="text-xl font-semibold text-slate-900">Halaman tidak tersedia untuk peran Anda</h1>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                {{ $exception?->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman ini.' }}
            </p>

            <div class="mt-6 flex flex-col gap-3">
                @auth
                    <a
                        href="{{ route(auth()->user()->role->dashboardRoute()) }}"
                        class="inline-flex items-center justify-center rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-900"
                    >
                        Kembali ke Beranda {{ auth()->user()->role->label() }}
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-900"
                    >
                        Masuk lewat SSO SACIBIRU
                    </a>
                @endauth
            </div>
        </div>
    </div>
@endsection
