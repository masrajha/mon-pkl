<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageFileController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        abort_if(Str::contains($path, ['..', '\\']), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()
            ->file(Storage::disk('public')->path($path), [
                'Cache-Control' => 'public, max-age=604800',
            ]);
    }
}
