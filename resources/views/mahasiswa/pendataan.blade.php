@php
    use App\Enums\JenisProgramMagang;
    use App\Enums\ModeKegiatan;
    use App\Enums\StatusPendataan;

    $editable = $pendataan === null || $pendataan->status_pendataan->isEditableByStudent();
    $requestedStep = (int) request()->query('step', $pendataan?->current_step ?? 1);
    $activeStep = $editable ? min(max($requestedStep, 1), 4) : 4;
    if ($pendataan === null) {
        $activeStep = 1;
    }

    $mappedIds = $pendataan?->konversiMataKuliahs->pluck('mata_kuliah_id')->all() ?? [];
    $totalMk = $pendataan?->konversiMataKuliahs->count() ?? 0;
    $totalSks = $pendataan?->konversiMataKuliahs->sum('sks_konversi') ?? 0;

    $statusVariant = match ($pendataan?->status_pendataan) {
        StatusPendataan::Terverifikasi, StatusPendataan::Selesai => 'success',
        StatusPendataan::PerluRevisi => 'danger',
        StatusPendataan::MenungguVerifikasi => 'warning',
        default => 'warning',
    };
@endphp

<x-layouts.portal title="Pendataan Magang">
    {{-- Page Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900">Pendataan Magang</h1>
            <p class="mt-1 text-sm text-slate-500">Lengkapi informasi program, Letter of Acceptance, dan pemetaan mata kuliahmu.</p>
        </div>
        <x-ui.status-badge
            :variant="$statusVariant"
            :label="$pendataan?->status_pendataan?->label() ?? 'Draf belum dikirim'"
        />
    </div>

    {{-- Revision Notice Banner --}}
    @if ($pendataan?->status_pendataan === StatusPendataan::PerluRevisi && $pendataan->catatan_verifikasi)
        <div class="mb-6 rounded-3xl bg-rose-50 p-5 text-sm text-rose-900 ring-1 ring-rose-200">
            <p class="font-heading font-bold text-rose-800">Pendataan Memerlukan Revisi</p>
            <p class="mt-1 leading-relaxed">{{ $pendataan->catatan_verifikasi }}</p>
        </div>
    @endif

    {{-- 4-Step Horizontal Stepper --}}
    <nav class="mb-6 rounded-3xl bg-white px-6 py-4 shadow-[0_6px_24px_rgba(1,1,183,0.04)] ring-1 ring-slate-100">
        @php
            $steps = [
                1 => 'Data Instansi',
                2 => 'Program & Periode',
                3 => 'Konversi MK',
                4 => 'Konfirmasi',
            ];
        @endphp
        <ol class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ($steps as $num => $label)
                @php
                    $isDone = $num < $activeStep;
                    $isCurrent = $num === $activeStep;
                    $canJump = $editable && ($pendataan !== null || $num === 1);
                @endphp
                <li class="flex items-center gap-3">
                    @if ($canJump)
                        <a href="{{ route('mahasiswa.pendataan.show', ['step' => $num]) }}" class="flex items-center gap-2.5">
                    @else
                        <div class="flex items-center gap-2.5">
                    @endif
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition {{ $isCurrent || $isDone ? 'bg-[#0101B7] text-white shadow-xs' : 'bg-[#F2F7FF] text-slate-400' }}">
                            {{ $num }}
                        </span>
                        <span class="text-xs font-semibold {{ $isCurrent || $isDone ? 'text-[#0101B7]' : 'text-slate-400' }}">
                            {{ $label }}
                        </span>
                    @if ($canJump)
                        </a>
                    @else
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        {{-- Left Wizard Card (2 Columns) --}}
        <div class="lg:col-span-2">
            {{-- STEP 1: Data Instansi --}}
            @if ($activeStep === 1)
                <section class="rounded-3xl bg-white p-7 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#F2F7FF] text-[#0101B7]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                        </div>
                        <div>
                            <h2 class="font-heading text-lg font-bold text-slate-900">Informasi Tempat Magang</h2>
                            <p class="text-xs text-slate-500">Masukkan data sesuai Letter of Acceptance.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('mahasiswa.pendataan.step1') }}" class="space-y-5">
                        @csrf
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Nama instansi</label>
                                <input type="text" name="nama_instansi" required value="{{ old('nama_instansi', $pendataan?->nama_instansi) }}" placeholder="Contoh: Telkom Indonesia" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Posisi</label>
                                <input type="text" name="posisi" required value="{{ old('posisi', $pendataan?->posisi) }}" placeholder="Contoh: UI/UX Designer Intern" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Alamat instansi</label>
                                <textarea name="alamat_instansi" rows="3" required placeholder="Jl. Japati No. 1, Bandung, Jawa Barat" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">{{ old('alamat_instansi', $pendataan?->alamat_instansi) }}</textarea>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">Kontak instansi</label>
                                    <input type="text" name="kontak_instansi" required value="{{ old('kontak_instansi', $pendataan?->kontak_instansi) }}" placeholder="talent@telkom.co.id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">Semester akademik</label>
                                    <input type="text" name="semester_akademik" value="{{ old('semester_akademik', $pendataan?->semester_akademik ?? 'Semester Genap 2027/2028') }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end border-t border-slate-100 pt-5">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#0101B7] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                                Simpan &amp; lanjutkan &rsaquo;
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- STEP 2: Program & Periode + 8 Skema MBKM + Upload LOA Wajib --}}
            @if ($activeStep === 2 && $pendataan !== null)
                <section class="rounded-3xl bg-white p-7 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#F2F7FF] text-[#0101B7]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h2 class="font-heading text-lg font-bold text-slate-900">Program &amp; Periode Magang</h2>
                            <p class="text-xs text-slate-500">Tentukan skema program MBKM, LOA resmi, pembimbing, dan durasi kegiatan.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('mahasiswa.pendataan.step2') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <div>
                            <p class="mb-3 text-xs font-semibold text-slate-700">Pilih Program</p>
                            @php
                                $selectedProgram = old('jenis_program', $pendataan->jenis_program?->value ?? JenisProgramMagang::Msib->value);
                            @endphp
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach (JenisProgramMagang::cases() as $program)
                                    <label class="group relative flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition {{ $selectedProgram === $program->value ? 'border-[#0101B7] bg-[#F2F7FF]/60 ring-1 ring-[#0101B7]' : 'border-slate-200 bg-white hover:border-[#0101B7]/50' }}">
                                        <input
                                            type="radio"
                                            name="jenis_program"
                                            value="{{ $program->value }}"
                                            @checked($selectedProgram === $program->value)
                                            class="peer sr-only"
                                            onchange="highlightProgramCards()"
                                        >
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-[11px] font-bold {{ $selectedProgram === $program->value ? 'bg-[#0101B7] text-white' : 'bg-[#F2F7FF] text-[#0101B7]' }}">
                                            {{ $program->code() }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-slate-900">{{ $program->label() }}</p>
                                            <p class="mt-1 text-[11px] leading-relaxed text-slate-500">{{ $program->description() }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Mandatory LOA Upload --}}
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-slate-900">Letter of Acceptance (LOA)</p>
                                    <p class="text-[11px] text-slate-500">Bukti resmi diterima pada program. Otomatis tersinkron ke Berkas Akhir Slot 01.</p>
                                </div>
                                @if ($pendataan->loa_path)
                                    <x-ui.status-badge variant="success" label="Sudah diunggah" />
                                @else
                                    <x-ui.status-badge variant="warning" label="Wajib diunggah" />
                                @endif
                            </div>

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-dashed border-slate-300 bg-[#F2F7FF]/50 p-4">
                                <div class="text-xs text-slate-600">
                                    <p class="font-semibold text-slate-800">Unggah file Letter of Acceptance</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">PDF, JPG, atau PNG · Maksimal 10 MB</p>
                                    @if ($pendataan->loa_path)
                                        <a href="{{ route('files.pendataan.slot', ['pendataan' => $pendataan, 'slot' => 'loa']) }}" target="_blank" class="mt-1 inline-block font-semibold text-[#0101B7] hover:underline">
                                            Lihat berkas LOA saat ini &rarr;
                                        </a>
                                    @endif
                                </div>
                                <input type="file" name="loa" accept=".pdf,.jpg,.jpeg,.png" class="text-xs text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-[#0101B7] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-blue-900">
                            </div>
                        </div>

                        {{-- Supervisor, Mode, & Dates --}}
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Dosen pembimbing UPI</label>
                                <select name="dosen_pembimbing_id" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                                    <option value="">Pilih dosen pembimbing...</option>
                                    @foreach ($dosens as $dosen)
                                        <option value="{{ $dosen->id }}" @selected((int) old('dosen_pembimbing_id', $pendataan->dosen_pembimbing_id) === $dosen->id)>
                                            {{ $dosen->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Mode kegiatan</label>
                                <select name="mode_kegiatan" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                                    @foreach (ModeKegiatan::cases() as $mode)
                                        <option value="{{ $mode->value }}" @selected(old('mode_kegiatan', $pendataan->mode_kegiatan?->value) === $mode->value)>
                                            {{ $mode->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Tanggal mulai</label>
                                <input type="date" name="tanggal_mulai" required value="{{ old('tanggal_mulai', $pendataan->tanggal_mulai?->format('Y-m-d')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Tanggal selesai</label>
                                <input type="date" name="tanggal_selesai" required value="{{ old('tanggal_selesai', $pendataan->tanggal_selesai?->format('Y-m-d')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-[#0101B7] focus:ring-2 focus:ring-[#0101B7]/15 focus:outline-none">
                            </div>
                        </div>

                        @if ($pendataan->durationInWeeks() !== null)
                            <div class="rounded-2xl bg-[#F2F7FF] px-4 py-3 text-xs font-semibold text-[#0101B7]">
                                Durasi program tercatat: {{ $pendataan->durationInWeeks() }} minggu
                            </div>
                        @endif

                        <div class="flex items-center justify-between border-t border-slate-100 pt-5">
                            <a href="{{ route('mahasiswa.pendataan.show', ['step' => 1]) }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                Kembali
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#0101B7] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                                Simpan &amp; lanjutkan &rsaquo;
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- STEP 3: Pemetaan Konversi Mata Kuliah --}}
            @if ($activeStep === 3 && $pendataan !== null)
                <section class="rounded-3xl bg-white p-7 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#F2F7FF] text-[#0101B7]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/></svg>
                        </div>
                        <div>
                            <h2 class="font-heading text-lg font-bold text-slate-900">Pemetaan Konversi Mata Kuliah</h2>
                            <p class="text-xs text-slate-500">Pilih mata kuliah yang relevan dengan aktivitas magangmu.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('mahasiswa.pendataan.step3') }}" class="space-y-5">
                        @csrf
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="grid grid-cols-12 bg-[#F2F7FF] px-4 py-3 text-[11px] font-bold text-slate-500 uppercase">
                                <div class="col-span-9">Mata Kuliah &amp; Dosen Pengampu</div>
                                <div class="col-span-3 text-right">SKS</div>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @foreach ($mataKuliahs as $mk)
                                    @php
                                        $checked = in_array($mk->id, old('mata_kuliah_ids', $mappedIds), true);
                                    @endphp
                                    <label class="grid cursor-pointer grid-cols-12 items-center px-4 py-3.5 transition hover:bg-[#F2F7FF]/40">
                                        <div class="col-span-9 flex items-start gap-3">
                                            <input
                                                type="checkbox"
                                                name="mata_kuliah_ids[]"
                                                value="{{ $mk->id }}"
                                                data-sks="{{ $mk->sks }}"
                                                @checked($checked)
                                                onchange="recalculateCourseSelection()"
                                                class="mt-1 h-4 w-4 rounded border-slate-300 text-[#0101B7] focus:ring-[#0101B7]"
                                            >
                                            <div>
                                                <p class="font-heading text-sm font-bold text-slate-900">{{ $mk->kode_mk }}</p>
                                                <p class="text-xs text-slate-600">
                                                    {{ $mk->nama_mk }}
                                                    @if ($mk->dosenPengampu)
                                                        <span class="text-slate-400">· {{ $mk->dosenPengampu->nama }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-span-3 text-right text-sm font-semibold text-slate-800">
                                            {{ $mk->sks }} SKS
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-8 rounded-2xl bg-[#F2F7FF] px-5 py-3.5">
                            <div>
                                <p class="text-[11px] text-slate-500">Total mata kuliah</p>
                                <p id="selected-mk-count" class="font-heading text-sm font-bold text-slate-900">{{ $totalMk }} mata kuliah</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-500">Total konversi</p>
                                <p id="selected-sks-total" class="font-heading text-sm font-bold text-[#0101B7]">{{ $totalSks }} SKS</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-100 pt-5">
                            <a href="{{ route('mahasiswa.pendataan.show', ['step' => 2]) }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                Kembali
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#0101B7] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                                Simpan &amp; lanjutkan &rsaquo;
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- STEP 4: Konfirmasi Pendataan (or Read-only Submitted Summary) --}}
            @if ($activeStep === 4 && $pendataan !== null)
                <section class="rounded-3xl bg-white p-7 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#F2F7FF] text-[#0101B7]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h2 class="font-heading text-lg font-bold text-slate-900">Konfirmasi Pendataan</h2>
                            <p class="text-xs text-slate-500">Periksa kembali seluruh informasi sebelum dikirim untuk verifikasi prodi.</p>
                        </div>
                    </div>

                    <div class="mb-5 rounded-2xl bg-emerald-50 p-4 text-xs text-emerald-900 ring-1 ring-emerald-200/70">
                        <p class="font-bold">
                            {{ $editable ? 'Seluruh data wajib siap dikirim' : 'Pendataan sudah dikirim — Status: '.$pendataan->status_pendataan->label() }}
                        </p>
                        <p class="mt-0.5 text-emerald-700">
                            Setelah dikirim, perubahan data memerlukan persetujuan Staff Prodi / Kaprodi.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold text-slate-900">Data Instansi</p>
                                @if ($editable)
                                    <a href="{{ route('mahasiswa.pendataan.show', ['step' => 1]) }}" class="text-xs font-semibold text-[#0101B7] hover:underline">Ubah</a>
                                @endif
                            </div>
                            <dl class="mt-3 space-y-1.5 text-xs">
                                <div class="flex justify-between gap-2"><dt class="text-slate-400">Instansi</dt><dd class="font-semibold text-slate-800">{{ $pendataan->nama_instansi }}</dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-400">Posisi</dt><dd class="font-semibold text-slate-800">{{ $pendataan->posisi }}</dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-400">Kontak</dt><dd class="font-semibold text-slate-800">{{ $pendataan->kontak_instansi }}</dd></div>
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold text-slate-900">Program &amp; Periode</p>
                                @if ($editable)
                                    <a href="{{ route('mahasiswa.pendataan.show', ['step' => 2]) }}" class="text-xs font-semibold text-[#0101B7] hover:underline">Ubah</a>
                                @endif
                            </div>
                            <dl class="mt-3 space-y-1.5 text-xs">
                                <div class="flex justify-between gap-2"><dt class="text-slate-400">Program</dt><dd class="font-semibold text-slate-800">{{ $pendataan->jenis_program?->shortLabel() ?? '-' }}</dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-400">Periode</dt><dd class="font-semibold text-slate-800">{{ $pendataan->tanggal_mulai?->format('d M Y') }} – {{ $pendataan->tanggal_selesai?->format('d M Y') }}</dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-400">Pembimbing UPI</dt><dd class="font-semibold text-slate-800">{{ $pendataan->dosenPembimbing?->nama ?? '-' }}</dd></div>
                                @if ($pendataan->dosenKunjung)
                                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Pembimbing Lapangan</dt><dd class="font-semibold text-[#0101B7]">{{ $pendataan->dosenKunjung->nama }}</dd></div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-slate-900">Konversi Mata Kuliah</p>
                            @if ($editable)
                                <a href="{{ route('mahasiswa.pendataan.show', ['step' => 3]) }}" class="text-xs font-semibold text-[#0101B7] hover:underline">Ubah</a>
                            @endif
                        </div>
                        <div class="mt-3 space-y-2">
                            @foreach ($pendataan->konversiMataKuliahs as $konversi)
                                <div class="flex items-center justify-between rounded-xl bg-[#F2F7FF] px-3.5 py-2.5 text-xs">
                                    <div>
                                        <span class="font-bold text-[#0101B7]">{{ $konversi->mataKuliah?->kode_mk }}</span>
                                        <span class="ml-1.5 font-medium text-slate-700">{{ $konversi->mataKuliah?->nama_mk }}</span>
                                        @if ($konversi->mataKuliah?->dosenPengampu)
                                            <span class="text-slate-400">· {{ $konversi->mataKuliah->dosenPengampu->nama }}</span>
                                        @endif
                                    </div>
                                    <span class="font-bold text-[#0101B7]">{{ $konversi->sks_konversi }} SKS</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($editable)
                        <form method="POST" action="{{ route('mahasiswa.pendataan.step4') }}" class="mt-6 space-y-5">
                            @csrf
                            <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-[#FFD166]/20 px-4 py-3.5 text-xs font-medium text-slate-800 ring-1 ring-[#FFD166]/60">
                                <input type="checkbox" name="pernyataan_disetujui" value="1" required class="h-4 w-4 rounded border-slate-300 text-[#0101B7] focus:ring-[#0101B7]">
                                <span>Saya menyatakan bahwa seluruh data yang diisi benar dan dapat dipertanggungjawabkan.</span>
                            </label>

                            <div class="flex items-center justify-between border-t border-slate-100 pt-5">
                                <a href="{{ route('mahasiswa.pendataan.show', ['step' => 3]) }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                    Kembali
                                </a>
                                <button type="submit" onclick="return confirm('Kirim pendataan magang untuk diverifikasi oleh Prodi?')" class="inline-flex items-center gap-2 rounded-full bg-[#0101B7] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                                    Kirim pendataan &#10003;
                                </button>
                            </div>
                        </form>
                    @endif
                </section>
            @endif
        </div>

        {{-- Right Sticky Summary Panel (Ringkasan Pendataan) --}}
        <aside class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F2F7FF] text-[#0101B7]">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            </div>
            <h3 class="mt-4 font-heading text-base font-bold text-slate-900">Ringkasan Pendataan</h3>

            <dl class="mt-4 space-y-3.5 text-xs">
                <div>
                    <dt class="text-slate-400">Mahasiswa</dt>
                    <dd class="mt-0.5 font-bold text-slate-900">{{ $user->name }}</dd>
                    <dd class="text-slate-500">{{ $user->identifier_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Konversi MK</dt>
                    <dd class="mt-0.5 font-bold text-slate-900">{{ $totalMk }} mata kuliah</dd>
                    <dd class="text-slate-500">Total {{ $totalSks }} SKS</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Status</dt>
                    <dd class="mt-1">
                        <x-ui.status-badge :variant="$statusVariant" :label="$pendataan?->status_pendataan?->label() ?? 'Draf belum dikirim'" />
                    </dd>
                </div>
            </dl>

            <p class="mt-5 border-t border-slate-100 pt-4 text-[11px] leading-relaxed text-slate-500">
                &#10003; Data akan diverifikasi Staff Prodi &amp; Kaprodi sebelum penerbitan SK Magang.
            </p>
        </aside>
    </div>

    @push('scripts')
        <script>
            function recalculateCourseSelection() {
                const boxes = document.querySelectorAll('input[name="mata_kuliah_ids[]"]:checked');
                let totalSks = 0;
                boxes.forEach((box) => {
                    totalSks += parseInt(box.dataset.sks || '0', 10);
                });
                const mkEl = document.getElementById('selected-mk-count');
                const sksEl = document.getElementById('selected-sks-total');
                if (mkEl) { mkEl.textContent = `${boxes.length} mata kuliah`; }
                if (sksEl) { sksEl.textContent = `${totalSks} SKS`; }
            }

            function highlightProgramCards() {
                document.querySelectorAll('input[name="jenis_program"]').forEach((radio) => {
                    const card = radio.closest('label');
                    if (!card) { return; }
                    if (radio.checked) {
                        card.classList.add('border-[#0101B7]', 'bg-[#F2F7FF]/60', 'ring-1', 'ring-[#0101B7]');
                        card.classList.remove('border-slate-200', 'bg-white');
                    } else {
                        card.classList.remove('border-[#0101B7]', 'bg-[#F2F7FF]/60', 'ring-1', 'ring-[#0101B7]');
                        card.classList.add('border-slate-200', 'bg-white');
                    }
                });
            }
        </script>
    @endpush
</x-layouts.portal>
