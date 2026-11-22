<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $jenisSk->label() }} - {{ $nomorSk }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.5; margin: 28px; }
        .header { text-align: center; border-bottom: 2px solid #0101B7; padding-bottom: 12px; margin-bottom: 18px; }
        .header h1 { font-size: 14px; margin: 0; color: #0101B7; text-transform: uppercase; }
        .header h2 { font-size: 12px; margin: 2px 0; text-transform: uppercase; }
        .header p { font-size: 10px; margin: 2px 0; color: #4B5563; }
        .title-block { text-align: center; margin-bottom: 18px; }
        .title-block h3 { font-size: 13px; margin: 0; text-transform: uppercase; text-decoration: underline; }
        .title-block p { margin: 4px 0 0; font-size: 11px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: bold; margin-top: 6px; }
        .badge-draft { background: #FEF3C7; color: #92400E; }
        .badge-approved { background: #D1FAE5; color: #065F46; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.meta td { padding: 5px 6px; vertical-align: top; }
        table.meta td.label { width: 32%; font-weight: bold; color: #374151; }
        table.courses { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        table.courses th, table.courses td { border: 1px solid #D1D5DB; padding: 6px 8px; text-align: left; }
        table.courses th { background: #F2F7FF; color: #0101B7; font-size: 10px; }
        .signature { margin-top: 32px; width: 45%; margin-left: auto; text-align: left; }
        .signature .stamp { margin: 14px 0; padding: 8px; border: 1px dashed #0101B7; color: #0101B7; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Universitas Pendidikan Indonesia</h1>
        <h2>Kampus Daerah Cibiru — Program Studi Rekayasa Perangkat Lunak</h2>
        <p>Jl. Pendidikan No. 15, Cibiru Wetan, Kec. Cileunyi, Kabupaten Bandung, Jawa Barat 40625</p>
    </div>

    <div class="title-block">
        <h3>Surat Keputusan {{ $jenisSk->label() }}</h3>
        <p>Nomor: <strong>{{ $nomorSk }}</strong></p>
        <span class="badge {{ $statusPengesahan->value === 'disahkan' ? 'badge-approved' : 'badge-draft' }}">
            STATUS: {{ strtoupper($statusPengesahan->label()) }}
        </span>
    </div>

    <p>
        Berdasarkan hasil verifikasi data pelaksanaan Program Penguatan Pengalaman Profesional Non Kependidikan (Magang/Studi Independen),
        Ketua Program Studi Rekayasa Perangkat Lunak UPI Kampus Cibiru menetapkan penugasan berikut:
    </p>

    <table class="meta">
        <tr>
            <td class="label">Nama Mahasiswa</td>
            <td>: {{ $pendataan->mahasiswa?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIM / Program Studi</td>
            <td>: {{ $pendataan->mahasiswa?->nim ?? '-' }} / {{ $pendataan->mahasiswa?->prodi ?? 'Rekayasa Perangkat Lunak' }}</td>
        </tr>
        <tr>
            <td class="label">Skema Program</td>
            <td>: {{ $pendataan->jenis_program?->label() ?? '-' }} ({{ $pendataan->mode_kegiatan?->label() ?? '-' }})</td>
        </tr>
        <tr>
            <td class="label">Instansi Mitra / Posisi</td>
            <td>: {{ $pendataan->nama_instansi }} — {{ $pendataan->posisi }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Instansi</td>
            <td>: {{ $pendataan->alamat_instansi }}</td>
        </tr>
        <tr>
            <td class="label">Periode Pelaksanaan</td>
            <td>: {{ $pendataan->tanggal_mulai?->format('d M Y') ?? '-' }} s.d. {{ $pendataan->tanggal_selesai?->format('d M Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Dosen Pembimbing UPI</td>
            <td>: {{ $pendataan->dosenPembimbing?->nama ?? '-' }} (NIP: {{ $pendataan->dosenPembimbing?->nip ?? '-' }})</td>
        </tr>
        <tr>
            <td class="label">Pembimbing Lapangan / Kunjung</td>
            <td>: {{ $pendataan->dosenKunjung?->nama ?? '-' }} (NIP: {{ $pendataan->dosenKunjung?->nip ?? '-' }})</td>
        </tr>
    </table>

    <p><strong>Daftar Pemetaan Konversi Mata Kuliah:</strong></p>
    <table class="courses">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode MK</th>
                <th>Mata Kuliah</th>
                <th>Dosen Pengampu</th>
                <th>SKS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pendataan->konversiMataKuliahs as $index => $konversi)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $konversi->mataKuliah?->kode_mk ?? '-' }}</td>
                    <td>{{ $konversi->mataKuliah?->nama_mk ?? '-' }}</td>
                    <td>{{ $konversi->mataKuliah?->dosenPengampu?->nama ?? '-' }}</td>
                    <td>{{ $konversi->sks_konversi }} SKS</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Belum ada mata kuliah konversi yang dipetakan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature">
        <p>Bandung, {{ \Carbon\Carbon::parse($tanggalTerbit)->format('d M Y') }}</p>
        <p>Ketua Program Studi Rekayasa Perangkat Lunak,</p>

        @if ($statusPengesahan->value === 'disahkan' && $approver !== null)
            <div class="stamp">
                Disahkan secara elektronik melalui SI RPL UPI (SSO SACIBIRU)<br>
                Waktu Pengesahan: {{ $approvedAt }}
            </div>
            <p><strong>{{ $approver->name }}</strong><br>NIP. {{ $approver->identifier_number ?? '-' }}</p>
        @else
            <div class="stamp">
                DRAF SK — MENUNGGU PENGESAHAN KAPRODI
            </div>
            <p><strong>Ketua Program Studi RPL</strong></p>
        @endif
    </div>
</body>
</html>
