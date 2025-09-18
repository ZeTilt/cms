<?php

namespace App\DataFixtures;

use App\Entity\Page;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class ZAdditionalPageFixtures extends Fixture
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
                'title' => 'Formation Niveau 2 et 3',
                'slug' => 'formation-niveau-2-et-3',
                'content' => '<div class="prose max-w-none">
<h1>Formation Niveau 2 et 3</h1>

<div class="grid md:grid-cols-2 gap-8">
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6">
        <h2 class="text-xl font-semibold mb-4 text-blue-800">Niveau 2</h2>
        <div class="space-y-3 text-blue-700">
            <p><strong>Premier niveau d\'autonomie</strong> sous la responsabilité d\'un Directeur de Plongée</p>
            <div class="bg-white p-3 rounded">
                <h3 class="font-semibold">Profondeurs autorisées :</h3>
                <ul class="list-disc list-inside text-sm mt-1">
                    <li>0-20m en autonomie</li>
                    <li>Jusqu\'à 40m avec un guide</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-green-50 border-l-4 border-green-400 p-6">
        <h2 class="text-xl font-semibold mb-4 text-green-800">Niveau 3</h2>
        <div class="space-y-3 text-green-700">
            <p><strong>Autonomie complète</strong> jusqu\'à 40m entre plongeurs</p>
            <div class="bg-white p-3 rounded">
                <h3 class="font-semibold">Privilèges :</h3>
                <ul class="list-disc list-inside text-sm mt-1">
                    <li>Plongée autonome jusqu\'à 40m</li>
                    <li>Jusqu\'à 60m avec un Directeur de Plongée</li>
                </ul>
            </div>
            <p class="text-sm italic">Rarement organisé par le club</p>
        </div>
    </div>
</div>

<div class="mt-8">
    <h2 class="text-xl font-semibold mb-6">Formation Niveau 2</h2>

    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold mb-4">Prérequis</h3>
            <ul class="space-y-2">
                <li class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm">Être titulaire du Niveau 1</span>
                </li>
                <li class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm">Expérience recommandée : 12 plongées</span>
                </li>
            </ul>

            <h3 class="text-lg font-semibold mt-6 mb-4">Compétences acquises</h3>
            <ul class="space-y-2">
                <li class="flex items-center">
                    <span class="bg-club-orange text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">⬆</span>
                    <span class="text-sm">Remontée sur bouée</span>
                </li>
                <li class="flex items-center">
                    <span class="bg-club-orange text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">🆘</span>
                    <span class="text-sm">Remontée d\'assistance depuis 20m</span>
                </li>
                <li class="flex items-center">
                    <span class="bg-club-orange text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">👥</span>
                    <span class="text-sm">Guide de palanquée</span>
                </li>
                <li class="flex items-center">
                    <span class="bg-club-orange text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">🧭</span>
                    <span class="text-sm">Navigation sous-marine</span>
                </li>
            </ul>
        </div>

        <div>
            <h3 class="text-lg font-semibold mb-4">Organisation</h3>
            <div class="space-y-3">
                <div class="p-3 bg-gray-50 rounded">
                    <h4 class="font-semibold text-sm">Inscriptions</h4>
                    <p class="text-sm text-gray-600">Mi-septembre</p>
                </div>
                <div class="p-3 bg-gray-50 rounded">
                    <h4 class="font-semibold text-sm">Formation physique</h4>
                    <p class="text-sm text-gray-600">Piscine + apnée d\'octobre à mai</p>
                </div>
                <div class="p-3 bg-gray-50 rounded">
                    <h4 class="font-semibold text-sm">Plongées techniques</h4>
                    <p class="text-sm text-gray-600">Eau douce ou mer selon météo</p>
                </div>
                <div class="p-3 bg-gray-50 rounded">
                    <h4 class="font-semibold text-sm">Périodes intensives</h4>
                    <p class="text-sm text-gray-600">Octobre-novembre et avril-mai</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>',
                'excerpt' => 'Formations Niveau 2 et 3 FFESSM : autonomie progressive de 20m à 40m. Inscriptions en septembre.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Formation Niveau 2 et 3 - Club Subaquatique des Vénètes',
                'meta_description' => 'Formations plongée Niveau 2 et 3 au Club des Vénètes. Autonomie progressive, compétences techniques et navigation.',
                'tags' => ['formation', 'niveau 2', 'niveau 3', 'autonomie', 'FFESSM']
            ],
            [
                'title' => 'Guide de palanquée',
                'slug' => 'guide-de-palanquee',
                'content' => '<div class="prose max-w-none">
<h1>Guide de palanquée</h1>

<div class="bg-gradient-to-r from-club-orange to-club-orange-dark text-white p-6 rounded-lg mb-8">
    <h2 class="text-2xl font-semibold mb-2">Anciennement "Niveau 4"</h2>
    <p class="text-orange-100">Formation d\'encadrant pour guider les plongeurs en toute sécurité</p>
</div>

<div class="grid md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-xl font-semibold mb-4">Prérequis</h2>
        <div class="space-y-3">
            <div class="flex items-center p-3 bg-blue-50 rounded">
                <svg class="w-6 h-6 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="font-semibold">Niveau 3 requis</p>
                    <p class="text-sm text-gray-600">Certification préalable obligatoire</p>
                </div>
            </div>

            <div class="flex items-center p-3 bg-orange-50 rounded">
                <svg class="w-6 h-6 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="font-semibold">Condition physique</p>
                    <p class="text-sm text-gray-600">Aisance parfaite et bonne condition physique</p>
                </div>
            </div>
        </div>

        <h3 class="text-lg font-semibold mt-6 mb-4">Avantages club</h3>
        <div class="bg-green-50 p-4 rounded-lg">
            <ul class="space-y-2 text-sm">
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">💰</span>
                    Aide financière à la formation
                </li>
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">🎯</span>
                    Tarifs préférentiels
                </li>
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">🤝</span>
                    Contrepartie : encadrement bénévole
                </li>
            </ul>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">Formation</h2>
        <div class="space-y-4">
            <div class="border-l-4 border-club-orange pl-4">
                <h3 class="font-semibold">Organisation</h3>
                <p class="text-sm text-gray-600">Organisée par le département et le club</p>
            </div>

            <div class="border-l-4 border-blue-400 pl-4">
                <h3 class="font-semibold">Calendrier</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Inscription : mi-septembre</li>
                    <li>• Formation physique : octobre à mai</li>
                    <li>• Plongées techniques : octobre-novembre et avril-juin</li>
                </ul>
            </div>

            <div class="border-l-4 border-green-400 pl-4">
                <h3 class="font-semibold">Validation</h3>
                <p class="text-sm text-gray-600">Examens théoriques, physiques et pratiques</p>
            </div>
        </div>

        <h3 class="text-lg font-semibold mt-6 mb-4">Prérogatives</h3>
        <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="font-semibold mb-2">Autorisé à encadrer :</h4>
            <ul class="space-y-1 text-sm">
                <li class="flex items-center">
                    <span class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2">N1</span>
                    Plongeurs Niveau 1 jusqu\'à 20 mètres
                </li>
                <li class="flex items-center">
                    <span class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2">N2</span>
                    Plongeurs Niveau 2 jusqu\'à 40 mètres
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h3 class="text-lg font-semibold mb-4">🆘 Le club a besoin d\'encadrants !</h3>
    <p class="mb-4">
        Devenez Guide de palanquée et participez activement à la vie du club en encadrant nos sorties et formations.
    </p>
    <div class="flex gap-3">
        <a href="/contact" class="bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark">
            Me renseigner
        </a>
        <a href="/calendrier" class="border border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white">
            Voir les formations
        </a>
    </div>
</div>
</div>',
                'excerpt' => 'Formation Guide de palanquée (ex-Niveau 4) pour encadrer les plongeurs. Aide financière du club.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Guide de palanquée - Club Subaquatique des Vénètes',
                'meta_description' => 'Formation Guide de palanquée au Club des Vénètes. Encadrement N1 et N2, aide financière, contrepartie bénévolat.',
                'tags' => ['formation', 'guide', 'encadrement', 'niveau 4']
            ],
            [
                'title' => 'Autres formations',
                'slug' => 'autres-formations',
                'content' => '<div class="prose max-w-none">
<h1>Autres formations</h1>

<p class="text-lg text-gray-600 mb-8">
    Complétez votre formation de plongeur avec nos spécialisations avancées et formations de sécurité.
</p>

<div class="grid gap-8">
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-md">
        <div class="bg-gradient-to-r from-green-500 to-green-600 p-4">
            <h2 class="text-xl font-semibold text-white flex items-center">
                <span class="mr-2">🫧</span>
                Formations Nitrox
            </h2>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-green-700 mb-3">Nitrox Élémentaire</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <span class="text-green-500 mr-2">•</span>
                            Plongée avec mélange à 40% d\'oxygène
                        </li>
                        <li class="flex items-center">
                            <span class="text-green-500 mr-2">•</span>
                            Plongées moins fatigantes
                        </li>
                        <li class="flex items-center">
                            <span class="text-green-500 mr-2">•</span>
                            Plus de sécurité
                        </li>
                        <li class="flex items-center">
                            <span class="text-green-500 mr-2">•</span>
                            Consommation d\'air réduite
                        </li>
                    </ul>
                    <p class="text-sm text-gray-600 mt-3">
                        <strong>Recommandé :</strong> Plongées vers 30m de profondeur
                    </p>
                </div>
                <div>
                    <h3 class="font-semibold text-green-700 mb-3">Nitrox Confirmé</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <span class="text-green-500 mr-2">•</span>
                            Choix du pourcentage d\'oxygène
                        </li>
                        <li class="flex items-center">
                            <span class="text-green-500 mr-2">•</span>
                            Utilisation d\'oxygène pur aux paliers
                        </li>
                        <li class="flex items-center">
                            <span class="text-green-500 mr-2">•</span>
                            Décompression optimisée
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-md">
        <div class="bg-gradient-to-r from-red-500 to-red-600 p-4">
            <h2 class="text-xl font-semibold text-white flex items-center">
                <span class="mr-2">🆘</span>
                RIFAP - Secours et Sauvetage
            </h2>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-red-700 mb-3">Compétences enseignées</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <span class="text-red-500 mr-2">•</span>
                            Techniques de sauvetage
                        </li>
                        <li class="flex items-center">
                            <span class="text-red-500 mr-2">•</span>
                            Remontée de plongeur inconscient
                        </li>
                        <li class="flex items-center">
                            <span class="text-red-500 mr-2">•</span>
                            Administration d\'oxygène médical
                        </li>
                        <li class="flex items-center">
                            <span class="text-red-500 mr-2">•</span>
                            Utilisation radio de base
                        </li>
                    </ul>
                </div>
                <div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-red-800 mb-2">⚠️ Obligatoire pour :</h4>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li>• Niveau 3</li>
                            <li>• Niveau 4 / Guide de palanquée</li>
                            <li>• Moniteurs</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-4 p-3 bg-yellow-50 rounded border-l-4 border-yellow-400">
                <p class="text-sm text-yellow-800">
                    <strong>Recyclage :</strong> Sessions périodiques de remise à niveau et sensibilisation au secours
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-md">
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4">
            <h2 class="text-xl font-semibold text-white flex items-center">
                <span class="mr-2">🎓</span>
                Formations Moniteur
            </h2>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-purple-700 mb-3">Initiateur</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        Premier niveau d\'enseignement pour former les plongeurs débutants
                    </p>
                    <ul class="space-y-1 text-sm">
                        <li class="flex items-center">
                            <span class="text-purple-500 mr-2">•</span>
                            Formation départementale
                        </li>
                        <li class="flex items-center">
                            <span class="text-purple-500 mr-2">•</span>
                            Enseigne en milieu protégé
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-semibold text-purple-700 mb-3">Moniteur Fédéral</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        Formation complète pour l\'enseignement tous niveaux
                    </p>
                    <ul class="space-y-1 text-sm">
                        <li class="flex items-center">
                            <span class="text-purple-500 mr-2">•</span>
                            Formation régionale
                        </li>
                        <li class="flex items-center">
                            <span class="text-purple-500 mr-2">•</span>
                            Toutes prérogatives d\'enseignement
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h3 class="text-lg font-semibold mb-4">🎯 Poursuivez votre formation !</h3>
    <p class="mb-4">
        Ces formations spécialisées vous permettront d\'enrichir votre pratique de la plongée et d\'acquérir de nouvelles compétences.
    </p>
    <div class="flex gap-3">
        <a href="/contact" class="bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark">
            Me renseigner
        </a>
        <a href="/calendrier" class="border border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white">
            Voir le planning
        </a>
    </div>
</div>
</div>',
                'excerpt' => 'Formations spécialisées : Nitrox, RIFAP, Moniteur. Complétez votre cursus de plongeur.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Autres formations - Club Subaquatique des Vénètes',
                'meta_description' => 'Formations spécialisées plongée : Nitrox, RIFAP secours, formations Moniteur au Club des Vénètes.',
                'tags' => ['formation', 'nitrox', 'RIFAP', 'moniteur', 'spécialisation']
            ],
            [
                'title' => 'Plongeurs extérieurs',
                'slug' => 'plongeurs-exterieurs',
                'content' => '<div class="prose max-w-none">
<h1>Plongeurs extérieurs</h1>

<div class="bg-blue-50 border-l-4 border-blue-400 p-6 mb-8">
    <h2 class="text-xl font-semibold text-blue-800 mb-2">Bienvenue aux plongeurs extérieurs !</h2>
    <p class="text-blue-700">
        Rejoignez nos sorties plongée et découvrez les sites exceptionnels du Golfe du Morbihan
    </p>
</div>

<div class="grid md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-xl font-semibold mb-4">Documents requis</h2>
        <div class="space-y-3">
            <div class="flex items-center p-3 bg-orange-50 rounded border-l-4 border-orange-400">
                <svg class="w-6 h-6 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <div>
                    <p class="font-semibold">Licence FFESSM</p>
                    <p class="text-sm text-gray-600">Le club peut en fournir une si nécessaire</p>
                </div>
            </div>

            <div class="flex items-center p-3 bg-green-50 rounded border-l-4 border-green-400">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zM8 6a2 2 0 114 0v1H8V6z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-semibold">Carte de niveau</p>
                    <p class="text-sm text-gray-600">Justificatif de votre niveau de plongée</p>
                </div>
            </div>

            <div class="flex items-center p-3 bg-red-50 rounded border-l-4 border-red-400">
                <svg class="w-6 h-6 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-semibold">Certificat médical</p>
                    <p class="text-sm text-gray-600">De moins d\'un an</p>
                </div>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">Tarifs</h2>
        <div class="space-y-4">
            <div class="bg-white border-2 border-club-orange rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-club-orange mb-2">25€</div>
                <p class="text-gray-600">par plongée</p>
            </div>

            <div class="bg-club-orange text-white rounded-lg p-6 text-center">
                <div class="text-3xl font-bold mb-2">100€</div>
                <p class="text-orange-100">forfait 5 plongées</p>
                <div class="text-sm mt-2 bg-orange-600 rounded px-2 py-1 inline-block">
                    Économie de 25€
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-8">
    <h2 class="text-xl font-semibold mb-6">Comment s\'inscrire ?</h2>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="text-center">
            <div class="bg-club-orange text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3 text-xl font-bold">1</div>
            <h3 class="font-semibold mb-2">Consultez le calendrier</h3>
            <p class="text-sm text-gray-600">
                Vérifiez les plongées organisées sur le calendrier d\'accueil. Les dates en orange indiquent une plongée prévue.
            </p>
        </div>

        <div class="text-center">
            <div class="bg-club-orange text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3 text-xl font-bold">2</div>
            <h3 class="font-semibold mb-2">Cliquez sur la date</h3>
            <p class="text-sm text-gray-600">
                Découvrez le nom du responsable, l\'heure de départ, la description et les places disponibles.
            </p>
        </div>

        <div class="text-center">
            <div class="bg-club-orange text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3 text-xl font-bold">3</div>
            <h3 class="font-semibold mb-2">Envoyez un email</h3>
            <p class="text-sm text-gray-600">
                Contactez-nous à l\'adresse indiquée pour réserver votre place.
            </p>
        </div>
    </div>
</div>

<div class="bg-gray-50 p-6 rounded-lg mt-8">
    <h2 class="text-xl font-semibold mb-4">Matériel fourni par le club</h2>
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <h3 class="font-semibold text-green-700 mb-3">✅ Inclus</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">•</span>
                    Bouteille de plongée
                </li>
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">•</span>
                    Gilet stabilisateur
                </li>
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">•</span>
                    Détendeurs
                </li>
            </ul>
        </div>

        <div>
            <h3 class="font-semibold text-red-700 mb-3">❌ Non fourni</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center">
                    <span class="text-red-500 mr-2">•</span>
                    Combinaison de plongée
                </li>
                <li class="flex items-center">
                    <span class="text-gray-500 mr-2">•</span>
                    <span class="text-gray-600">À prévoir personnellement</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="bg-blue-50 p-6 rounded-lg mt-8">
    <h2 class="text-xl font-semibold mb-4">Déroulement d\'une sortie</h2>
    <div class="space-y-3 text-sm">
        <div class="flex items-start">
            <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-3 mt-0.5">1</span>
            <div>
                <strong>Rendez-vous au club</strong> 30 minutes avant l\'heure programmée
            </div>
        </div>
        <div class="flex items-start">
            <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-3 mt-0.5">2</span>
            <div>
                <strong>Vérification des documents</strong> par le directeur de plongée
            </div>
        </div>
        <div class="flex items-start">
            <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-3 mt-0.5">3</span>
            <div>
                <strong>Destinations typiques :</strong> Larmor Baden, Ria d\'Etel, Lorient/Groix
            </div>
        </div>
        <div class="flex items-start">
            <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-3 mt-0.5">4</span>
            <div>
                <strong>Retour au club</strong> pour rincer et ranger le matériel
            </div>
        </div>
    </div>
</div>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h3 class="text-lg font-semibold mb-4">🤿 Prêt à plonger avec nous ?</h3>
    <p class="mb-4">
        Rejoignez nos sorties et découvrez les merveilles sous-marines du Golfe du Morbihan !
    </p>
    <div class="flex gap-3">
        <a href="/calendrier" class="bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark">
            Voir le calendrier
        </a>
        <a href="/contact" class="border border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white">
            Nous contacter
        </a>
    </div>
</div>
</div>',
                'excerpt' => 'Rejoignez nos sorties plongée ! 25€/plongée ou 100€ les 5. Documents requis : licence FFESSM, niveau, certificat médical.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Plongeurs extérieurs - Club Subaquatique des Vénètes',
                'meta_description' => 'Plongeurs extérieurs bienvenus ! Tarifs : 25€/plongée. Matériel fourni. Sorties Golfe du Morbihan, Ria d\'Etel.',
                'tags' => ['plongeurs extérieurs', 'tarifs', 'sorties', 'matériel']
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