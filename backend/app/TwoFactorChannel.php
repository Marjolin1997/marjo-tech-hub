<?php

namespace App;

enum TwoFactorChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case WhatsApp = 'whatsapp';

    public function requiresVerifiedPhone(): bool
    {
        return match ($this) {
            self::Email => false,
            self::Sms, self::WhatsApp => true,
        };
    }
}
