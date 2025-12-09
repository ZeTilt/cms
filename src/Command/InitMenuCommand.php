<?php

namespace App\Command;

use App\Entity\Menu;
use App\Entity\MenuItem;
use App\Repository\MenuRepository;
use App\Repository\PageRepository;
use App\Service\MenuManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-menu',
    description: 'Initialise le menu principal avec la structure par défaut',
)]
class InitMenuCommand extends Command
{
    public function __construct(
        private MenuManager $menuManager,
        private MenuRepository $menuRepository,
        private PageRepository $pageRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force la recréation même si le menu existe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $existingMenu = $this->menuRepository->findByLocation('main');

        if ($existingMenu && !$input->getOption('force')) {
            $io->warning('Le menu "main" existe déjà. Utilisez --force pour le recréer.');
            return Command::SUCCESS;
        }

        if ($existingMenu) {
            $io->note('Suppression du menu existant...');
            $this->menuManager->deleteMenu($existingMenu);
        }

        $io->section('Création du menu principal');

        // Créer le menu principal
        $menu = $this->menuManager->createMenu('Menu principal', 'main');
        $io->text('Menu "main" créé');

        // 1. Dropdown "Le club"
        $leClub = $this->menuManager->createMenuItem($menu, 'Le club', MenuItem::TYPE_DROPDOWN, null);
        $io->text('  - Dropdown "Le club" créé');

        $this->createPageMenuItem($menu, 'Qui sommes nous', 'qui-sommes-nous', $leClub, '👥');
        $this->createPageMenuItem($menu, 'Où nous trouver', 'ou-nous-trouver', $leClub, '📍');
        $this->createPageMenuItem($menu, 'Tarifs Adhésion et licence 2025', 'tarifs-2025', $leClub, '💰');
        $this->createPageMenuItem($menu, 'Nos partenaires', 'nos-partenaires', $leClub, '🤝');

        // 2. Dropdown "Nos activités"
        $activites = $this->menuManager->createMenuItem($menu, 'Nos activités', MenuItem::TYPE_DROPDOWN, null, [
            'cssClass' => 'w-72',
        ]);
        $io->text('  - Dropdown "Nos activités" créé');

        // Sous-titre Formations
        $this->menuManager->createMenuItem($menu, 'Formations', MenuItem::TYPE_DROPDOWN, $activites, [
            'cssClass' => 'nav-menu-header',
        ]);
        $this->createPageMenuItem($menu, 'Niveau 1', 'formation-niveau-1', $activites, '🤿');
        $this->createPageMenuItem($menu, 'Niveau 2 et 3', 'formation-niveau-2-et-3', $activites, '🔰');
        $this->createPageMenuItem($menu, 'Guide de palanquée', 'guide-de-palanquee', $activites, '👨‍🏫');
        $this->createPageMenuItem($menu, 'Autres formations', 'autres-formations', $activites, '🎓');

        // Sous-titre Activités
        $this->menuManager->createMenuItem($menu, 'Activités', MenuItem::TYPE_DROPDOWN, $activites, [
            'cssClass' => 'nav-menu-header mt-2',
        ]);
        $this->createPageMenuItem($menu, 'Les sorties', 'les-sorties', $activites, '🏊');
        $this->createPageMenuItem($menu, 'Plongeurs extérieurs', 'plongeurs-exterieurs', $activites, '🏊‍♂️');
        $this->createPageMenuItem($menu, 'Apnée', 'apnee', $activites, '🫁');
        $this->createPageMenuItem($menu, 'La piscine', 'la-piscine', $activites, '🏊‍♀️');
        $this->createPageMenuItem($menu, 'Gonflage', 'gonflage', $activites, '🫧');

        // 3. Lien Calendrier
        $this->menuManager->createMenuItem($menu, 'Calendrier', MenuItem::TYPE_ROUTE, null, [
            'route' => 'public_calendar',
        ]);
        $io->text('  - Lien "Calendrier" créé');

        // 4. Lien Actualités
        $this->menuManager->createMenuItem($menu, 'Actualités', MenuItem::TYPE_ROUTE, null, [
            'route' => 'blog_index',
        ]);
        $io->text('  - Lien "Actualités" créé');

        $io->success('Menu principal initialisé avec succès !');
        $io->note('Les liens de connexion/déconnexion et Contact sont gérés directement dans le template.');

        return Command::SUCCESS;
    }

    private function createPageMenuItem(
        Menu $menu,
        string $label,
        string $slug,
        ?MenuItem $parent,
        ?string $icon = null
    ): ?MenuItem {
        $page = $this->pageRepository->findOneBy(['slug' => $slug]);

        $options = ['icon' => $icon];

        if ($page) {
            $options['page'] = $page;
            return $this->menuManager->createMenuItem($menu, $label, MenuItem::TYPE_PAGE, $parent, $options);
        } else {
            // Créer un lien vers la route avec le slug
            $options['route'] = 'public_page_show';
            $options['routeParams'] = ['slug' => $slug];
            return $this->menuManager->createMenuItem($menu, $label, MenuItem::TYPE_ROUTE, $parent, $options);
        }
    }
}
