<x-layouts.portal title="Mata Kuliah & Rubrik — Admin">
    <div class="mb-6">
        <h1 class="font-heading text-2xl font-bold text-slate-900">Master Mata Kuliah &amp; Rubrik Konversi</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola mata kuliah konversi MBKM, penetapan dosen pengampu, dan komponen rubrik berbobot total 100%.</p>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        {{-- Left: Existing Courses --}}
        <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100 lg:col-span-7">
            <h2 class="font-heading text-base font-bold text-slate-900">Daftar Mata Kuliah Konversi ({{ $courses->count() }})</h2>
            <div class="mt-4 space-y-4">
                @forelse ($courses as $course)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-heading text-sm font-bold text-[#0101B7]">{{ $course->kode_mk }}</span>
                                <span class="ml-1.5 font-heading text-sm font-bold text-slate-900">{{ $course->nama_mk }}</span>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $course->sks }} SKS · Pengampu: {{ $course->dosenPengampu?->nama ?? 'Belum ditetapkan' }}
                                </p>
                            </div>
                            <x-ui.status-badge :variant="$course->is_active ? 'success' : 'neutral'" :label="$course->is_active ? 'Aktif' : 'Nonaktif'" />
                        </div>

                        <div class="mt-3 space-y-1.5 border-t border-slate-100 pt-3">
                            @foreach ($course->rubrikKomponens as $komp)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-600">{{ $komp->urutan }}. {{ $komp->nama_komponen }}</span>
                                    <span class="font-bold text-[#0101B7]">{{ $komp->bobot_persen }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Belum ada mata kuliah konversi.</p>
                @endforelse
            </div>
        </section>

        {{-- Right: Create New Course + 4 Rubric Components --}}
        <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100 lg:col-span-5">
            <h2 class="font-heading text-base font-bold text-slate-900">Tambah Mata Kuliah Baru</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pastikan jumlah bobot seluruh komponen rubrik tepat <strong>100%</strong>.</p>

            <form method="POST" action="{{ route('admin.mata-kuliah.store') }}" class="mt-5 space-y-4">
                @csrf
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Kode MK</label>
                        <input type="text" name="kode_mk" required placeholder="IF405" class="w-full rounded-2xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Nama Mata Kuliah</label>
                        <input type="text" name="nama_mk" required placeholder="Cloud Computing" class="w-full rounded-2xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">SKS</label>
                        <input type="number" name="sks" min="1" max="20" value="3" required class="w-full rounded-2xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Dosen Pengampu</label>
                        <select name="dosen_pengampu_id" class="w-full rounded-2xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                            <option value="">Pilih dosen...</option>
                            @foreach ($dosens as $dosen)
                                <option value="{{ $dosen->id }}">{{ $dosen->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="rounded-2xl bg-[#F2F7FF] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-xs font-bold text-slate-800">Komponen Rubrik Capaian</p>
                        <span id="rubrik-total-badge" class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800">Total: 100%</span>
                    </div>
                    <div class="space-y-2.5">
                        @foreach ([['Pemahaman Konsep', 25], ['Implementasi Praktik', 35], ['Analisis & Pemecahan Masalah', 25], ['Dokumentasi & Presentasi', 15]] as $i => [$defaultName, $defaultWeight])
                            <div class="grid grid-cols-12 gap-2">
                                <input type="text" name="rubrik_komponens[{{ $i }}][nama_komponen]" value="{{ $defaultName }}" required class="col-span-9 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0101B7] focus:outline-none">
                                <input type="number" name="rubrik_komponens[{{ $i }}][bobot_persen]" value="{{ $defaultWeight }}" min="1" max="100" required oninput="recomputeRubrikWeight()" class="rubrik-weight col-span-3 rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-center text-xs font-bold focus:border-[#0101B7] focus:outline-none">
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white hover:bg-blue-900">
                    Simpan Mata Kuliah &amp; Rubrik
                </button>
            </form>
        </section>
    </div>

    @push('scripts')
        <script>
            function recomputeRubrikWeight() {
                let sum = 0;
                document.querySelectorAll('.rubrik-weight').forEach((el) => {
                    sum += parseInt(el.value || '0', 10);
                });
                const badge = document.getElementById('rubrik-total-badge');
                if (!badge) { return; }
                badge.textContent = `Total: ${sum}%`;
                badge.className = sum === 100
                    ? 'rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800'
                    : 'rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-700';
            }
        </script>
    @endpush
</x-layouts.portal>
