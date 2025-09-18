<?php

namespace App\DataFixtures;

use App\Entity\Page;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class PageFixtures extends Fixture
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Get the first user as author (if exists, otherwise create a temporary one)
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
                'title' => 'Qui sommes-nous ?',
                'slug' => 'qui-sommes-nous',
                'content' => '<div class="prose max-w-none">
<h1>Qui sommes-nous ?</h1>

<p>Le CSV (Club Subaquatique Les Vénètes) a été fondé le 1er mars 1960.</p>

<p>Nous sommes une association loi 1901 affiliée à la FFESSM (n° 03-56012). Tous les membres du club sont bénévoles. Nous comptons 250 membres pratiquants la plongée à l\'air et aux mélanges (NITROX, TRIMIX), la biologie sous-marine, la photographie sous-marine, l\'apnée, la nage avec palme, la nage en eau vive…</p>

<p>Le club se situe à Vannes. De ce fait, notre terrain de jeu préféré est le Golfe du Morbihan mais nous sortons aussi régulièrement sur la Ria d\'Etel, Houat, Groix etc…</p>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h2 class="text-xl font-semibold mb-4">Nos chiffres</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center">
            <div class="text-2xl font-bold text-club-orange">250</div>
            <div class="text-sm">Membres</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-club-orange">1960</div>
            <div class="text-sm">Fondation</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-club-orange">65</div>
            <div class="text-sm">Ans d\'expérience</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-club-orange">100%</div>
            <div class="text-sm">Bénévoles</div>
        </div>
    </div>
</div>
</div>',
                'excerpt' => 'Découvrez l\'histoire et les valeurs du Club Subaquatique des Vénètes, fondé en 1960.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Qui sommes-nous ? - Club Subaquatique des Vénètes',
                'meta_description' => 'Le CSV est une association loi 1901 fondée en 1960, affiliée à la FFESSM. 250 membres bénévoles pratiquent la plongée dans le Golfe du Morbihan.',
                'tags' => ['club', 'histoire', 'association']
            ],
            [
                'title' => 'Où nous trouver',
                'slug' => 'ou-nous-trouver',
                'content' => '<div class="prose max-w-none">
<h1>Où nous trouver ?</h1>

<p>Plongeurs, apnéistes et pêcheurs sous-marins, débutants ou expérimentés, nous vous accueillons au bord du Golfe du Morbihan, à Vannes, 5 avenue du Président Wilson (quartier de la gare).</p>

<div class="grid md:grid-cols-2 gap-8 mt-8">
    <div>
        <h2 class="text-xl font-semibold mb-4">Contact</h2>
        <div class="space-y-2">
            <p><strong>Téléphone :</strong> 02 97 42 47 00</p>
            <p><strong>Email :</strong> contact@plongee-venetes.fr</p>
        </div>

        <h3 class="text-lg font-semibold mt-6 mb-4">Horaires</h3>
        <p>Une permanence est assurée au club tous les <strong>vendredis à partir de 18h</strong>.</p>

        <h3 class="text-lg font-semibold mt-6 mb-4">Adresse</h3>
        <address class="not-italic">
            <strong>Club Subaquatique des Vénètes</strong><br>
            5 avenue du Président Wilson<br>
            Quartier de la gare<br>
            56000 Vannes, France
        </address>
    </div>
    
    <div>
        <h2 class="text-xl font-semibold mb-4">Plan d\'accès</h2>
        <div class="bg-gray-100 rounded-lg p-4 h-64 flex items-center justify-center">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2668.8!2d-2.7606!3d47.6587!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDfCsDM5JzMxLjMiTiAywrA0NSczOC4yIlc!5e0!3m2!1sfr!2sfr!4v1234567890"
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                title="Plan du Club Subaquatique des Vénètes">
            </iframe>
        </div>
        <p class="text-sm text-gray-600 mt-2">
            Le club est situé dans le quartier de la gare de Vannes, facilement accessible en transport en commun.
        </p>
    </div>
</div>
</div>',
                'excerpt' => 'Retrouvez-nous au 5 avenue du Président Wilson à Vannes, quartier de la gare. Permanence tous les vendredis à 18h.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Où nous trouver - Club Subaquatique des Vénètes',
                'meta_description' => 'Adresse, horaires et contact du Club Subaquatique des Vénètes à Vannes. Permanence tous les vendredis à 18h.',
                'tags' => ['contact', 'adresse', 'horaires']
            ],
            [
                'title' => 'Tarifs 2025',
                'slug' => 'tarifs-2025',
                'content' => '<div class="prose max-w-none">
<h1>Tarifs Adhésion et licence 2025</h1>

<div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-blue-700">
                <strong>Inscription pour la saison 2025/2026</strong><br>
                L\'inscription se fait en ligne via HelloAsso.
            </p>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-xl font-semibold mb-4">Documents requis</h2>
        <ul class="list-disc pl-6 space-y-2">
            <li>Certificat médical (CACI) de moins d\'1 an</li>
            <li>Autorisation parentale pour les mineurs</li>
            <li>Photo d\'identité</li>
        </ul>

        <h3 class="text-lg font-semibold mt-6 mb-4">Inscription en ligne</h3>
        <a href="https://www.helloasso.com/associations/club-subaquatique-les-venetes/adhesions/licence-et-adhesion-csv-2025-2026" 
           class="inline-block bg-club-orange text-white px-6 py-3 rounded-lg hover:bg-club-orange-dark transition-colors"
           target="_blank" rel="noopener">
            S\'inscrire sur HelloAsso
        </a>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">Informations pratiques</h2>
        <div class="space-y-4">
            <div class="p-4 bg-gray-50 rounded-lg">
                <h4 class="font-semibold">Tarifs détaillés</h4>
                <p class="text-sm mt-1">
                    Consultez le PDF des tarifs pour connaître tous les détails des cotisations et frais.
                </p>
                <a href="/assets/documents/Tarifs-CSV-2025.pdf" 
                   class="text-club-orange hover:underline text-sm"
                   target="_blank">
                    📄 Télécharger le PDF des tarifs
                </a>
            </div>
            
            <div class="p-4 bg-gray-50 rounded-lg">
                <h4 class="font-semibold">Assurance</h4>
                <p class="text-sm mt-1">
                    Informations sur les assurances disponibles pour les licenciés.
                </p>
                <a href="https://www.assurdiving.com/courtier-assurances/offre-licencies.html?offre=e36f7e7d-fd60-4dd8-98f5-3a213dc79c36" 
                   class="text-club-orange hover:underline text-sm"
                   target="_blank">
                    🔗 Voir l\'offre assurance
                </a>
            </div>
        </div>
    </div>
</div>
</div>',
                'excerpt' => 'Découvrez les tarifs d\'adhésion et de licence pour la saison 2025/2026. Inscription en ligne via HelloAsso.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Tarifs 2025 - Club Subaquatique des Vénètes',
                'meta_description' => 'Tarifs d\'adhésion et licence 2025/2026 du Club Subaquatique des Vénètes. Inscription en ligne sur HelloAsso.',
                'tags' => ['tarifs', 'adhésion', 'licence', '2025']
            ],
            [
                'title' => 'Nos partenaires',
                'slug' => 'nos-partenaires',
                'content' => '<div class="prose max-w-none">
<h1>Nos partenaires</h1>

<p>Le Club Subaquatique des Vénètes collabore avec différents partenaires institutionnels et commerciaux pour offrir les meilleures conditions de pratique à ses membres.</p>

<div class="grid gap-8 mt-8">
    <div>
        <h2 class="text-xl font-semibold mb-6 text-club-orange">Partenaires institutionnels</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <h3 class="font-semibold mb-2">FFESSM</h3>
                <p class="text-sm text-gray-600 mb-4">Fédération Française d\'Études et de Sports Sous-Marins</p>
                <a href="https://ffessm.fr" target="_blank" class="text-club-orange hover:underline text-sm">
                    Visiter le site
                </a>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <h3 class="font-semibold mb-2">CIBPL</h3>
                <p class="text-sm text-gray-600 mb-4">Comité Régional Bretagne Pays de Loire</p>
                <a href="https://www.cibpl.fr/" target="_blank" class="text-club-orange hover:underline text-sm">
                    Visiter le site
                </a>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <h3 class="font-semibold mb-2">Comité Morbihan</h3>
                <p class="text-sm text-gray-600 mb-4">Comité Départemental du Morbihan</p>
                <a href="https://www.cibpl.fr/ffessm-morbihan/" target="_blank" class="text-club-orange hover:underline text-sm">
                    Visiter le site
                </a>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-6 text-club-orange">Partenaires commerciaux</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <h3 class="font-semibold mb-2">Aqua-Sport</h3>
                <p class="text-sm text-gray-600 mb-4">Magasin de matériel de plongée et sports nautiques</p>
                <a href="https://aqua-sport.fr/" target="_blank" class="text-club-orange hover:underline text-sm">
                    Visiter le site
                </a>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <h3 class="font-semibold mb-2">Apneya</h3>
                <p class="text-sm text-gray-600 mb-4">Boutique en ligne spécialisée dans les produits d\'apnée et de chasse sous-marine</p>
                <div class="flex items-center justify-between">
                    <a href="https://www.apneya.com/" target="_blank" class="text-club-orange hover:underline text-sm">
                        Visiter le site
                    </a>
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                        -10% membres CSV
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-blue-50 border-l-4 border-blue-400 p-6 mt-8">
    <h3 class="font-semibold mb-2">Vous souhaitez devenir partenaire ?</h3>
    <p class="text-sm">
        Contactez-nous pour découvrir les opportunités de partenariat avec le Club Subaquatique des Vénètes.
    </p>
    <a href="mailto:contact@plongee-venetes.fr" class="text-club-orange hover:underline text-sm font-medium">
        contact@plongee-venetes.fr
    </a>
</div>
</div>',
                'excerpt' => 'Découvrez nos partenaires institutionnels et commerciaux qui nous accompagnent dans notre passion de la plongée.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Nos partenaires - Club Subaquatique des Vénètes',
                'meta_description' => 'Partenaires du Club Subaquatique des Vénètes : FFESSM, CIBPL, Aqua-Sport, Apneya et autres collaborations.',
                'tags' => ['partenaires', 'collaboration', 'FFESSM']
            ],
            [
                'title' => 'Formation Niveau 1',
                'slug' => 'formation-niveau-1',
                'content' => '<div class="prose max-w-none">
<h1>Formation Niveau 1</h1>

<div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
    <p class="text-sm text-blue-700">
        <strong>Accessible dès 14 ans</strong> - Permet la plongée jusqu\'à 20 mètres sous supervision d\'un instructeur
    </p>
</div>

<div class="grid md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-xl font-semibold mb-4">Organisation de la formation</h2>
        <ul class="space-y-3">
            <li class="flex items-start">
                <span class="bg-club-orange text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-3 mt-0.5">1</span>
                <div>
                    <strong>Inscriptions</strong><br>
                    <span class="text-sm text-gray-600">Début septembre</span>
                </div>
            </li>
            <li class="flex items-start">
                <span class="bg-club-orange text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-3 mt-0.5">2</span>
                <div>
                    <strong>Formation piscine</strong><br>
                    <span class="text-sm text-gray-600">D\'octobre au printemps</span>
                </div>
            </li>
            <li class="flex items-start">
                <span class="bg-club-orange text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-3 mt-0.5">3</span>
                <div>
                    <strong>Plongées en mer</strong><br>
                    <span class="text-sm text-gray-600">4 plongées entre avril et mai</span>
                </div>
            </li>
        </ul>

        <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
            <h3 class="font-semibold text-yellow-800">Important</h3>
            <p class="text-sm text-yellow-700 mt-1">
                La formation ne se fait pas en 1-2 semaines ! C\'est un processus progressif sur plusieurs mois.
            </p>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">Compétences acquises</h2>
        <div class="space-y-3">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">Préparation et montage du matériel</span>
            </div>
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">Gestion du détendeur respiratoire</span>
            </div>
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">Techniques de remontée contrôlée</span>
            </div>
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">Vidage du masque</span>
            </div>
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">Signes de communication sous-marine</span>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-2">Lieu de formation</h3>
            <p class="text-sm text-gray-600">
                <strong>Piscine :</strong> Formation technique d\'octobre au printemps<br>
                <strong>Mer :</strong> Validation dans le Golfe du Morbihan
            </p>
        </div>
    </div>
</div>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h3 class="text-lg font-semibold mb-2">Objectif de la formation</h3>
    <p class="text-gray-700">
        Profiter des beautés des fonds marins morbihanais en toute sécurité, accompagné d\'un encadrant qualifié.
    </p>
</div>
</div>',
                'excerpt' => 'Formation Niveau 1 FFESSM accessible dès 14 ans. Cours en piscine d\'octobre au printemps, validation en mer.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Formation Niveau 1 - Club Subaquatique des Vénètes',
                'meta_description' => 'Formation plongée Niveau 1 FFESSM au Club des Vénètes. Accessible dès 14 ans, formation progressive sur plusieurs mois.',
                'tags' => ['formation', 'niveau 1', 'plongée', 'FFESSM']
            ],
            [
                'title' => 'Les sorties',
                'slug' => 'les-sorties',
                'content' => '<div class="prose max-w-none">
<h1>Les sorties</h1>

<p>Les plongées s\'organisent en fonction des disponibilités et des envies des encadrants. Le Club Subaquatique des Vénètes propose des sorties régulières pour tous les niveaux.</p>

<div class="grid md:grid-cols-2 gap-8 mt-8">
    <div>
        <h2 class="text-xl font-semibold mb-4">Organisation des sorties</h2>
        
        <div class="space-y-4">
            <div class="p-4 bg-blue-50 rounded-lg">
                <h3 class="font-semibold text-blue-800">Planification</h3>
                <p class="text-sm text-blue-700 mt-1">
                    Les directeurs de plongée annoncent les sorties lors des permanences du vendredi, avec :
                </p>
                <ul class="list-disc list-inside text-sm text-blue-700 mt-2 space-y-1">
                    <li>Date et heure</li>
                    <li>Lieu de plongée</li>
                    <li>Niveau minimum requis</li>
                </ul>
            </div>

            <div class="p-4 bg-green-50 rounded-lg">
                <h3 class="font-semibold text-green-800">Fréquence</h3>
                <ul class="list-disc list-inside text-sm text-green-700 mt-1 space-y-1">
                    <li>Haute saison : presque tous les week-ends</li>
                    <li>Plongées du soir en semaine</li>
                    <li>Sorties exceptionnelles vers des sites plus lointains</li>
                </ul>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">Destinations</h2>
        
        <div class="space-y-4">
            <div>
                <h3 class="font-semibold text-club-orange">Sites principaux</h3>
                <ul class="list-disc list-inside text-sm space-y-1 mt-2">
                    <li><strong>Golfe du Morbihan</strong> - Notre terrain de jeu préféré</li>
                    <li><strong>Ria d\'Etel</strong> - Sorties régulières</li>
                    <li><strong>Île de Houat</strong> - Sorties à la journée</li>
                </ul>
            </div>

            <div class="p-4 bg-yellow-50 rounded-lg">
                <h3 class="font-semibold text-yellow-800">Réservations</h3>
                <div class="text-sm text-yellow-700 mt-1 space-y-2">
                    <p><strong>Membres :</strong> Système de réservation en ligne</p>
                    <p><strong>Plongeurs extérieurs :</strong> Contact direct avec le directeur de plongée</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-orange-50 border-l-4 border-orange-400 p-6 mt-8">
    <h3 class="font-semibold text-orange-800">Limitation pour Niveau 1</h3>
    <p class="text-sm text-orange-700">
        Maximum 2 plongeurs Niveau 1 par encadrant déjà inscrit à la sortie.
    </p>
</div>

<div class="bg-club-orange-light p-6 rounded-lg mt-8">
    <h3 class="text-lg font-semibold mb-4">Rejoignez nos sorties !</h3>
    <p class="mb-4">
        Participez à nos aventures sous-marines et découvrez les richesses du Golfe du Morbihan et des sites environnants.
    </p>
    <div class="flex flex-wrap gap-3">
        <a href="/calendrier" class="inline-block bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark transition-colors">
            Voir le calendrier
        </a>
        <a href="/contact" class="inline-block border border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white transition-colors">
            Nous contacter
        </a>
    </div>
</div>
</div>',
                'excerpt' => 'Découvrez nos sorties plongée dans le Golfe du Morbihan, la Ria d\'Etel et vers Houat. Sorties régulières pour tous niveaux.',
                'template_path' => 'pages/page.html.twig',
                'meta_title' => 'Les sorties - Club Subaquatique des Vénètes',
                'meta_description' => 'Sorties plongée du Club des Vénètes : Golfe du Morbihan, Ria d\'Etel, Houat. Réservation en ligne pour les membres.',
                'tags' => ['sorties', 'plongée', 'Golfe du Morbihan', 'Houat']
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