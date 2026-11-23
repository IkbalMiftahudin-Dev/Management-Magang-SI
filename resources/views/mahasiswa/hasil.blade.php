<x-layouts.portal title="SK & Hasil Konversi Nilai">
    <div class="mb-6">
        <h1 class="font-heading text-2xl font-bold text-slate-900">SK Magang &amp; Hasil Konversi Nilai</h1>
        <p class="mt-1 text-sm text-slate-500">Unduh dokumen SK yang telah disahkan Kaprodi dan pantau nilai akhir mata kuliah konversi.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        {{-- Approved SK List --}}
        <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100 lg:col-span-5">
            <h2 class="font-heading text-base font-bold text-slate-900">Dokumen SK Sah</h2>
            <p class="mt-0.5 text-xs text-slate-500">Hanya SK yang sudah disahkan Kaprodi yang dapat diunduh.</p>

            <div class="mt-5 space-y-3">
                @forelse ($approvedSks as $sk)
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-[#F2F7FF] p-4">
                        <div class="min-w-0">
                            <x-ui.status-badge variant="success" :label="$sk->jenis_sk->label()" />
                            <p class="mt-2 truncate font-heading text-sm font-bold text-slate-900">{{ $sk->nomor_sk }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Disahkan {{ $sk->approved_at?->translatedFormat('d M Y H:i') }}
                            </p>
                        </div>
                        <a
                            href="{{ route('files.sk', ['dokumenSk' => $sk, 'download' => 1]) }}"
                            class="shrink-0 rounded-full bg-[#0101B7] px-4 py-2 text-xs font-semibold text-white hover:bg-blue-900"
                        >
                            Unduh PDF
                        </a>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-6 text-center text-xs text-slate-400">
                        Belum ada dokumen SK yang disahkan.
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Published Conversion Grades --}}
        <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100 lg:col-span-7">
            <h2 class="font-heading text-base font-bold text-slate-900">Nilai Konversi Mata Kuliah</h2>
            <p class="mt-0.5 text-xs text-slate-500">Nilai yang telah dipublikasikan oleh Dosen Pengampu mata kuliah.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#F2F7FF] text-[11px] font-bold text-slate-500 uppercase">
                            <th class="rounded-l-xl px-4 py-3">Mata Kuliah</th>
                            <th class="px-4 py-3 text-center">SKS</th>
                            <th class="px-4 py-3 text-center">Nilai Akhir</th>
                            <th class="rounded-r-xl px-4 py-3 text-center">Huruf</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($publishedGrades as $grade)
                            <tr>
                                <td class="px-4 py-3.5">
                                    <p class="font-heading text-xs font-bold text-slate-900">
                                        {{ $grade->mataKuliah?->kode_mk }} · {{ $grade->mataKuliah?->nama_mk }}
                                    </p>
                                    @if ($grade->catatan_dosen)
                                        <p class="mt-1 text-[11px] text-slate-500">{{ $grade->catatan_dosen }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs font-semibold text-slate-700">{{ $grade->sks_konversi }}</td>
                                <td class="px-4 py-3.5 text-center font-heading text-sm font-bold text-[#0101B7]">{{ $grade->nilai_akhir }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <x-ui.status-badge variant="success" :label="$grade->nilai_huruf ?? '-'" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-xs text-slate-400">
                                    Belum ada nilai konversi yang dipublikasikan oleh dosen pengampu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.portal>
