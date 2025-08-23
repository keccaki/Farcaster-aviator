<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_pair',
        'rate',
        'source',
        'last_updated'
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'last_updated' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the base currency from the pair
     */
    public function getBaseCurrencyAttribute(): string
    {
        return explode('/', $this->currency_pair)[0];
    }

    /**
     * Get the quote currency from the pair
     */
    public function getQuoteCurrencyAttribute(): string
    {
        return explode('/', $this->currency_pair)[1];
    }

    /**
     * Check if rate is fresh (updated within last 5 minutes)
     */
    public function isFresh(): bool
    {
        return $this->last_updated && $this->last_updated->diffInMinutes(now()) < 5;
    }

    /**
     * Check if rate is stale (older than 5 minutes)
     */
    public function isStale(): bool
    {
        return !$this->isFresh();
    }

    /**
     * Get formatted rate
     */
    public function getFormattedRateAttribute(): string
    {
        return '$' . number_format($this->rate, 2);
    }

    /**
     * Get age of the rate
     */
    public function getAgeAttribute(): string
    {
        if (!$this->last_updated) {
            return 'Unknown';
        }

        return $this->last_updated->diffForHumans();
    }

    /**
     * Scope for specific currency pair
     */
    public function scopePair($query, string $pair)
    {
        return $query->where('currency_pair', $pair);
    }

    /**
     * Scope for fresh rates
     */
    public function scopeFresh($query)
    {
        return $query->where('last_updated', '>', now()->subMinutes(5));
    }

    /**
     * Scope for stale rates
     */
    public function scopeStale($query)
    {
        return $query->where('last_updated', '<=', now()->subMinutes(5));
    }

    /**
     * Scope for specific source
     */
    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Get rate for a specific currency pair
     */
    public static function getRate(string $pair): ?float
    {
        $rate = static::where('currency_pair', $pair)
                     ->where('last_updated', '>', now()->subMinutes(5))
                     ->first();

        return $rate ? (float) $rate->rate : null;
    }

    /**
     * Update or create rate for a currency pair
     */
    public static function updateRate(string $pair, float $rate, string $source = 'api'): self
    {
        return static::updateOrCreate(
            ['currency_pair' => $pair],
            [
                'rate' => $rate,
                'source' => $source,
                'last_updated' => now()
            ]
        );
    }

    /**
     * Get all supported currency pairs
     */
    public static function getSupportedPairs(): array
    {
        return ['SOL/USD', 'USDT/USD'];
    }

    /**
     * Convert amount from one currency to another
     */
    public static function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $pair = "{$fromCurrency}/{$toCurrency}";
        $rate = static::getRate($pair);

        if ($rate) {
            return $amount * $rate;
        }

        // Try reverse pair
        $reversePair = "{$toCurrency}/{$fromCurrency}";
        $reverseRate = static::getRate($reversePair);

        if ($reverseRate) {
            return $amount / $reverseRate;
        }

        // Fallback to default rates
        $defaultRates = [
            'SOL/USD' => 100.0,
            'USDT/USD' => 1.0
        ];

        return $amount * ($defaultRates[$pair] ?? 1.0);
    }
} 