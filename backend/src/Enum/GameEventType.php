<?php

declare(strict_types=1);

namespace App\Enum;

enum GameEventType: string
{
    case USER_REGISTERED = 'user_registered';
    case LOGIN_SUCCEEDED = 'login_succeeded';

    case CAR_CREATED = 'car_created';
    case LEVEL_UP = 'level_up';

    case CARD_SELECTED = 'card_selected';
    case CARD_UPGRADED = 'card_upgraded';

    case DUEL_COMPLETED = 'duel_completed';
    case ADMIN_COOLDOWN_RESET = 'admin_cooldown_reset';
    case ADMIN_CARD_UPDATED = 'admin_card_updated';
    case ADMIN_GAME_SETTINGS_UPDATED = 'admin_game_settings_updated';
    case EQUIPMENT_EQUIPPED = 'equipment_equipped';
}
