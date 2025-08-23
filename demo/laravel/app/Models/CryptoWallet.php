<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_address',
        'private_key_encrypted',
        'derivation_path',
        'wallet_type',
        'status'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all crypto transactions for this wallet
     */
    public function cryptoTransactions(): HasMany
    {
        return $this->hasMany(CryptoTransaction::class, 'wallet_address', 'wallet_address');
    }

    /**
     * Get deposits for this wallet
     */
    public function deposits(): HasMany
    {
        return $this->cryptoTransactions()
            ->where('transaction_type', 'deposit')
            ->where('status', 'confirmed');
    }

    /**
     * Get withdrawals for this wallet
     */
    public function withdrawals(): HasMany
    {
        return $this->cryptoTransactions()
            ->where('transaction_type', 'withdrawal');
    }

    /**
     * Check if wallet is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get wallet balance summary
     */
    public function getBalanceSummary(): array
    {
        $solDeposits = $this->deposits()->where('currency', 'SOL')->sum('amount');
        $solWithdrawals = $this->withdrawals()->where('currency', 'SOL')->where('status', 'confirmed')->sum('amount');
        $solBalance = $solDeposits - $solWithdrawals;

        $usdtDeposits = $this->deposits()->where('currency', 'USDT')->sum('amount');
        $usdtWithdrawals = $this->withdrawals()->where('currency', 'USDT')->where('status', 'confirmed')->sum('amount');
        $usdtBalance = $usdtDeposits - $usdtWithdrawals;

        return [
            'sol_balance' => max(0, $solBalance),
            'usdt_balance' => max(0, $usdtBalance),
            'total_deposits' => $this->deposits()->count(),
            'total_withdrawals' => $this->withdrawals()->count()
        ];
    }

    /**
     * Scope for active wallets
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for specific wallet type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('wallet_type', $type);
    }
} 