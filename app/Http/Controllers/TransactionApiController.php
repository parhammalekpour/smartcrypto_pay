<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionApiController extends Controller
{
    public function show(Request $request, Transaction $transaction)
    {
        $user = auth()->user();

        // Authorization: allow if user owns the wallet or is the merchant/sender
        $ownsWallet = $transaction->wallet && $transaction->wallet->user_id === $user->id;
        $isMerchant = $transaction->merchant_id !== null && $transaction->merchant_id === $user->id;
        $isSender = $transaction->sender_id !== null && $transaction->sender_id === $user->id;

        if (!($ownsWallet || $isMerchant || $isSender || $user->isAdmin())) {
            abort(403);
        }

        $statusLabel = match ($transaction->status) {
            'processing' => __('transactions.processing'),
            'pending' => __('common.pending'),
            'confirmed' => __('common.confirmed'),
            'completed' => __('common.completed'),
            'failed' => __('common.failed'),
            'cancelled' => __('common.cancelled'),
            default => ucfirst($transaction->status),
        };

        return response()->json([
            'id' => $transaction->id,
            'status' => $transaction->status,
            'status_label' => $statusLabel,
            'amount' => (string)$transaction->amount,
            'currency' => $transaction->currency,
            'sender_wallet_address' => $transaction->sender_wallet_address,
            'receiver_wallet_address' => $transaction->receiver_wallet_address,
            'tx_hash' => $transaction->tx_hash,
            'block_number' => $transaction->block_number,
            'confirmations' => (int)($transaction->confirmations ?? 0),
            'created_at' => $transaction->created_at?->toDateTimeString(),
            'updated_at' => $transaction->updated_at?->toDateTimeString(),
            'failure_reason' => $transaction->status === 'failed' ? ($transaction->description ?: null) : null,
        ]);
    }
}
