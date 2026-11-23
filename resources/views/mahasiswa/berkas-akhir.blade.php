@php
    use App\Enums\StatusBerkasAkhir;
    use App\Enums\StatusPendataan;
    use App\Services\BerkasAkhirService;

    $canUpload = $pendataan !== null && $pendataan->status_pendataan === StatusPendataan::Terverifikasi;
    $uploadedCount = $berkas?->totalUploadedCount() ?? 0;
    $percentage = $berkas?->completionPercentage() ?? 0;
    $canSubmit = $canUpload && ($berkas?->isMandatoryComplete() ?? false);
@endphp

<x-layouts.portal title="Pengumpulan Berkas Akhir">
    {{-- Header + Progress Bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900">Pengumpulan Berkas Akhir</h1>
            <p class="mt-1 text-sm text-slate-500">Unggah seluruh dokumen akhir dalam satu proses yang terpusat.</p>
        </div>

        <div class="flex items-center gap-4">
            <span class="font-heading text-3xl font-bold text-[#0101B7]">{{ $percentage }}%</span>
            <div class="w-36">
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-[#0101B7] transition-all" style="width: {{ $percentage }}%"></div>
                </div>
                <p class="mt-1 text-[11px] font-medium text-slate-500">{{ $uploadedCount }} dari 6 lengkap</p>
            </div>
        </div>
    </div>

    {{-- One-Stop Info Banner --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl bg-[#8DCCF1]/20 px-5 py-4 ring-1 ring-[#8DCCF1]/40">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-[#0101B7] shadow-xs">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            </div>
            <div>
                <p class="font-heading text-xs font-bold text-slate-900">One-Stop Submission</p>
                <p class="text-xs text-slate-600">Unggah 5 berkas wajib dan 1 sertifikat opsional. Format PDF maksimal 10 MB per berkas.</p>
            </div>
        </div>
        @if ($berkas)
            <x-ui.status-badge
                :variant="$berkas->status_kelengkapan === StatusBerkasAkhir::BelumLengkap ? 'warning' : 'success'"
                :label="$berkas->status_kelengkapan->label()"
            />
        @endif
    </div>

    {{-- 6 Numbered Upload Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @php $slotIndex = 1; @endphp
        @foreach (BerkasAkhirService::SLOTS as $slotKey => $meta)
            @php
                $filePath = $berkas?->{$meta['path']};
                $uploadedAt = $berkas?->{$meta['timestamp']};
                $isUploaded = is_string($filePath) && $filePath !== '';
            @endphp
            <article class="relative flex flex-col justify-between rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.04)] {{ $isUploaded ? 'ring-1 ring-emerald-200' : 'border border-dashed border-slate-300' }}">
                <div>
                    <div class="flex items-start justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $isUploaded ? 'bg-emerald-50 text-emerald-600' : 'bg-[#F2F7FF] text-[#0101B7]' }}">
                            @if ($isUploaded)
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            @endif
                        </div>
                        <span class="font-heading text-lg font-bold text-slate-200">{{ sprintf('%02d', $slotIndex++) }}</span>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <h2 class="font-heading text-base font-bold text-slate-900">{{ $meta['label'] }}</h2>
                        @if (! $meta['mandatory'])
                            <x-ui.status-badge variant="info" label="Opsional" />
                        @endif
                    </div>

                    @if ($isUploaded && $pendataan !== null)
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-ui.status-badge variant="success" :label="'Terunggah · '.($uploadedAt?->translatedFormat('d M') ?? 'OK')" />
                            <a href="{{ route('files.pendataan.slot', ['pendataan' => $pendataan, 'slot' => $slotKey]) }}" target="_blank" class="text-xs font-semibold text-[#0101B7] hover:underline">
                                Pratinjau PDF &rsaquo;
                            </a>
                        </div>
                    @else
                        <p class="mt-1.5 text-xs text-slate-400">Pilih berkas PDF untuk diunggah ke penyimpanan aman.</p>
                    @endif
                </div>

                @if ($canUpload)
                    <form method="POST" action="{{ route('mahasiswa.berkas-akhir.upload', ['slot' => $slotKey]) }}" enctype="multipart/form-data" class="mt-5 flex items-center gap-2 border-t border-slate-100 pt-4">
                        @csrf
                        <input type="file" name="file" accept=".pdf" required class="min-w-0 flex-1 text-[11px] text-slate-600 file:mr-2 file:rounded-full file:border-0 file:bg-[#F2F7FF] file:px-3 file:py-1.5 file:text-[11px] file:font-semibold file:text-[#0101B7] hover:file:bg-[#0101B7] hover:file:text-white">
                        <button type="submit" class="shrink-0 rounded-full bg-[#0101B7] px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-900">
                            Unggah
                        </button>
                    </form>
                @endif
            </article>
        @endforeach
    </div>

    {{-- Bottom Submit All Bar --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-5 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
        <div>
            <p class="font-heading text-sm font-bold text-slate-900">Pastikan seluruh dokumen wajib sudah benar</p>
            <p class="mt-0.5 text-xs text-slate-500">
                Berkas yang dikirim akan langsung tersedia untuk dinilai oleh Dosen Pengampu mata kuliah konversi.
            </p>
        </div>

        <form method="POST" action="{{ route('mahasiswa.berkas-akhir.submit') }}" onsubmit="return confirm('Kirim seluruh berkas akhir untuk dinilai dosen?')">
            @csrf
            <button
                type="submit"
                @disabled(! $canSubmit)
                class="rounded-full bg-[#0101B7] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Kirim seluruh berkas
            </button>
        </form>
    </div>
</x-layouts.portal>
