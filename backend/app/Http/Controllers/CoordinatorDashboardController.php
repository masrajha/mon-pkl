<?php

namespace App\Http\Controllers;

use App\Models\InternshipCoordinator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoordinatorDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $assignments = InternshipCoordinator::query()
            ->with(['internshipPeriod', 'studyProgram'])
            ->where('status', 'active')
            ->whereHas('lecturer', fn ($query) => $query->where('user_id', $request->user()->id))
            ->orderByDesc('id')
            ->get();

        return view('coordinator.dashboard', compact('assignments'));
    }
}
