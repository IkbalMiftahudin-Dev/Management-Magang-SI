@if (session('status') || $errors->any())
    <div
        id="portal-toast"
        class="fixed right-5 bottom-5 z-50 flex max-w-md items-start gap-3 rounded-2xl bg-[#0F172A] px-5 py-4 text-white shadow-2xl ring-1 ring-white/10"
        role="status"
    >
        @if ($errors->any())
            <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-500/20 text-rose-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1 text-xs leading-relaxed">
                <p class="font-semibold text-white">Periksa kembali data yang dikirim</p>
                <p class="mt-0.5 text-slate-300">{{ $errors->first() }}</p>
            </div>
        @else
            <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1 text-xs leading-relaxed">
                <p class="font-semibold text-white">{{ session('status') }}</p>
            </div>
        @endif

        <button
            type="button"
            onclick="document.getElementById('portal-toast')?.remove()"
            class="rounded-lg p-1 text-slate-400 hover:bg-white/10 hover:text-white"
            aria-label="Tutup notifikasi"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endif
