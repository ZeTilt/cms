<?php

namespace App\Command;

use App\Entity\Page;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:demo:create-plongee-pages',
    description: 'Create pages specific to the diving club website',
)]
class CreatePlongeePagesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des pages pour le Club de Plongée des Vénètes');

        // Get admin user
        $admin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@zetilt.cms']);

        if (!$admin) {
            $io->error('Utilisateur admin non trouvé. Exécutez d\'abord zetilt:cms:init');
            return Command::FAILURE;
        }

        // Delete existing demo pages first
        $this->deleteExistingDemoPages($io);
        
        // Create diving club pages
        $this->createClubPages($admin, $io);
        $this->createActivityPages($admin, $io);
        $this->createNewsPages($admin, $io);

        $this->entityManager->flush();

        $io->success('Pages du club de plongée créées avec succès !');
        $io->note('Visitez /admin/pages pour les gérer');

        return Command::SUCCESS;
    }

    private function deleteExistingDemoPages(SymfonyStyle $io): void
    {
        $io->section('Suppression des pages de démo existantes');
        
        $demoPages = $this->entityManager->getRepository(Page::class)
            ->createQueryBuilder('p')
            ->where('p.title IN (:titles)')
            ->setParameter('titles', ['About', 'Services', 'Contact'])
            ->getQuery()
            ->getResult();

        foreach ($demoPages as $page) {
            $this->entityManager->remove($page);
            $io->writeln("✓ Supprimé: {$page->getTitle()}");
        }
    }

    private function createClubPages(User $admin, SymfonyStyle $io): void
    {
        $io->section('Création des pages "Le Club"');

        $clubPages = [
            [
                'title' => 'Qui sommes nous',
                'slug' => 'qui-sommes-nous',
                'content' => '<div class="hero-section bg-orange-100 p-8 rounded-lg mb-8">
    <h1 class="text-4xl font-bold text-orange-800 mb-4">Club Subaquatique des Vénètes</h1>
    <p class="text-xl text-gray-700">Découvrez les fonds marins avec passion depuis 1975</p>
</div>

<div class="grid md:grid-cols-2 gap-8 mb-8">
    <div>
        <h2 class="text-2xl font-semibold text-orange-700 mb-4">Notre Histoire</h2>
        <p class="mb-4">Le Club Subaquatique des Vénètes a été fondé en 1975 par un groupe de passionnés de plongée sous-marine. Depuis près de 50 ans, nous transmettons notre amour des fonds marins et formons de nouveaux plongeurs dans un esprit de convivialité et de sécurité.</p>
        
        <p class="mb-4">Affilié à la <strong>Fédération Française d\'Études et de Sports Sous-Marins (FFESSM)</strong>, notre club respecte les plus hauts standards de formation et de sécurité.</p>
    </div>
    
    <div>
        <h2 class="text-2xl font-semibold text-orange-700 mb-4">Nos Valeurs</h2>
        <ul class="space-y-2">
            <li class="flex items-start">
                <span class="text-orange-500 mr-2">🤝</span>
                <span><strong>Convivialité :</strong> Un esprit familial et d\'entraide</span>
            </li>
            <li class="flex items-start">
                <span class="text-orange-500 mr-2">🛡️</span>
                <span><strong>Sécurité :</strong> Formation rigoureuse et respect des procédures</span>
            </li>
            <li class="flex items-start">
                <span class="text-orange-500 mr-2">🌊</span>
                <span><strong>Respect :</strong> De l\'environnement marin et de la biodiversité</span>
            </li>
            <li class="flex items-start">
                <span class="text-orange-500 mr-2">📚</span>
                <span><strong>Formation :</strong> Transmission des savoirs et progression continue</span>
            </li>
        </ul>
    </div>
</div>

<div class="bg-blue-50 p-6 rounded-lg">
    <h2 class="text-2xl font-semibold text-blue-800 mb-4">Le Club en Chiffres</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
        <div>
            <div class="text-3xl font-bold text-blue-600">150+</div>
            <div class="text-gray-600">Membres actifs</div>
        </div>
        <div>
            <div class="text-3xl font-bold text-blue-600">15</div>
            <div class="text-gray-600">Moniteurs</div>
        </div>
        <div>
            <div class="text-3xl font-bold text-blue-600">50</div>
            <div class="text-gray-600">Sorties par an</div>
        </div>
        <div>
            <div class="text-3xl font-bold text-blue-600">48</div>
            <div class="text-gray-600">Années d\'expérience</div>
        </div>
    </div>
</div>',
                'excerpt' => 'Découvrez l\'histoire et les valeurs du Club Subaquatique des Vénètes, votre club de plongée depuis 1975.',
            ],
            [
                'title' => 'Où nous trouver',
                'slug' => 'ou-nous-trouver',
                'content' => '<div class="grid md:grid-cols-2 gap-8 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-orange-800 mb-6">Où nous trouver</h1>
        
        <div class="bg-gray-50 p-6 rounded-lg mb-6">
            <h2 class="text-xl font-semibold mb-4">📍 Adresse du Club</h2>
            <p class="mb-2"><strong>Club Subaquatique des Vénètes</strong></p>
            <p class="mb-2">Piscine Municipale de Vannes</p>
            <p class="mb-2">Rue de la Marne</p>
            <p class="mb-4">56000 Vannes</p>
            
            <p class="text-sm text-gray-600">
                <strong>Accès :</strong> Parking gratuit disponible<br>
                <strong>Transports :</strong> Bus ligne 3, arrêt "Piscine"
            </p>
        </div>

        <div class="bg-blue-50 p-6 rounded-lg">
            <h2 class="text-xl font-semibold mb-4">⏰ Horaires d\'Entraînement</h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium">Mardi</span>
                    <span>20h00 - 22h00</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Jeudi</span>
                    <span>20h00 - 22h00</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Samedi</span>
                    <span>14h00 - 16h00</span>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-orange-100 rounded">
                <p class="text-sm text-orange-800">
                    <strong>Note :</strong> Les horaires peuvent varier selon la saison et la disponibilité de la piscine.
                </p>
            </div>
        </div>
    </div>
    
    <div>
        <div class="bg-gray-200 rounded-lg h-96 flex items-center justify-center mb-6">
            <div class="text-center text-gray-600">
                <span class="text-4xl mb-2 block">🗺️</span>
                <p>Carte interactive</p>
                <p class="text-sm">(À intégrer avec Google Maps)</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <div class="bg-green-50 p-4 rounded-lg">
                <h3 class="font-semibold text-green-800 mb-2">🚗 En Voiture</h3>
                <p class="text-sm text-green-700">Sortie autoroute A82, direction centre-ville, suivre "Piscine Municipale"</p>
            </div>
            
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-800 mb-2">🚌 En Bus</h3>
                <p class="text-sm text-blue-700">Ligne 3 du réseau KICEO, arrêt "Piscine Municipale"</p>
            </div>
            
            <div class="bg-purple-50 p-4 rounded-lg">
                <h3 class="font-semibold text-purple-800 mb-2">🚂 En Train</h3>
                <p class="text-sm text-purple-700">Gare SNCF de Vannes à 10 minutes en bus ou 20 minutes à pied</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-orange-50 p-6 rounded-lg">
    <h2 class="text-2xl font-semibold text-orange-800 mb-4">📞 Contact</h2>
    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <p class="font-medium">Président</p>
            <p>Jean-Marc DUPONT</p>
            <p class="text-sm text-gray-600">president@plongee-venetes.fr</p>
        </div>
        <div>
            <p class="font-medium">Secrétaire</p>
            <p>Marie MARTIN</p>
            <p class="text-sm text-gray-600">secretaire@plongee-venetes.fr</p>
        </div>
        <div>
            <p class="font-medium">Responsable Formation</p>
            <p>Pierre BERNARD</p>
            <p class="text-sm text-gray-600">formation@plongee-venetes.fr</p>
        </div>
    </div>
</div>',
                'excerpt' => 'Retrouvez toutes les informations pratiques pour nous rejoindre : adresse, horaires, accès et contacts.',
            ],
            [
                'title' => 'Tarifs Adhésion et licence 2025',
                'slug' => 'tarifs-2025',
                'content' => '<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-orange-800 mb-8 text-center">Tarifs Adhésion et Licence 2025</h1>
    
    <div class="bg-orange-50 p-6 rounded-lg mb-8">
        <h2 class="text-xl font-semibold text-orange-800 mb-4">📋 Ce qui est inclus dans l\'adhésion</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <ul class="space-y-2">
                <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Licence FFESSM</li>
                <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Assurance responsabilité civile</li>
                <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Accès aux entraînements en piscine</li>
                <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Accès au matériel du club</li>
            </ul>
            <ul class="space-y-2">
                <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Formations théoriques</li>
                <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Sorties mer organisées</li>
                <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Événements et sorties club</li>
                <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Carnet de plongée numérique</li>
            </ul>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <!-- Tarifs Adultes -->
        <div class="bg-white border-2 border-blue-200 rounded-lg p-6 shadow-lg">
            <div class="text-center mb-4">
                <h3 class="text-xl font-bold text-blue-800">Adultes</h3>
                <p class="text-sm text-gray-600">18 ans et plus</p>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span>Adhésion annuelle</span>
                    <span class="font-bold text-blue-600">180€</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Licence FFESSM</span>
                    <span class="font-bold text-blue-600">45€</span>
                </div>
                <hr>
                <div class="flex justify-between items-center text-lg">
                    <span class="font-bold">Total</span>
                    <span class="font-bold text-blue-800">225€</span>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    S\'inscrire
                </button>
            </div>
        </div>

        <!-- Tarifs Jeunes -->
        <div class="bg-white border-2 border-orange-200 rounded-lg p-6 shadow-lg relative">
            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-sm">Populaire</span>
            </div>
            
            <div class="text-center mb-4">
                <h3 class="text-xl font-bold text-orange-800">Jeunes</h3>
                <p class="text-sm text-gray-600">Moins de 18 ans</p>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span>Adhésion annuelle</span>
                    <span class="font-bold text-orange-600">120€</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Licence FFESSM</span>
                    <span class="font-bold text-orange-600">35€</span>
                </div>
                <hr>
                <div class="flex justify-between items-center text-lg">
                    <span class="font-bold">Total</span>
                    <span class="font-bold text-orange-800">155€</span>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <button class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                    S\'inscrire
                </button>
            </div>
        </div>

        <!-- Tarifs Famille -->
        <div class="bg-white border-2 border-green-200 rounded-lg p-6 shadow-lg">
            <div class="text-center mb-4">
                <h3 class="text-xl font-bold text-green-800">Famille</h3>
                <p class="text-sm text-gray-600">2 adultes + enfants</p>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span>Adhésion couple</span>
                    <span class="font-bold text-green-600">320€</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Enfant supplémentaire</span>
                    <span class="font-bold text-green-600">+100€</span>
                </div>
                <div class="flex justify-between items-center text-sm text-gray-600">
                    <span>Licences comprises</span>
                    <span>✓</span>
                </div>
                <hr>
                <div class="flex justify-between items-center text-lg">
                    <span class="font-bold">À partir de</span>
                    <span class="font-bold text-green-800">320€</span>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    S\'inscrire
                </button>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="bg-blue-50 p-6 rounded-lg">
            <h3 class="text-lg font-bold text-blue-800 mb-4">💳 Modalités de paiement</h3>
            <ul class="space-y-2 text-sm">
                <li>• Paiement en 3 fois sans frais</li>
                <li>• Chèques vacances acceptés</li>
                <li>• Virement bancaire possible</li>
                <li>• Réduction de 10% pour les demandeurs d\'emploi</li>
            </ul>
        </div>
        
        <div class="bg-green-50 p-6 rounded-lg">
            <h3 class="text-lg font-bold text-green-800 mb-4">📅 Dates importantes</h3>
            <ul class="space-y-2 text-sm">
                <li>• <strong>1er septembre :</strong> Ouverture des inscriptions</li>
                <li>• <strong>15 septembre :</strong> Reprise des entraînements</li>
                <li>• <strong>30 septembre :</strong> Assemblée Générale</li>
                <li>• <strong>31 décembre :</strong> Fin des inscriptions à tarif réduit</li>
            </ul>
        </div>
    </div>

    <div class="bg-orange-100 p-6 rounded-lg text-center">
        <h3 class="text-lg font-bold text-orange-800 mb-4">🎁 Offre spéciale nouveaux membres</h3>
        <p class="mb-4">Première séance d\'essai <strong>gratuite</strong> avec prêt du matériel complet</p>
        <p class="text-sm text-orange-700">Valable jusqu\'au 31 octobre 2025</p>
    </div>
</div>',
                'excerpt' => 'Découvrez nos tarifs 2025 pour l\'adhésion au club et la licence FFESSM. Plusieurs formules adaptées à tous.',
            ],
            [
                'title' => 'Nos partenaires',
                'slug' => 'nos-partenaires',
                'content' => '<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-orange-800 mb-8 text-center">Nos Partenaires</h1>
    
    <p class="text-lg text-gray-700 mb-12 text-center">Le Club Subaquatique des Vénètes collabore avec des partenaires de confiance pour vous offrir les meilleurs services et équipements.</p>

    <!-- Partenaires Officiels -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-orange-700 mb-6">🏛️ Partenaires Officiels</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                <div class="w-16 h-16 bg-blue-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-white font-bold text-xl">FFESSM</span>
                </div>
                <h3 class="font-bold text-lg mb-2">FFESSM</h3>
                <p class="text-gray-600 text-sm mb-4">Fédération Française d\'Études et de Sports Sous-Marins</p>
                <p class="text-xs text-gray-500">Affiliation n° 56000123</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                <div class="w-16 h-16 bg-red-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-white font-bold text-xl">CROS</span>
                </div>
                <h3 class="font-bold text-lg mb-2">CROS Bretagne</h3>
                <p class="text-gray-600 text-sm mb-4">Comité Régional Olympique et Sportif de Bretagne</p>
                <p class="text-xs text-gray-500">Soutien aux projets sportifs</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                <div class="w-16 h-16 bg-green-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-white font-bold text-xl">CDCK</span>
                </div>
                <h3 class="font-bold text-lg mb-2">CDCK 56</h3>
                <p class="text-gray-600 text-sm mb-4">Comité Départemental de Canoë-Kayak</p>
                <p class="text-xs text-gray-500">Partenariat activités nautiques</p>
            </div>
        </div>
    </div>

    <!-- Partenaires Matériel -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-orange-700 mb-6">🛒 Partenaires Matériel</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-gray-50 p-6 rounded-lg">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-500 rounded-full mr-4 flex items-center justify-center">
                        <span class="text-white font-bold">AS</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">Aqua Sports Vannes</h3>
                        <p class="text-gray-600 text-sm">Magasin de plongée</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Remise de 15% sur le matériel pour les membres</li>
                    <li>• Service après-vente prioritaire</li>
                    <li>• Révision gratuite des détendeurs</li>
                </ul>
                <div class="mt-4 p-3 bg-blue-100 rounded">
                    <p class="text-xs text-blue-800">📍 12 Rue du Commerce, 56000 Vannes</p>
                    <p class="text-xs text-blue-800">📞 02 97 XX XX XX</p>
                </div>
            </div>
            
            <div class="bg-gray-50 p-6 rounded-lg">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-orange-500 rounded-full mr-4 flex items-center justify-center">
                        <span class="text-white font-bold">TS</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">Technique Sub</h3>
                        <p class="text-gray-600 text-sm">Spécialiste équipement technique</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Fourniture de matériel pour les formations</li>
                    <li>• Conseils techniques personnalisés</li>
                    <li>• Réparations express</li>
                </ul>
                <div class="mt-4 p-3 bg-orange-100 rounded">
                    <p class="text-xs text-orange-800">🌐 www.technique-sub.fr</p>
                    <p class="text-xs text-orange-800">📧 contact@technique-sub.fr</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Partenaires Voyages -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-orange-700 mb-6">✈️ Partenaires Voyages</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-blue-400 rounded-full mx-auto mb-2 flex items-center justify-center">
                        <span class="text-white text-2xl">🏝️</span>
                    </div>
                    <h3 class="font-bold">Blue Dreams Plongée</h3>
                    <p class="text-sm text-gray-600">Séjours Mer Rouge</p>
                </div>
                <p class="text-xs text-gray-700">Tarifs préférentiels pour nos membres sur les croisières plongée</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-green-400 rounded-full mx-auto mb-2 flex items-center justify-center">
                        <span class="text-white text-2xl">🐠</span>
                    </div>
                    <h3 class="font-bold">Atlantique Plongée</h3>
                    <p class="text-sm text-gray-600">Sorties locales</p>
                </div>
                <p class="text-xs text-gray-700">Partenaire pour les sorties en Atlantique et formation Nitrox</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-purple-400 rounded-full mx-auto mb-2 flex items-center justify-center">
                        <span class="text-white text-2xl">🌊</span>
                    </div>
                    <h3 class="font-bold">Océan Voyages</h3>
                    <p class="text-sm text-gray-600">Destinations tropicales</p>
                </div>
                <p class="text-xs text-gray-700">Spécialiste des voyages plongée Maldives, Philippines, Indonésie</p>
            </div>
        </div>
    </div>

    <!-- Partenaires Institutionnels -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-orange-700 mb-6">🏛️ Soutiens Institutionnels</h2>
        <div class="bg-gray-50 p-8 rounded-lg">
            <div class="grid md:grid-cols-4 gap-6 text-center">
                <div>
                    <div class="w-20 h-20 bg-blue-700 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <span class="text-white font-bold text-sm">VANNES</span>
                    </div>
                    <h4 class="font-semibold">Ville de Vannes</h4>
                    <p class="text-xs text-gray-600">Mise à disposition de la piscine</p>
                </div>
                
                <div>
                    <div class="w-20 h-20 bg-red-600 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <span class="text-white font-bold text-sm">GMVA</span>
                    </div>
                    <h4 class="font-semibold">Golfe du Morbihan</h4>
                    <p class="text-xs text-gray-600">Soutien financier projets</p>
                </div>
                
                <div>
                    <div class="w-20 h-20 bg-green-600 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <span class="text-white font-bold text-sm">56</span>
                    </div>
                    <h4 class="font-semibold">Conseil Départemental</h4>
                    <p class="text-xs text-gray-600">Aide aux équipements</p>
                </div>
                
                <div>
                    <div class="w-20 h-20 bg-purple-600 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <span class="text-white font-bold text-sm">BZH</span>
                    </div>
                    <h4 class="font-semibold">Région Bretagne</h4>
                    <p class="text-xs text-gray-600">Subventions formation</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Devenir Partenaire -->
    <div class="bg-orange-50 p-8 rounded-lg text-center">
        <h2 class="text-2xl font-bold text-orange-800 mb-4">🤝 Devenir Partenaire</h2>
        <p class="text-gray-700 mb-6">Vous souhaitez soutenir notre club et bénéficier d\'une visibilité auprès de nos 150 membres ?</p>
        <div class="space-x-4">
            <button class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700">
                Nos offres de partenariat
            </button>
            <button class="border border-orange-600 text-orange-600 px-6 py-3 rounded-lg hover:bg-orange-50">
                Nous contacter
            </button>
        </div>
    </div>
</div>',
                'excerpt' => 'Découvrez nos partenaires institutionnels, matériel, voyages qui soutiennent le Club Subaquatique des Vénètes.',
            ],
        ];

        foreach ($clubPages as $pageData) {
            $page = new Page();
            $page->setTitle($pageData['title'])
                 ->setSlug($pageData['slug'])
                 ->setContent($pageData['content'])
                 ->setExcerpt($pageData['excerpt'])
                 ->setType('page')
                 ->setStatus('published')
                 ->setAuthor($admin);
                 
            $page->publish();
            $this->entityManager->persist($page);
            $io->writeln("✓ Créé: {$pageData['title']}");
        }
    }

    private function createActivityPages(User $admin, SymfonyStyle $io): void
    {
        $io->section('Création des pages "Nos activités"');

        $activityPages = [
            [
                'title' => 'Nos activités',
                'slug' => 'nos-activites',
                'content' => '<div class="max-w-6xl mx-auto">
    <h1 class="text-4xl font-bold text-orange-800 mb-8 text-center">Nos Activités</h1>
    
    <p class="text-xl text-gray-700 mb-12 text-center">Découvrez toute la richesse des activités proposées par le Club Subaquatique des Vénètes</p>

    <!-- Formations -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-orange-700 mb-8">📚 Formations</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-blue-500">
                <div class="text-center mb-4">
                    <span class="text-4xl mb-2 block">🤿</span>
                    <h3 class="text-xl font-bold text-blue-700">Niveau 1</h3>
                </div>
                <ul class="text-sm space-y-2 mb-4">
                    <li>• Plongée jusqu\'à 20m accompagné</li>
                    <li>• Techniques de base</li>
                    <li>• Sécurité et prévention</li>
                    <li>• Respect de l\'environnement</li>
                </ul>
                <div class="text-center">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">Débutant</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-green-500">
                <div class="text-center mb-4">
                    <span class="text-4xl mb-2 block">🌊</span>
                    <h3 class="text-xl font-bold text-green-700">Niveau 2</h3>
                </div>
                <ul class="text-sm space-y-2 mb-4">
                    <li>• Plongée jusqu\'à 20m en autonomie</li>
                    <li>• Plongée jusqu\'à 40m accompagné</li>
                    <li>• Navigation sous-marine</li>
                    <li>• Assistance et sauvetage</li>
                </ul>
                <div class="text-center">
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">Intermédiaire</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-purple-500">
                <div class="text-center mb-4">
                    <span class="text-4xl mb-2 block">⭐</span>
                    <h3 class="text-xl font-bold text-purple-700">Niveau 3</h3>
                </div>
                <ul class="text-sm space-y-2 mb-4">
                    <li>• Plongée jusqu\'à 60m en autonomie</li>
                    <li>• Guide de palanquée</li>
                    <li>• Planification de plongée</li>
                    <li>• Encadrement de niveau 1</li>
                </ul>
                <div class="text-center">
                    <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">Avancé</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Spécialités -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-orange-700 mb-8">🎯 Spécialités</h2>
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg">
                <h3 class="text-xl font-bold text-blue-800 mb-4">🌀 Nitrox</h3>
                <p class="text-gray-700 mb-4">Formation à la plongée aux mélanges enrichis en oxygène pour des plongées plus longues et plus sûres.</p>
                <ul class="text-sm space-y-1">
                    <li>• Théorie des mélanges gazeux</li>
                    <li>• Analyseur de gaz</li>
                    <li>• Planification Nitrox</li>
                </ul>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg">
                <h3 class="text-xl font-bold text-green-800 mb-4">🔦 Plongée de Nuit</h3>
                <p class="text-gray-700 mb-4">Découvrez les fonds marins sous un autre angle avec nos formations à la plongée nocturne.</p>
                <ul class="text-sm space-y-1">
                    <li>• Techniques spécifiques</li>
                    <li>• Matériel d\'éclairage</li>
                    <li>• Faune nocturne</li>
                </ul>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg">
                <h3 class="text-xl font-bold text-purple-800 mb-4">📸 Photo Sous-marine</h3>
                <p class="text-gray-700 mb-4">Apprenez à immortaliser vos plongées avec nos cours de photographie sous-marine.</p>
                <ul class="text-sm space-y-1">
                    <li>• Réglages d\'appareil</li>
                    <li>• Composition sous l\'eau</li>
                    <li>• Éclairage artificiel</li>
                </ul>
            </div>

            <div class="bg-gradient-to-br from-red-50 to-red-100 p-6 rounded-lg">
                <h3 class="text-xl font-bold text-red-800 mb-4">🆘 Secours</h3>
                <p class="text-gray-700 mb-4">Formation aux techniques de sauvetage et de secours en plongée sous-marine.</p>
                <ul class="text-sm space-y-1">
                    <li>• Assistance en surface</li>
                    <li>• Remontée d\'urgence</li>
                    <li>• Premiers secours</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Sorties -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-orange-700 mb-8">🚤 Sorties et Voyages</h2>
        <div class="bg-blue-50 p-8 rounded-lg">
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-xl font-bold text-blue-800 mb-4">🏠 Sorties Locales</h3>
                    <div class="space-y-3">
                        <div class="bg-white p-4 rounded border-l-4 border-blue-500">
                            <h4 class="font-semibold">Golfe du Morbihan</h4>
                            <p class="text-sm text-gray-600">Épaves, herbiers, faune locale</p>
                            <p class="text-xs text-blue-600">Tous les dimanches matin</p>
                        </div>
                        <div class="bg-white p-4 rounded border-l-4 border-blue-500">
                            <h4 class="font-semibold">Belle-Île-en-Mer</h4>
                            <p class="text-sm text-gray-600">Plongées sur épaves historiques</p>
                            <p class="text-xs text-blue-600">1 weekend/mois</p>
                        </div>
                        <div class="bg-white p-4 rounded border-l-4 border-blue-500">
                            <h4 class="font-semibild">Presqu\'île de Crozon</h4>
                            <p class="text-sm text-gray-600">Tombants et grottes</p>
                            <p class="text-xs text-blue-600">Sorties d\'été</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold text-blue-800 mb-4">✈️ Voyages</h3>
                    <div class="space-y-3">
                        <div class="bg-white p-4 rounded border-l-4 border-green-500">
                            <h4 class="font-semibold">Méditerranée</h4>
                            <p class="text-sm text-gray-600">Port-Cros, Lavandou, Marseille</p>
                            <p class="text-xs text-green-600">Printemps & Automne</p>
                        </div>
                        <div class="bg-white p-4 rounded border-l-4 border-orange-500">
                            <h4 class="font-semibold">Mer Rouge</h4>
                            <p class="text-sm text-gray-600">Égypte, croisières</p>
                            <p class="text-xs text-orange-600">Voyage annuel</p>
                        </div>
                        <div class="bg-white p-4 rounded border-l-4 border-purple-500">
                            <h4 class="font-semibold">Destinations Tropicales</h4>
                            <p class="text-sm text-gray-600">Maldives, Philippines, Indonésie</p>
                            <p class="text-xs text-purple-600">Voyages d\'exception</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Planning -->
    <div class="bg-orange-50 p-8 rounded-lg">
        <h2 class="text-2xl font-bold text-orange-800 mb-6 text-center">📅 Planning Hebdomadaire</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center">
                <h3 class="font-bold text-lg mb-2">Mardi</h3>
                <div class="bg-white p-4 rounded">
                    <p class="text-sm text-gray-600">20h00 - 22h00</p>
                    <p class="font-semibold">Formation technique</p>
                    <p class="text-xs text-blue-600">Piscine municipale</p>
                </div>
            </div>
            <div class="text-center">
                <h3 class="font-bold text-lg mb-2">Jeudi</h3>
                <div class="bg-white p-4 rounded">
                    <p class="text-sm text-gray-600">20h00 - 22h00</p>
                    <p class="font-semibold">Entraînement libre</p>
                    <p class="text-xs text-blue-600">Piscine municipale</p>
                </div>
            </div>
            <div class="text-center">
                <h3 class="font-bold text-lg mb-2">Dimanche</h3>
                <div class="bg-white p-4 rounded">
                    <p class="text-sm text-gray-600">09h00 - 17h00</p>
                    <p class="font-semibold">Sortie mer</p>
                    <p class="text-xs text-green-600">Selon météo</p>
                </div>
            </div>
        </div>
    </div>
</div>',
                'excerpt' => 'Toutes nos activités : formations FFESSM, spécialités, sorties locales et voyages plongée.',
            ],
        ];

        foreach ($activityPages as $pageData) {
            $page = new Page();
            $page->setTitle($pageData['title'])
                 ->setSlug($pageData['slug'])
                 ->setContent($pageData['content'])
                 ->setExcerpt($pageData['excerpt'])
                 ->setType('page')
                 ->setStatus('published')
                 ->setAuthor($admin);
                 
            $page->publish();
            $this->entityManager->persist($page);
            $io->writeln("✓ Créé: {$pageData['title']}");
        }
    }

    private function createNewsPages(User $admin, SymfonyStyle $io): void
    {
        $io->section('Création des articles d\'actualités');

        $newsArticles = [
            [
                'title' => 'Reprise des entraînements - Saison 2025',
                'content' => '<div class="max-w-4xl mx-auto">
    <div class="bg-orange-100 p-6 rounded-lg mb-8">
        <h1 class="text-3xl font-bold text-orange-800 mb-4">🏊‍♀️ Reprise des entraînements - Saison 2025</h1>
        <div class="flex items-center text-sm text-gray-600">
            <span class="mr-4">📅 15 septembre 2025</span>
            <span class="mr-4">👤 Bureau du Club</span>
            <span>🏷️ Actualités, Entraînements</span>
        </div>
    </div>

    <div class="prose max-w-none">
        <p class="text-lg text-gray-700 mb-6">Chers membres, nous sommes ravis de vous annoncer la reprise officielle des entraînements en piscine pour la saison 2025 !</p>

        <h2 class="text-2xl font-bold text-orange-700 mb-4">📅 Planning des entraînements</h2>
        
        <div class="bg-blue-50 p-6 rounded-lg mb-6">
            <h3 class="font-bold text-blue-800 mb-3">Horaires réguliers :</h3>
            <ul class="space-y-2">
                <li><strong>Mardi :</strong> 20h00 - 22h00 (Formation technique et perfectionnement)</li>
                <li><strong>Jeudi :</strong> 20h00 - 22h00 (Entraînement libre et préparation N2/N3)</li>
                <li><strong>Samedi :</strong> 14h00 - 16h00 (Séance découverte et baptêmes)</li>
            </ul>
        </div>

        <h2 class="text-2xl font-bold text-orange-700 mb-4">🆕 Nouveautés 2025</h2>
        
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div class="bg-green-50 p-6 rounded-lg">
                <h3 class="font-bold text-green-800 mb-3">🤿 Nouveau matériel</h3>
                <ul class="text-sm space-y-1">
                    <li>• 10 nouveaux détendeurs Aqualung</li>
                    <li>• Renouvellement des combinaisons</li>
                    <li>• Acquisition d\'analyseurs Nitrox</li>
                </ul>
            </div>
            
            <div class="bg-purple-50 p-6 rounded-lg">
                <h3 class="font-bold text-purple-800 mb-3">👨‍🏫 Nouveaux moniteurs</h3>
                <ul class="text-sm space-y-1">
                    <li>• Marie DUBOIS (MF1 spé. photo)</li>
                    <li>• Thomas MARTIN (MF1 spé. Nitrox)</li>
                    <li>• Sophie BERNARD (E3 assistante)</li>
                </ul>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-orange-700 mb-4">🎯 Objectifs de la saison</h2>
        
        <div class="bg-yellow-50 p-6 rounded-lg mb-6">
            <ul class="space-y-3">
                <li class="flex items-start">
                    <span class="text-yellow-600 mr-2 mt-1">⭐</span>
                    <span><strong>Formation :</strong> 20 nouveaux Niveau 1 et 10 Niveau 2</span>
                </li>
                <li class="flex items-start">
                    <span class="text-yellow-600 mr-2 mt-1">🌊</span>
                    <span><strong>Sorties :</strong> 30 sorties mer programmées</span>
                </li>
                <li class="flex items-start">
                    <span class="text-yellow-600 mr-2 mt-1">✈️</span>
                    <span><strong>Voyage :</strong> Mer Rouge en avril 2025</span>
                </li>
                <li class="flex items-start">
                    <span class="text-yellow-600 mr-2 mt-1">🏆</span>
                    <span><strong>Compétitions :</strong> Participation au championnat régional</span>
                </li>
            </ul>
        </div>

        <h2 class="text-2xl font-bold text-orange-700 mb-4">📋 Informations pratiques</h2>
        
        <div class="bg-gray-50 p-6 rounded-lg mb-6">
            <h3 class="font-bold mb-3">Ce qu\'il faut apporter :</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <ul class="space-y-1 text-sm">
                    <li>✓ Certificat médical à jour</li>
                    <li>✓ Assurance responsabilité civile</li>
                    <li>✓ Carte de membre 2025</li>
                    <li>✓ Maillot de bain et serviette</li>
                </ul>
                <ul class="space-y-1 text-sm">
                    <li>✓ Palmes, masque, tuba (si vous en avez)</li>
                    <li>✓ Carnet de plongée</li>
                    <li>✓ Bonne humeur ! 😊</li>
                </ul>
            </div>
        </div>

        <div class="bg-orange-50 p-6 rounded-lg text-center">
            <h3 class="text-xl font-bold text-orange-800 mb-4">🎉 Soirée de reprise</h3>
            <p class="mb-4">Rejoignez-nous le <strong>samedi 16 septembre à 19h30</strong> à la salle des fêtes pour notre traditionnelle soirée de reprise avec :</p>
            <ul class="inline-block text-left space-y-1 mb-4">
                <li>• Présentation du bureau et des nouveaux membres</li>
                <li>• Présentation du programme de la saison</li>
                <li>• Pot de l\'amitié et buffet partagé</li>
                <li>• Projection des photos des vacances</li>
            </ul>
            <p class="text-sm text-orange-700">Entrée libre - Inscriptions souhaitées</p>
        </div>
    </div>
</div>',
                'excerpt' => 'La saison 2025 commence ! Découvrez les nouveaux horaires, le matériel et les objectifs de cette nouvelle année.',
                'tags' => ['actualités', 'entraînements', 'saison 2025'],
                'published_at' => new \DateTimeImmutable('-1 day'),
            ],
            [
                'title' => 'Sortie Belle-Île : Un weekend exceptionnel',
                'content' => '<div class="max-w-4xl mx-auto">
    <div class="bg-blue-100 p-6 rounded-lg mb-8">
        <h1 class="text-3xl font-bold text-blue-800 mb-4">🏝️ Sortie Belle-Île : Un weekend exceptionnel</h1>
        <div class="flex items-center text-sm text-gray-600">
            <span class="mr-4">📅 28-29 septembre 2025</span>
            <span class="mr-4">👤 Pierre MARTIN</span>
            <span>🏷️ Sorties, Belle-Île, Épaves</span>
        </div>
    </div>

    <div class="prose max-w-none">
        <p class="text-lg text-gray-700 mb-6">Retour sur notre fantastique sortie à Belle-Île-en-Mer des 28 et 29 septembre ! 24 plongeurs ont pu découvrir les merveilles sous-marines de cette île emblématique du Morbihan.</p>

        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white border rounded-lg overflow-hidden shadow-lg">
                <div class="h-48 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                    <span class="text-white text-6xl">📸</span>
                </div>
                <div class="p-4">
                    <h3 class="font-bold mb-2">Épave du Président Coty</h3>
                    <p class="text-sm text-gray-600">Plongée sur l\'épave mythique par 24m de fond, une visibilité exceptionnelle de 15m !</p>
                </div>
            </div>
            
            <div class="bg-white border rounded-lg overflow-hidden shadow-lg">
                <div class="h-48 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                    <span class="text-white text-6xl">🐠</span>
                </div>
                <div class="p-4">
                    <h3 class="font-bold mb-2">Pointe du Cardinal</h3>
                    <p class="text-sm text-gray-600">Tombant rocheux avec une biodiversité incroyable : congres, homards, et bancs de sars !</p>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-blue-700 mb-4">🌊 Plongées réalisées</h2>
        
        <div class="space-y-4 mb-8">
            <div class="bg-blue-50 p-6 rounded-lg">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-bold text-blue-800">Samedi matin - Épave du Président Coty</h3>
                    <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-sm">24m</span>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm mb-2"><strong>Conditions :</strong></p>
                        <ul class="text-sm space-y-1">
                            <li>• Visibilité : 15 mètres</li>
                            <li>• Température : 16°C</li>
                            <li>• Courant : Faible</li>
                            <li>• État de mer : 1/2</li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-sm mb-2"><strong>Faune observée :</strong></p>
                        <ul class="text-sm space-y-1">
                            <li>• Bancs de sars et de bars</li>
                            <li>• Plusieurs congres</li>
                            <li>• Anémones bijoux</li>
                            <li>• Araignées de mer</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 p-6 rounded-lg">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-bold text-green-800">Samedi après-midi - Pointe des Poulains</h3>
                    <span class="bg-green-200 text-green-800 px-3 py-1 rounded-full text-sm">18m</span>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm mb-2"><strong>Conditions :</strong></p>
                        <ul class="text-sm space-y-1">
                            <li>• Visibilité : 12 mètres</li>
                            <li>• Température : 16°C</li>
                            <li>• Courant : Modéré</li>
                            <li>• État de mer : 2</li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-sm mb-2"><strong>Faune observée :</strong></p>
                        <ul class="text-sm space-y-1">
                            <li>• Homard bleu de 40cm !</li>
                            <li>• Banc de maquereaux</li>
                            <li>• Oursins violets</li>
                            <li>• Étoiles de mer</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 p-6 rounded-lg">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-bold text-purple-800">Dimanche matin - Pointe du Cardinal</h3>
                    <span class="bg-purple-200 text-purple-800 px-3 py-1 rounded-full text-sm">22m</span>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm mb-2"><strong>Conditions :</strong></p>
                        <ul class="text-sm space-y-1">
                            <li>• Visibilité : 18 mètres</li>
                            <li>• Température : 15°C</li>
                            <li>• Courant : Fort</li>
                            <li>• État de mer : 2/3</li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-sm mb-2"><strong>Highlights :</strong></p>
                        <ul class="text-sm space-y-1">
                            <li>• Passage d\'un banc de thons</li>
                            <li>• Congre de plus de 1,5m</li>
                            <li>• Gorgones violettes</li>
                            <li>• Nudibranches colorés</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-blue-700 mb-4">🏆 Les moments forts</h2>
        
        <div class="bg-yellow-50 p-6 rounded-lg mb-6">
            <div class="space-y-4">
                <div class="flex items-start">
                    <span class="text-2xl mr-3">🦞</span>
                    <div>
                        <h3 class="font-bold">Le homard géant</h3>
                        <p class="text-sm text-gray-700">Claire et Julien ont eu la chance d\'observer un magnifique homard bleu de près de 40cm aux Poulains. Un spécimen exceptionnel !</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <span class="text-2xl mr-3">🐟</span>
                    <div>
                        <h3 class="font-bold">Le passage de thons</h3>
                        <p class="text-sm text-gray-700">Dimanche matin, un impressionnant banc de thons a traversé notre palanquée au Cardinal. Moment magique immortalisé en vidéo !</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <span class="text-2xl mr-3">📊</span>
                    <div>
                        <h3 class="font-bold">Première N2 réussie</h3>
                        <p class="text-sm text-gray-700">Félicitations à Emma qui a validé sa première plongée autonome à 20m ! Un grand moment d\'émotion.</p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-blue-700 mb-4">📊 Bilan de la sortie</h2>
        
        <div class="grid md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-3xl font-bold text-blue-600">24</div>
                <div class="text-gray-600 text-sm">Plongeurs</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-3xl font-bold text-green-600">6</div>
                <div class="text-gray-600 text-sm">Plongées</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-3xl font-bold text-purple-600">18m</div>
                <div class="text-gray-600 text-sm">Visibilité max</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-3xl font-bold text-orange-600">100%</div>
                <div class="text-gray-600 text-sm">Satisfaction</div>
            </div>
        </div>

        <div class="bg-orange-50 p-6 rounded-lg">
            <h2 class="text-xl font-bold text-orange-800 mb-4">📅 Prochaines sorties</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center bg-white p-3 rounded">
                    <div>
                        <h3 class="font-semibold">Presqu\'île de Crozon</h3>
                        <p class="text-sm text-gray-600">Weekend plongée & formation N3</p>
                    </div>
                    <span class="text-sm font-bold text-orange-600">12-13 octobre</span>
                </div>
                
                <div class="flex justify-between items-center bg-white p-3 rounded">
                    <div>
                        <h3 class="font-semibold">Golfe du Morbihan</h3>
                        <p class="text-sm text-gray-600">Plongée découverte herbiers</p>
                    </div>
                    <span class="text-sm font-bold text-orange-600">19 octobre</span>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <button class="bg-orange-600 text-white px-6 py-2 rounded hover:bg-orange-700">
                    S\'inscrire aux sorties
                </button>
            </div>
        </div>
    </div>
</div>',
                'excerpt' => 'Retour sur notre magnifique sortie à Belle-Île avec 24 plongeurs et des conditions exceptionnelles !',
                'tags' => ['sorties', 'belle-île', 'plongée', 'épaves'],
                'published_at' => new \DateTimeImmutable('-3 days'),
            ],
            [
                'title' => 'Formation Nitrox : Les inscriptions sont ouvertes',
                'content' => '<div class="max-w-4xl mx-auto">
    <div class="bg-green-100 p-6 rounded-lg mb-8">
        <h1 class="text-3xl font-bold text-green-800 mb-4">🌀 Formation Nitrox : Les inscriptions sont ouvertes !</h1>
        <div class="flex items-center text-sm text-gray-600">
            <span class="mr-4">📅 Publié le 25 septembre 2025</span>
            <span class="mr-4">👤 Thomas MARTIN - MF1</span>
            <span>🏷️ Formation, Nitrox, Spécialité</span>
        </div>
    </div>

    <div class="prose max-w-none">
        <p class="text-lg text-gray-700 mb-6">Vous souhaitez prolonger vos plongées et découvrir les techniques de plongée aux mélanges ? La formation <strong>Nitrox Confirmé</strong> est faite pour vous !</p>

        <div class="bg-blue-50 p-6 rounded-lg mb-8">
            <h2 class="text-xl font-bold text-blue-800 mb-4">🎯 Qu\'est-ce que le Nitrox ?</h2>
            <p class="mb-4">Le Nitrox est un mélange gazeux enrichi en oxygène (entre 22% et 40% au lieu de 21% dans l\'air). Cette technique permet :</p>
            <ul class="space-y-2">
                <li class="flex items-start">
                    <span class="text-green-500 mr-2 mt-1">✓</span>
                    <span><strong>Des plongées plus longues</strong> grâce à des paliers réduits</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2 mt-1">✓</span>
                    <span><strong>Une meilleure sécurité</strong> avec moins d\'azote dissous</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2 mt-1">✓</span>
                    <span><strong>Moins de fatigue</strong> après les plongées</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2 mt-1">✓</span>
                    <span><strong>Plus d\'intervalles courts</strong> entre les plongées</span>
                </li>
            </ul>
        </div>

        <h2 class="text-2xl font-bold text-green-700 mb-4">📚 Programme de formation</h2>
        
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white border rounded-lg p-6">
                <h3 class="font-bold text-green-800 mb-4">📖 Partie Théorique (6h)</h3>
                <ul class="space-y-2 text-sm">
                    <li>• Physique des gaz et mélanges</li>
                    <li>• Avantages et limites du Nitrox</li>
                    <li>• Calculs de profondeur équivalente</li>
                    <li>• Utilisation des tables Nitrox</li>
                    <li>• Analyse des mélanges gazeux</li>
                    <li>• Règles de sécurité spécifiques</li>
                    <li>• Matériel compatible Nitrox</li>
                </ul>
            </div>
            
            <div class="bg-white border rounded-lg p-6">
                <h3 class="font-bold text-green-800 mb-4">🌊 Partie Pratique (4 plongées)</h3>
                <ul class="space-y-2 text-sm">
                    <li>• Analyse du mélange avant plongée</li>
                    <li>• Planification avec tables Nitrox</li>
                    <li>• 2 plongées en piscine (procédures)</li>
                    <li>• 2 plongées en milieu naturel</li>
                    <li>• Comparaison air/Nitrox</li>
                    <li>• Gestion des paliers Nitrox</li>
                    <li>• Utilisation de l\'analyseur</li>
                </ul>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-green-700 mb-4">🎓 Conditions d\'accès</h2>
        
        <div class="bg-yellow-50 p-6 rounded-lg mb-6">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-bold mb-3">Prérequis obligatoires :</h3>
                    <ul class="space-y-1 text-sm">
                        <li>✓ Niveau 2 FFESSM ou équivalent</li>
                        <li>✓ Minimum 25 plongées validées</li>
                        <li>✓ Certificat médical de moins de 1 an</li>
                        <li>✓ Être à jour de ses cotisations</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold mb-3">Recommandations :</h3>
                    <ul class="space-y-1 text-sm">
                        <li>• Expérience en autonomie</li>
                        <li>• Aisance avec les tables</li>
                        <li>• Maîtrise de la planification</li>
                        <li>• Stabilisation parfaite</li>
                    </ul>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-green-700 mb-4">📅 Planning et tarifs</h2>
        
        <div class="bg-white border rounded-lg overflow-hidden mb-6">
            <div class="bg-green-600 text-white p-4">
                <h3 class="font-bold text-lg">Session Automne 2025</h3>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-bold mb-3">📆 Dates :</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Théorie (soirs)</span>
                                <span>7, 14, 21 novembre</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Pratique piscine</span>
                                <span>28 nov, 5 déc</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Plongées mer</span>
                                <span>12 décembre</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Examen</span>
                                <span>19 décembre</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-bold mb-3">💰 Tarifs :</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Membres du club</span>
                                <span class="font-bold text-green-600">120€</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Externes</span>
                                <span class="font-bold">180€</span>
                            </div>
                            <div class="text-xs text-gray-600 mt-3">
                                Inclus : manuel, certification FFESSM, mélanges Nitrox pour les plongées pratiques
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-green-700 mb-4">👨‍🏫 Votre formateur</h2>
        
        <div class="bg-gray-50 p-6 rounded-lg mb-6">
            <div class="flex items-start space-x-4">
                <div class="w-20 h-20 bg-green-600 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold text-xl">TM</span>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Thomas MARTIN</h3>
                    <p class="text-green-700 font-semibold mb-2">Moniteur Fédéral 1er degré - Spécialité Nitrox</p>
                    <div class="text-sm text-gray-700 space-y-1">
                        <p>• 15 ans d\'expérience en formation Nitrox</p>
                        <p>• Plus de 200 plongeurs certifiés</p>
                        <p>• Instructeur Trimix et recycleur</p>
                        <p>• Moniteur depuis 2010</p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-green-700 mb-4">📝 Inscription</h2>
        
        <div class="bg-orange-50 p-6 rounded-lg mb-6">
            <h3 class="font-bold text-orange-800 mb-4">🏃‍♂️ Places limitées à 8 participants !</h3>
            <div class="space-y-3 mb-4">
                <p><strong>Dossier d\'inscription à fournir :</strong></p>
                <ul class="text-sm space-y-1 ml-4">
                    <li>• Formulaire d\'inscription complété</li>
                    <li>• Copie du certificat médical</li>
                    <li>• Copie de la certification Niveau 2</li>
                    <li>• Justificatif des 25 plongées minimum</li>
                    <li>• Règlement (chèque ou espèces)</li>
                </ul>
            </div>
            <div class="text-center">
                <button class="bg-orange-600 text-white px-8 py-3 rounded-lg hover:bg-orange-700 mr-4">
                    Télécharger le dossier
                </button>
                <button class="border border-orange-600 text-orange-600 px-8 py-3 rounded-lg hover:bg-orange-50">
                    Contact formateur
                </button>
            </div>
        </div>

        <div class="bg-blue-50 p-6 rounded-lg">
            <h3 class="font-bold text-blue-800 mb-4">❓ Questions fréquentes</h3>
            <div class="space-y-4">
                <div>
                    <h4 class="font-semibold text-sm">Le matériel doit-il être compatible Nitrox ?</h4>
                    <p class="text-sm text-gray-700">Jusqu\'à 40% d\'O2, le matériel standard convient. Au-delà, un matériel spécialisé est nécessaire.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-sm">Puis-je plonger au Nitrox partout ?</h4>
                    <p class="text-sm text-gray-700">La plupart des centres de plongée proposent du Nitrox. Vérifiez toujours la disponibilité avant votre voyage.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-sm">Quelle est la durée de validité ?</h4>
                    <p class="text-sm text-gray-700">La certification Nitrox FFESSM est permanente, mais il est recommandé de pratiquer régulièrement.</p>
                </div>
            </div>
        </div>
    </div>
</div>',
                'excerpt' => 'Inscriptions ouvertes pour la formation Nitrox ! Prolongez vos plongées avec les mélanges enrichis.',
                'tags' => ['formation', 'nitrox', 'spécialité'],
                'published_at' => new \DateTimeImmutable('-5 days'),
            ]
        ];

        foreach ($newsArticles as $articleData) {
            $page = new Page();
            $page->setTitle($articleData['title'])
                 ->setContent($articleData['content'])
                 ->setExcerpt($articleData['excerpt'])
                 ->setTags($articleData['tags'])
                 ->setType('blog')
                 ->setStatus('published')
                 ->setAuthor($admin)
                 ->setPublishedAt($articleData['published_at']);
                 
            $page->publish();
            $this->entityManager->persist($page);
            $io->writeln("✓ Créé: {$articleData['title']}");
        }
    }
}