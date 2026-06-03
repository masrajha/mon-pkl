<?php

namespace App\Http\Controllers;

use App\Services\LocationSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationSuggestionController extends Controller
{
    public function __construct(private readonly LocationSuggestionService $locations)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->locations->internal($request->string('q')->toString())->all(),
        ]);
    }
}
