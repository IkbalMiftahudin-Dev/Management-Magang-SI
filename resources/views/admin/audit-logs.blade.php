<x-layouts.portal title="Audit Trail Sistem — Admin">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900">Audit Trail Sistem</h1>
            <p class="mt-1 text-sm text-slate-500">Catatan aktivitas autentikasi SSO, akses RBAC, verifikasi pendataan, pengesahan SK, dan publikasi nilai.</p>
        </div>

        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="text" name="action" value="{{ request('action') }}" placeholder="Filter aksi (mis. sk.approved)" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs focus:border-[#0101B7] focus:outline-none">
            <button type="submit" class="rounded-full bg-[#0101B7] px-4 py-2 text-xs font-semibold text-white hover:bg-blue-900">Filter</button>
        </form>
    </div>

    <section class="rounded-3xl bg-white p-6 shadow-[0_6px_24px_rgba(1,1,183,0.05)] ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F2F7FF] text-[11px] font-bold text-slate-500 uppercase">
                        <th class="rounded-l-xl px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Aksi</th>
                        <th class="rounded-r-xl px-4 py-3">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3.5 text-slate-500">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3.5 font-semibold text-slate-900">{{ $log->user?->name ?? 'Sistem / Guest' }}</td>
                            <td class="px-4 py-3.5">
                                <x-ui.status-badge variant="info" :label="$log->role ?? '-'" />
                            </td>
                            <td class="px-4 py-3.5 font-mono text-[#0101B7]">{{ $log->action }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 font-mono text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada log aktivitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.portal>
