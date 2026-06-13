<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateWebPushVapidKeys extends Command
{
    protected $signature = 'silat:web-push:generate-vapid-keys';

    protected $description = 'Generate VAPID keys for browser Web Push notifications.';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $exception) {
            $keys = $this->generateWithNode();
        }

        $this->line('Tambahkan ke .env production:');
        $this->line('MONPKL_WEB_PUSH_VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('MONPKL_WEB_PUSH_VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('MONPKL_WEB_PUSH_VAPID_SUBJECT='.config('app.url'));

        return self::SUCCESS;
    }

    private function generateWithNode(): array
    {
        $script = <<<'JS'
const { generateKeyPairSync } = require('crypto');
const { privateKey, publicKey } = generateKeyPairSync('ec', { namedCurve: 'prime256v1' });
const pub = publicKey.export({ format: 'jwk' });
const priv = privateKey.export({ format: 'jwk' });
const b64uToBuf = s => Buffer.from(s.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - s.length % 4) % 4), 'base64');
const b64u = b => Buffer.from(b).toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
console.log(JSON.stringify({
  publicKey: b64u(Buffer.concat([Buffer.from([4]), b64uToBuf(pub.x), b64uToBuf(pub.y)])),
  privateKey: priv.d
}));
JS;

        $path = storage_path('framework/cache/vapid-keygen-'.uniqid().'.cjs');
        file_put_contents($path, $script);

        try {
            $output = shell_exec('node '.escapeshellarg($path));
            $keys = json_decode((string) $output, true);
        } finally {
            @unlink($path);
        }

        if (! is_array($keys) || blank($keys['publicKey'] ?? null) || blank($keys['privateKey'] ?? null)) {
            throw new \RuntimeException('Gagal membuat VAPID key. Pastikan OpenSSL PHP atau Node.js tersedia.');
        }

        return $keys;
    }
}
