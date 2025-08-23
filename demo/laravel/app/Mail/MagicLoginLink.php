<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\MagicLoginToken;

class MagicLoginLink extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;
    public $expiryMinutes;
    public $loginUrl;

    /**
     * Create a new message instance.
     *
     * @param MagicLoginToken $magicToken
     */
    public function __construct(MagicLoginToken $magicToken)
    {
        $this->token = $magicToken->token;
        $this->email = $magicToken->email;
        $this->expiryMinutes = $magicToken->expires_at->diffInMinutes(now());
        $this->loginUrl = url("/auth/magic-login?token=" . $this->token);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('🚀 Your Aviator Game Magic Login Link')
                    ->view('emails.magic-login')
                    ->with([
                        'loginUrl' => $this->loginUrl,
                        'expiryMinutes' => $this->expiryMinutes,
                        'email' => $this->email,
                        'appName' => config('app.name', 'Aviator Game')
                    ]);
    }
} 