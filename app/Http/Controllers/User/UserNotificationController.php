<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\UserNotification;
use Auth;

class UserNotificationController extends Controller
{
    public function getNotifications()
    {
        $user = Auth::user();

        $notifications = UserNotification::where('notifiable_id', $user->id)
            ->select('notifications.data',  'notifications.notifiable_id', 'notifications.read_at', 'notifications.created_at')
            ->get();

        return response()
            ->json([
                'notifications' => $notifications
            ]);
    }
}
