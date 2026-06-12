<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class AuditLogService
{
    private const HIDDEN_KEYS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'verification_token',
    ];

    public function recordModelEvent(Model $model, string $event, array $metadata = []): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $oldValues = $event === 'created' ? [] : $this->sanitize($model->getOriginal());
        $newValues = $event === 'deleted' ? [] : $this->sanitize($model->getAttributes());
        $changedValues = $event === 'updated'
            ? $this->changedValues($model, $oldValues, $newValues)
            : [];

        if ($event === 'updated' && $changedValues === []) {
            return;
        }

        $user = Auth::user();

        AuditLog::query()->create([
            'category' => $this->categoryFor($model),
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'auditable_label' => $this->labelFor($model),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'ip_address' => Request::ip(),
            'user_agent' => Str::limit((string) Request::userAgent(), 1000, ''),
            'method' => Request::method(),
            'url' => Str::limit((string) Request::fullUrl(), 2000, ''),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_values' => $changedValues,
            'metadata' => $metadata,
        ]);
    }

    private function changedValues(Model $model, array $oldValues, array $newValues): array
    {
        return collect($model->getChanges())
            ->except(['updated_at'])
            ->mapWithKeys(fn ($value, string $key): array => [
                $key => [
                    'old' => Arr::get($oldValues, $key),
                    'new' => Arr::get($newValues, $key),
                ],
            ])
            ->all();
    }

    private function sanitize(array $values): array
    {
        return collect($values)
            ->reject(fn ($value, string $key): bool => in_array($key, self::HIDDEN_KEYS, true))
            ->map(fn ($value) => is_string($value) && strlen($value) > 4000 ? Str::limit($value, 4000, '...') : $value)
            ->all();
    }

    private function categoryFor(Model $model): string
    {
        return match ($model::class) {
            \App\Models\InternshipPeriodSetting::class => 'configuration',
            \App\Models\CheckIn::class => 'attendance',
            \App\Models\Sanction::class => 'sanction',
            \App\Models\FieldSupervisorAssessment::class => 'assessment',
            \App\Models\FinalAssessment::class => 'final_assessment',
            \App\Models\InternshipEnrollment::class => 'enrollment',
            \App\Models\ForgottenAttendanceRequest::class => 'forgotten_attendance',
            \App\Models\User::class,
            \App\Models\Student::class,
            \App\Models\Lecturer::class,
            \App\Models\Program::class,
            \App\Models\StudyProgram::class,
            \App\Models\Organization::class,
            \App\Models\InternshipPlace::class,
            \App\Models\InternshipPeriod::class,
            \App\Models\PeriodDeadline::class,
            \App\Models\InternshipCoordinator::class,
            \App\Models\ReportViewerAssignment::class => 'master_data',
            default => 'system',
        };
    }

    private function labelFor(Model $model): ?string
    {
        foreach (['name', 'full_name', 'title', 'display_name', 'document_number', 'npm', 'email', 'code'] as $key) {
            if (filled($model->getAttribute($key))) {
                return (string) $model->getAttribute($key);
            }
        }

        if (method_exists($model, 'student') && $model->relationLoaded('student')) {
            return $model->student?->full_name;
        }

        return $model->getKey() ? class_basename($model).' #'.$model->getKey() : class_basename($model);
    }
}
