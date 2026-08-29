<?php

namespace App\Models;

use App\Models\Deposit;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'user_id',
        'merchant_id',
        'sender_id',
        'recipient_id',
        'type',
        'amount',
        'currency',
        'status',
        'reference',
        'description',
        'payment_request_id',
        'sender_wallet_address',
        'receiver_wallet_address',
        'from_address',
        'to_address',
        'tx_hash',
        'nonce',
        'block_number',
        'block_hash',
        'transaction_index',
        'receipt_status',
        'gas_used',
        'gas_price',
        'max_fee_per_gas',
        'max_priority_fee_per_gas',
        'confirmed_at',
        'failed_at',
        'replaced_by',
        'replacement_of',
        'failure_reason',
        'broadcasted_at',
        'last_checked_at',
        'confirmations',
    ];

    protected $casts = [
        'amount' => 'string',
        'nonce' => 'integer',
        'block_number' => 'integer',
        'transaction_index' => 'integer',
        'confirmations' => 'integer',
        'receipt_status' => 'string',
        'gas_used' => 'string',
        'gas_price' => 'string',
        'max_fee_per_gas' => 'string',
        'max_priority_fee_per_gas' => 'string',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'broadcasted_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    public function deposit()
    {
        return $this->hasOne(Deposit::class, 'tx_hash', 'reference');
    }

    public function getSenderWalletAddressAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        return $this->deposit?->sender_wallet_address;
    }

    public function getSenderNameAttribute()
    {
        if ($this->sender) {
            return $this->sender->name;
        }
        return __('transactions.unknown');
    }

    public function getRecipientNameAttribute()
    {
        if ($this->recipient) {
            return $this->recipient->name;
        }
        return __('transactions.unknown');
    }

    public function getCurrencyAttribute()
    {
        return $this->wallet->currency ?? 'UNKNOWN';
    }

    /**
     * Ensure amount is returned as a fixed decimal string with 18 decimals for ETH precision
     * and without scientific notation (SQLite sometimes returns values like 1.0E-18).
     */
    public function getAmountAttribute($value)
    {
        if ($value === null) {
            return $value;
        }

        $raw = (string) $value;

        // Determine currency from stored attribute first.
        // IMPORTANT: if the currency attribute exists but is NULL, treat as unknown (do NOT fall back to wallet currency).
        $currency = '';
        $attrs = $this->attributes ?? [];
        if (array_key_exists('currency', $attrs) && $attrs['currency'] !== null) {
            // currency explicitly set (and not null) on transaction
            $currency = strtoupper(trim((string) ($attrs['currency'] ?? '')));
        } else {
            // Only fall back to wallet->currency when the transaction record does NOT have a currency attribute at all.
            try {
                if (!array_key_exists('currency', $attrs)) {
                    $currency = strtoupper(trim((string) ($this->wallet?->currency ?? '')));
                }
            } catch (\Throwable $_) {
                $currency = '';
            }
        }

        $hasScientific = stripos($raw, 'e') !== false;
        $normalized = $hasScientific ? $this->scientificToDecimal($raw) : $raw;

        // Currency-aware normalization
        try {
            if ($currency === 'ETH') {
                // ETH uses 18 decimals
                return bcadd($normalized, '0', 18);
            }

            if ($currency === 'USDT') {
                // USDT uses 6 decimals
                return bcadd($normalized, '0', 6);
            }

            // Unknown currency: do not invent a precision. Only normalize scientific notation.
            if ($hasScientific) {
                // scientificToDecimal returns a plain decimal string; preserve that
                return $normalized;
            }

            // Preserve original stored representation as much as possible
            return $raw;
        } catch (\Throwable $_) {
            // Fallback: return original raw string
            return $raw;
        }
    }

    private function scientificToDecimal(string $s): string
    {
        // Matches mantissa and exponent like 1.234E-5
        if (!preg_match('/^([+-]?\d*\.?\d+)[eE]([+-]?\d+)$/', trim($s), $m)) {
            return $s;
        }

        $mantissa = $m[1];
        $exp = (int) $m[2];

        // Remove sign for now
        $sign = '';
        if (str_starts_with($mantissa, '+') || str_starts_with($mantissa, '-')) {
            $sign = $mantissa[0];
            $mantissa = substr($mantissa, 1);
        }

        if (strpos($mantissa, '.') !== false) {
            list($intPart, $fracPart) = explode('.', $mantissa, 2);
            $mInt = $intPart . $fracPart;
            $d = strlen($fracPart);
        } else {
            $mInt = $mantissa;
            $d = 0;
        }

        // remove leading zeros
        $mInt = ltrim($mInt, '0');
        if ($mInt === '') {
            return '0';
        }

        $power = $exp - $d;

        if ($power >= 0) {
            return $sign . $mInt . str_repeat('0', $power);
        }

        $decimalPlaces = -$power;
        $len = strlen($mInt);

        if ($decimalPlaces > $len) {
            $zeros = str_repeat('0', $decimalPlaces - $len);
            $res = $sign . '0.' . $zeros . $mInt;
            // Trim unnecessary trailing zeros in fractional part for minimal representation
            if (strpos($res, '.') !== false) {
                $res = rtrim($res, '0');
                if (substr($res, -1) === '.') {
                    $res = substr($res, 0, -1);
                }
            }
            return $res;
        }

        $intPart = substr($mInt, 0, $len - $decimalPlaces);
        $fracPart = substr($mInt, $len - $decimalPlaces);

        $res = $sign . $intPart . '.' . $fracPart;
        // Trim unnecessary trailing zeros in fractional part for minimal representation
        if (strpos($res, '.') !== false) {
            $res = rtrim($res, '0');
            if (substr($res, -1) === '.') {
                $res = substr($res, 0, -1);
            }
        }

        return $res;
    }

    public function getMerchantNameAttribute()
    {
        if ($this->paymentRequest && $this->paymentRequest->merchant) {
            return $this->paymentRequest->merchant->name;
        }
        return null;
    }

    public function getDisplayTitleAttribute()
    {
        if ($this->type === 'transfer') {
            if ($this->merchant_name) {
                return __('transactions.payment_to_merchant', ['merchant' => $this->merchant_name]);
            }

            if ($this->recipient) {
                return __('transactions.transfer_to_recipient', ['name' => $this->recipient->name]);
            }

            return __('transactions.transfer_to_unknown');
        }

        if ($this->type === 'deposit') {
            if ($this->description === 'Demo Deposit') {
                return __('transactions.deposit_demo', ['description' => $this->description]);
            }

            if (!empty($this->sender_wallet_address)) {
                return __('transactions.deposit_from_wallet', ['address' => $this->sender_wallet_address]);
            }

            if ($this->sender) {
                return __('transactions.deposit_from_sender', ['sender' => $this->sender->name]);
            }

            if (!empty($this->reference)) {
                return __('transactions.deposit_from_transaction', ['reference' => substr($this->reference, 0, 10) . '...']);
            }

            return __('transactions.deposit_from_external');
        }

        return $this->description ?? __('transactions.default');
    }
}
