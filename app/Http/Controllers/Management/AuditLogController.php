<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->latest('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('event')) {
            $query->where('event', $request->string('event')->toString());
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($query) use ($search): void {
                $query->where('auditable_label', 'like', $search)
                    ->orWhere('auditable_type', 'like', $search)
                    ->orWhere('user_name', 'like', $search)
                    ->orWhere('user_email', 'like', $search)
                    ->orWhere('url', 'like', $search);
            });
        }

        return view('management.audit-logs.index', [
            'logs' => $query->paginate((int) $request->integer('per_page', 20))->withQueryString(),
            'categories' => AuditLog::query()->select('category')->distinct()->orderBy('category')->pluck('category'),
            'events' => AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event'),
            'selectedCategory' => $request->string('category')->toString(),
            'selectedEvent' => $request->string('event')->toString(),
        ]);
    }
}
