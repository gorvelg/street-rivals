<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Car;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserCarFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(
        ObjectManager $manager,
    ): void {
        /*
         * =====================================================
         * ADMIN
         * =====================================================
         */

        $admin = $this->createUser(
            manager: $manager,
            email: 'ggorvel@laposte.net',
            password: 'qwertyuiop',
            roles: [
                'ROLE_ADMIN',
            ],
        );

        $this->createCar(
            manager: $manager,
            user: $admin,
            pilotName: 'Admin Racer',
            color: '#e63946',
            money: 5000,
            level: 1,
            xp: 0,
            speed: 10,
            acceleration: 10,
            grip: 10,
            solidity: 10,
        );

        /*
         * =====================================================
         * PLAYER 1
         * =====================================================
         */

        $player1 = $this->createUser(
            manager: $manager,
            email: 'player1@street-rivals.test',
            password: 'Player1234!',
        );

        $this->createCar(
            manager: $manager,
            user: $player1,
            pilotName: 'Raven',
            color: '#457b9d',
            money: 1500,
            level: 1,
            xp: 0,
            speed: 10,
            acceleration: 10,
            grip: 10,
            solidity: 10,
        );

        /*
         * =====================================================
         * PLAYER 2
         * =====================================================
         */

        $player2 = $this->createUser(
            manager: $manager,
            email: 'player2@street-rivals.test',
            password: 'Player1234!',
        );

        $this->createCar(
            manager: $manager,
            user: $player2,
            pilotName: 'Blaze',
            color: '#f4a261',
            money: 2200,
            level: 2,
            xp: 0,
            speed: 11,
            acceleration: 12,
            grip: 10,
            solidity: 9,
        );

        /*
         * =====================================================
         * PLAYER 3
         * =====================================================
         */

        $player3 = $this->createUser(
            manager: $manager,
            email: 'player3@street-rivals.test',
            password: 'Player1234!',
        );

        $this->createCar(
            manager: $manager,
            user: $player3,
            pilotName: 'Ghost',
            color: '#8d99ae',
            money: 2300,
            level: 2,
            xp: 0,
            speed: 10,
            acceleration: 11,
            grip: 12,
            solidity: 9,
        );

        /*
         * =====================================================
         * PLAYER 4
         * =====================================================
         */

        $player4 = $this->createUser(
            manager: $manager,
            email: 'player4@street-rivals.test',
            password: 'Player1234!',
        );

        $this->createCar(
            manager: $manager,
            user: $player4,
            pilotName: 'Vortex',
            color: '#6a4c93',
            money: 3000,
            level: 3,
            xp: 0,
            speed: 13,
            acceleration: 12,
            grip: 11,
            solidity: 10,
        );

        /*
         * =====================================================
         * PLAYER 5
         * =====================================================
         */

        $player5 = $this->createUser(
            manager: $manager,
            email: 'player5@street-rivals.test',
            password: 'Player1234!',
        );

        $this->createCar(
            manager: $manager,
            user: $player5,
            pilotName: 'Tank',
            color: '#2a9d8f',
            money: 3000,
            level: 3,
            xp: 0,
            speed: 9,
            acceleration: 10,
            grip: 10,
            solidity: 14,
        );

        /*
         * =====================================================
         * PLAYER 6
         *
         * Un compte avec deux voitures pour tester le garage.
         * =====================================================
         */

        $player6 = $this->createUser(
            manager: $manager,
            email: 'player6@street-rivals.test',
            password: 'Player1234!',
        );

        $this->createCar(
            manager: $manager,
            user: $player6,
            pilotName: 'Nitro',
            color: '#ff006e',
            money: 3500,
            level: 4,
            xp: 0,
            speed: 14,
            acceleration: 13,
            grip: 11,
            solidity: 10,
        );

        $this->createCar(
            manager: $manager,
            user: $player6,
            pilotName: 'Apex',
            color: '#3a86ff',
            money: 2800,
            level: 2,
            xp: 0,
            speed: 11,
            acceleration: 10,
            grip: 13,
            solidity: 10,
        );

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(
        ObjectManager $manager,
        string $email,
        string $password,
        array $roles = [
            'ROLE_USER',
        ],
    ): User {
        $user = new User();

        $user
            ->setEmail($email)
            ->setRoles($roles)
            ->setIsActive(true);

        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $password,
            ),
        );

        $manager->persist($user);

        return $user;
    }

    private function createCar(
        ObjectManager $manager,
        User $user,
        string $pilotName,
        string $color,
        int $money,
        int $level,
        int $xp,
        int $speed,
        int $acceleration,
        int $grip,
        int $solidity,
    ): Car {
        $car = new Car();

        $car
            ->setUser($user)
            ->setPilotName($pilotName)
            ->setColor($color)
            ->setMoney($money)
            ->setLevel($level)
            ->setXp($xp)
            ->setSpeed($speed)
            ->setAcceleration($acceleration)
            ->setGrip($grip)
            ->setSolidity($solidity);

        $manager->persist($car);

        return $car;
    }
}
