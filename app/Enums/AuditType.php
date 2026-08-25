<?php

namespace App\Enums;

enum AuditType: string
{
    case Purchase = 'purchase';
    case AchievementUnlocked = 'achievement_unlocked';
    case BadgeUnlocked = 'badge_unlocked';
    case BankAccountLinked = 'bank_account_linked';
    case PayoutAttempted = 'payout_attempted';
    case PayoutVerified = 'payout_verified';
}
