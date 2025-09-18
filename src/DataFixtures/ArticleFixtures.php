<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleFixtures extends Fixture
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

        $articles = [
            [
                'title' => 'Quand le réveil sonne Tôt un dimanche matin de fin août…',
                'slug' => 'quand-le-reveil-sonne-tot-un-dimanche-matin-de-fin-aout',
                'content' => '<div class="prose max-w-none">
<p>Certains se sont passés de grasse matinée ce dimanche et ne l\'ont pas regretté !!</p>

<p>Par Bérengère :</p>

<p>Dimanche matin, belle lumière sur le golfe, belle visibilité aux Gorêts, bancs de poissons, et petit déjeuner à bord du Fleur de Corail... Que demander de plus ?</p>

<p>Merci pour cette belle matinée !</p>

[carousel]
https://www.plongee-venetes.fr/wp-content/uploads/2025/08/matinee-gorets-1.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/08/matinee-gorets-2.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/08/matinee-gorets-3.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/08/matinee-gorets-4.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/08/matinee-gorets-5.jpg
[/carousel]
</div>',
                'excerpt' => 'Une belle matinée de plongée aux Gorêts avec une belle lumière sur le golfe et une excellente visibilité.',
                'category' => 'Sorties',
                'tags' => ['sortie', 'Gorêts', 'plongée matin', 'golfe'],
                'published_at' => '2025-08-25 10:30:00'
            ],
            [
                'title' => 'Plongées baptêmes à Pont Lorois 17/08/25',
                'slug' => 'plongees-baptemes-a-pont-lorois-17-08-25',
                'content' => '<div class="prose max-w-none">
<p>Dimanche 17 août, Chris a organisé des plongées et baptêmes à Pont Lorois.</p>

<p>28 participants au total (plongeurs, moniteurs et baptisés) pour cette belle journée ensoleillée sur la Ria d\'Etel.</p>

<p>L\'eau était translucide et agréable !</p>
</div>',
                'excerpt' => '28 participants pour une belle journée de plongées et baptêmes sur la Ria d\'Etel avec une eau translucide.',
                'category' => 'Baptêmes',
                'tags' => ['baptême', 'Pont Lorois', 'Ria d\'Etel', 'formation'],
                'published_at' => '2025-08-17 16:00:00'
            ],
            [
                'title' => 'PESH6 de René',
                'slug' => 'pesh6-de-rene',
                'content' => '<div class="prose max-w-none">
<p>René a validé son premier niveau de plongée, le PESH 6 mètres au Vieux Passage à Etel.</p>

<p>La validation s\'est faite avec l\'aide de Romuald, Eric, Sébastien, Fred, Fabien et Claudio.</p>

<p>Journée magnifique et très belle visibilité !</p>

[carousel]
https://www.plongee-venetes.fr/wp-content/uploads/2025/08/pesh6-rene-1.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/08/pesh6-rene-2.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/08/pesh6-rene-3.jpg
[/carousel]
</div>',
                'excerpt' => 'René obtient son premier niveau de plongée PESH 6 mètres au Vieux Passage à Etel.',
                'category' => 'Formations',
                'tags' => ['PESH6', 'formation', 'Vieux Passage', 'Etel'],
                'published_at' => '2025-08-03 14:00:00'
            ],
            [
                'title' => 'Sortie à Houat',
                'slug' => 'sortie-a-houat',
                'content' => '<div class="prose max-w-none">
<p>Sortie plongée du Club Subaquatique des Vénètes à l\'île de Houat le 21 juin.</p>

<p>Belle plongée avec une rencontre exceptionnelle : un phoque curieux est venu nous rendre visite !</p>

[carousel]
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/houat-1.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/houat-2.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/houat-3.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/houat-4.jpg
[/carousel]

<p>Vidéo du phoque rencontré lors de la plongée :</p>
<video controls style="width: 100%; max-width: 600px;">
    <source src="https://www.plongee-venetes.fr/wp-content/uploads/2025/06/phoque-houat.mp4" type="video/mp4">
    Votre navigateur ne supporte pas la lecture de vidéos.
</video>
</div>',
                'excerpt' => 'Belle sortie plongée à Houat avec une rencontre exceptionnelle avec un phoque curieux.',
                'category' => 'Sorties',
                'tags' => ['sortie', 'Houat', 'phoque', 'faune marine'],
                'published_at' => '2025-06-21 18:00:00'
            ],
            [
                'title' => 'Plongée du soir Gorêts',
                'slug' => 'plongee-du-soir-gorets',
                'content' => '<div class="prose max-w-none">
<p>C\'est l\'été au CSV et le mot d\'ordre est CONVIVIALITÉ.</p>

<p>Plongée du soir aux Gorêts avec une température de l\'eau à 19 degrés et une bonne visibilité.</p>

<p>Une des palanquées a déployé un parachute de palier au mouillage. Plongée réussie !</p>

<p>Merci à Ludovic le pilote, à Béa la mousse et aux guides de palanquée.</p>

[carousel]
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/plongee-soir-gorets-1.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/plongee-soir-gorets-2.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/plongee-soir-gorets-3.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/plongee-soir-gorets-4.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/plongee-soir-gorets-5.jpg
[/carousel]
</div>',
                'excerpt' => 'Plongée du soir conviviale aux Gorêts avec une eau à 19°C et une bonne visibilité.',
                'category' => 'Sorties',
                'tags' => ['plongée soir', 'Gorêts', 'convivialité', 'été'],
                'published_at' => '2025-06-15 20:30:00'
            ],
            [
                'title' => 'Handicap et plongée',
                'slug' => 'handicap-et-plongee',
                'content' => '<div class="prose max-w-none">
<p>Sortie de la section Handicap du CSV.</p>

<p>René et Romuald ont été accueillis par le club d\'Etel (CNRE) pour leur première plongée mer de l\'année.</p>

<p>Une barge équipée d\'un palan et d\'un harnais a permis à René de mettre à l\'eau. Plongée au Vieux Passage avec Eric B, Seb P, Fred B, Romuald et René.</p>

<p>Vivement les prochaines aventures !</p>

<p>Capture d\'écran d\'un article de presse relatant l\'événement.</p>
</div>',
                'excerpt' => 'Première plongée mer de l\'année pour la section Handicap, accueillie par le club d\'Etel.',
                'category' => 'Handisub',
                'tags' => ['handicap', 'handisub', 'inclusion', 'Vieux Passage'],
                'published_at' => '2025-06-10 16:00:00'
            ],
            [
                'title' => 'Journée handisub Gabriel Deshayes CSV CSA',
                'slug' => 'journee-handisub-gabriel-deshayes-csv-csa',
                'content' => '<div class="prose max-w-none">
<p>Le vendredi 20 juin, le Club Subaquatique des Vénètes a accueilli une journée Handisub avec l\'Association Gabriel Deshayes.</p>

<p>6 jeunes d\'une classe spécialisée pour troubles du langage ont été initiés à la plongée sous-marine.</p>

<p>Participants : 6 élèves et 2 enseignants, encadrés par les moniteurs du Club Subaquatique Auréen.</p>

<p>Formation initiale en piscine suivie de plongées en mer. Tous les participants ont reçu leurs diplômes de baptême de plongée.</p>

<p>Journée marquée par le soleil et la bonne humeur, tous sont repartis avec le sourire après leur aventure sous-marine.</p>

<p>Pique-nique partagé sur le "Fleur de Corail".</p>
</div>',
                'excerpt' => '6 jeunes de l\'Association Gabriel Deshayes initiés à la plongée dans le cadre d\'une journée handisub.',
                'category' => 'Handisub',
                'tags' => ['handisub', 'Gabriel Deshayes', 'inclusion', 'baptême'],
                'published_at' => '2025-06-20 17:00:00'
            ],
            [
                'title' => 'Pot N1 pour l\'obtention de leur diplôme',
                'slug' => 'pot-n1-pour-lobtention-de-leur-diplome',
                'content' => '<div class="prose max-w-none">
<p>Le 6 juin, les nouveaux diplômés "Niveau 1" du Club Subaquatique des Vénètes ont organisé un barbecue pour fêter leur réussite.</p>

<p>Ils ont invité les membres du club et les moniteurs qui les ont accompagnés tout au long de l\'année.</p>

<p>Soirée joyeuse avec Frédéric qui a animé le groupe en chantant, et les chansons ont continué tard dans la nuit.</p>

<p>Le club espère que le prochain groupe de plongeurs 2025/2026 maintiendra le même esprit positif.</p>

[carousel]
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/pot-n1-1.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/pot-n1-2.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/pot-n1-3.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/06/pot-n1-4.jpg
[/carousel]
</div>',
                'excerpt' => 'Barbecue organisé par les nouveaux diplômés Niveau 1 pour célébrer leur réussite avec le club.',
                'category' => 'Formations',
                'tags' => ['niveau 1', 'diplôme', 'célébration', 'barbecue'],
                'published_at' => '2025-06-06 19:00:00'
            ],
            [
                'title' => 'Fin de formation niveau 1',
                'slug' => 'fin-de-formation-niveau-1',
                'content' => '<div class="prose max-w-none">
<p>Fin de formation Niveau 1 pour le Club Subaquatique des Vénètes.</p>

<p>La dernière journée de formation était le 17 mai, avec un soleil magnifique et des conditions de mer les plus idéales.</p>

<p>20 nouveaux plongeurs Niveau 1 ont terminé leur formation et n\'ont maintenant qu\'une envie : plonger et découvrir les sites.</p>

<p>Un grand merci aux moniteurs pour cette session et à nos plongeurs pour leur bonne humeur.</p>

[carousel]
https://www.plongee-venetes.fr/wp-content/uploads/2025/05/fin-formation-n1-1.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/05/fin-formation-n1-2.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/05/fin-formation-n1-3.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/05/fin-formation-n1-4.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/05/fin-formation-n1-5.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/05/fin-formation-n1-6.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/05/fin-formation-n1-7.jpg,
https://www.plongee-venetes.fr/wp-content/uploads/2025/05/fin-formation-n1-8.jpg
[/carousel]
</div>',
                'excerpt' => '20 nouveaux plongeurs Niveau 1 ont terminé leur formation dans des conditions idéales le 17 mai.',
                'category' => 'Formations',
                'tags' => ['niveau 1', 'formation', 'diplôme', 'réussite'],
                'published_at' => '2025-05-17 16:00:00'
            ]
        ];

        foreach ($articles as $articleData) {
            $article = new Article();
            $article->setTitle($articleData['title'])
                    ->setSlug($articleData['slug'])
                    ->setContent($articleData['content'])
                    ->setExcerpt($articleData['excerpt'])
                    ->setCategory($articleData['category'])
                    ->setTags($articleData['tags'])
                    ->setAuthor($author)
                    ->setStatus('published')
                    ->setPublishedAt(new \DateTime($articleData['published_at']));

            $manager->persist($article);
        }

        $manager->flush();
    }
}