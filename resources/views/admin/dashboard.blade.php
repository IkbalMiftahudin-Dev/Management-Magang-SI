<x-layouts.portal title="Dashboard Tata Kelola Sistem — Admin">
    <div class="mb-6">
        <h1 class="font-heading text-2xl font-bold text-slate-900">Dashboard Tata Kelola Sistem</h1>
        <p class="mt-1 text-sm text-slate-500">Ringkasan master data akademik, lowongan magang, dan pemantauan rekap operasional secara read-only.</p>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card :value="$summary['total_mata_kuliah']" label="Mata Kuliah Konversi">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$summary['total_dosen']" label="Referensi Dosen">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$summary['total_mahasiswa']" label="Profil Mahasiswa">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card :value="$summary['lowongan']['aktif']" label="Lowongan Aktif">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
            <h2 class="font-heading text-base font-bold text-slate-900">Ringkasan Operasional Magang (Read-Only)</h2>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-2xl bg-[#F2F7FF] p-4">
                    <dt class="text-slate-500">Menunggu Verifikasi</dt>
                    <dd class="mt-1 font-heading text-2xl font-bold text-[#0101B7]">{{ $summary['verifikasi_readonly']['menunggu_verifikasi'] }}</dd>
                </div>
                <div class="rounded-2xl bg-[#F2F7FF] p-4">
                    <dt class="text-slate-500">Pendataan Aktif</dt>
                    <dd class="mt-1 font-heading text-2xl font-bold text-slate-900">{{ $summary['verifikasi_readonly']['pendataan_aktif'] }}</dd>
                </div>
                <div class="rounded-2xl bg-[#F2F7FF] p-4">
                    <dt class="text-slate-500">SK Belum Disahkan</dt>
                    <dd class="mt-1 font-heading text-2xl font-bold text-amber-700">{{ $summary['verifikasi_readonly']['sk_belum_disahkan'] }}</dd>
                </div>
                <div class="rounded-2xl bg-[#F2F7FF] p-4">
                    <dt class="text-slate-500">Kelengkapan Berkas</dt>
                    <dd class="mt-1 font-heading text-2xl font-bold text-emerald-700">{{ $summary['verifikasi_readonly']['kelengkapan_berkas_persen'] }}%</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
            <h2 class="font-heading text-base font-bold text-slate-900">Akses Cepat Administrasi</h2>
            <p class="mt-1 text-xs text-slate-500">Pengelolaan master data dan pemantauan keamanan sistem.</p>
            <div class="mt-5 grid gap-3">
                <a href="{{ route('admin.mata-kuliah.index') }}" class="flex items-center justify-between rounded-2xl bg-[#F2F7FF] px-5 py-4 text-sm font-semibold text-[#0101B7] transition hover:bg-[#0101B7] hover:text-white">
                    <span>Kelola Mata Kuliah Konversi &amp; Rubrik 100%</span>
                    <span>&rsaquo;</span>
                </a>
                <a href="{{ route('admin.lowongan.index') }}" class="flex items-center justify-between rounded-2xl bg-[#F2F7FF] px-5 py-4 text-sm font-semibold text-[#0101B7] transition hover:bg-[#0101B7] hover:text-white">
                    <span>Kelola Portal Lowongan Magang</span>
                    <span>&rsaquo;</span>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center justify-between rounded-2xl bg-[#F2F7FF] px-5 py-4 text-sm font-semibold text-[#0101B7] transition hover:bg-[#0101B7] hover:text-white">
                    <span>Lihat Audit Trail &amp; Log Keamanan SSO</span>
                    <span>&rsaquo;</span>
                </a>
            </div>
        </section>
    </div>
</x-layouts.portal>
