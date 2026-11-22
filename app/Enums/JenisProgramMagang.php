<?php

namespace App\Enums;

enum JenisProgramMagang: string
{
    case Msib = 'msib';
    case StudiIndependen = 'si';
    case PertukaranMahasiswa = 'pmm';
    case KampusMengajar = 'km';
    case Isma = 'isma';
    case WirausahaMerdeka = 'wmk';
    case PraktisiMengajar = 'pm';
    case MagangMandiri = 'mm';

    public function code(): string
    {
        return match ($this) {
            self::Msib => '01',
            self::StudiIndependen => '02',
            self::PertukaranMahasiswa => '03',
            self::KampusMengajar => '04',
            self::Isma => '05',
            self::WirausahaMerdeka => '06',
            self::PraktisiMengajar => '07',
            self::MagangMandiri => '08',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Msib => 'Magang Bersertifikat (MSIB)',
            self::StudiIndependen => 'Studi Independen (SI)',
            self::PertukaranMahasiswa => 'Pertukaran Mahasiswa Merdeka (PMM)',
            self::KampusMengajar => 'Kampus Mengajar (KM)',
            self::Isma => 'Indonesian International Student Mobility Awards (ISMA)',
            self::WirausahaMerdeka => 'Wirausaha Merdeka (WMK)',
            self::PraktisiMengajar => 'Praktisi Mengajar (PM)',
            self::MagangMandiri => 'Magang Mandiri (MM)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Msib => 'MSIB',
            self::StudiIndependen => 'Studi Independen',
            self::PertukaranMahasiswa => 'PMM',
            self::KampusMengajar => 'Kampus Mengajar',
            self::Isma => 'ISMA',
            self::WirausahaMerdeka => 'WMK',
            self::PraktisiMengajar => 'Praktisi Mengajar',
            self::MagangMandiri => 'Magang Mandiri',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Msib => 'Program magang yang diawasi langsung oleh Kemdikbud Ristek selama satu semester untuk mendapatkan pengalaman kerja dan praktik terbaik industri.',
            self::StudiIndependen => 'Kesempatan mempelajari kompetensi spesifik dan praktis langsung dari para pakar selama satu semester melalui pembelajaran dan praktik langsung.',
            self::PertukaranMahasiswa => 'Program yang memberikan pengalaman mengikuti kegiatan perkuliahan di luar kampus, baik dalam lingkup nasional maupun internasional.',
            self::KampusMengajar => 'Kesempatan belajar di luar kampus selama satu semester dengan menjadi mitra guru dalam pengembangan strategi pembelajaran kreatif.',
            self::Isma => 'Program beasiswa mobilitas internasional mahasiswa Indonesia ke perguruan tinggi terkemuka dan industri di luar negeri.',
            self::WirausahaMerdeka => 'Program untuk mendukung terciptanya wirausaha mahasiswa sekaligus pembelajaran dan pengembangan diri di luar kelas.',
            self::PraktisiMengajar => 'Kolaborasi aktif praktisi ahli dan dosen dalam pertukaran ilmu dan keahlian dunia kerja melalui mata kuliah luring maupun daring.',
            self::MagangMandiri => 'Kegiatan magang yang memberi kebebasan mencari lowongan sesuai minat mahasiswa mengikuti ketentuan perusahaan dan perguruan tinggi.',
        };
    }
}
