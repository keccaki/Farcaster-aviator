<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoSecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'risk_score',
        'security_flags',
        'geo_location',
        'device_fingerprint'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'risk_score' => 'integer',
        'security_flags' => 'array',
        'geo_location' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the security log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is a high-risk event
     */
    public function isHighRisk(): bool
    {
        return $this->risk_score >= 70;
    }

    /**
     * Check if this is a medium-risk event
     */
    public function isMediumRisk(): bool
    {
        return $this->risk_score >= 40 && $this->risk_score < 70;
    }

    /**
     * Check if this is a low-risk event
     */
    public function isLowRisk(): bool
    {
        return $this->risk_score < 40;
    }

    /**
     * Get risk level as string
     */
    public function getRiskLevelAttribute(): string
    {
        if ($this->isHighRisk()) {
            return 'high';
        } elseif ($this->isMediumRisk()) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get country from geo location
     */
    public function getCountryAttribute(): string
    {
        return $this->geo_location['country'] ?? 'Unknown';
    }

    /**
     * Get city from geo location
     */
    public function getCityAttribute(): string
    {
        return $this->geo_location['city'] ?? 'Unknown';
    }

    /**
     * Scope for high-risk events
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>=', 70);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific action
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for recent events
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for specific IP address
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }
} 