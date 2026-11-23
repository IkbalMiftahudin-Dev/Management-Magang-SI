@php
    use App\Enums\StatusPendataan;
    use App\Enums\StatusValidasiLogbook;
    use App\Enums\TipeLogbook;

    $canCreate = $pendataan !== null && $pendataan->status_pendataan === StatusPendataan::Terverifikasi;
@endphp

<x-layouts.portal title="Logbook Kegiatan">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900">Logbook Kegiatan</h1>
            <p class="mt-1 text-sm text-slate-500">Catat progres kegiatan mingguan atau rekap bulanan dan pantau validasi dosen pembimbing.</p>
        </div>
    </div>

    @if (! $canCreate)
        <div class="mb-6 rounded-3xl bg-amber-50 p-5 text-sm text-amber-900 ring-1 ring-amber-200">
            <p class="font-heading font-bold">Pengisian Logbook Menunggu Verifikasi Pendataan</p>
            <p class="mt-1 text-xs leading-relaxed">
                Anda dapat mulai mengisi logbook kegiatan setelah Pendataan Magang berstatus <strong>Terverifikasi</strong>.
            </p>
        </div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-12">
        {{-- Left Column: Catat Kegiatan Form --}}
        <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100 lg:col-span-5">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#F2F7FF] text-[#0101B7]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
                </div>
                <div>
                    <h2 class="font-heading text-base font-bold text-slate-900">Catat Kegiatan</h2>
                    <p class="text-xs text-slate-500">Bagikan progres magangmu minggu ini.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('mahasiswa.logbook.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                {{-- Segmented Mode Toggle --}}
                <div class="grid grid-cols-2 gap-1.5 rounded-full bg-[#F2F7FF] p-1">
                    @foreach (TipeLogbook::cases() as $index => $tipe)
                        <label class="cursor-pointer text-center">
                            <input type="radio" name="tipe_pengisian" value="{{ $tipe->value }}" @checked(old('tipe_pengisian', TipeLogbook::Mingguan->value) === $tipe->value) class="peer sr-only">
                            <span class="block rounded-full px-3 py-2 text-xs font-semibold text-slate-500 transition peer-checked:bg-white peer-checked:text-[#0101B7] peer-checked:shadow-xs">
                                {{ $tipe->label() }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Tanggal</label>
                        <input type="date" name="tanggal" required value="{{ old('tanggal', now()->format('Y-m-d')) }}" class="w-full rounded-2xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Minggu ke-</label>
                        <input type="number" name="minggu_ke" min="1" max="52" required value="{{ old('minggu_ke', max(1, $logbooks->count() + 1)) }}" class="w-full rounded-2xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">Deskripsi kegiatan</label>
                    <textarea name="deskripsi_kegiatan" rows="4" required placeholder="Ceritakan kegiatan, progres, atau kendala yang dihadapi (atau 4 poin rekap setiap Jumat)..." class="w-full rounded-2xl border border-slate-200 px-3.5 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">{{ old('deskripsi_kegiatan') }}</textarea>
                </div>

                <div class="rounded-2xl border border-dashed border-slate-300 bg-[#F2F7FF]/50 p-4">
                    <label class="block text-xs font-semibold text-slate-800">Unggah foto bukti</label>
                    <p class="mt-0.5 text-[11px] text-slate-400">PNG atau JPG, maksimal 5 MB</p>
                    <input type="file" name="foto_bukti" accept=".jpg,.jpeg,.png" class="mt-2.5 w-full text-xs text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-[#0101B7] file:px-4 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-blue-900">
                </div>

                <button
                    type="submit"
                    @disabled(! $canCreate)
                    class="w-full rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-45"
                >
                    Simpan kegiatan
                </button>
            </form>
        </section>

        {{-- Right Column: Riwayat Kegiatan (3-Column Structured Table) --}}
        <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100 lg:col-span-7">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-heading text-base font-bold text-slate-900">Riwayat Kegiatan</h2>
                    <p class="text-xs text-slate-500">{{ $logbooks->count() }} aktivitas tercatat</p>
                </div>
                @if ($pendataan?->semester_akademik)
                    <x-ui.status-badge variant="info" :label="$pendataan->semester_akademik" />
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-[#F2F7FF] text-[11px] font-bold text-slate-500 uppercase">
                            <th class="rounded-l-xl px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Kegiatan</th>
                            <th class="rounded-r-xl px-4 py-3 text-right">Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logbooks as $logbook)
                            @php
                                $badgeVariant = match ($logbook->status_validasi) {
                                    StatusValidasiLogbook::Tervalidasi => 'success',
                                    StatusValidasiLogbook::Revisi => 'danger',
                                    default => 'warning',
                                };
                            @endphp
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-4">
                                    <p class="text-xs font-bold text-slate-900">{{ $logbook->tanggal?->translatedFormat('d M Y') }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">Minggu {{ $logbook->minggu_ke }} · {{ $logbook->tipe_pengisian->label() }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-xs leading-relaxed text-slate-700">{{ $logbook->deskripsi_kegiatan }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <x-ui.status-badge :variant="$badgeVariant" :label="$logbook->status_validasi->label()" />
                                        @if ($logbook->catatan_dosen)
                                            <span class="text-[11px] text-slate-500">Catatan: {{ $logbook->catatan_dosen }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if ($logbook->foto_bukti_path)
                                            <a
                                                href="{{ route('files.logbook', $logbook) }}"
                                                target="_blank"
                                                title="Lihat foto bukti"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#F2F7FF] text-[#0101B7] transition hover:bg-[#0101B7] hover:text-white"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </a>
                                        @endif

                                        @if ($logbook->status_validasi !== StatusValidasiLogbook::Tervalidasi)
                                            <form method="POST" action="{{ route('mahasiswa.logbook.destroy', $logbook) }}" onsubmit="return confirm('Hapus entri logbook ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 transition hover:bg-rose-600 hover:text-white">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-10 text-center text-xs text-slate-400">
                                    Belum ada catatan logbook kegiatan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.portal>
