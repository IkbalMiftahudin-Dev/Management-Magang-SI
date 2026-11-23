@php
    use App\Enums\StatusPenilaian;
    use App\Enums\TingkatKesesuaian;
@endphp

<x-layouts.portal title="Penilaian & SK — Dosen">
    <div class="mb-6">
        <h1 class="font-heading text-2xl font-bold text-slate-900">Verifikasi &amp; Penilaian</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola penilaian konversi mata kuliah yang Anda ampu serta verifikasi bukti aktivitas MBKM mahasiswa.</p>
    </div>

    {{-- Blue Course Banner (MATA KULIAH YANG DIAMPU) --}}
    @if ($selectedCourse !== null)
        <section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-gradient-to-r from-[#0101B7] to-[#3436E6] p-6 text-white shadow-[0_10px_28px_rgba(1,1,183,0.22)]">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#FFD166] text-[#0101B7]">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold tracking-[0.16em] text-blue-200 uppercase">Mata Kuliah yang Diampu</p>
                    <h2 class="mt-0.5 font-heading text-2xl font-bold">{{ $selectedCourse->kode_mk }} · {{ $selectedCourse->nama_mk }}</h2>
                    <p class="mt-0.5 text-xs text-blue-100">{{ $selectedCourse->sks }} SKS · Konversi kegiatan MBKM</p>
                </div>
            </div>

            @if ($courses->count() > 1)
                <form method="GET" action="{{ route('dosen.penilaian.index') }}">
                    <select name="mata_kuliah_id" onchange="this.form.submit()" class="rounded-full bg-white/15 px-4 py-2 text-xs font-semibold text-white ring-1 ring-white/25 focus:outline-none">
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" class="text-slate-900" @selected($selectedCourse->id === $course->id)>
                                {{ $course->kode_mk }} — {{ $course->nama_mk }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </section>
    @endif

    {{-- 4 KPI Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card :value="$metrics['pengajuan']" label="Pengajuan mahasiswa">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['belum_dinilai']" label="Belum dinilai">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['draf']" label="Draf penilaian">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['dipublikasikan']" label="Nilai dipublikasikan">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>
    </div>

    {{-- Mahasiswa Pengaju Konversi Table --}}
    <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-heading text-base font-bold text-slate-900">Mahasiswa Pengaju Konversi</h2>
                <p class="text-xs text-slate-500">
                    Mahasiswa dengan aktivitas MBKM yang relevan dengan {{ $selectedCourse?->nama_mk ?? 'mata kuliah Anda' }}.
                </p>
            </div>

            <form method="GET" action="{{ route('dosen.penilaian.index') }}" class="flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2">
                @if ($selectedCourse)
                    <input type="hidden" name="mata_kuliah_id" value="{{ $selectedCourse->id }}">
                @endif
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari mahasiswa..." class="w-44 border-0 bg-transparent text-xs focus:ring-0 focus:outline-none">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-[#F2F7FF] text-[11px] font-bold text-slate-500 uppercase">
                        <th class="rounded-l-xl px-4 py-3">Mahasiswa</th>
                        <th class="px-4 py-3">Tempat &amp; Posisi MBKM</th>
                        <th class="px-4 py-3">Kesesuaian</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($submissions as $item)
                        @php
                            $mhs = $item->pendataanMagang?->mahasiswa;
                            $mhsName = $mhs?->nama ?? 'Mahasiswa';
                            $kesesuaianVar = $item->tingkat_kesesuaian === TingkatKesesuaian::SangatSesuai ? 'success' : 'info';
                            $statusVar = match ($item->status_penilaian) {
                                StatusPenilaian::Dipublikasikan => 'success',
                                StatusPenilaian::Draf => 'warning',
                                default => 'danger',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0101B7] font-heading text-xs font-bold text-white">
                                        {{ mb_strtoupper(mb_substr($mhsName, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-heading text-xs font-bold text-slate-900">{{ $mhsName }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $mhs?->nim ?? '-' }} · RPL</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-xs font-bold text-slate-900">{{ $item->pendataanMagang?->nama_instansi }}</p>
                                <p class="text-[11px] text-slate-400">{{ $item->pendataanMagang?->posisi }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <x-ui.status-badge :variant="$kesesuaianVar" :label="$item->tingkat_kesesuaian->label()" />
                            </td>
                            <td class="px-4 py-4">
                                <x-ui.status-badge :variant="$statusVar" :label="$item->status_penilaian->label()" />
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a
                                    href="{{ route('dosen.penilaian.show', $item) }}"
                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-4 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-[#0101B7] hover:bg-[#0101B7] hover:text-white"
                                >
                                    Lihat detail &rsaquo;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-xs text-slate-400">
                                Belum ada mahasiswa pengaju konversi untuk mata kuliah ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.portal>
