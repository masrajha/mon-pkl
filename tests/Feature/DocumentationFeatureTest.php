<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentationFeatureTest extends TestCase
{
    public function test_public_documentation_role_menu_is_available(): void
    {
        $this->get('/docs/')
            ->assertOk()
            ->assertSee('Dokumentasi Publik')
            ->assertSee('Mahasiswa')
            ->assertSee('Dosen Pembimbing')
            ->assertSee('Koordinator')
            ->assertSee('Admin');
    }

    public function test_public_documentation_role_page_has_outline_slides(): void
    {
        $this->get(route('docs.show', 'mahasiswa'))
            ->assertOk()
            ->assertSee('Manual Mahasiswa')
            ->assertSee('Outline Mahasiswa')
            ->assertSee('Ringkasan Hak Akses Mahasiswa')
            ->assertSee('data-slide-target', false);
    }

    public function test_unknown_documentation_role_returns_not_found(): void
    {
        $this->get('/docs/unknown-role')->assertNotFound();
    }
}
