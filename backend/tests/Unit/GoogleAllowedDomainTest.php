<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\GoogleAuthController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class GoogleAllowedDomainTest extends TestCase
{
    #[DataProvider('allowedEmails')]
    public function test_allowed_unila_email_domains(string $email): void
    {
        config(['services.google.allowed_domains' => ['unila.ac.id', '*.unila.ac.id']]);

        $this->assertTrue($this->isAllowedEmailDomain($email));
    }

    #[DataProvider('blockedEmails')]
    public function test_blocks_non_unila_email_domains(string $email): void
    {
        config(['services.google.allowed_domains' => ['unila.ac.id', '*.unila.ac.id']]);

        $this->assertFalse($this->isAllowedEmailDomain($email));
    }

    public static function allowedEmails(): array
    {
        return [
            ['user@unila.ac.id'],
            ['user@mail.unila.ac.id'],
            ['user@student.fmipa.unila.ac.id'],
        ];
    }

    public static function blockedEmails(): array
    {
        return [
            ['user@gmail.com'],
            ['user@evilunila.ac.id'],
            ['user@unila.ac.id.example.com'],
            ['not-an-email'],
        ];
    }

    private function isAllowedEmailDomain(string $email): bool
    {
        $method = new ReflectionMethod(GoogleAuthController::class, 'isAllowedEmailDomain');

        return $method->invoke(new GoogleAuthController(), $email);
    }
}
