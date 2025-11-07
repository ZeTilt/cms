<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_fr', [$this, 'formatDateFr']),
        ];
    }

    /**
     * Formate une date en français
     *
     * @param \DateTimeInterface|string|int $date
     * @param string $format Format personnalisé ou 'full', 'long', 'medium', 'short'
     */
    public function formatDateFr($date, string $format = 'full'): string
    {
        if (!$date instanceof \DateTimeInterface) {
            if (is_string($date)) {
                $date = new \DateTime($date);
            } elseif (is_int($date)) {
                $date = new \DateTime('@' . $date);
            } else {
                return '';
            }
        }

        // Formats prédéfinis
        $formats = [
            'full' => 'l j F Y à H:i',       // lundi 9 décembre 2024 à 14:30
            'long' => 'l j F Y',              // lundi 9 décembre 2024
            'medium' => 'j F Y',              // 9 décembre 2024
            'short' => 'd/m/Y',               // 09/12/2024
            'time' => 'H:i',                  // 14:30
            'datetime' => 'd/m/Y à H:i',      // 09/12/2024 à 14:30
        ];

        // Si le format est dans les prédéfinis, on l'utilise
        $formatToUse = $formats[$format] ?? $format;

        // Formater la date
        $formatted = $date->format($formatToUse);

        // Traductions des jours
        $days = [
            'Monday' => 'lundi',
            'Tuesday' => 'mardi',
            'Wednesday' => 'mercredi',
            'Thursday' => 'jeudi',
            'Friday' => 'vendredi',
            'Saturday' => 'samedi',
            'Sunday' => 'dimanche',
        ];

        // Traductions des mois
        $months = [
            'January' => 'janvier',
            'February' => 'février',
            'March' => 'mars',
            'April' => 'avril',
            'May' => 'mai',
            'June' => 'juin',
            'July' => 'juillet',
            'August' => 'août',
            'September' => 'septembre',
            'October' => 'octobre',
            'November' => 'novembre',
            'December' => 'décembre',
        ];

        // Remplacer les jours et mois anglais par français
        $formatted = str_replace(array_keys($days), array_values($days), $formatted);
        $formatted = str_replace(array_keys($months), array_values($months), $formatted);

        return $formatted;
    }
}
