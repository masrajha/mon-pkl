<?php

namespace App\Http\Controllers;

use App\Models\FieldSupervisorAccessToken;
use App\Models\InternshipEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FieldSupervisorPortalController extends Controller
{
    public function token(Request $request, string $token): View
    {
        $accessToken = FieldSupervisorAccessToken::query()
            ->with($this->enrollmentRelations())
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_unless($accessToken->isValid(), 403, 'Token akses pembimbing lapangan tidak valid atau sudah kedaluwarsa.');

        $accessToken->forceFill(['last_accessed_at' => now()])->save();

        return $this->renderDashboard(collect([$accessToken->enrollment]), $accessToken->email, accessMode: 'token');
    }

    public function index(Request $request): View
    {
        $email = Str::lower(trim((string) $request->user()?->email));

        $enrollments = InternshipEnrollment::query()
            ->with($this->enrollmentRelations())
            ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->latest('id')
            ->get();

        return $this->renderDashboard($enrollments, $email, accessMode: 'login');
    }

    private function renderDashboard($enrollments, string $email, string $accessMode): View
    {
        return view('field-supervisor.dashboard', [
            'enrollments' => $enrollments,
            'email' => $email,
            'accessMode' => $accessMode,
            'dailyRowsByEnrollment' => $enrollments->mapWithKeys(fn (InternshipEnrollment $enrollment) => [
                $enrollment->id => $this->dailyRows($enrollment),
            ]),
        ]);
    }

    private function enrollmentRelations(): array
    {
        return [
            'student',
            'studyProgram',
            'internshipPeriod.program',
            'internshipPlace',
            'lecturer',
            'checkIns' => fn ($query) => $query->orderBy('checked_at'),
        ];
    }

    private function dailyRows(InternshipEnrollment $enrollment)
    {
        return $enrollment->checkIns
            ->groupBy(fn ($checkIn) => $checkIn->checked_at?->toDateString() ?: 'tanpa-tanggal')
            ->map(function ($items) {
                $checkIn = $items->firstWhere('action', 'check_in') ?: $items->first();
                $checkOut = $items->firstWhere('action', 'check_out') ?: $items->firstWhere('pair_id', $checkIn?->id);

                return [
                    'date' => $checkIn?->checked_at ?: $items->first()?->checked_at,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'duration_minutes' => $checkOut?->duration_minutes ?? $checkIn?->duration_minutes,
                ];
            })
            ->sortByDesc('date')
            ->values();
    }
}
