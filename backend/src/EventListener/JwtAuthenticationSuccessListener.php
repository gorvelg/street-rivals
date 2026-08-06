<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Enum\GameEventType;
use App\Service\GameEventTracker;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(
    event: 'lexik_jwt_authentication.on_authentication_success'
)]
final class JwtAuthenticationSuccessListener
{
    public function __construct(
        private readonly GameEventTracker $eventTracker,
    ) {
    }

    public function __invoke(
        AuthenticationSuccessEvent $event
    ): void {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $this->eventTracker->trackAndFlush(
            type: GameEventType::LOGIN_SUCCEEDED,
            user: $user,
            payload: [
                'authenticationMethod' => 'jwt',
            ],
        );
    }
}
