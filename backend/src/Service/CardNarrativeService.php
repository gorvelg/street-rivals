<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Card;
use App\Entity\CardNarrativePhrase;
use App\Enum\NarrativeContext;
use App\Enum\NarrativePhraseType;
use App\Repository\CardNarrativePhraseRepository;

final readonly class CardNarrativeService
{
    public function __construct(
        private CardNarrativePhraseRepository $repository,
    ) {
    }

    /**
     * @return list<array{
     *     type: string,
     *     text: string
     * }>
     */
    public function buildCardLines(
        Card $card,
        string $pilotName,
        string $opponentName,
        NarrativeContext $context,
        int $value,
        string $stat,
        string $seed,
    ): array {
        $lines = [];

        /*
         * Ligne d'activation.
         */
        $activation =
            $this->pickPhrase(
                card: $card,
                type: NarrativePhraseType::ACTIVATION,
                context: $context,
                seed: $seed . ':activation',
            );

        if ($activation !== null) {
            $lines[] = [
                'type' => 'card_activation',
                'text' => $this->render(
                    $activation->getText(),
                    $card,
                    $pilotName,
                    $opponentName,
                    $value,
                    $stat,
                ),
            ];
        } else {
            /*
             * Fallback automatique.
             *
             * Une nouvelle carte fonctionnera donc même
             * si aucune phrase n'a encore été configurée.
             */
            $lines[] = [
                'type' => 'card_activation',
                'text' => sprintf(
                    '%s utilise %s.',
                    $pilotName,
                    $card->getName(),
                ),
            ];
        }

        /*
         * Ligne expliquant l'effet.
         */
        $effect =
            $this->pickPhrase(
                card: $card,
                type: NarrativePhraseType::EFFECT,
                context: $context,
                seed: $seed . ':effect',
            );

        if ($effect !== null) {
            $lines[] = [
                'type' => 'card_effect',
                'text' => $this->render(
                    $effect->getText(),
                    $card,
                    $pilotName,
                    $opponentName,
                    $value,
                    $stat,
                ),
            ];
        } else {
            $lines[] = [
                'type' => 'card_effect',
                'text' => sprintf(
                    '%s : %s %s.',
                    $card->getName(),
                    $this->formatSigned($value),
                    $this->statLabel($stat),
                ),
            ];
        }

        /*
         * La réaction est facultative.
         */
        $reaction =
            $this->pickPhrase(
                card: $card,
                type: NarrativePhraseType::REACTION,
                context: $context,
                seed: $seed . ':reaction',
            );

        if ($reaction !== null) {
            $lines[] = [
                'type' => 'card_reaction',
                'text' => $this->render(
                    $reaction->getText(),
                    $card,
                    $pilotName,
                    $opponentName,
                    $value,
                    $stat,
                ),
            ];
        }

        return $lines;
    }

    private function pickPhrase(
        Card $card,
        NarrativePhraseType $type,
        NarrativeContext $context,
        string $seed,
    ): ?CardNarrativePhrase {
        $phrases =
            $this->repository->findCandidates(
                $card,
                $type,
                $context,
            );

        if ($phrases === []) {
            return null;
        }

        $totalWeight = 0;

        foreach ($phrases as $phrase) {
            $totalWeight +=
                $phrase->getWeight();
        }

        if ($totalWeight <= 0) {
            return null;
        }

        /*
         * Sélection pseudo-aléatoire déterministe.
         *
         * Même seed = même phrase.
         *
         * Très important pour les replays :
         * le texte ne change pas simplement parce
         * que le replay est recalculé.
         */
        $hash =
            (int) sprintf(
                '%u',
                crc32($seed),
            );

        $target =
            $hash % $totalWeight;

        $cursor = 0;

        foreach ($phrases as $phrase) {
            $cursor +=
                $phrase->getWeight();

            if ($target < $cursor) {
                return $phrase;
            }
        }

        return $phrases[array_key_last($phrases)];
    }

    private function render(
        string $template,
        Card $card,
        string $pilotName,
        string $opponentName,
        int $value,
        string $stat,
    ): string {
        return strtr(
            $template,
            [
                '{pilot}' =>
                    $pilotName,

                '{opponent}' =>
                    $opponentName,

                '{card}' =>
                    (string) $card->getName(),

                '{value}' =>
                    $this->formatSigned($value),

                '{stat}' =>
                    $this->statLabel($stat),
            ],
        );
    }

    private function formatSigned(
        int $value,
    ): string {
        if ($value > 0) {
            return '+' . $value;
        }

        return (string) $value;
    }

    private function statLabel(
        string $stat,
    ): string {
        return match ($stat) {
            'speed' =>
            'vitesse',

            'acceleration' =>
            'accélération',

            'grip' =>
            'grip',

            'solidity' =>
            'solidité',

            default =>
            $stat,
        };
    }
}
