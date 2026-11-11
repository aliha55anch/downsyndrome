<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationSendController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.create', [
            'sendKey' => (string) Str::uuid(),
            'recipients' => User::query()->whereIn('role', [User::ROLE_PARENT, User::ROLE_APPROVED_DOCTOR])
                ->orderBy('role')->orderBy('name')->limit(250)->get(['id', 'name', 'email', 'role']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'send_key' => ['required', 'uuid'],
            'audience' => ['required', 'in:patients,doctors,all'],
            'user_ids' => ['nullable', 'array', 'max:100'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:3000'],
            'internal_url' => ['nullable', 'string', 'max:500', 'regex:#^/(?!/)[A-Za-z0-9/_?&=.%\-]*$#'],
        ]);

        $roles = match ($data['audience']) {
            'patients' => [User::ROLE_PARENT],
            'doctors' => [User::ROLE_APPROVED_DOCTOR],
            default => [User::ROLE_PARENT, User::ROLE_APPROVED_DOCTOR],
        };
        $users = User::query()->whereIn('role', $roles)
            ->when(! empty($data['user_ids']), fn ($query) => $query->whereIn('id', $data['user_ids']))
            ->orderBy('id');
        $recipientCount = (clone $users)->count();
        if ($recipientCount === 0) {
            return back()->withInput()->withErrors(['user_ids' => 'No eligible recipients match this audience and selection.']);
        }

        $created = DB::transaction(function () use ($request, $data, $users, $recipientCount): bool {
            $inserted = DB::table('admin_notification_batches')->insertOrIgnore([
                'send_key' => $data['send_key'],
                'admin_user_id' => $request->user()->id,
                'audience' => $data['audience'],
                'recipient_count' => $recipientCount,
                'title' => trim($data['title']),
                'message' => trim($data['message']),
                'internal_url' => $data['internal_url'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if (! $inserted) return false;
            (clone $users)->chunkById(100, function ($recipients) use ($data): void {
                foreach ($recipients as $user) {
                    $user->notifications()->create([
                        'id' => (string) Str::uuid(),
                        'type' => 'admin_broadcast',
                        'data' => ['title' => trim($data['title']), 'message' => trim($data['message']), 'url' => $data['internal_url'] ?? null],
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
            return true;
        });

        return redirect()->route('admin.notifications.create')->with('status', $created
            ? 'Notification sent to '.$recipientCount.' recipient(s). The send is recorded in the administration audit log.'
            : 'This notification form was already submitted. No duplicate notifications were sent.');
    }
}
