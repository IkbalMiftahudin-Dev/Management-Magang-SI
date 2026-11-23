@php
    use App\Enums\StatusPenilaian;
    use App\Enums\StatusValidasiLogbook;

    $pendataan = $konversi->pendataanMagang;
    $mhs = $pendataan?->mahasiswa;
    $mk = $konversi->mataKuliah;
    $berkas = $pendataan?->berkasAkhir;
    $logbooks = $pendataan?->logbooks ?? collect();
    $validatedLogbooks = $logbooks->where('status_validasi', StatusValidasiLogbook::Tervalidasi)->count();
    $existingScores = $konversi->penilaianDetails->pluck('nilai', 'rubrik_komponen_id')->all();
    $canGrade = auth()->user()->can('grade', $konversi);
    $isPembimbingUpi = auth()->user()->dosen !== null && $pendataan?->dosen_pembimbing_id === auth()->user()->dosen->id;
@endphp

<x-layouts.portal :title="'Penilaian '.($mhs?->nama ?? 'Mahasiswa')">
    <x-slot:breadcrumb>
        <a href="{{ route('dosen.penilaian.index', ['mata_kuliah_id' => $mk?->id]) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0101B7] hover:underline">
            &larr; Kembali ke daftar mahasiswa
        </a>
    </x-slot:breadcrumb>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900">Penilaian {{ $mhs?->nama }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Nilai diberikan untuk mata kuliah <strong>{{ $mk?->nama_mk }}</strong> yang diampu oleh {{ $konversi->dosenPenilai?->nama ?? auth()->user()->name }}.
            </p>
        </div>
        <x-ui.status-badge
            :variant="$konversi->status_penilaian === StatusPenilaian::Dipublikasikan ? 'success' : 'warning'"
            :label="$konversi->status_penilaian === StatusPenilaian::Dipublikasikan ? 'Dipublikasikan & Terkunci' : 'Belum dipublikasikan'"
        />
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        {{-- Left Column (8 cols) --}}
        <div class="space-y-6 lg:col-span-8">
            {{-- Student & Program Summary Card --}}
            <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0101B7] font-heading text-sm font-bold text-white">
                            {{ mb_strtoupper(mb_substr($mhs?->nama ?? 'MH', 0, 2)) }}
                        </div>
                        <div>
                            <h2 class="font-heading text-base font-bold text-slate-900">{{ $mhs?->nama }}</h2>
                            <p class="text-xs text-slate-500">{{ $mhs?->nim ?? '-' }} · {{ $mhs?->prodi ?? 'Rekayasa Perangkat Lunak' }}</p>
                        </div>
                    </div>
                    <x-ui.status-badge variant="success" :label="$konversi->tingkat_kesesuaian->label()" />
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5"><p class="text-[11px] text-slate-400">Instansi</p><p class="mt-0.5 text-xs font-bold text-slate-900">{{ $pendataan?->nama_instansi }}</p></div>
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5"><p class="text-[11px] text-slate-400">Posisi</p><p class="mt-0.5 text-xs font-bold text-slate-900">{{ $pendataan?->posisi }}</p></div>
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5"><p class="text-[11px] text-slate-400">Program</p><p class="mt-0.5 text-xs font-bold text-slate-900">{{ $pendataan?->jenis_program?->shortLabel() ?? '-' }}</p></div>
                    <div class="rounded-2xl bg-[#F2F7FF] p-3.5"><p class="text-[11px] text-slate-400">Periode</p><p class="mt-0.5 text-xs font-bold text-slate-900">{{ $pendataan?->tanggal_mulai?->format('d M') }} – {{ $pendataan?->tanggal_selesai?->format('d M Y') }}</p></div>
                </div>
            </section>

            {{-- Bukti Pendukung Konversi (3 Cards) --}}
            <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                <h2 class="font-heading text-base font-bold text-slate-900">Bukti Pendukung Konversi</h2>
                <p class="mt-0.5 text-xs text-slate-500">Periksa kelengkapan logbook dan berkas akhir sebelum menetapkan nilai.</p>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-bold text-slate-900">Logbook Kegiatan</p>
                        <p class="mt-1 text-[11px] text-slate-500">{{ $logbooks->count() }} entri · {{ $validatedLogbooks }} tervalidasi</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-bold text-slate-900">Laporan &amp; Berkas Akhir</p>
                        <p class="mt-1 text-[11px] text-slate-500">{{ $berkas?->mandatoryUploadedCount() ?? 0 }} dari 5 dokumen wajib lengkap</p>
                        @if ($pendataan && $berkas?->laporan_akhir_path)
                            <a href="{{ route('files.pendataan.slot', ['pendataan' => $pendataan, 'slot' => 'laporan_akhir']) }}" target="_blank" class="mt-2 inline-block text-[11px] font-semibold text-[#0101B7] hover:underline">Lihat Laporan PDF &rsaquo;</a>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-bold text-slate-900">Rubrik Konversi MK</p>
                        <p class="mt-1 text-[11px] text-slate-500">Pemetaan CPL &amp; aktivitas magang</p>
                        @if ($pendataan && $berkas?->rubrik_konversi_path)
                            <a href="{{ route('files.pendataan.slot', ['pendataan' => $pendataan, 'slot' => 'rubrik_konversi']) }}" target="_blank" class="mt-2 inline-block text-[11px] font-semibold text-[#0101B7] hover:underline">Lihat Rubrik PDF &rsaquo;</a>
                        @endif
                    </div>
                </div>

                @if ($isPembimbingUpi && $logbooks->isNotEmpty())
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="text-xs font-bold text-slate-800">Validasi Logbook (Mode Dosen Pembimbing UPI)</p>
                        <div class="mt-2.5 space-y-2">
                            @foreach ($logbooks->take(5) as $lb)
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-[#F2F7FF]/70 px-4 py-2.5 text-xs">
                                    <div class="min-w-0 flex-1">
                                        <span class="font-bold text-slate-900">Minggu {{ $lb->minggu_ke }}</span>
                                        <span class="ml-1 text-slate-600">{{ \Illuminate\Support\Str::limit($lb->deskripsi_kegiatan, 80) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-ui.status-badge :variant="$lb->status_validasi === StatusValidasiLogbook::Tervalidasi ? 'success' : 'warning'" :label="$lb->status_validasi->label()" />
                                        @if ($lb->status_validasi !== StatusValidasiLogbook::Tervalidasi)
                                            <form method="POST" action="{{ route('dosen.bimbingan.logbook.validate', $lb) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status_validasi" value="{{ StatusValidasiLogbook::Tervalidasi->value }}">
                                                <button type="submit" class="rounded-full bg-emerald-600 px-3 py-1 text-[11px] font-semibold text-white hover:bg-emerald-700">Validasi</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- Rubrik Penilaian Berbobot --}}
            <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                <h2 class="font-heading text-base font-bold text-slate-900">Rubrik Penilaian {{ $mk?->nama_mk }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">Masukkan nilai 0–100 untuk setiap komponen capaian mata kuliah.</p>

                <form id="penilaian-form" method="POST" action="{{ route('dosen.penilaian.draft', $konversi) }}" class="mt-5 space-y-5">
                    @csrf
                    <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                        <div class="grid grid-cols-12 bg-[#F2F7FF] px-4 py-3 text-[11px] font-bold text-slate-500 uppercase">
                            <div class="col-span-7">Komponen Capaian</div>
                            <div class="col-span-2 text-center">Bobot</div>
                            <div class="col-span-3 text-right">Nilai (0–100)</div>
                        </div>

                        @foreach ($mk?->rubrikKomponens ?? [] as $idx => $komponen)
                            @php
                                $val = old("scores.$idx.nilai", $existingScores[$komponen->id] ?? '');
                            @endphp
                            <div class="grid grid-cols-12 items-center gap-3 px-4 py-3.5">
                                <div class="col-span-7">
                                    <p class="text-xs font-bold text-slate-900">{{ $komponen->nama_komponen }}</p>
                                    @if ($komponen->deskripsi)
                                        <p class="mt-0.5 text-[11px] text-slate-500">{{ $komponen->deskripsi }}</p>
                                    @endif
                                </div>
                                <div class="col-span-2 text-center text-xs font-bold text-[#0101B7]">
                                    {{ $komponen->bobot_persen }}%
                                </div>
                                <div class="col-span-3 text-right">
                                    <input type="hidden" name="scores[{{ $idx }}][rubrik_komponen_id]" value="{{ $komponen->id }}">
                                    <input
                                        type="number"
                                        name="scores[{{ $idx }}][nilai]"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value="{{ $val }}"
                                        data-weight="{{ $komponen->bobot_persen }}"
                                        oninput="recomputeWeightedGrade()"
                                        @disabled(! $canGrade)
                                        class="score-input w-24 rounded-xl border border-slate-200 px-3 py-2 text-center font-heading text-sm font-bold text-slate-900 focus:border-[#0101B7] focus:outline-none disabled:bg-slate-50"
                                    >
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-slate-700">Nilai Instansi (Mitra)</label>
                            <input
                                type="number"
                                name="nilai_instansi"
                                id="nilai-instansi-input"
                                min="0"
                                max="100"
                                step="0.01"
                                value="{{ old('nilai_instansi', $konversi->nilai_instansi) }}"
                                oninput="document.getElementById('live-nilai-instansi').textContent = this.value || '-'"
                                @disabled(! $canGrade)
                                class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold focus:border-[#0101B7] focus:outline-none disabled:bg-slate-50"
                            >
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold text-slate-700">Catatan dosen pengampu</label>
                            <textarea
                                name="catatan_dosen"
                                rows="2"
                                @disabled(! $canGrade)
                                placeholder="Tuliskan evaluasi capaian mahasiswa selama magang..."
                                class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none disabled:bg-slate-50"
                            >{{ old('catatan_dosen', $konversi->catatan_dosen) }}</textarea>
                        </div>
                    </div>
                </form>
            </section>
        </div>

        {{-- Right Sticky Panel: HASIL KONVERSI --}}
        <aside class="rounded-3xl bg-white p-6 text-center shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100 lg:col-span-4">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F2F7FF] text-[#0101B7]">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13"/></svg>
            </div>
            <p class="mt-3 text-[11px] font-bold tracking-[0.16em] text-slate-400 uppercase">Hasil Konversi</p>
            <h3 class="mt-1 font-heading text-lg font-bold text-slate-900">{{ $mk?->nama_mk }}</h3>
            <p class="text-xs text-slate-500">{{ $mk?->kode_mk }} · {{ $konversi->sks_konversi }} SKS</p>

            {{-- Circular Score Display --}}
            <div class="mx-auto mt-6 flex h-32 w-32 flex-col items-center justify-center rounded-full border-8 border-[#F2F7FF] bg-white shadow-inner ring-2 ring-[#0101B7]/20">
                <span id="live-nilai-akhir" class="font-heading text-3xl font-bold text-[#0101B7]">
                    {{ $konversi->nilai_akhir !== null ? rtrim(rtrim((string) $konversi->nilai_akhir, '0'), '.') : '--' }}
                </span>
                <span class="text-[11px] font-medium text-slate-400">Nilai akhir</span>
            </div>

            <div class="mt-4">
                <span id="live-nilai-huruf" class="inline-flex rounded-full bg-emerald-50 px-4 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200/70">
                    Nilai huruf {{ $konversi->nilai_huruf ?? '-' }}
                </span>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-[#F2F7FF] p-3">
                    <p class="text-[11px] text-slate-400">Nilai instansi</p>
                    <p id="live-nilai-instansi" class="mt-0.5 font-heading text-base font-bold text-slate-900">
                        {{ $konversi->nilai_instansi !== null ? rtrim(rtrim((string) $konversi->nilai_instansi, '0'), '.') : '-' }}
                    </p>
                </div>
                <div class="rounded-2xl bg-[#F2F7FF] p-3">
                    <p class="text-[11px] text-slate-400">Nilai dosen</p>
                    <p id="live-nilai-dosen" class="mt-0.5 font-heading text-base font-bold text-slate-900">
                        {{ $konversi->nilai_dosen !== null ? rtrim(rtrim((string) $konversi->nilai_dosen, '0'), '.') : '-' }}
                    </p>
                </div>
            </div>

            <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4 text-left">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0101B7] text-xs font-bold text-white">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] text-slate-400">Dinilai oleh</p>
                    <p class="truncate text-xs font-bold text-slate-900">{{ $konversi->dosenPenilai?->nama ?? auth()->user()->name }}</p>
                </div>
            </div>

            @if ($canGrade)
                <div class="mt-6 space-y-2.5">
                    <button
                        type="button"
                        onclick="submitEvaluationForm('{{ route('dosen.penilaian.draft', $konversi) }}', false)"
                        class="w-full rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900"
                    >
                        Simpan sebagai draf
                    </button>
                    <button
                        type="button"
                        onclick="submitEvaluationForm('{{ route('dosen.penilaian.publish', $konversi) }}', true)"
                        class="w-full rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:border-emerald-600 hover:bg-emerald-600 hover:text-white"
                    >
                        Publikasikan nilai
                    </button>
                </div>
            @endif
        </aside>
    </div>

    @push('scripts')
        <script>
            function scoreToLetter(score) {
                if (score >= 85) return 'A';
                if (score >= 80) return 'A-';
                if (score >= 75) return 'B+';
                if (score >= 70) return 'B';
                if (score >= 65) return 'B-';
                if (score >= 60) return 'C+';
                if (score >= 55) return 'C';
                if (score >= 40) return 'D';
                return 'E';
            }

            function recomputeWeightedGrade() {
                const inputs = document.querySelectorAll('.score-input');
                let weightedSum = 0;
                let totalWeight = 0;
                inputs.forEach((input) => {
                    const val = parseFloat(input.value);
                    const weight = parseFloat(input.dataset.weight || '0');
                    if (!Number.isNaN(val) && weight > 0) {
                        weightedSum += val * weight;
                        totalWeight += weight;
                    }
                });
                if (totalWeight <= 0) { return; }
                const score = Math.round((weightedSum / totalWeight) * 100) / 100;
                const formatted = Number.isInteger(score) ? String(score) : score.toFixed(2);
                document.getElementById('live-nilai-akhir').textContent = formatted;
                document.getElementById('live-nilai-dosen').textContent = formatted;
                document.getElementById('live-nilai-huruf').textContent = 'Nilai huruf ' + scoreToLetter(score);
            }

            function submitEvaluationForm(actionUrl, isPublish) {
                if (isPublish && !confirm('Publikasikan dan kunci nilai konversi ini secara permanen? Nilai yang dipublikasikan tidak dapat diubah kembali.')) {
                    return;
                }
                const form = document.getElementById('penilaian-form');
                if (!form) { return; }
                form.action = actionUrl;
                form.submit();
            }
        </script>
    @endpush
</x-layouts.portal>
