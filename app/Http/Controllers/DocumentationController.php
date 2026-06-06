<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentationController extends Controller
{
    public function index(): View
    {
        return view('docs.index', [
            'roles' => $this->roles(),
            'metadata' => $this->metadata(),
        ]);
    }

    public function show(string $role): View
    {
        $roles = $this->roles();
        $roleConfig = $roles[$role] ?? null;

        if (! $roleConfig) {
            throw new NotFoundHttpException();
        }

        $section = $this->roleSection($roleConfig['heading']);
        $slides = $this->slides($section);

        return view('docs.show', [
            'roles' => $roles,
            'roleKey' => $role,
            'role' => $roleConfig,
            'slides' => $slides,
        ]);
    }

    private function roles(): array
    {
        return [
            'mahasiswa' => [
                'heading' => 'Role Mahasiswa',
                'label' => 'Mahasiswa',
                'description' => 'Pendaftaran program, presensi, pembekalan, laporan, catatan harian, dan pengajuan perubahan.',
                'icon' => 'M',
            ],
            'dosen-pembimbing' => [
                'heading' => 'Role Dosen Pembimbing',
                'label' => 'Dosen Pembimbing',
                'description' => 'Monitoring mahasiswa bimbingan, review laporan, catatan revisi, dan rekap bimbingan.',
                'icon' => 'D',
            ],
            'koordinator' => [
                'heading' => 'Role Koordinator',
                'label' => 'Koordinator',
                'description' => 'Validasi pendaftaran, pembekalan, perubahan pembimbing, review laporan, dan monitoring scope prodi/periode.',
                'icon' => 'K',
            ],
            'admin' => [
                'heading' => 'Role Admin',
                'label' => 'Admin',
                'description' => 'Master data, konfigurasi program, email/notifikasi, validasi, peserta periode, pembekalan, seminar, dan rekap lintas scope.',
                'icon' => 'A',
            ],
            'pembimbing-lapangan' => [
                'heading' => 'Role Pembimbing Lapangan',
                'label' => 'Pembimbing Lapangan',
                'description' => 'Portal terbatas untuk melihat mahasiswa terkait melalui token URL atau login email pembimbing lapangan.',
                'icon' => 'P',
            ],
        ];
    }

    private function metadata(): array
    {
        $manual = File::get(base_path('manuals.md'));
        $version = $this->matchFirst('/^\*\*Versi dokumen:\*\*\s*(.+?)\s*$/m', $manual);
        $updatedAt = $this->matchFirst('/^\*\*Tanggal pembaruan:\*\*\s*(.+?)\s*$/m', $manual);
        $status = $this->matchFirst('/^\*\*Status:\*\*\s*(.+?)\s*$/m', $manual);
        $whatsNew = [];

        if (preg_match('/^> \*\*What\'s New[^\n]*\*\*\R(?P<body>(?:^>.*\R?)*)/m', $manual, $matches)) {
            $lines = preg_split('/\R/', trim($matches['body']));

            foreach ($lines as $line) {
                $item = trim(preg_replace('/^>\s?-\s?/', '', $line));

                if ($item !== '' && $item !== '>') {
                    $whatsNew[] = $item;
                }
            }
        }

        return [
            'version' => $version,
            'updated_at' => $updatedAt,
            'status' => $status,
            'whats_new' => $whatsNew,
        ];
    }

    private function matchFirst(string $pattern, string $subject): ?string
    {
        if (! preg_match($pattern, $subject, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function roleSection(string $heading): string
    {
        $manual = File::get(base_path('manuals.md'));
        $pattern = '/^## Bagian \d+\. '.preg_quote($heading, '/').'\R(?P<body>.*?)(?=^## Bagian \d+\. Role |\z)/ms';

        if (! preg_match($pattern, $manual, $matches)) {
            throw new NotFoundHttpException();
        }

        return trim("## {$heading}\n".$matches['body']);
    }

    private function slides(string $markdown): array
    {
        preg_match_all('/^### (?P<title>.+)$/m', $markdown, $matches, PREG_OFFSET_CAPTURE);

        if ($matches[0] === []) {
            return [[
                'id' => 'manual',
                'title' => 'Manual',
                'html' => Str::markdown($markdown),
            ]];
        }

        $slides = [];
        $count = count($matches[0]);

        for ($index = 0; $index < $count; $index++) {
            $start = $matches[0][$index][1];
            $end = $matches[0][$index + 1][1] ?? strlen($markdown);
            $title = trim($matches['title'][$index][0]);

            $slides[] = [
                'id' => Str::slug($title),
                'title' => $title,
                'html' => Str::markdown(trim(substr($markdown, $start, $end - $start))),
            ];
        }

        return $slides;
    }
}
