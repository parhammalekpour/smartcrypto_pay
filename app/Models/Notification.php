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

    public static function localizeText(?string $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        $locale = app()->getLocale() === 'fa' ? 'fa' : 'en';

        if (str_contains($text, '.') && !str_contains($text, ' ')) {
            $translated = __($text, [], app()->getLocale());
            if ($translated !== $text) {
                return $translated;
            }
        }

        $titleMap = [
            'fa' => [
                'Demo deposit' => 'سپرده آزمایشی',
                'Transfer successful' => 'انتقال موفق',
                'Transfer received' => 'دریافت انتقال',
                'Payment completed' => 'پرداخت تکمیل شد',
                'Payment received' => 'پرداخت دریافت شد',
                'Payment request rejected' => 'درخواست پرداخت رد شد',
                'Request rejected' => 'درخواست رد شد',
                'Deposit received' => 'واریز دریافت شد',
            ],
            'en' => [
                'سپرده آزمایشی' => 'Demo deposit',
                'سپرده تجربی' => 'Demo deposit',
                'انتقال موفق' => 'Transfer successful',
                'دریافت انتقال' => 'Transfer received',
                'پرداخت تکمیل شد' => 'Payment completed',
                '✅ پرداخت تکمیل شد' => 'Payment completed',
                'پرداخت دریافت شد' => 'Payment received',
                '✅ پرداخت دریافت شد' => 'Payment received',
                'درخواست پرداخت رد شد' => 'Payment request rejected',
                'درخواست رد شد' => 'Request rejected',
                'واریز دریافت شد' => 'Deposit received',
            ],
        ];

        if (isset($titleMap[$locale][$text])) {
            return $titleMap[$locale][$text];
        }

        $patternMap = [
            'fa' => [
                '/^Added\s+(.+)\s+(.+)\s+to your wallet$/' => 'مقدار %s %s به کیف پول شما اضافه شد',
                '/^You sent\s+(.+)\s+(.+)\s+to\s+(.+)$/' => 'شما %s %s را به %s ارسال کردید',
                '/^You received\s+(.+)\s+(.+)\s+from\s+(.+)$/' => 'شما %s %s را از %s دریافت کردید',
                '/^You paid\s+(.+)\s+(.+)\s+to\s+(.+)$/' => 'شما %s %s را به %s پرداخت کردید',
                '/^Invoice\s+(.+)\s+was rejected by\s+(.+)$/' => 'فاکتور %s توسط %s رد شد',
                '/^Deposit of\s+(.+)\s+(.+)\s+from\s+(.+)$/' => 'واریز %s %s از %s',
            ],
            'en' => [
                '/^مقدار\s+(.+)\s+(.+)\s+به کیف پول شما اضافه شد$/' => 'Added %s %s to your wallet',
                '/^شما\s+(.+)\s+(.+)\s*(?:را\s+)?به\s+(.+)\s+ارسال کردید$/' => 'You sent %s %s to %s',
                '/^شما\s+(.+)\s+(.+)\s*(?:را\s+)?از\s+(.+)\s+دریافت کردید$/' => 'You received %s %s from %s',
                '/^شما\s+(.+)\s+(.+)\s*(?:را\s+)?به\s+(.+)\s+پرداخت کردید$/' => 'You paid %s %s to %s',
                '/^فاکتور\s+(.+)\s+توسط\s+(.+)\s+رد شد$/' => 'Invoice %s was rejected by %s',
                '/^واریز\s+(.+)\s+(.+)\s+از\s+(.+)$/' => 'Deposit of %s %s from %s',
            ],
        ];

        foreach ($patternMap[$locale] as $pattern => $template) {
            if (preg_match($pattern, $text, $matches)) {
                return sprintf($template, ...array_slice($matches, 1));
            }
        }

        return $text;
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
