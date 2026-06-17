<?php

namespace App\Http\Controllers;

use App\Models\WfaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WfaRequestApprovalController extends Controller
{
    public function approveAsManagement(Request $request, WfaRequest $wfaRequest): RedirectResponse
    {
        $this->authorizeManagement($request, $wfaRequest);
        $this->approve($request, $wfaRequest);

        return back()->with('status', 'Pengajuan WFA berhasil disetujui.');
    }

    public function rejectAsManagement(Request $request, WfaRequest $wfaRequest): RedirectResponse
    {
        $this->authorizeManagement($request, $wfaRequest);
        $this->reject($request, $wfaRequest);

        return back()->with('status', 'Pengajuan WFA berhasil ditolak.');
    }

    private function approve(Request $request, WfaRequest $wfaRequest): void
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $wfaRequest, $data): void {
            $freshRequest = WfaRequest::query()->lockForUpdate()->findOrFail($wfaRequest->id);

            if ($freshRequest->status !== 'pending') {
                throw ValidationException::withMessages(['wfa_request' => 'Pengajuan WFA ini sudah diproses.']);
            }

            $freshRequest->forceFill([
                'status' => 'approved',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? null,
            ])->save();
        });
    }

    private function reject(Request $request, WfaRequest $wfaRequest): void
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($wfaRequest->status !== 'pending') {
            throw ValidationException::withMessages(['wfa_request' => 'Pengajuan WFA ini sudah diproses.']);
        }

        $wfaRequest->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ])->save();
    }

    private function authorizeManagement(Request $request, WfaRequest $wfaRequest): void
    {
        if ($request->user()?->hasRole('admin')) {
            return;
        }

        $wfaRequest->loadMissing('enrollment');
        $allowed = $request->user()?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $wfaRequest->enrollment?->internship_period_id)
            ->where('study_program_id', $wfaRequest->enrollment?->study_program_id)
            ->exists();

        abort_unless($allowed, 403);
    }
}
