<?php

namespace App\Service;

class LocationData
{
    public const VILLES = [
        '── Grand Tunis ──' => 'disabled',
        'Tunis' => 'Tunis',
        'Ariana' => 'Ariana',
        'Ben Arous' => 'Ben Arous',
        'La Manouba' => 'La Manouba',
        'La Marsa' => 'La Marsa',
        'Le Bardo' => 'Le Bardo',
        'Carthage' => 'Carthage',
        'Hammam-Lif' => 'Hammam-Lif',

        '── Nord ──' => 'disabled',
        'Bizerte' => 'Bizerte',
        'Béja' => 'Béja',
        'Jendouba' => 'Jendouba',
        'Le Kef' => 'Le Kef',
        'Siliana' => 'Siliana',
        'Tabarka' => 'Tabarka',
        'Ain Draham' => 'Ain Draham',

        '── Cap Bon ──' => 'disabled',
        'Nabeul' => 'Nabeul',
        'Hammamet' => 'Hammamet',
        'Kelibia' => 'Kelibia',
        'Menzel Bourguiba' => 'Menzel Bourguiba',

        '── Centre-Est ──' => 'disabled',
        'Sousse' => 'Sousse',
        'Monastir' => 'Monastir',
        'Mahdia' => 'Mahdia',
        'Kairouan' => 'Kairouan',
        'Kasserine' => 'Kasserine',
        'Sidi Bouzid' => 'Sidi Bouzid',
        'Msaken' => 'Msaken',
        'Skanes' => 'Skanes',

        '── Sud-Est ──' => 'disabled',
        'Sfax' => 'Sfax',
        'Gabès' => 'Gabès',
        'Médenine' => 'Médenine',
        'Tataouine' => 'Tataouine',
        'Jerba' => 'Jerba',
        'Zarzis' => 'Zarzis',
        'Ben Gardane' => 'Ben Gardane',

        '── Sud-Ouest ──' => 'disabled',
        'Gafsa' => 'Gafsa',
        'Tozeur' => 'Tozeur',
        'Kébili' => 'Kébili',
        'Douz' => 'Douz',
        'El Hamma' => 'El Hamma',
    ];

    public const VILLES_CHOICES = [
        'Tunis', 'Ariana', 'Ben Arous', 'La Manouba', 'La Marsa', 'Le Bardo',
        'Carthage', 'Hammam-Lif', 'Bizerte', 'Béja', 'Jendouba', 'Le Kef',
        'Siliana', 'Tabarka', 'Ain Draham', 'Nabeul', 'Hammamet', 'Kelibia',
        'Menzel Bourguiba', 'Sousse', 'Monastir', 'Mahdia', 'Kairouan',
        'Kasserine', 'Sidi Bouzid', 'Msaken', 'Skanes', 'Sfax', 'Gabès',
        'Médenine', 'Tataouine', 'Jerba', 'Zarzis', 'Ben Gardane', 'Gafsa',
        'Tozeur', 'Kébili', 'Douz', 'El Hamma',
    ];

    public const UNIVERSITES = [
        '── Grand Tunis ──' => 'disabled',
        'Université de Tunis' => 'Université de Tunis',
        'Université de Carthage' => 'Université de Carthage',
        'Université de La Manouba' => 'Université de La Manouba',
        'Université de Tunis El Manar' => 'Université de Tunis El Manar',
        'Université Zitouna' => 'Université Zitouna',

        '── Nord ──' => 'disabled',
        'Université de Jendouba' => 'Université de Jendouba',

        '── Centre-Est ──' => 'disabled',
        'Université de Sousse' => 'Université de Sousse',
        'Université de Monastir' => 'Université de Monastir',
        'Université de Kairouan' => 'Université de Kairouan',

        '── Centre-Ouest ──' => 'disabled',
        'Université de Kasserine' => 'Université de Kasserine',
        'Université de Sidi Bouzid' => 'Université de Sidi Bouzid',

        '── Sud ──' => 'disabled',
        'Université de Sfax' => 'Université de Sfax',
        'Université de Gabès' => 'Université de Gabès',
        'Université de Gafsa' => 'Université de Gafsa',
    ];

    public const UNIVERSITES_CHOICES = [
        'Université de Tunis',
        'Université de Carthage',
        'Université de La Manouba',
        'Université de Tunis El Manar',
        'Université Zitouna',
        'Université de Jendouba',
        'Université de Sousse',
        'Université de Monastir',
        'Université de Kairouan',
        'Université de Kasserine',
        'Université de Sidi Bouzid',
        'Université de Sfax',
        'Université de Gabès',
        'Université de Gafsa',
    ];

    public static function getVillesChoices(): array
    {
        return self::VILLES_CHOICES;
    }

    public static function getChoiceMap(): array
    {
        return array_combine(self::VILLES_CHOICES, self::VILLES_CHOICES) ?: [];
    }

    public static function getUniversitiesChoiceMap(): array
    {
        return array_combine(self::UNIVERSITES_CHOICES, self::UNIVERSITES_CHOICES) ?: [];
    }

    /**
     * Liste groupée (optgroups) pour les selects inscription / profil.
     *
     * @return array<string, array<string, string>>
     */
    public static function getUniversitiesGroupedChoices(): array
    {
        return [
            'Grand Tunis' => [
                'Université de Tunis' => 'Université de Tunis',
                'Université de Carthage' => 'Université de Carthage',
                'Université de La Manouba' => 'Université de La Manouba',
                'Université de Tunis El Manar' => 'Université de Tunis El Manar',
                'Université Zitouna' => 'Université Zitouna',
            ],
            'Nord' => [
                'Université de Jendouba' => 'Université de Jendouba',
            ],
            'Centre-Est' => [
                'Université de Sousse' => 'Université de Sousse',
                'Université de Monastir' => 'Université de Monastir',
                'Université de Kairouan' => 'Université de Kairouan',
            ],
            'Centre-Ouest' => [
                'Université de Kasserine' => 'Université de Kasserine',
                'Université de Sidi Bouzid' => 'Université de Sidi Bouzid',
            ],
            'Sud' => [
                'Université de Sfax' => 'Université de Sfax',
                'Université de Gabès' => 'Université de Gabès',
                'Université de Gafsa' => 'Université de Gafsa',
            ],
        ];
    }
}
