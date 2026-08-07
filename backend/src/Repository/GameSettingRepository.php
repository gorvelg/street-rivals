<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GameSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameSetting>
 */
final class GameSettingRepository
    extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct(
            $registry,
            GameSetting::class,
        );
    }

    public function findOneByKey(
        string $key,
    ): ?GameSetting {
        return $this->findOneBy([
            'key' => $key,
        ]);
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, GameSetting>
     */
    public function findIndexedByKeys(
        array $keys,
    ): array {
        if ($keys === []) {
            return [];
        }

        /** @var list<GameSetting> $settings */
        $settings = $this
            ->createQueryBuilder('setting')
            ->andWhere(
                'setting.key IN (:keys)',
            )
            ->setParameter(
                'keys',
                $keys,
            )
            ->getQuery()
            ->getResult();

        $indexedSettings = [];

        foreach ($settings as $setting) {
            $indexedSettings[
            $setting->getKey()
            ] = $setting;
        }

        return $indexedSettings;
    }
}
