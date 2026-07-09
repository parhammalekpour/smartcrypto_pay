<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'icon',
        'read'
    ];

    protected $casts = [
        'read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createNotification($userId, $title, $message, $type = 'info', $icon = null)
    {
        // Create new notification
        self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'icon' => $icon,
        ]);

        // Keep only last 5 notifications for this user
        $userNotifications = self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($userNotifications->count() > 5) {
            $toDelete = $userNotifications->slice(5);
            foreach ($toDelete as $notification) {
                $notification->delete();
            }
        }
    }
}
