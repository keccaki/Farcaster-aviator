<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MagicLoginToken extends Model
{
    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    /**
     * Generate a secure magic login token
     *
     * @param string $email
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return self
     */
    public static function generateToken(string $email, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        // Clean up any existing unused tokens for this email
        self::where('email', $email)
            ->where('used', false)
            ->delete();

        return self::create([
            'email' => $email,
            'token' => self::generateSecureToken(),
            'expires_at' => Carbon::now()->addMinutes(15), // 15-minute expiry for security
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Generate a cryptographically secure token
     *
     * @return string
     */
    private static function generateSecureToken(): string
    {
        return hash('sha256', Str::random(64) . microtime(true) . random_bytes(32));
    }

    /**
     * Verify and consume a magic login token
     *
     * @param string $token
     * @return User|null
     */
    public static function verifyToken(string $token): ?User
    {
        $magicToken = self::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$magicToken) {
            return null;
        }

        // Find the user
        $user = User::where('email', $magicToken->email)->first();
        
        if (!$user) {
            return null;
        }

        // Mark token as used (single-use security)
        $magicToken->update(['used' => true]);

        return $user;
    }

    /**
     * Clean up expired tokens (for scheduled cleanup)
     *
     * @return int Number of deleted tokens
     */
    public static function cleanupExpiredTokens(): int
    {
        return self::where('expires_at', '<', Carbon::now())->delete();
    }

    /**
     * Check if token is valid (without consuming it)
     *
     * @param string $token
     * @return bool
     */
    public static function isValidToken(string $token): bool
    {
        return self::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    /**
     * Get remaining time for token
     *
     * @param string $token
     * @return int|null Minutes remaining
     */
    public static function getTokenExpiryMinutes(string $token): ?int
    {
        $magicToken = self::where('token', $token)
            ->where('used', false)
            ->first();

        if (!$magicToken || $magicToken->expires_at < Carbon::now()) {
            return null;
        }

        return Carbon::now()->diffInMinutes($magicToken->expires_at);
    }
} 