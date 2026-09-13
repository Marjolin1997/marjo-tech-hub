<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\LoginVerificationCode;
use App\TwoFactorChannel;
use RuntimeException;

class TwoFactorDeliveryService
{
    public function send(User $user, TwoFactorChannel $channel, string $code): void
    {
        if (! $user->canUseTwoFactorChannel($channel)) {
            throw new RuntimeException('This verification channel is not available for your account.');
        }

        match ($channel) {
            TwoFactorChannel::Email => $user->notify(new LoginVerificationCode($code)),
            TwoFactorChannel::Sms => $this->sendSms($user, $code),
            TwoFactorChannel::WhatsApp => $this->sendWhatsApp($user, $code),
        };
    }

    private function sendSms(User $user, string $code): never
    {
        throw new RuntimeException('SMS verification is not configured yet. Choose email for now.');
    }

    private function sendWhatsApp(User $user, string $code): never
    {
        throw new RuntimeException('WhatsApp verification is not configured yet. Choose email for now.');
    }
}
