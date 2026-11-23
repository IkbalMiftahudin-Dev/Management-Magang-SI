<x-layouts.portal title="Beranda — Portal Magang">
    {{-- Hero Banner --}}
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0101B7] via-[#1B1CD6] to-[#3B3DF0] p-7 text-white shadow-[0_14px_34px_rgba(1,1,183,0.22)] lg:p-10">
        <div class="pointer-events-none absolute -top-20 -right-20 h-72 w-72 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-24 right-24 h-64 w-64 rounded-full bg-[#8DCCF1]/20 blur-2xl"></div>

        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#FFD166] px-3.5 py-1 text-xs font-bold text-slate-900">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
                Portal Magang Terpadu
            </span>

            <h1 class="mt-4 font-heading text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
                Mulai langkah menuju
                <span class="block text-[#FFD166]">karier impianmu.</span>
            </h1>

            <p class="mt-3 max-w-xl text-sm leading-relaxed text-blue-100">
                Temukan peluang dari mitra prodi dan tawaran dosen, catat progres logbook, dan tuntaskan program magang dalam satu tempat.
            </p>

            {{-- Search Pill --}}
            <form method="GET" action="{{ route('mahasiswa.beranda') }}" class="mt-6 flex flex-col gap-2 rounded-3xl bg-white p-2 shadow-lg sm:flex-row sm:items-center sm:rounded-full">
                @if ($sumber)
                    <input type="hidden" name="sumber" value="{{ $sumber }}">
                @endif
                <div class="flex flex-1 items-center gap-2.5 px-3 py-1.5">
                    <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari posisi atau perusahaan..."
                        class="w-full border-0 bg-transparent text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0 focus:outline-none"
                    >
                </div>
                <button
                    type="submit"
                    class="rounded-full bg-[#FFD166] px-6 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-amber-300"
                >
                    Cari lowongan
                </button>
            </form>
        </div>
    </section>

    {{-- Section Heading + Category Filters --}}
    <section class="mt-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold tracking-[0.16em] text-[#0101B7] uppercase">Peluang Terbaru</p>
                <h2 class="mt-1 font-heading text-2xl font-bold text-slate-900">Lowongan untukmu</h2>
                <p class="mt-0.5 text-xs text-slate-500">Rekomendasi berdasarkan program studi Rekayasa Perangkat Lunak dan minatmu.</p>
            </div>

            @if ($search || $sumber)
                <a href="{{ route('mahasiswa.beranda') }}" class="text-xs font-semibold text-[#0101B7] hover:underline">
                    Reset filter &rarr;
                </a>
            @endif
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('mahasiswa.beranda', array_filter(['search' => $search])) }}"
                    class="rounded-full px-4 py-2 text-xs font-semibold transition {{ ! $sumber ? 'bg-[#0101B7] text-white shadow-xs' : 'bg-white text-slate-600 ring-1 ring-slate-200/80 hover:text-[#0101B7]' }}"
                >
                    Semua
                </a>
                <a
                    href="{{ route('mahasiswa.beranda', array_filter(['search' => $search, 'sumber' => 'mitra_kampus'])) }}"
                    class="rounded-full px-4 py-2 text-xs font-semibold transition {{ $sumber === 'mitra_kampus' ? 'bg-[#0101B7] text-white shadow-xs' : 'bg-white text-slate-600 ring-1 ring-slate-200/80 hover:text-[#0101B7]' }}"
                >
                    Mitra Kampus
                </a>
                <a
                    href="{{ route('mahasiswa.beranda', array_filter(['search' => $search, 'sumber' => 'tawaran_dosen'])) }}"
                    class="rounded-full px-4 py-2 text-xs font-semibold transition {{ $sumber === 'tawaran_dosen' ? 'bg-[#0101B7] text-white shadow-xs' : 'bg-white text-slate-600 ring-1 ring-slate-200/80 hover:text-[#0101B7]' }}"
                >
                    Tawaran Dosen
                </a>
            </div>

            <p class="text-xs font-medium text-slate-400">{{ $lowongans->count() }} lowongan aktif</p>
        </div>

        {{-- Vacancy Cards Grid --}}
        @if ($lowongans->isEmpty())
            <div class="mt-6 rounded-3xl bg-white p-12 text-center shadow-xs ring-1 ring-slate-100">
                <p class="font-heading text-base font-semibold text-slate-800">Belum ada lowongan yang cocok</p>
                <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
            </div>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($lowongans as $lowongan)
                    @php
                        $isDosen = $lowongan->sumber_lowongan->value === 'tawaran_dosen';
                        $initials = mb_strtoupper(mb_substr($lowongan->nama_instansi, 0, 2));
                    @endphp
                    <article class="group flex flex-col overflow-hidden rounded-3xl bg-white shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_rgba(1,1,183,0.10)]">
                        {{-- Colored Top Header Banner --}}
                        <div class="relative h-24 px-4 pt-3.5 {{ $isDosen ? 'bg-gradient-to-br from-[#F4C430] to-[#FFD978]' : 'bg-gradient-to-br from-[#0101B7] to-[#3B3DF0]' }}">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $isDosen ? 'bg-amber-950/15 text-slate-900' : 'bg-white text-[#0101B7]' }}">
                                {{ $lowongan->sumber_lowongan->cardBadgeLabel() }}
                            </span>

                            <div class="mt-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-white font-heading text-sm font-bold text-[#0101B7] shadow-sm ring-1 ring-slate-100">
                                {{ $initials }}
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="flex flex-1 flex-col p-4 pt-4">
                            <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                                </svg>
                                <span class="truncate">{{ $lowongan->nama_instansi }}</span>
                            </p>

                            <h3 class="mt-1.5 font-heading text-base font-bold leading-snug text-slate-900 group-hover:text-[#0101B7]">
                                {{ $lowongan->judul_lowongan }}
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $lowongan->posisi_bidang }}</p>

                            <div class="mt-4 flex flex-wrap gap-1.5">
                                <span class="rounded-full bg-[#F2F7FF] px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                    {{ $lowongan->lokasi_mode }}
                                </span>
                                <span class="rounded-full bg-[#F2F7FF] px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                    {{ $lowongan->jumlah_pelamar }} pelamar
                                </span>
                            </div>

                            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3.5 text-xs">
                                <span class="text-slate-400">
                                    {{ $lowongan->tanggal_posting?->translatedFormat('d M Y') }}
                                </span>
                                <a
                                    href="{{ route('mahasiswa.lowongan.show', $lowongan) }}"
                                    class="font-semibold text-[#0101B7] transition group-hover:translate-x-0.5"
                                >
                                    Lihat detail &rsaquo;
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.portal>
