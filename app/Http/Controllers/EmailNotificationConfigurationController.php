<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\EmailNotification;
use App\Models\SystemSetting;
use App\Services\EmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailNotificationConfigurationController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString() ?: 'status';
        $tab = in_array($tab, ['status', 'queue', 'mail'], true) ? $tab : 'status';

        $query = EmailNotification::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('recipient_email', 'like', '%'.$search.'%')
                ->orWhere('recipient_name', 'like', '%'.$search.'%')
                ->orWhere('subject', 'like', '%'.$search.'%')
                ->orWhere('type', 'like', '%'.$search.'%'));
        }

        $statusCounts = EmailNotification::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('email-notifications.index', [
            'tab' => $tab,
            'enabled' => $this->emails->notificationsEnabled(),
            'statusCounts' => $statusCounts,
            'notifications' => $query
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
            'mailSettings' => $this->emails->mailSettings(),
            'categories' => $this->categories(),
            'categorySettings' => $this->emails->categorySettings(),
        ]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        SystemSetting::putValue('email_notifications', [
            'enabled' => $request->boolean('enabled'),
        ]);

        return redirect()
            ->route('email-notifications.index')
            ->with('status', 'Status email notifikasi berhasil diperbarui.');
    }

    public function updateCoverage(Request $request): RedirectResponse
    {
        $definitions = $this->emails->categoryDefinitions();
        $enabled = collect($request->input('categories', []))
            ->filter(fn ($value) => (string) $value === '1')
            ->keys()
            ->all();

        $settings = collect($definitions)
            ->mapWithKeys(fn (array $category, string $key) => [
                $key => $category['implemented'] && in_array($key, $enabled, true),
            ])
            ->all();

        SystemSetting::putValue('email_notification_categories', $settings);

        return redirect()
            ->route('email-notifications.index')
            ->with('status', 'Cakupan notifikasi berhasil diperbarui.');
    }

    public function updateMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mailer' => ['required', Rule::in(['smtp', 'log', 'array'])],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ]);

        $stored = SystemSetting::getValue('mail_settings');
        if (filled($validated['password'] ?? null)) {
            $validated['password_encrypted'] = Crypt::encryptString($validated['password']);
        } elseif (filled($stored['password_encrypted'] ?? null)) {
            $validated['password_encrypted'] = $stored['password_encrypted'];
        }
        unset($validated['password']);

        SystemSetting::putValue('mail_settings', $validated);

        return redirect()
            ->route('email-notifications.index', ['tab' => 'mail'])
            ->with('status', 'Konfigurasi mail server berhasil disimpan.');
    }

    public function processPending(): RedirectResponse
    {
        $result = $this->emails->processDue(100);
        $skipped = $result['skipped'] ?? 0;

        return redirect()
            ->route('email-notifications.index', ['tab' => 'queue'])
            ->with('status', "Antrean diproses. Terkirim: {$result['sent']}, gagal: {$result['failed']}, dilewati: {$skipped}.");
    }

    public function retryFailed(): RedirectResponse
    {
        $reset = EmailNotification::query()
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'failed_at' => null,
                'error_message' => null,
                'scheduled_for' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('email-notifications.index', ['tab' => 'queue', 'status' => 'pending'])
            ->with('status', "{$reset} email gagal dikembalikan ke antrean.");
    }

    private function categories(): array
    {
        $settings = $this->emails->categorySettings();

        return collect($this->emails->categoryDefinitions())
            ->map(fn (array $category, string $key) => [
                'key' => $key,
                'label' => $category['label'],
                'type' => rtrim($category['prefix'], '.'),
                'implemented' => $category['implemented'],
                'enabled' => (bool) ($settings[$key] ?? false),
            ])
            ->values()
            ->all();
    }
}
