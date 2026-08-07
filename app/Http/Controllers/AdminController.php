<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'verified_kyc' => User::where('kyc_verified', true)->count(),
            'pending_kyc' => $this->pendingKycQuery()->count(),
            'transactions' => Transaction::count(),
            'completed_transactions' => Transaction::where('status', 'completed')->count(),
            'cancelled_transactions' => Transaction::where('status', 'cancelled')->count(),
        ];

        $recentUsers = User::latest()->take(8)->get();
        $pendingKycUsers = $this->pendingKycQuery()->latest()->take(8)->get();
        $recentTransactions = Transaction::with(['sender', 'recipient', 'wallet', 'deposit'])->latest()->take(8)->get();
        $activity = DB::table('audit_logs')
            ->select('audit_logs.*', 'actors.name as actor_name', 'targets.name as user_name')
            ->leftJoin('users as actors', 'actors.id', '=', 'audit_logs.actor_id')
            ->leftJoin('users as targets', 'targets.id', '=', 'audit_logs.user_id')
            ->latest('audit_logs.created_at')
            ->take(12)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'pendingKycUsers', 'recentTransactions', 'activity'));
    }

    public function users()
    {
        $users = User::latest()->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'شما نمی‌توانید حساب خودتان را حذف کنید.']);
        }

        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'امکان حذف ادمین وجود ندارد.']);
        }

        $user->wallets()->delete();
        $user->notifications()->delete();
        $user->delete();

        $this->logAction('user_deleted', $user->id, 'user', $user->id, ['deleted' => true]);

        return back()->with('success', 'کاربر با موفقیت حذف شد.');
    }

    /**
     * Show wallets belonging to a user (admin monitoring)
     */
    public function userWallets(User $user)
    {
        $wallets = $user->wallets()->latest()->paginate(20);

        return view('admin.wallets_user', compact('user', 'wallets'));
    }

    /**
     * Destroy a wallet (admin action). This will cascade-delete related transactions via DB constraints.
     */
    public function destroyWallet(\App\Models\Wallet $wallet)
    {
        $userId = $wallet->user_id;
        $balance = $wallet->balance;

        $wallet->delete();

        $this->logAction('wallet_deleted', $userId, 'wallet', $wallet->id, ['balance' => (string)$balance]);

        return back()->with('success', 'کیف پول با موفقیت حذف شد.');
    }

    public function kyc(Request $request)
    {
        $query = $this->kycQuery();

        if ($request->filled('status')) {
            $query->where('kyc_verified', $request->boolean('status'));
        }

        $users = $query->paginate(20)->appends($request->query());

        return view('admin.kyc', compact('users'));
    }

    public function approveKyc(User $user)
    {
        $user->update(['kyc_verified' => true]);
        $this->logAction('kyc_approved', $user->id, 'user', $user->id, ['verified' => true]);

        return back()->with('success', 'KYC برای کاربر تایید شد.');
    }

    public function rejectKyc(User $user)
    {
        $user->update(['kyc_verified' => false]);
        $this->logAction('kyc_rejected', $user->id, 'user', $user->id, ['verified' => false]);

        return back()->with('success', 'KYC برای کاربر رد شد.');
    }

    public function transactions(Request $request)
    {
        $query = Transaction::with(['sender', 'recipient', 'wallet', 'deposit'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('sender', fn ($senderQuery) => $senderQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('recipient', fn ($recipientQuery) => $recipientQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->paginate(20)->appends($request->query());
        $statuses = ['pending', 'completed', 'cancelled', 'failed'];

        return view('admin.transactions', compact('transactions', 'statuses'));
    }

    public function cancelTransaction(Transaction $transaction)
    {
        if ($transaction->status === 'cancelled') {
            return back()->with('info', 'این تراکنش از قبل لغو شده است.');
        }

        if ($transaction->wallet) {
            if ($transaction->type === 'deposit') {
                $transaction->wallet->decrement('balance', $transaction->amount);
            } elseif ($transaction->type === 'transfer') {
                if ($transaction->sender_id) {
                    $transaction->wallet->increment('balance', $transaction->amount);
                } elseif ($transaction->recipient_id) {
                    $transaction->wallet->decrement('balance', $transaction->amount);
                }
            }
        }

        $transaction->status = 'cancelled';
        $transaction->save();
        $this->logAction('transaction_cancelled', $transaction->id, 'transaction', $transaction->id, ['status' => 'cancelled']);

        return back()->with('success', 'تراکنش با موفقیت لغو شد.');
    }

    protected function pendingKycQuery()
    {
        return User::where('kyc_verified', false)
            ->where(function ($query) {
                $query->whereNotNull('kyc_selfie')
                    ->where('kyc_selfie', '!=', '')
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('kyc_documents')
                            ->whereRaw('JSON_LENGTH(COALESCE(kyc_documents, "[]")) > 0');
                    });
            });
    }

    protected function kycQuery()
    {
        return User::where(function ($query) {
            $query->whereNotNull('kyc_selfie')
                ->where('kyc_selfie', '!=', '')
                ->orWhere(function ($subQuery) {
                    $subQuery->whereNotNull('kyc_documents')
                        ->whereRaw('JSON_LENGTH(COALESCE(kyc_documents, "[]")) > 0');
                });
        })
        ->orderBy('kyc_verified', 'asc')
        ->latest();
    }

    protected function logAction(string $action, ?int $userId, string $resourceType, $resourceId, array $data = []): void
    {
        DB::table('audit_logs')->insert([
            'actor_id' => auth()->id(),
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => (string) $resourceId,
            'diff' => json_encode($data),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
