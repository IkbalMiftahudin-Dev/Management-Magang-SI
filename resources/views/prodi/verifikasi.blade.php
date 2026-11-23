@php
    use App\Enums\StatusPendataan;
    use App\Enums\UserRole;

    $prefix = auth()->user()->role === UserRole::Kaprodi ? 'kaprodi' : 'staff';
@endphp

<x-layouts.portal title="Verifikasi & Penilaian">
    {{-- Header + Otomatisasi Generasi SK CTA --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900">Verifikasi &amp; Penilaian</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola verifikasi pendataan, plotting pembimbing lapangan, dan otomatisasi dokumen SK.</p>
        </div>

        <form method="POST" action="{{ route($prefix.'.verifikasi.generate-sk-bulk') }}" onsubmit="return confirm('Generate draf SK otomatis untuk seluruh pendataan yang sudah terverifikasi?')">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(1,1,183,0.24)] transition hover:bg-blue-900">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                <span>Otomatisasi Generasi SK</span>
            </button>
        </form>
    </div>

    {{-- 4 KPI Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card :value="$metrics['menunggu_verifikasi']" label="Menunggu verifikasi">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['pendataan_aktif']" label="Pendataan aktif">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['sk_belum_disahkan']" label="SK belum disahkan">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['kelengkapan_berkas_persen'].'%'" label="Kelengkapan berkas">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>
    </div>

    {{-- Rekap Pendataan Mahasiswa Table --}}
    <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-heading text-base font-bold text-slate-900">Rekap Pendataan Mahasiswa</h2>
                <p class="text-xs text-slate-500">Pantau status verifikasi, tetapkan Pembimbing Lapangan, dan tinjau dokumen SK.</p>
            </div>

            <form method="GET" action="{{ route($prefix.'.verifikasi.index') }}" class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari mahasiswa..." class="w-44 border-0 bg-transparent text-xs text-slate-800 placeholder:text-slate-400 focus:ring-0 focus:outline-none">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-[#F2F7FF] text-[11px] font-bold text-slate-500 uppercase">
                        <th class="rounded-l-xl px-4 py-3">Mahasiswa</th>
                        <th class="px-4 py-3">Instansi</th>
                        <th class="px-4 py-3">Program</th>
                        <th class="px-4 py-3">Dosen Pembimbing UPI</th>
                        <th class="px-4 py-3">Pembimbing Lapangan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rekap as $row)
                        @php
                            $badgeVar = match ($row->status_pendataan) {
                                StatusPendataan::Terverifikasi, StatusPendataan::Selesai => 'success',
                                StatusPendataan::PerluRevisi => 'danger',
                                default => 'warning',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-heading text-xs font-bold text-slate-900">{{ $row->mahasiswa?->nama ?? '-' }}</p>
                                <p class="text-[11px] text-slate-400">{{ $row->mahasiswa?->nim ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-700">{{ $row->nama_instansi }}</td>
                            <td class="px-4 py-4 text-xs text-slate-600">{{ $row->jenis_program?->shortLabel() ?? '-' }}</td>
                            <td class="px-4 py-4 text-xs text-slate-700">{{ $row->dosenPembimbing?->nama ?? '-' }}</td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route($prefix.'.verifikasi.pembimbing-lapangan', $row) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select
                                        name="dosen_kunjung_id"
                                        onchange="this.form.submit()"
                                        class="w-44 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 focus:border-[#0101B7] focus:outline-none"
                                    >
                                        <option value="">Pilih dosen...</option>
                                        @foreach ($dosens as $dosen)
                                            <option value="{{ $dosen->id }}" @selected($row->dosen_kunjung_id === $dosen->id)>
                                                {{ $dosen->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-4">
                                <x-ui.status-badge :variant="$badgeVar" :label="$row->status_pendataan->label()" />
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a
                                    href="{{ route($prefix.'.verifikasi.show', $row) }}"
                                    class="inline-flex items-center rounded-full border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-[#0101B7] transition hover:bg-[#0101B7] hover:text-white"
                                >
                                    Tinjau &rsaquo;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-xs text-slate-400">Belum ada data pendataan mahasiswa yang masuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.portal>
