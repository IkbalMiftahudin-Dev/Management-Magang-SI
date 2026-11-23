<x-layouts.portal :title="$lowongan->judul_lowongan">
    <x-slot:breadcrumb>
        <a href="{{ route('mahasiswa.beranda') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0101B7] hover:underline">
            &larr; Kembali ke Beranda Lowongan
        </a>
    </x-slot:breadcrumb>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-3xl bg-white p-7 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <x-ui.status-badge
                            :variant="$lowongan->sumber_lowongan->value === 'tawaran_dosen' ? 'gold' : 'info'"
                            :label="$lowongan->sumber_lowongan->cardBadgeLabel()"
                        />
                        <h1 class="mt-3 font-heading text-2xl font-bold text-slate-900">{{ $lowongan->judul_lowongan }}</h1>
                        <p class="mt-1 text-sm font-medium text-slate-600">
                            {{ $lowongan->nama_instansi }} · {{ $lowongan->posisi_bidang }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="rounded-full bg-[#F2F7FF] px-3.5 py-1.5 text-xs font-semibold text-slate-700">{{ $lowongan->lokasi_mode }}</span>
                    <span class="rounded-full bg-[#F2F7FF] px-3.5 py-1.5 text-xs font-semibold text-slate-700">{{ $lowongan->jumlah_pelamar }} pelamar</span>
                    @if ($lowongan->batas_waktu)
                        <span class="rounded-full bg-amber-50 px-3.5 py-1.5 text-xs font-semibold text-amber-800">
                            Batas: {{ $lowongan->batas_waktu->translatedFormat('d M Y') }}
                        </span>
                    @endif
                </div>

                <div class="mt-6 border-t border-slate-100 pt-6">
                    <h2 class="font-heading text-base font-bold text-slate-900">Deskripsi Pekerjaan</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $lowongan->deskripsi }}</p>
                </div>

                @if ($lowongan->persyaratan)
                    <div class="mt-6 border-t border-slate-100 pt-6">
                        <h2 class="font-heading text-base font-bold text-slate-900">Persyaratan</h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $lowongan->persyaratan }}</p>
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                <h2 class="font-heading text-base font-bold text-slate-900">Informasi Pelamaran</h2>
                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
                    Mahasiswa melamar secara mandiri ke instansi mitra atau platform nasional. Setelah menerima Letter of Acceptance (LOA), lanjutkan ke menu <strong>Pendataan Magang</strong>.
                </p>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5">
                        <dt class="text-[11px] font-semibold text-slate-400 uppercase">Kontak Mitra</dt>
                        <dd class="mt-0.5 font-semibold text-[#0101B7]">{{ $lowongan->kontak }}</dd>
                    </div>

                    @if ($lowongan->dosenPenanggungJawab)
                        <div class="rounded-2xl bg-[#F2F7FF] p-3.5">
                            <dt class="text-[11px] font-semibold text-slate-400 uppercase">Dosen Penanggung Jawab</dt>
                            <dd class="mt-0.5 font-semibold text-slate-800">{{ $lowongan->dosenPenanggungJawab->nama }}</dd>
                        </div>
                    @endif
                </dl>

                <a
                    href="{{ route('mahasiswa.pendataan.show') }}"
                    class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-900"
                >
                    Sudah Diterima? Isi Pendataan &rarr;
                </a>
            </section>
        </aside>
    </div>
</x-layouts.portal>
