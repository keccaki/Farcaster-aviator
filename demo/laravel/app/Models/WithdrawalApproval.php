<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_transaction_id',
        'user_id',
        'amount',
        'approval_tier',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'expires_at'
    ];

    protected $casts = [
        'crypto_transaction_id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'decimal:9',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the crypto transaction that needs approval
     */
    public function cryptoTransaction(): BelongsTo
    {
        return $this->belongsTo(CryptoTransaction::class);
    }

    /**
     * Get the user requesting the withdrawal
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who approved the withdrawal
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if approval is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if approval is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if approval is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if approval has expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Check if approval requires auto processing
     */
    public function isAutoApproval(): bool
    {
        return $this->approval_tier === 'auto';
    }

    /**
     * Check if approval requires manual processing
     */
    public function isManualApproval(): bool
    {
        return $this->approval_tier === 'manual';
    }

    /**
     * Check if approval requires multi-signature
     */
    public function isMultiSigApproval(): bool
    {
        return $this->approval_tier === 'multi_sig';
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        $currency = $this->cryptoTransaction->currency ?? 'USD';
        $decimals = $currency === 'SOL' ? 9 : 6;
        return number_format($this->amount, $decimals) . ' ' . $currency;
    }

    /**
     * Get time remaining until expiration
     */
    public function getTimeRemainingAttribute(): ?string
    {
        if (!$this->expires_at || $this->expires_at->isPast()) {
            return null;
        }

        $diff = $this->expires_at->diffForHumans();
        return $diff;
    }

    /**
     * Approve the withdrawal
     */
    public function approve(int $approvedBy, string $reason = null): bool
    {
        if (!$this->isPending() || $this->isExpired()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason
        ]);

        return true;
    }

    /**
     * Reject the withdrawal
     */
    public function reject(int $rejectedBy, string $reason): bool
    {
        if (!$this->isPending() || $this->isExpired()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason
        ]);

        return true;
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'expired'
        ]);

        return true;
    }

    /**
     * Scope for pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for approved approvals
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected approvals
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for expired approvals
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere('expires_at', '<=', now());
    }

    /**
     * Scope for specific approval tier
     */
    public function scopeTier($query, string $tier)
    {
        return $query->where('approval_tier', $tier);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for recent approvals
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
} 