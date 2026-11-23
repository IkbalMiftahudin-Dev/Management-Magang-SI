@php
    use App\Enums\JenisSk;
    use App\Enums\StatusPendataan;
    use App\Enums\StatusPengesahanSk;
    use App\Enums\UserRole;

    $isKaprodi = auth()->user()->role === UserRole::Kaprodi;
    $prefix = $isKaprodi ? 'kaprodi' : 'staff';
@endphp

<x-layouts.portal :title="'Tinjau Pendataan — '.($pendataan->mahasiswa?->nama ?? 'Mahasiswa')">
    <x-slot:breadcrumb>
        <a href="{{ route($prefix.'.verifikasi.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0101B7] hover:underline">
            &larr; Kembali ke Rekap Pendataan
        </a>
    </x-slot:breadcrumb>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900">{{ $pendataan->mahasiswa?->nama }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                NIM {{ $pendataan->mahasiswa?->nim ?? '-' }} · {{ $pendataan->nama_instansi }} ({{ $pendataan->posisi }})
            </p>
        </div>
        <x-ui.status-badge
            :variant="in_array($pendataan->status_pendataan, [StatusPendataan::Terverifikasi, StatusPendataan::Selesai], true) ? 'success' : ($pendataan->status_pendataan === StatusPendataan::PerluRevisi ? 'danger' : 'warning')"
            :label="$pendataan->status_pendataan->label()"
        />
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        {{-- Left Details --}}
        <div class="space-y-6 lg:col-span-7">
            <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                <h2 class="font-heading text-base font-bold text-slate-900">Informasi Program &amp; Berkas</h2>
                <dl class="mt-4 grid gap-3 text-xs sm:grid-cols-2">
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5"><dt class="text-slate-400">Program</dt><dd class="mt-1 font-bold text-slate-900">{{ $pendataan->jenis_program?->label() ?? '-' }}</dd></div>
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5"><dt class="text-slate-400">Periode &amp; Mode</dt><dd class="mt-1 font-bold text-slate-900">{{ $pendataan->tanggal_mulai?->format('d M Y') }} – {{ $pendataan->tanggal_selesai?->format('d M Y') }} ({{ $pendataan->mode_kegiatan?->label() }})</dd></div>
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5"><dt class="text-slate-400">Dosen Pembimbing UPI</dt><dd class="mt-1 font-bold text-slate-900">{{ $pendataan->dosenPembimbing?->nama ?? '-' }}</dd></div>
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5"><dt class="text-slate-400">Pembimbing Lapangan</dt><dd class="mt-1 font-bold text-[#0101B7]">{{ $pendataan->dosenKunjung?->nama ?? 'Belum ditetapkan' }}</dd></div>
                </dl>

                @if ($pendataan->loa_path)
                    <div class="mt-4">
                        <a href="{{ route('files.pendataan.slot', ['pendataan' => $pendataan, 'slot' => 'loa']) }}" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-[#F2F7FF] px-4 py-2 text-xs font-semibold text-[#0101B7] hover:bg-[#0101B7] hover:text-white">
                            Lihat Letter of Acceptance (LOA) &rarr;
                        </a>
                    </div>
                @endif

                <div class="mt-5 border-t border-slate-100 pt-5">
                    <h3 class="font-heading text-sm font-bold text-slate-900">Mata Kuliah Konversi ({{ $pendataan->totalConvertedSks() }} SKS)</h3>
                    <div class="mt-3 space-y-2">
                        @foreach ($pendataan->konversiMataKuliahs as $konv)
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3.5 py-2.5 text-xs">
                                <span class="font-semibold text-slate-800">{{ $konv->mataKuliah?->kode_mk }} · {{ $konv->mataKuliah?->nama_mk }}</span>
                                <span class="font-bold text-[#0101B7]">{{ $konv->sks_konversi }} SKS</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Dokumen SK Section --}}
            <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-heading text-base font-bold text-slate-900">Dokumen SK Magang &amp; Kunjung</h2>
                        <p class="text-xs text-slate-500">Staff/Kaprodi dapat men-generate draf SK; pengesahan final eksklusif oleh Kaprodi.</p>
                    </div>
                </div>

                @if (in_array($pendataan->status_pendataan, [StatusPendataan::Terverifikasi, StatusPendataan::Selesai], true))
                    <div class="mt-4 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route($prefix.'.verifikasi.generate-sk', $pendataan) }}">
                            @csrf
                            <input type="hidden" name="jenis_sk" value="{{ JenisSk::SkMagang->value }}">
                            <button type="submit" class="rounded-full bg-[#0101B7] px-4 py-2 text-xs font-semibold text-white hover:bg-blue-900">
                                Generate Draf SK Magang
                            </button>
                        </form>
                        @if ($pendataan->dosen_kunjung_id)
                            <form method="POST" action="{{ route($prefix.'.verifikasi.generate-sk', $pendataan) }}">
                                @csrf
                                <input type="hidden" name="jenis_sk" value="{{ JenisSk::SkKunjung->value }}">
                                <button type="submit" class="rounded-full border border-[#0101B7] px-4 py-2 text-xs font-semibold text-[#0101B7] hover:bg-[#F2F7FF]">
                                    Generate Draf SK Kunjung
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                <div class="mt-4 space-y-3">
                    @forelse ($pendataan->dokumenSks as $sk)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-heading text-xs font-bold text-slate-900">{{ $sk->jenis_sk->label() }}</span>
                                    <x-ui.status-badge :variant="$sk->status_pengesahan === StatusPengesahanSk::Disahkan ? 'success' : 'warning'" :label="$sk->status_pengesahan->label()" />
                                </div>
                                <p class="mt-1 font-mono text-xs text-slate-500">{{ $sk->nomor_sk }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('files.sk', $sk) }}" target="_blank" class="rounded-full bg-[#F2F7FF] px-3.5 py-1.5 text-xs font-semibold text-[#0101B7] hover:bg-blue-100">
                                    Pratinjau PDF
                                </a>
                                @if ($isKaprodi && $sk->status_pengesahan === StatusPengesahanSk::Draft)
                                    <form method="POST" action="{{ route('kaprodi.sk.approve', $sk) }}" onsubmit="return confirm('Sahkan SK {{ $sk->nomor_sk }} secara resmi? SK yang telah disahkan tidak dapat diubah kembali.')">
                                        @csrf
                                        <button type="submit" class="rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                            Sahkan SK &#10003;
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada draf SK yang dibuat.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Right Actions: Verifikasi Status --}}
        <aside class="space-y-6 lg:col-span-5">
            <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                <h2 class="font-heading text-base font-bold text-slate-900">Keputusan Verifikasi Pendataan</h2>
                <p class="mt-1 text-xs text-slate-500">Setujui agar mahasiswa dapat mengisi logbook dan SK dapat diterbitkan, atau kembalikan dengan catatan revisi.</p>

                <form method="POST" action="{{ route($prefix.'.verifikasi.status', $pendataan) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Keputusan Status</label>
                        <select name="status" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                            <option value="{{ StatusPendataan::Terverifikasi->value }}" @selected($pendataan->status_pendataan === StatusPendataan::Terverifikasi)>Terverifikasi (Setujui)</option>
                            <option value="{{ StatusPendataan::PerluRevisi->value }}" @selected($pendataan->status_pendataan === StatusPendataan::PerluRevisi)>Perlu Revisi (Kembalikan ke Mahasiswa)</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Catatan Verifikasi (wajib bila Perlu Revisi)</label>
                        <textarea name="catatan_verifikasi" rows="3" placeholder="Tuliskan catatan perbaikan jika ada..." class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">{{ old('catatan_verifikasi', $pendataan->catatan_verifikasi) }}</textarea>
                    </div>
                    <button type="submit" class="w-full rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white hover:bg-blue-900">
                        Simpan Keputusan Verifikasi
                    </button>
                </form>
            </section>
        </aside>
    </div>
</x-layouts.portal>
