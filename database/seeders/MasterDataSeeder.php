<?php

namespace Database\Seeders;

use App\Enums\StatusLowongan;
use App\Enums\SumberLowongan;
use App\Models\Dosen;
use App\Models\LowonganMagang;
use App\Models\MataKuliah;
use App\Models\RubrikKomponen;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Seed reference lecturers, conversion courses with weighted rubrics, and sample internship vacancies.
     */
    public function run(): void
    {
        $dosens = [
            'raditya' => Dosen::query()->updateOrCreate(
                ['nip' => '198211172006042001'],
                [
                    'nama' => 'Raditya Muhammad, S.T., M.T.',
                    'email' => 'raditya@upi.edu',
                    'bidang_kepakaran' => 'Cyber Security & Network Engineering',
                ],
            ),
            'asyifa' => Dosen::query()->updateOrCreate(
                ['nip' => '199104122019032001'],
                [
                    'nama' => 'Asyifa Imanda Septiana, S.Pd., M.Eng.',
                    'email' => 'asyifa@upi.edu',
                    'bidang_kepakaran' => 'Interaksi Manusia Komputer & UI/UX',
                ],
            ),
            'hendriyana' => Dosen::query()->updateOrCreate(
                ['nip' => '198807152018031001'],
                [
                    'nama' => 'Hendriyana, S.T., M.Kom.',
                    'email' => 'hendriyana@upi.edu',
                    'bidang_kepakaran' => 'Manajemen Proyek Perangkat Lunak',
                ],
            ),
            'sari' => Dosen::query()->updateOrCreate(
                ['nip' => '198503212012122001'],
                [
                    'nama' => 'Dr. Sari Wulandari',
                    'email' => 'sari.wulandari@upi.edu',
                    'bidang_kepakaran' => 'Pengembangan Aplikasi Web & Arsitektur Sistem',
                ],
            ),
            'rina' => Dosen::query()->updateOrCreate(
                ['nip' => '199006082019032002'],
                [
                    'nama' => 'Rina Amelia, M.T.',
                    'email' => 'rina.amelia@upi.edu',
                    'bidang_kepakaran' => 'Software Quality Assurance & Testing',
                ],
            ),
            'iqbal' => Dosen::query()->updateOrCreate(
                ['nip' => '196008011986032001'],
                [
                    'nama' => 'Mochamad Iqbal Ardimansyah, S.T., M.Kom.',
                    'email' => 'iqbal.ardimansyah@upi.edu',
                    'bidang_kepakaran' => 'Rekayasa Perangkat Lunak & Tata Kelola TI',
                ],
            ),
        ];

        $courses = [
            [
                'kode_mk' => 'IF352',
                'nama_mk' => 'Cyber Security',
                'sks' => 3,
                'dosen_pengampu_id' => $dosens['raditya']->id,
                'deskripsi' => 'Konversi kegiatan MBKM pada bidang keamanan siber, hardening sistem, dan respons insiden.',
                'rubrik' => [
                    ['urutan' => 1, 'nama_komponen' => 'Pemahaman konsep keamanan siber', 'deskripsi' => 'Ancaman, kerentanan, kontrol, dan manajemen risiko', 'bobot_persen' => 25],
                    ['urutan' => 2, 'nama_komponen' => 'Implementasi kontrol keamanan', 'deskripsi' => 'Penerapan hardening, monitoring, dan respons insiden', 'bobot_persen' => 35],
                    ['urutan' => 3, 'nama_komponen' => 'Analisis kasus dan pemecahan masalah', 'deskripsi' => 'Ketepatan analisis serta rekomendasi mitigasi', 'bobot_persen' => 25],
                    ['urutan' => 4, 'nama_komponen' => 'Dokumentasi dan presentasi', 'deskripsi' => 'Kualitas laporan teknis dan komunikasi hasil', 'bobot_persen' => 15],
                ],
            ],
            [
                'kode_mk' => 'IF231',
                'nama_mk' => 'Interaksi Manusia Komputer',
                'sks' => 3,
                'dosen_pengampu_id' => $dosens['asyifa']->id,
                'deskripsi' => 'Konversi kegiatan MBKM pada bidang riset pengguna, prototyping, dan usability testing.',
                'rubrik' => [
                    ['urutan' => 1, 'nama_komponen' => 'Riset dan pemahaman kebutuhan pengguna', 'deskripsi' => 'User interview, persona, dan user journey mapping', 'bobot_persen' => 25],
                    ['urutan' => 2, 'nama_komponen' => 'Perancangan desain antarmuka', 'deskripsi' => 'Wireframe, design system, dan high-fidelity prototype', 'bobot_persen' => 35],
                    ['urutan' => 3, 'nama_komponen' => 'Pengujian kebergunaan (usability testing)', 'deskripsi' => 'Evaluasi iteratif dan analisis temuan pengujian', 'bobot_persen' => 25],
                    ['urutan' => 4, 'nama_komponen' => 'Dokumentasi dan presentasi desain', 'deskripsi' => 'Kejelasan handoff desain dan penyampaian solusi', 'bobot_persen' => 15],
                ],
            ],
            [
                'kode_mk' => 'IF242',
                'nama_mk' => 'Manajemen Proyek Perangkat Lunak',
                'sks' => 3,
                'dosen_pengampu_id' => $dosens['hendriyana']->id,
                'deskripsi' => 'Konversi kegiatan MBKM pada pengelolaan sprint, kolaborasi tim, dan manajemen risiko proyek.',
                'rubrik' => [
                    ['urutan' => 1, 'nama_komponen' => 'Perencanaan ruang lingkup dan jadwal', 'deskripsi' => 'WBS, backlog grooming, dan estimasi sprint', 'bobot_persen' => 25],
                    ['urutan' => 2, 'nama_komponen' => 'Eksekusi dan kolaborasi tim', 'deskripsi' => 'Kontribusi nyata dalam siklus agile/scrum tim mitra', 'bobot_persen' => 35],
                    ['urutan' => 3, 'nama_komponen' => 'Pengendalian mutu dan risiko', 'deskripsi' => 'Pelacakan isu, mitigasi hambatan, dan evaluasi deliverables', 'bobot_persen' => 25],
                    ['urutan' => 4, 'nama_komponen' => 'Pelaporan proyek', 'deskripsi' => 'Kelengkapan dokumentasi retrospektif dan laporan akhir', 'bobot_persen' => 15],
                ],
            ],
            [
                'kode_mk' => 'IF251',
                'nama_mk' => 'Pengembangan Aplikasi Web',
                'sks' => 3,
                'dosen_pengampu_id' => $dosens['sari']->id,
                'deskripsi' => 'Konversi kegiatan MBKM pada pengembangan aplikasi web frontend, backend, dan API.',
                'rubrik' => [
                    ['urutan' => 1, 'nama_komponen' => 'Arsitektur dan desain basis data', 'deskripsi' => 'Struktur modul, relasi data, dan kontrak API', 'bobot_persen' => 25],
                    ['urutan' => 2, 'nama_komponen' => 'Implementasi fitur dan kualitas kode', 'deskripsi' => 'Clean code, keamanan dasar, dan kesesuaian spesifikasi', 'bobot_persen' => 35],
                    ['urutan' => 3, 'nama_komponen' => 'Pengujian dan deployment', 'deskripsi' => 'Automated testing, debugging, dan kesiapan rilis', 'bobot_persen' => 25],
                    ['urutan' => 4, 'nama_komponen' => 'Dokumentasi teknis', 'deskripsi' => 'Dokumentasi kode, README, dan laporan implementasi', 'bobot_persen' => 15],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $rubrikItems = $courseData['rubrik'];
            unset($courseData['rubrik']);

            $mataKuliah = MataKuliah::query()->updateOrCreate(
                ['kode_mk' => $courseData['kode_mk']],
                array_merge($courseData, ['is_active' => true]),
            );

            foreach ($rubrikItems as $item) {
                RubrikKomponen::query()->updateOrCreate(
                    [
                        'mata_kuliah_id' => $mataKuliah->id,
                        'urutan' => $item['urutan'],
                    ],
                    [
                        'nama_komponen' => $item['nama_komponen'],
                        'deskripsi' => $item['deskripsi'],
                        'bobot_persen' => $item['bobot_persen'],
                    ],
                );
            }
        }

        $vacancies = [
            [
                'judul_lowongan' => 'Cloud Engineer Intern',
                'nama_instansi' => 'Gojek',
                'posisi_bidang' => 'Infrastructure',
                'lokasi_mode' => 'Jakarta · Hybrid',
                'sumber_lowongan' => SumberLowongan::MitraKampus,
                'kontak' => 'campus-hiring@gojek.com',
                'deskripsi' => 'Membantu pengelolaan infrastruktur cloud, CI/CD pipeline, dan observability layanan backend.',
                'persyaratan' => 'Memahami Linux, Docker, dasar Kubernetes, dan Git workflow.',
                'jumlah_pelamar' => 12,
                'dosen_penanggung_jawab_id' => $dosens['raditya']->id,
            ],
            [
                'judul_lowongan' => 'Research Assistant',
                'nama_instansi' => 'Lab RPL Informatika',
                'posisi_bidang' => 'AI Research',
                'lokasi_mode' => 'Kampus · Fleksibel',
                'sumber_lowongan' => SumberLowongan::TawaranDosen,
                'kontak' => 'lab.rpl@upi.edu',
                'deskripsi' => 'Asisten riset pengembangan model machine learning dan pengolahan dataset akademik Prodi RPL.',
                'persyaratan' => 'Menguasai Python, dasar statistik, dan terbiasa menulis dokumentasi eksperimen.',
                'jumlah_pelamar' => 9,
                'dosen_penanggung_jawab_id' => $dosens['iqbal']->id,
            ],
            [
                'judul_lowongan' => 'UI/UX Designer Intern',
                'nama_instansi' => 'Telkom Indonesia',
                'posisi_bidang' => 'Product Design',
                'lokasi_mode' => 'Bandung · Hybrid',
                'sumber_lowongan' => SumberLowongan::MitraKampus,
                'kontak' => 'talent@telkom.co.id',
                'deskripsi' => 'Merancang wireframe, design system, dan melakukan usability testing untuk produk digital B2B.',
                'persyaratan' => 'Menguasai Figma, memiliki portofolio UI/UX, dan mampu berkolaborasi lintas disiplin.',
                'jumlah_pelamar' => 24,
                'dosen_penanggung_jawab_id' => $dosens['asyifa']->id,
            ],
            [
                'judul_lowongan' => 'Quality Assurance',
                'nama_instansi' => 'Shopee Indonesia',
                'posisi_bidang' => 'Software Quality',
                'lokasi_mode' => 'Jakarta · Remote',
                'sumber_lowongan' => SumberLowongan::MitraKampus,
                'kontak' => 'careers@shopee.co.id',
                'deskripsi' => 'Menyusun test case, menjalankan regression testing, dan otomasi pengujian API/UI.',
                'persyaratan' => 'Memahami SDLC, Black-Box Testing, Postman, dan dasar otomasi pengujian.',
                'jumlah_pelamar' => 14,
                'dosen_penanggung_jawab_id' => $dosens['rina']->id,
            ],
            [
                'judul_lowongan' => 'Frontend Developer',
                'nama_instansi' => 'Dicoding Indonesia',
                'posisi_bidang' => 'Web Engineering',
                'lokasi_mode' => 'Remote',
                'sumber_lowongan' => SumberLowongan::TawaranDosen,
                'kontak' => 'internship@dicoding.com',
                'deskripsi' => 'Mengembangkan komponen antarmuka responsif dan aksesibel untuk platform pembelajaran developer.',
                'persyaratan' => 'Menguasai HTML, Tailwind CSS, JavaScript modern, dan integrasi REST API.',
                'jumlah_pelamar' => 31,
                'dosen_penanggung_jawab_id' => $dosens['sari']->id,
            ],
        ];

        foreach ($vacancies as $vacancy) {
            LowonganMagang::query()->updateOrCreate(
                [
                    'judul_lowongan' => $vacancy['judul_lowongan'],
                    'nama_instansi' => $vacancy['nama_instansi'],
                ],
                array_merge($vacancy, [
                    'tanggal_posting' => now()->toDateString(),
                    'batas_waktu' => now()->addMonths(2)->toDateString(),
                    'status' => StatusLowongan::Aktif,
                ]),
            );
        }
    }
}
