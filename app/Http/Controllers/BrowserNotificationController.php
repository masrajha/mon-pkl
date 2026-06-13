<?php

namespace App\Http\Controllers;

use App\Models\BrowserNotification;
use App\Models\BrowserPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrowserNotificationController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        $now = now();

        $notifications = BrowserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('shown_at')
            ->whereNull('read_at')
            ->where(function ($query) use ($now): void {
                $query->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->oldest('scheduled_for')
            ->limit(5)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(fn (BrowserNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'action_url' => $notification->action_url,
                'type' => $notification->type,
                'data' => $notification->data ?? [],
            ])->values(),
        ]);
    }

    public function markShown(Request $request, BrowserNotification $browserNotification): JsonResponse
    {
        abort_unless($browserNotification->user_id === $request->user()->id, 403);

        $browserNotification->forceFill([
            'shown_at' => $browserNotification->shown_at ?? now(),
        ])->save();

        return response()->json(['ok' => true]);
    }

    public function markRead(Request $request, BrowserNotification $browserNotification): JsonResponse
    {
        abort_unless($browserNotification->user_id === $request->user()->id, 403);

        $browserNotification->forceFill([
            'read_at' => $browserNotification->read_at ?? now(),
            'shown_at' => $browserNotification->shown_at ?? now(),
        ])->save();

        return response()->json(['ok' => true]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:3000'],
            'keys.p256dh' => ['required', 'string', 'max:1000'],
            'keys.auth' => ['required', 'string', 'max:1000'],
            'contentEncoding' => ['nullable', 'string', 'max:30'],
        ]);

        BrowserPushSubscription::query()->updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'browser' => $request->userAgent(),
                'is_active' => true,
                'last_used_at' => now(),
                'revoked_at' => null,
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:3000'],
        ]);

        BrowserPushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $validated['endpoint'])
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }
}
