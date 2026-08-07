<?php

declare(strict_types=1);

namespace App\Enum;

enum GameSettingKey: string
{
    case DUEL_DAILY_ATTACK_LIMIT =
    'duel_daily_attack_limit';

    case DUEL_PAIR_DAILY_LIMIT =
    'duel_pair_daily_limit';

    case DUEL_PAIR_COOLDOWN_SECONDS =
    'duel_pair_cooldown_seconds';
}
