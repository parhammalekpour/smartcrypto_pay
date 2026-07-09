<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getUnreadCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getNotifications()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'icon' => $notification->icon,
                    'read' => $notification->read,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = Notification::find($id);

        if ($notification && $notification->user_id === auth()->id()) {
            $notification->update(['read' => true]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function deleteNotification($id)
    {
        $notification = Notification::find($id);

        if ($notification && $notification->user_id === auth()->id()) {
            $notification->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['success' => true]);
    }

    public function checkRefreshStatus(Request $request)
    {
        $user = auth()->user();
        $lastRefreshTime = $request->query('lastRefresh') ? strtotime($request->query('lastRefresh')) : (time() - 60);
        $lastRefreshCarbon = \Carbon\Carbon::createFromTimestamp($lastRefreshTime);

        $hasChanges = false;
        $changeType = null;
        $details = [];

        // Check for new transactions for this user (as sender or recipient)
        $newTransactions = \App\Models\Transaction::where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                  ->orWhere('recipient_id', $user->id)
                  ->orWhereHas('wallet', function ($q) use ($user) {
                      $q->where('user_id', $user->id);
                  });
        })
        ->where('created_at', '>', $lastRefreshCarbon)
        ->count();

        if ($newTransactions > 0) {
            $hasChanges = true;
            $changeType = 'transaction';
            $details['transactionCount'] = $newTransactions;
        }

        // Check for new/updated payment requests for merchants
        if ($user->isMerchant()) {
            $newPaymentRequests = \App\Models\PaymentRequest::where('merchant_id', $user->id)
                ->where('updated_at', '>', $lastRefreshCarbon)
                ->count();

            if ($newPaymentRequests > 0) {
                $hasChanges = true;
                $changeType = 'payment_request';
                $details['paymentRequestCount'] = $newPaymentRequests;
            }

            // Check for new wallet updates
            $newWalletUpdates = \App\Models\Wallet::where('user_id', $user->id)
                ->where('updated_at', '>', $lastRefreshCarbon)
                ->count();

            if ($newWalletUpdates > 0) {
                $hasChanges = true;
                $changeType = 'wallet_update';
                $details['walletUpdateCount'] = $newWalletUpdates;
            }
        } else {
            // Check for user wallets update
            $newWalletUpdates = \App\Models\Wallet::where('user_id', $user->id)
                ->where('updated_at', '>', $lastRefreshCarbon)
                ->count();

            if ($newWalletUpdates > 0) {
                $hasChanges = true;
                $changeType = 'wallet_update';
                $details['walletUpdateCount'] = $newWalletUpdates;
            }

            // Check for new payment requests (as recipient)
            $newPaymentRequests = \App\Models\PaymentRequest::where('recipient_user_id', $user->id)
                ->where('created_at', '>', $lastRefreshCarbon)
                ->count();

            if ($newPaymentRequests > 0) {
                $hasChanges = true;
                $changeType = 'payment_request';
                $details['paymentRequestCount'] = $newPaymentRequests;
            }
        }

        return response()->json([
            'hasChanges' => $hasChanges,
            'changeType' => $changeType,
            'details' => $details,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
