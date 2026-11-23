@php
    use App\Enums\StatusLowongan;
    use App\Enums\SumberLowongan;
    use App\Enums\UserRole;

    $prefix = match (auth()->user()->role) {
        UserRole::Kaprodi => 'kaprodi',
        UserRole::Admin => 'admin',
        default => 'staff',
    };
@endphp

<x-layouts.portal title="Kelola Lowongan">
    {{-- Header + CTA --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900">Kelola Lowongan</h1>
            <p class="mt-1 text-sm text-slate-500">Publikasikan dan perbarui informasi lowongan magang untuk mahasiswa.</p>
        </div>

        <button
            type="button"
            onclick="openLowonganModal()"
            class="inline-flex items-center gap-2 rounded-full bg-[#0101B7] px-5 py-3 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(1,1,183,0.24)] transition hover:bg-blue-900"
        >
            <span>+ Tambah lowongan</span>
        </button>
    </div>

    {{-- 4 KPI Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card :value="$metrics['aktif']" label="Lowongan aktif">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['draft']" label="Draf lowongan">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['total_pelamar']" label="Total pelamar">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$metrics['mitra_terhubung']" label="Mitra terhubung">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>
    </div>

    {{-- Daftar Lowongan Table --}}
    <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-heading text-base font-bold text-slate-900">Daftar Lowongan</h2>
                <p class="text-xs text-slate-500">Kelola lowongan dari mitra kampus dan tawaran dosen.</p>
            </div>

            <form method="GET" action="{{ route($prefix.'.lowongan.index') }}" class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari lowongan..." class="w-44 border-0 bg-transparent text-xs text-slate-800 placeholder:text-slate-400 focus:ring-0 focus:outline-none">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-[#F2F7FF] text-[11px] font-bold text-slate-500 uppercase">
                        <th class="rounded-l-xl px-4 py-3">Lowongan</th>
                        <th class="px-4 py-3">Sumber</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Pelamar</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($lowongans as $item)
                        @php
                            $isDosen = $item->sumber_lowongan === SumberLowongan::TawaranDosen;
                            $statusVar = match ($item->status) {
                                StatusLowongan::Aktif => 'success',
                                StatusLowongan::Draft => 'warning',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F2F7FF] font-heading text-xs font-bold text-[#0101B7]">
                                        {{ mb_strtoupper(mb_substr($item->nama_instansi, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-heading text-xs font-bold text-slate-900">{{ $item->judul_lowongan }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $item->nama_instansi }} · {{ $item->lokasi_mode }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <x-ui.status-badge :variant="$isDosen ? 'gold' : 'info'" :label="$item->sumber_lowongan->cardBadgeLabel()" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-xs text-slate-500">
                                {{ $item->tanggal_posting?->translatedFormat('d M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-xs text-slate-500">
                                {{ $item->jumlah_pelamar }} pelamar
                            </td>
                            <td class="px-4 py-4">
                                <x-ui.status-badge :variant="$statusVar" :label="$item->status->label()" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <form method="POST" action="{{ route($prefix.'.lowongan.update', $item) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="{{ $item->status === StatusLowongan::Aktif ? StatusLowongan::Arsip->value : StatusLowongan::Aktif->value }}">
                                        <button type="submit" title="Toggle Aktif/Arsip" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-[#0101B7] transition hover:bg-[#0101B7] hover:text-white">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route($prefix.'.lowongan.destroy', $item) }}" onsubmit="return confirm('Hapus lowongan ini secara permanen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 text-rose-600 transition hover:bg-rose-600 hover:text-white">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-xs text-slate-400">Belum ada lowongan magang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Blue Header Overlay Modal: Tambah Lowongan Baru --}}
    <div id="lowongan-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/55 p-4 backdrop-blur-xs">
        <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between bg-gradient-to-r from-[#0101B7] to-[#2E2EE8] px-7 py-5 text-white">
                <div>
                    <h3 class="font-heading text-lg font-bold">Tambah Lowongan Baru</h3>
                    <p class="mt-0.5 text-xs text-blue-100">Lengkapi informasi yang akan ditampilkan di portal mahasiswa.</p>
                </div>
                <button type="button" onclick="closeLowonganModal()" class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route($prefix.'.lowongan.store') }}" class="max-h-[78vh] space-y-4 overflow-y-auto p-7">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Judul lowongan</label>
                        <input type="text" name="judul_lowongan" required placeholder="Contoh: UI/UX Designer Intern" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Nama instansi</label>
                        <input type="text" name="nama_instansi" required placeholder="Nama perusahaan atau unit" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Posisi / bidang</label>
                        <input type="text" name="posisi_bidang" required placeholder="Product Design" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Lokasi &amp; mode kerja</label>
                        <input type="text" name="lokasi_mode" required placeholder="Bandung · Hybrid" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Sumber lowongan</label>
                        <select name="sumber_lowongan" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                            @foreach (SumberLowongan::cases() as $sumberOpt)
                                <option value="{{ $sumberOpt->value }}">{{ $sumberOpt->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Kontak</label>
                        <input type="text" name="kontak" required placeholder="talent@perusahaan.id" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Deskripsi</label>
                        <textarea name="deskripsi" rows="3" required placeholder="Jelaskan lingkup pekerjaan..." class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Persyaratan</label>
                        <textarea name="persyaratan" rows="3" placeholder="Tuliskan kemampuan yang dibutuhkan..." class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none"></textarea>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Dosen penanggung jawab</label>
                        <select name="dosen_penanggung_jawab_id" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                            <option value="">Pilih dosen...</option>
                            @foreach ($dosens as $dosen)
                                <option value="{{ $dosen->id }}">{{ $dosen->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Status publikasi</label>
                        <select name="status" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#0101B7] focus:outline-none">
                            <option value="aktif">Publikasikan langsung (Aktif)</option>
                            <option value="draft">Simpan sebagai Draf</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" onclick="closeLowonganModal()" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="rounded-full bg-[#0101B7] px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-900">
                        Publikasikan lowongan
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openLowonganModal() {
                const el = document.getElementById('lowongan-modal');
                el?.classList.remove('hidden');
                el?.classList.add('flex');
            }
            function closeLowonganModal() {
                const el = document.getElementById('lowongan-modal');
                el?.classList.add('hidden');
                el?.classList.remove('flex');
            }
        </script>
    @endpush
</x-layouts.portal>
