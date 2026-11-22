<?php

namespace App\Http\Controllers;

use App\Models\DokumenSk;
use App\Models\Logbook;
use App\Models\PendataanMagang;
use App\Services\PrivateFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileController extends Controller
{
    /**
     * Allowed file slot names mapped to columns on berkas_akhirs (plus pendataan loa).
     *
     * @var array<string, string>
     */
    private const SLOT_COLUMNS = [
        'loa' => 'loa_path',
        'rubrik_konversi' => 'rubrik_konversi_path',
        'laporan_akhir' => 'laporan_akhir_path',
        'draf_pks' => 'draf_pks_path',
        'transkrip_instansi' => 'transkrip_instansi_path',
        'sertifikat' => 'sertifikat_path',
    ];

    public function __construct(
        private readonly PrivateFileService $fileService,
    ) {}

    /**
     * Stream a protected LOA or Berkas Akhir document after checking PendataanMagangPolicy::view.
     */
    public function pendataanSlot(Request $request, PendataanMagang $pendataan, string $slot): StreamedResponse
    {
        Gate::authorize('view', $pendataan);

        abort_unless(array_key_exists($slot, self::SLOT_COLUMNS), 404, 'Jenis berkas tidak dikenal.');

        $column = self::SLOT_COLUMNS[$slot];
        $berkas = $pendataan->berkasAkhir;

        $path = $berkas?->{$column};

        if ($slot === 'loa' && ($path === null || $path === '')) {
            $path = $pendataan->loa_path;
        }

        abort_if($path === null || $path === '', 404, 'Berkas belum diunggah.');

        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';
        $downloadName = sprintf('%s-%s.%s', $slot, $pendataan->id, $ext);

        return $this->fileService->streamResponse(
            path: $path,
            downloadName: $downloadName,
            forceDownload: $request->boolean('download'),
        );
    }

    /**
     * Stream a protected logbook photo evidence after checking LogbookPolicy::view.
     */
    public function logbook(Request $request, Logbook $logbook): StreamedResponse
    {
        Gate::authorize('view', $logbook);

        abort_if($logbook->foto_bukti_path === null || $logbook->foto_bukti_path === '', 404, 'Foto bukti tidak tersedia.');

        $ext = pathinfo($logbook->foto_bukti_path, PATHINFO_EXTENSION) ?: 'jpg';

        return $this->fileService->streamResponse(
            path: $logbook->foto_bukti_path,
            downloadName: sprintf('logbook-%d-minggu-%d.%s', $logbook->id, $logbook->minggu_ke, $ext),
            forceDownload: $request->boolean('download'),
        );
    }

    /**
     * Stream a protected SK PDF document after checking DokumenSkPolicy::download.
     */
    public function dokumenSk(Request $request, DokumenSk $dokumenSk): StreamedResponse
    {
        Gate::authorize('download', $dokumenSk);

        $safeNumber = preg_replace('/[^A-Za-z0-9\-_]/', '_', $dokumenSk->nomor_sk) ?: 'dokumen-sk';

        return $this->fileService->streamResponse(
            path: $dokumenSk->file_path,
            downloadName: $safeNumber.'.pdf',
            forceDownload: $request->boolean('download'),
        );
    }
}
