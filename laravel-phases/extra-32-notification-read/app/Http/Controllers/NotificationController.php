<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\Notifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The notification bell.
 *  GET  /notifications           the list inside the bell. It is loaded only when the bell is clicked, so the pages stay fast.
 *  POST /notifications/read      one notification is read (it was clicked, or "Mark as read")
 *  POST /notifications/read-all  "Mark all as read"
 * The role of the logged-in user decides what is shown (customers have no bell).
 */
class NotificationController extends Controller
{
    private const AREAS = [
        Role::STAFF => 'staff',
        Role::VET_ADMIN => 'admin',
        Role::SUPER_ADMIN => 'superadmin',
    ];

    public function __invoke(Request $request): View
    {
        $area = $this->area($request);

        return view('partials.notification-list', [
            'notes' => Notifications::for($request->user(), $area),
            'unread' => Notifications::unreadCount($request->user(), $area),
            'limit' => 10,
        ]);
    }

    public function read(Request $request): JsonResponse
    {
        $area = $this->area($request);
        $key = $request->validate(['key' => ['required', 'string', 'size:40']])['key'];

        Notifications::markRead($request->user(), $area, $key);

        return response()->json(['unread' => Notifications::unreadCount($request->user(), $area)]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $area = $this->area($request);

        Notifications::markRead($request->user(), $area);

        return response()->json(['unread' => Notifications::unreadCount($request->user(), $area)]);
    }

    private function area(Request $request): string
    {
        $area = self::areaOf($request->user());
        abort_unless($area, 403);

        return $area;
    }

    // The area of a user, for the number on the bell
    public static function areaOf(?\App\Models\User $user): ?string
    {
        return self::AREAS[$user?->role?->slug] ?? null;
    }
}
