<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CryptoTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_hash',
        'wallet_address',
        'transaction_type',
        'currency',
        'amount',
        'amount_usd',
        'network_fee',
        'status',
        'confirmations',
        'required_confirmations',
        'block_height',
        'from_address',
        'to_address',
        'memo',
        'processed_at'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'decimal:9',
        'amount_usd' => 'decimal:2',
        'network_fee' => 'decimal:9',
        'confirmations' => 'integer',
        'required_confirmations' => 'integer',
        'block_height' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the crypto wallet associated with this transaction
     */
    public function cryptoWallet(): BelongsTo
    {
        return $this->belongsTo(CryptoWallet::class, 'wallet_address', 'wallet_address');
    }

    /**
     * Get the withdrawal approval if this is a withdrawal
     */
    public function withdrawalApproval(): HasOne
    {
        return $this->hasOne(WithdrawalApproval::class);
    }

    /**
     * Get the linked traditional transaction
     */
    public function traditionalTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'crypto_transaction_id', 'id');
    }

    /**
     * Check if transaction is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is a deposit
     */
    public function isDeposit(): bool
    {
        return $this->transaction_type === 'deposit';
    }

    /**
     * Check if transaction is a withdrawal
     */
    public function isWithdrawal(): bool
    {
        return $this->transaction_type === 'withdrawal';
    }

    /**
     * Check if transaction has enough confirmations
     */
    public function hasEnoughConfirmations(): bool
    {
        return $this->confirmations >= $this->required_confirmations;
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, $this->currency === 'SOL' ? 9 : 6) . ' ' . $this->currency;
    }

    /**
     * Get formatted USD amount
     */
    public function getFormattedUsdAmountAttribute(): string
    {
        return '$' . number_format($this->amount_usd, 2);
    }

    /**
     * Get blockchain explorer URL
     */
    public function getExplorerUrlAttribute(): string
    {
        return "https://solscan.io/tx/{$this->transaction_hash}";
    }

    /**
     * Scope for deposits
     */
    public function scopeDeposits($query)
    {
        return $query->where('transaction_type', 'deposit');
    }

    /**
     * Scope for withdrawals
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('transaction_type', 'withdrawal');
    }

    /**
     * Scope for confirmed transactions
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for specific currency
     */
    public function scopeCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for today's transactions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for recent transactions
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
} 