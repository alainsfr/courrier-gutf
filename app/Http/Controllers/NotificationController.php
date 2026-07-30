<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('notifications.index', [
            'unread' => $user->unreadNotifications()->latest()->paginate(15, ['*'], 'unread_page'),
            'read' => $user->readNotifications()->latest()->paginate(10, ['*'], 'read_page'),
            'unreadTotal' => $user->unreadNotifications()->count(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if (! $this->isSafeAppUrl($url)) {
            return redirect()
                ->route('notifications.index')
                ->with('status', __('notifications.marked_read'));
        }

        return redirect()->to($url);
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back()->with('status', __('notifications.marked_read'));
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', __('notifications.all_read'));
    }

    private function isSafeAppUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl === '') {
            return false;
        }

        return str_starts_with($url, $appUrl.'/') || $url === $appUrl;
    }
}
