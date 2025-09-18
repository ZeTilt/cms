<?php

namespace App\DataFixtures;

use App\Entity\Page;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class ZFinalPageFixtures extends Fixture
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Get the first user as author
        $author = $manager->getRepository(User::class)->findOneBy([]);
        if (!$author) {
            // Create a temporary admin user if none exists
            $author = new User();
            $author->setEmail('admin@temp.com')
                   ->setFirstName('Admin')
                   ->setLastName('Temp')
                   ->setRoles(['ROLE_ADMIN'])
                   ->setPassword('$2y$13$temp');
            $manager->persist($author);
            $manager->flush();
        }

        $pages = [
            [
                'title' => 'Apnée',
                'slug' => 'apnee',
                'content' => '<div class="prose max-w-none">
<h1>Section Apnée</h1>

<div class="bg-gradient-to-r from-cyan-500 to-blue-500 text-white p-6 rounded-lg mb-8">
    <h2 class="text-2xl font-semibold mb-2">🫁 Découvrez l\'apnée au CSV</h2>
    <p class="text-cyan-100">Environ 50 membres pratiquent l\'apnée dans notre section dédiée</p>
</div>

<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-8">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-red-700">
                <strong>Section complète pour l\'année 2025-2026</strong><br>
                Les inscriptions sont fermées pour cette saison.
            </p>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-xl font-semibold mb-4">Entraînements</h2>

        <div class="space-y-4">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-blue-700">Jeudi</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Principal</span>
                </div>
                <p class="text-sm text-gray-600 mb-1">21h00 - 22h30</p>
                <p class="text-sm text-gray-500">Septembre à juin</p>
                <p class="text-sm font-medium mt-2">Piscine d\'Elven</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-green-700">Mercredi</h3>
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Avancé</span>
                </div>
                <p class="text-sm text-gray-600 mb-1">19h45 - 21h30</p>
                <p class="text-sm text-gray-500">Hors débutants</p>
                <p class="text-sm font-medium mt-2">Piscine d\'Elven</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-orange-700">Lundi</h3>
                    <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">Compétition</span>
                </div>
                <p class="text-sm text-gray-600 mb-1">20h00 - 21h30</p>
                <p class="text-sm text-gray-500">Compétiteurs uniquement</p>
                <p class="text-sm font-medium mt-2">Piscine d\'Elven</p>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">Formation</h2>

        <div class="space-y-4">
            <div class="bg-cyan-50 p-4 rounded-lg">
                <h3 class="font-semibold text-cyan-800 mb-3">Encadrement qualifié</h3>
                <ul class="text-sm text-cyan-700 space-y-1">
                    <li>• Moniteurs IE1 à MEF1</li>
                    <li>• Techniques statiques et dynamiques</li>
                    <li>• Apnée bi-palmes, mono-palme, sans palmes</li>
                </ul>
            </div>

            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-800 mb-3">Niveaux proposés</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Pass\' Apnéiste (débutant)</li>
                    <li>• Apnéiste Bronze, Argent, Or</li>
                    <li>• Apnéiste Expert Eau Libre</li>
                </ul>
            </div>
        </div>

        <h3 class="text-lg font-semibold mt-6 mb-4">Matériel requis</h3>
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-semibold mb-2">Équipement minimum :</h4>
            <ul class="text-sm space-y-1">
                <li class="flex items-center">
                    <span class="text-blue-500 mr-2">•</span>
                    Palmes
                </li>
                <li class="flex items-center">
                    <span class="text-blue-500 mr-2">•</span>
                    Masque
                </li>
                <li class="flex items-center">
                    <span class="text-blue-500 mr-2">•</span>
                    Tuba
                </li>
                <li class="flex items-center">
                    <span class="text-blue-500 mr-2">•</span>
                    Lestage
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="mt-8">
    <h2 class="text-xl font-semibold mb-6">Activités spéciales</h2>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-purple-700 mb-3">🏊‍♀️ Sessions fosse</h3>
            <p class="text-sm text-gray-600">
                Entraînements en profondeur dans des fosses spécialisées pour travailler l\'apnée en profondeur.
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-green-700 mb-3">🌊 Milieu naturel</h3>
            <p class="text-sm text-gray-600">
                Sorties en mer pour pratiquer l\'apnée dans des conditions réelles.
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-red-700 mb-3">🆘 Ateliers sécurité</h3>
            <p class="text-sm text-gray-600">
                Formation aux techniques de sécurité spécifiques à l\'apnée.
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-yellow-700 mb-3">🧘‍♀️ Initiation yoga</h3>
            <p class="text-sm text-gray-600">
                Techniques de relaxation et de respiration pour améliorer les performances.
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-700 mb-3">🏊‍♂️ Mono-palme</h3>
            <p class="text-sm text-gray-600">
                Initiation et perfectionnement à la technique mono-palme.
            </p>
        </div>
    </div>
</div>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h3 class="text-lg font-semibold mb-4">🌊 Rejoignez-nous la saison prochaine !</h3>
    <p class="mb-4">
        La section apnée est complète cette année, mais n\'hésitez pas à nous contacter pour être informé des ouvertures pour la saison 2026-2027.
    </p>
    <div class="flex gap-3">
        <a href="/contact" class="bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark">
            Nous contacter
        </a>
        <a href="/calendrier" class="border border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white">
            Voir les activités
        </a>
    </div>
</div>
</div>',
                'excerpt' => 'Section apnée du CSV : 50 membres, 3 créneaux hebdomadaires à Elven. Formations du Pass\' Apnéiste à Expert.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Apnée - Club Subaquatique des Vénètes',
                'meta_description' => 'Section apnée CSV : entraînements Elven, niveaux Pass\' Apnéiste à Expert, encadrement qualifié IE1-MEF1.',
                'tags' => ['apnée', 'entraînement', 'piscine', 'Elven', 'compétition']
            ],
            [
                'title' => 'La piscine',
                'slug' => 'la-piscine',
                'content' => '<div class="prose max-w-none">
<h1>Activités Piscine</h1>

<div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-6 rounded-lg mb-8">
    <h2 class="text-2xl font-semibold mb-2">🏊‍♀️ Entraînements en piscine</h2>
    <p class="text-blue-100">Formations et perfectionnement technique dans nos trois piscines partenaires</p>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white border-2 border-blue-200 rounded-lg p-6 text-center">
        <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-blue-700">Kercado</h3>
        <p class="text-sm text-gray-600 mt-2">Piscine municipale de Vannes</p>
    </div>

    <div class="bg-white border-2 border-green-200 rounded-lg p-6 text-center">
        <div class="bg-green-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-green-700">Elven</h3>
        <p class="text-sm text-gray-600 mt-2">Piscine intercommunale</p>
    </div>

    <div class="bg-white border-2 border-purple-200 rounded-lg p-6 text-center">
        <div class="bg-purple-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-purple-700">Grandchamp</h3>
        <p class="text-sm text-gray-600 mt-2">Piscine du lycée</p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-xl font-semibold mb-6">Types d\'entraînements</h2>

        <div class="space-y-4">
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <h3 class="font-semibold text-blue-800">Formation Niveau 1</h3>
                <p class="text-sm text-blue-700 mt-1">
                    Apprentissage des bases : respiration au détendeur, vidage de masque, stabilisation
                </p>
                <div class="mt-2 text-xs text-blue-600">
                    📅 Octobre à mai
                </div>
            </div>

            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <h3 class="font-semibold text-green-800">Formation Niveau 2</h3>
                <p class="text-sm text-green-700 mt-1">
                    Techniques avancées : remontée assistée, navigation, autonomie
                </p>
                <div class="mt-2 text-xs text-green-600">
                    📅 Octobre-novembre et avril-mai
                </div>
            </div>

            <div class="bg-purple-50 border-l-4 border-purple-400 p-4">
                <h3 class="font-semibold text-purple-800">Guide de palanquée</h3>
                <p class="text-sm text-purple-700 mt-1">
                    Formation d\'encadrant : sauvetage, organisation, pédagogie
                </p>
                <div class="mt-2 text-xs text-purple-600">
                    📅 Octobre à mai
                </div>
            </div>

            <div class="bg-cyan-50 border-l-4 border-cyan-400 p-4">
                <h3 class="font-semibold text-cyan-800">Apnée</h3>
                <p class="text-sm text-cyan-700 mt-1">
                    Statique, dynamique, techniques bi-palmes et mono-palme
                </p>
                <div class="mt-2 text-xs text-cyan-600">
                    📅 Septembre à juin
                </div>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-6">Organisation des séances</h2>

        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="font-semibold mb-4 text-gray-800">🕐 Créneaux horaires</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="font-medium">Mardi</span>
                        <span class="text-gray-600">20h00 - 22h00</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="font-medium">Jeudi</span>
                        <span class="text-gray-600">20h00 - 22h00</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="font-medium">Samedi</span>
                        <span class="text-gray-600">14h00 - 16h00</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">
                    Horaires détaillés disponibles selon les piscines
                </p>
            </div>

            <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
                <h3 class="font-semibold mb-3 text-orange-800">👥 Encadrement</h3>
                <ul class="text-sm text-orange-700 space-y-1">
                    <li>• Moniteurs FFESSM qualifiés</li>
                    <li>• Guides de palanquée expérimentés</li>
                    <li>• Adaptation aux niveaux de chacun</li>
                    <li>• Groupes de 4-6 plongeurs maximum</li>
                </ul>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <h3 class="font-semibold mb-3 text-yellow-800">🎯 Objectifs</h3>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>• Maîtrise technique en milieu protégé</li>
                    <li>• Préparation aux plongées en mer</li>
                    <li>• Perfectionnement des gestes</li>
                    <li>• Condition physique et aisance aquatique</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h3 class="text-lg font-semibold mb-4">💧 L\'étape essentielle de votre formation</h3>
    <p class="mb-4">
        La piscine est le lieu idéal pour acquérir et perfectionner les techniques de plongée en toute sécurité, avant de découvrir les merveilles sous-marines en milieu naturel.
    </p>
    <div class="flex gap-3">
        <a href="/formation-niveau-1" class="bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark">
            Commencer ma formation
        </a>
        <a href="/contact" class="border border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white">
            Plus d\'infos
        </a>
    </div>
</div>
</div>',
                'excerpt' => 'Entraînements piscine dans nos 3 sites : Kercado, Elven, Grandchamp. Formations N1, N2, GP et apnée.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'La piscine - Club Subaquatique des Vénètes',
                'meta_description' => 'Entraînements piscine CSV : Kercado, Elven, Grandchamp. Formations plongée et apnée avec moniteurs qualifiés.',
                'tags' => ['piscine', 'formation', 'entraînement', 'technique']
            ],
            [
                'title' => 'Station de gonflage',
                'slug' => 'gonflage',
                'content' => '<div class="prose max-w-none">
<h1>Station de gonflage</h1>

<div class="bg-gradient-to-r from-gray-600 to-gray-800 text-white p-6 rounded-lg mb-8">
    <h2 class="text-2xl font-semibold mb-2">⚗️ Station Nitrox et Trimix</h2>
    <p class="text-gray-100">Service de gonflage professionnel ouvert aux plongeurs extérieurs qualifiés</p>
</div>

<div class="grid md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-xl font-semibold mb-6">Services proposés</h2>

        <div class="space-y-4">
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <h3 class="font-semibold text-blue-800 flex items-center">
                    <span class="mr-2">💨</span>
                    Air comprimé
                </h3>
                <p class="text-sm text-blue-700 mt-1">
                    Gonflage air standard pour toutes vos plongées
                </p>
                <div class="mt-2 text-xs text-blue-600 font-medium">
                    €0.002 / litre
                </div>
            </div>

            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <h3 class="font-semibold text-green-800 flex items-center">
                    <span class="mr-2">🫧</span>
                    Nitrox
                </h3>
                <p class="text-sm text-green-700 mt-1">
                    Mélanges enrichis en oxygène pour plongées plus sûres
                </p>
                <div class="mt-2 text-xs text-green-600 font-medium">
                    Seules les bouteilles Nitrox autorisées
                </div>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <h3 class="font-semibold text-yellow-800 flex items-center">
                    <span class="mr-2">⚡</span>
                    Trimix
                </h3>
                <p class="text-sm text-yellow-700 mt-1">
                    Mélanges ternaires pour plongées techniques profondes
                </p>
                <div class="mt-2 text-xs text-yellow-600 font-medium">
                    Pour plongeurs techniques qualifiés
                </div>
            </div>
        </div>

        <h3 class="text-lg font-semibold mt-8 mb-4">Conditions d\'accès</h3>
        <div class="bg-orange-50 p-4 rounded-lg">
            <ul class="text-sm space-y-2">
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-orange-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Plongeurs extérieurs qualifiés</span>
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-orange-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Licence FFESSM en cours de validité</span>
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-orange-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Qualification Nitrox/Trimix selon besoins</span>
                </li>
            </ul>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-6">Tarification</h2>

        <div class="space-y-4">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold mb-3">Gaz de base (par litre)</h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="font-medium">Air</span>
                        <span class="text-green-600 font-bold">€0.002</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-orange-50 rounded">
                        <span class="font-medium">Oxygène</span>
                        <span class="text-orange-600 font-bold">€0.02</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-yellow-50 rounded">
                        <span class="font-medium">Hélium</span>
                        <span class="text-yellow-600 font-bold">€0.042</span>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold mb-3">Exemples de gonflage</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>15L 220b Nx32</span>
                        <span class="font-semibold">€15</span>
                    </div>
                    <div class="flex justify-between">
                        <span>15L 220b Nx36</span>
                        <span class="font-semibold">€18</span>
                    </div>
                    <div class="flex justify-between">
                        <span>7L 200b O₂</span>
                        <span class="font-semibold">€28</span>
                    </div>
                    <div class="flex justify-between">
                        <span>S80 (11.1L) 200b Nx50</span>
                        <span class="font-semibold">€19</span>
                    </div>
                </div>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-semibold mb-3">Mélanges Trimix</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>15L 220b Tx18/40</span>
                        <span class="font-semibold">€63.50</span>
                    </div>
                    <div class="flex justify-between">
                        <span>2x12L 220b Tx18/40</span>
                        <span class="font-semibold">€101.50</span>
                    </div>
                </div>
                <p class="text-xs text-red-600 mt-2">
                    Tarifs incluant hélium, oxygène et analyse
                </p>
            </div>
        </div>

        <div class="bg-gray-100 p-4 rounded-lg mt-6">
            <h3 class="font-semibold mb-2 text-gray-800">📞 Contact</h3>
            <div class="text-sm">
                <p class="font-medium">Claudio Pascual</p>
                <p class="text-gray-600">Responsable station de gonflage</p>
                <p class="text-blue-600 font-medium">06 75 75 48 26</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mt-8">
    <h3 class="font-semibold mb-3 text-yellow-800">⚙️ Équipement professionnel</h3>
    <div class="grid md:grid-cols-2 gap-4 text-sm">
        <div>
            <h4 class="font-semibold mb-2">Matériel de mélange</h4>
            <ul class="space-y-1 text-yellow-700">
                <li>• Compresseurs haute pression</li>
                <li>• Système de mélange automatisé</li>
                <li>• Analyseurs O₂ et He</li>
            </ul>
        </div>
        <div>
            <h4 class="font-semibold mb-2">Contrôle qualité</h4>
            <ul class="space-y-1 text-yellow-700">
                <li>• Analyse systématique des mélanges</li>
                <li>• Traçabilité des gonflages</li>
                <li>• Maintenance préventive régulière</li>
            </ul>
        </div>
    </div>
</div>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h3 class="text-lg font-semibold mb-4">⚗️ Service professionnel de gonflage</h3>
    <p class="mb-4">
        Notre station équipée vous propose des mélanges gazeux de qualité pour toutes vos plongées, du loisir à la plongée technique.
    </p>
    <div class="flex gap-3">
        <a href="tel:0675754826" class="bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark">
            Contacter Claudio
        </a>
        <a href="/contact" class="border border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white">
            Infos générales
        </a>
    </div>
</div>
</div>',
                'excerpt' => 'Station de gonflage Nitrox/Trimix. Air €0.002/L, O₂ €0.02/L, He €0.042/L. Contact : Claudio 06 75 75 48 26.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Station de gonflage - Club Subaquatique des Vénètes',
                'meta_description' => 'Station gonflage Nitrox/Trimix CSV. Tarifs compétitifs, équipement professionnel. Contact Claudio Pascual.',
                'tags' => ['gonflage', 'nitrox', 'trimix', 'station', 'technique']
            ]
        ];

        foreach ($pages as $pageData) {
            $page = new Page();
            $page->setTitle($pageData['title'])
                 ->setSlug($pageData['slug'])
                 ->setContent($pageData['content'])
                 ->setExcerpt($pageData['excerpt'])
                 ->setTemplatePath($pageData['template_path'])
                 ->setType('page')
                 ->setStatus('published')
                 ->setMetaTitle($pageData['meta_title'])
                 ->setMetaDescription($pageData['meta_description'])
                 ->setTags($pageData['tags'])
                 ->setAuthor($author)
                 ->setCreatedAt(new \DateTimeImmutable())
                 ->setUpdatedAt(new \DateTimeImmutable());

            $manager->persist($page);
        }

        $manager->flush();
    }
}