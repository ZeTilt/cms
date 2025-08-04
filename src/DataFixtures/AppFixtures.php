<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\AttributeDefinition;
use App\Entity\EntityAttribute;
use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\EventRegistration;
use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\Module;
use App\Entity\Page;
use App\Entity\Service;
use App\Entity\User;
use App\Entity\UserType;
use App\Entity\UserTypeAttribute;
use App\Entity\UserAttribute;
use App\Service\AttributeDefinitionManager;
use App\Service\AttributeManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private AttributeDefinitionManager $attributeDefinitionManager,
        private AttributeManager $attributeManager
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Clear existing data
        $manager->getConnection()->executeStatement('DELETE FROM entity_attributes');
        $manager->getConnection()->executeStatement('DELETE FROM event_registrations');
        $manager->getConnection()->executeStatement('DELETE FROM events');
        $manager->getConnection()->executeStatement('DELETE FROM event_types');
        $manager->getConnection()->executeStatement('DELETE FROM articles');
        $manager->getConnection()->executeStatement('DELETE FROM gallery');
        $manager->getConnection()->executeStatement('DELETE FROM services');
        $manager->getConnection()->executeStatement('DELETE FROM users');
        $manager->getConnection()->executeStatement('DELETE FROM user_types');
        $manager->getConnection()->executeStatement('DELETE FROM user_type_attributes');
        $manager->getConnection()->executeStatement('DELETE FROM attribute_definitions');
        $manager->getConnection()->executeStatement('DELETE FROM pages');
        $manager->getConnection()->executeStatement('UPDATE sqlite_sequence SET seq = 0');

        // Create modules first
        $this->loadModules($manager);
        
        // Create EAV attribute definitions first
        $this->loadEavAttributeDefinitions($manager);
        
        // Create event types
        $eventTypes = $this->loadEventTypes($manager);
        
        // Create user types and their attributes
        $userTypes = $this->loadUserTypes($manager);
        
        // Create users with diving levels and comprehensive data
        $users = $this->loadUsers($manager, $userTypes);
        
        // Create sample content with lots of data
        $this->loadPages($manager, $users);
        $this->loadArticles($manager, $users);
        $this->loadServices($manager);
        $events = $this->loadEvents($manager, $users, $eventTypes);
        $this->loadEventRegistrations($manager, $events, $users);
        $this->loadGalleries($manager, $users);
        
        $manager->flush();
    }

    private function loadModules(ObjectManager $manager): void
    {
        $modules = [
            ['name' => 'blog', 'display_name' => 'Blog', 'description' => 'Article and blog management'],
            ['name' => 'events', 'display_name' => 'Events', 'description' => 'Event calendar and management'],
            ['name' => 'services', 'display_name' => 'Services', 'description' => 'Service catalog management'],
            ['name' => 'gallery', 'display_name' => 'Gallery', 'description' => 'Photo gallery management'],
            ['name' => 'userplus', 'display_name' => 'User Plus', 'description' => 'Advanced user management'],
            ['name' => 'registration', 'display_name' => 'Registration', 'description' => 'User registration system'],
            ['name' => 'business', 'display_name' => 'Business', 'description' => 'Business customizations'],
            ['name' => 'translation', 'display_name' => 'Translation', 'description' => 'Multi-language support']
        ];

        foreach ($modules as $moduleData) {
            $module = new Module();
            $module->setName($moduleData['name']);
            $module->setDisplayName($moduleData['display_name']);
            $module->setDescription($moduleData['description']);
            $module->setActive(true);
            $module->setConfig([]);
            $manager->persist($module);
        }
        
        $manager->flush();
    }

    private function loadUserTypes(ObjectManager $manager): array
    {
        $userTypes = [];

        // Create diving user types
        $plongeurType = new UserType();
        $plongeurType->setName('plongeur');
        $plongeurType->setDisplayName('Plongeur');
        $plongeurType->setDescription('Membre plongeur du club');
        $plongeurType->setActive(true);
        $manager->persist($plongeurType);
        $userTypes['plongeur'] = $plongeurType;

        $instructorType = new UserType();
        $instructorType->setName('instructeur');
        $instructorType->setDisplayName('Instructeur');
        $instructorType->setDescription('Instructeur de plongée');
        $instructorType->setActive(true);
        $manager->persist($instructorType);
        $userTypes['instructeur'] = $instructorType;

        $directorType = new UserType();
        $directorType->setName('directeur_plongee');
        $directorType->setDisplayName('Directeur de Plongée');
        $directorType->setDescription('Directeur de plongée du club');
        $directorType->setActive(true);
        $manager->persist($directorType);
        $userTypes['directeur'] = $directorType;

        $manager->flush();

        // Create user type attributes for diving levels
        $niveauPlongeeAttr = new UserTypeAttribute();
        $niveauPlongeeAttr->setUserType($plongeurType);
        $niveauPlongeeAttr->setAttributeKey('niveau_plongee');
        $niveauPlongeeAttr->setDisplayName('Niveau de plongée');
        $niveauPlongeeAttr->setAttributeType('select');
        $niveauPlongeeAttr->setRequired(true);
        $niveauPlongeeAttr->setOptions(['debutant', 'niveau1', 'niveau2', 'niveau3', 'niveau4', 'niveau5']);
        $niveauPlongeeAttr->setDefaultValue('debutant');
        $niveauPlongeeAttr->setDisplayOrder(1);
        $manager->persist($niveauPlongeeAttr);

        $dateValiditeAttr = new UserTypeAttribute();
        $dateValiditeAttr->setUserType($plongeurType);
        $dateValiditeAttr->setAttributeKey('date_validite_certificat');
        $dateValiditeAttr->setDisplayName('Validité certificat médical');
        $dateValiditeAttr->setAttributeType('date');
        $dateValiditeAttr->setRequired(true);
        $dateValiditeAttr->setDisplayOrder(2);
        $manager->persist($dateValiditeAttr);

        $numeroLicenceAttr = new UserTypeAttribute();
        $numeroLicenceAttr->setUserType($plongeurType);
        $numeroLicenceAttr->setAttributeKey('numero_licence');
        $numeroLicenceAttr->setDisplayName('Numéro de licence FFESSM');
        $numeroLicenceAttr->setAttributeType('text');
        $numeroLicenceAttr->setRequired(false);
        $numeroLicenceAttr->setDisplayOrder(3);
        $manager->persist($numeroLicenceAttr);

        $telephoneAttr = new UserTypeAttribute();
        $telephoneAttr->setUserType($plongeurType);
        $telephoneAttr->setAttributeKey('telephone');
        $telephoneAttr->setDisplayName('Téléphone');
        $telephoneAttr->setAttributeType('text');
        $telephoneAttr->setRequired(true);
        $telephoneAttr->setDisplayOrder(4);
        $manager->persist($telephoneAttr);

        $adresseAttr = new UserTypeAttribute();
        $adresseAttr->setUserType($plongeurType);  
        $adresseAttr->setAttributeKey('adresse');
        $adresseAttr->setDisplayName('Adresse');
        $adresseAttr->setAttributeType('textarea');
        $adresseAttr->setRequired(true);
        $adresseAttr->setDisplayOrder(5);
        $manager->persist($adresseAttr);

        // Pilot attribute for diver type
        $piloteAttr = new UserTypeAttribute();
        $piloteAttr->setUserType($plongeurType);
        $piloteAttr->setAttributeKey('pilote');
        $piloteAttr->setDisplayName('Pilote de bateau');
        $piloteAttr->setAttributeType('select');
        $piloteAttr->setRequired(false);
        $piloteAttr->setOptions(['non', 'oui']);
        $piloteAttr->setDefaultValue('non');
        $piloteAttr->setDisplayOrder(6);
        $manager->persist($piloteAttr);

        // Same attributes for instructor type
        $instructorNiveauAttr = new UserTypeAttribute();
        $instructorNiveauAttr->setUserType($instructorType);
        $instructorNiveauAttr->setAttributeKey('niveau_plongee');
        $instructorNiveauAttr->setDisplayName('Niveau de plongée');
        $instructorNiveauAttr->setAttributeType('select');
        $instructorNiveauAttr->setRequired(true);
        $instructorNiveauAttr->setOptions(['niveau4', 'niveau5', 'initiateur', 'e1', 'e2', 'e3', 'e4']);
        $instructorNiveauAttr->setDefaultValue('niveau4');
        $instructorNiveauAttr->setDisplayOrder(1);
        $manager->persist($instructorNiveauAttr);

        $instructorTelAttr = new UserTypeAttribute();
        $instructorTelAttr->setUserType($instructorType);
        $instructorTelAttr->setAttributeKey('telephone');
        $instructorTelAttr->setDisplayName('Téléphone');
        $instructorTelAttr->setAttributeType('text');
        $instructorTelAttr->setRequired(true);
        $instructorTelAttr->setDisplayOrder(2);
        $manager->persist($instructorTelAttr);

        $instructorAdresseAttr = new UserTypeAttribute();
        $instructorAdresseAttr->setUserType($instructorType);
        $instructorAdresseAttr->setAttributeKey('adresse');
        $instructorAdresseAttr->setDisplayName('Adresse');
        $instructorAdresseAttr->setAttributeType('textarea');
        $instructorAdresseAttr->setRequired(true);
        $instructorAdresseAttr->setDisplayOrder(3);
        $manager->persist($instructorAdresseAttr);

        // Director attributes
        $directorNiveauAttr = new UserTypeAttribute();
        $directorNiveauAttr->setUserType($directorType);
        $directorNiveauAttr->setAttributeKey('niveau_plongee');
        $directorNiveauAttr->setDisplayName('Niveau de plongée');
        $directorNiveauAttr->setAttributeType('select');
        $directorNiveauAttr->setRequired(true);
        $directorNiveauAttr->setOptions(['niveau5', 'initiateur', 'e3', 'e4']);
        $directorNiveauAttr->setDefaultValue('niveau5');
        $directorNiveauAttr->setDisplayOrder(1);
        $manager->persist($directorNiveauAttr);

        $manager->flush();

        return $userTypes;
    }

    private function loadUsers(ObjectManager $manager, array $userTypes): array
    {
        $users = [];

        // Super Admin
        $superAdmin = new User();
        $superAdmin->setEmail('superadmin@zetilt.fr')
                   ->setFirstName('Super')
                   ->setLastName('Admin')
                   ->setPassword($this->passwordHasher->hashPassword($superAdmin, 'superadmin123'))
                   ->setRoles(['ROLE_SUPER_ADMIN'])
                   ->setActive(true);
        $manager->persist($superAdmin);
        $users[] = $superAdmin;

        // Regular Admin
        $admin = new User();
        $admin->setEmail('admin@zetilt.fr')
              ->setFirstName('Jean')
              ->setLastName('Administrateur')
              ->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'))
              ->setRoles(['ROLE_ADMIN'])
              ->setActive(true);
        $manager->persist($admin);
        $users[] = $admin;

        // Create 50 diving members with different levels
        $divingMembers = [
            // Débutants (10 membres)
            ['first' => 'Marie', 'last' => 'Dupont', 'level' => 'debutant', 'email' => 'marie.dupont@email.com', 'phone' => '06.12.34.56.78', 'address' => '15 rue de la Mer, 83000 Toulon'],
            ['first' => 'Pierre', 'last' => 'Martin', 'level' => 'debutant', 'email' => 'pierre.martin@email.com', 'phone' => '06.23.45.67.89', 'address' => '22 avenue du Port, 83100 Toulon'],
            ['first' => 'Sophie', 'last' => 'Leroy', 'level' => 'debutant', 'email' => 'sophie.leroy@email.com', 'phone' => '06.34.56.78.90', 'address' => '8 impasse des Coraux, 83000 Toulon'],
            ['first' => 'Julien', 'last' => 'Moreau', 'level' => 'debutant', 'email' => 'julien.moreau@email.com', 'phone' => '06.45.67.89.01', 'address' => '31 chemin des Embiez, 83140 Six-Fours'],
            ['first' => 'Emma', 'last' => 'Bernard', 'level' => 'debutant', 'email' => 'emma.bernard@email.com', 'phone' => '06.56.78.90.12', 'address' => '5 rue du Soleil, 83110 Sanary'],
            ['first' => 'Lucas', 'last' => 'Petit', 'level' => 'debutant', 'email' => 'lucas.petit@email.com', 'phone' => '06.67.89.01.23', 'address' => '18 boulevard de la Plage, 83150 Bandol'],
            ['first' => 'Chloé', 'last' => 'Roux', 'level' => 'debutant', 'email' => 'chloe.roux@email.com', 'phone' => '06.78.90.12.34', 'address' => '12 avenue des Pins, 83000 Toulon'],
            ['first' => 'Thomas', 'last' => 'Girard', 'level' => 'debutant', 'email' => 'thomas.girard@email.com', 'phone' => '06.89.01.23.45', 'address' => '27 rue Maritime, 83140 Six-Fours'],
            ['first' => 'Léa', 'last' => 'Michel', 'level' => 'debutant', 'email' => 'lea.michel@email.com', 'phone' => '06.90.12.34.56', 'address' => '3 place du Marché, 83110 Sanary'],
            ['first' => 'Antoine', 'last' => 'Gauthier', 'level' => 'debutant', 'email' => 'antoine.gauthier@email.com', 'phone' => '06.01.23.45.67', 'address' => '14 chemin de la Corniche, 83150 Bandol'],

            // Niveau 1 (15 membres)
            ['first' => 'Camille', 'last' => 'Dubois', 'level' => 'niveau1', 'email' => 'camille.dubois@email.com', 'phone' => '06.12.23.34.45', 'address' => '21 rue des Palmiers, 83000 Toulon'],
            ['first' => 'Maxime', 'last' => 'Laurent', 'level' => 'niveau1', 'email' => 'maxime.laurent@email.com', 'phone' => '06.23.34.45.56', 'address' => '16 avenue Neptune, 83100 Toulon'],
            ['first' => 'Clara', 'last' => 'Simon', 'level' => 'niveau1', 'email' => 'clara.simon@email.com', 'phone' => '06.34.45.56.67', 'address' => '9 impasse Bleue, 83000 Toulon'],
            ['first' => 'Hugo', 'last' => 'Lefebvre', 'level' => 'niveau1', 'email' => 'hugo.lefebvre@email.com', 'phone' => '06.45.56.67.78', 'address' => '33 chemin des Algues, 83140 Six-Fours'],
            ['first' => 'Manon', 'last' => 'Morel', 'level' => 'niveau1', 'email' => 'manon.morel@email.com', 'phone' => '06.56.67.78.89', 'address' => '7 rue de la Jetée, 83110 Sanary'],
            ['first' => 'Théo', 'last' => 'Fournier', 'level' => 'niveau1', 'email' => 'theo.fournier@email.com', 'phone' => '06.67.78.89.90', 'address' => '19 boulevard du Littoral, 83150 Bandol'],
            ['first' => 'Océane', 'last' => 'Giraud', 'level' => 'niveau1', 'email' => 'oceane.giraud@email.com', 'phone' => '06.78.89.90.01', 'address' => '11 avenue des Vagues, 83000 Toulon'],
            ['first' => 'Nathan', 'last' => 'Bonnet', 'level' => 'niveau1', 'email' => 'nathan.bonnet@email.com', 'phone' => '06.89.90.01.12', 'address' => '25 rue des Dauphins, 83140 Six-Fours'],
            ['first' => 'Inès', 'last' => 'Dupuis', 'level' => 'niveau1', 'email' => 'ines.dupuis@email.com', 'phone' => '06.90.01.12.23', 'address' => '4 place des Pêcheurs, 83110 Sanary'],
            ['first' => 'Valentin', 'last' => 'Martinez', 'level' => 'niveau1', 'email' => 'valentin.martinez@email.com', 'phone' => '06.01.12.23.34', 'address' => '17 chemin de la Calanque, 83150 Bandol'],
            ['first' => 'Jade', 'last' => 'Andre', 'level' => 'niveau1', 'email' => 'jade.andre@email.com', 'phone' => '06.12.13.24.35', 'address' => '23 rue de l\'Océan, 83000 Toulon'],
            ['first' => 'Ethan', 'last' => 'Garcia', 'level' => 'niveau1', 'email' => 'ethan.garcia@email.com', 'phone' => '06.23.24.35.46', 'address' => '13 avenue des Îles, 83100 Toulon'],
            ['first' => 'Lola', 'last' => 'David', 'level' => 'niveau1', 'email' => 'lola.david@email.com', 'phone' => '06.34.35.46.57', 'address' => '6 impasse des Mouettes, 83000 Toulon'],
            ['first' => 'Mattéo', 'last' => 'Bertrand', 'level' => 'niveau1', 'email' => 'matteo.bertrand@email.com', 'phone' => '06.45.46.57.68', 'address' => '29 chemin des Rochers, 83140 Six-Fours'],
            ['first' => 'Zoé', 'last' => 'Robert', 'level' => 'niveau1', 'email' => 'zoe.robert@email.com', 'phone' => '06.56.57.68.79', 'address' => '1 rue des Sirènes, 83110 Sanary'],

            // Niveau 2 (12 membres)
            ['first' => 'Alexandre', 'last' => 'Richard', 'level' => 'niveau2', 'email' => 'alexandre.richard@email.com', 'phone' => '06.67.68.79.80', 'address' => '20 boulevard Aquatique, 83150 Bandol'],
            ['first' => 'Marine', 'last' => 'Vidal', 'level' => 'niveau2', 'email' => 'marine.vidal@email.com', 'phone' => '06.78.79.80.91', 'address' => '10 avenue Poséidon, 83000 Toulon'],
            ['first' => 'Romain', 'last' => 'Clement', 'level' => 'niveau2', 'email' => 'romain.clement@email.com', 'phone' => '06.89.80.91.02', 'address' => '26 rue des Gorgones, 83140 Six-Fours'],
            ['first' => 'Mathilde', 'last' => 'Vincent', 'level' => 'niveau2', 'email' => 'mathilde.vincent@email.com', 'phone' => '06.90.91.02.13', 'address' => '2 place des Coraux, 83110 Sanary'],
            ['first' => 'Florian', 'last' => 'Rousseau', 'level' => 'niveau2', 'email' => 'florian.rousseau@email.com', 'phone' => '06.01.02.13.24', 'address' => '15 chemin Sous-Marin, 83150 Bandol'],
            ['first' => 'Anaïs', 'last' => 'Lopez', 'level' => 'niveau2', 'email' => 'anais.lopez@email.com', 'phone' => '06.12.03.14.25', 'address' => '24 rue Abyssale, 83000 Toulon'],
            ['first' => 'Adrien', 'last' => 'Fontaine', 'level' => 'niveau2', 'email' => 'adrien.fontaine@email.com', 'phone' => '06.23.04.15.26', 'address' => '18 avenue des Profondeurs, 83100 Toulon'],
            ['first' => 'Pauline', 'last' => 'Chevalier', 'level' => 'niveau2', 'email' => 'pauline.chevalier@email.com', 'phone' => '06.34.05.16.27', 'address' => '8 impasse des Éponges, 83000 Toulon'],
            ['first' => 'Kevin', 'last' => 'Garnier', 'level' => 'niveau2', 'email' => 'kevin.garnier@email.com', 'phone' => '06.45.06.17.28', 'address' => '32 chemin des Herbiers, 83140 Six-Fours'],
            ['first' => 'Laura', 'last' => 'Faure', 'level' => 'niveau2', 'email' => 'laura.faure@email.com', 'phone' => '06.56.07.18.29', 'address' => '9 rue des Anémones, 83110 Sanary'],
            ['first' => 'Quentin', 'last' => 'Perrin', 'level' => 'niveau2', 'email' => 'quentin.perrin@email.com', 'phone' => '06.67.08.19.30', 'address' => '21 boulevard des Récifs, 83150 Bandol'],
            ['first' => 'Sarah', 'last' => 'Morin', 'level' => 'niveau2', 'email' => 'sarah.morin@email.com', 'phone' => '06.78.09.20.31', 'address' => '12 avenue des Profondeurs, 83000 Toulon'],

            // Niveau 3 (8 membres)
            ['first' => 'Benjamin', 'last' => 'Robin', 'level' => 'niveau3', 'email' => 'benjamin.robin@email.com', 'phone' => '06.89.10.21.32', 'address' => '28 rue des Épaves, 83140 Six-Fours'],
            ['first' => 'Margot', 'last' => 'Blanchard', 'level' => 'niveau3', 'email' => 'margot.blanchard@email.com', 'phone' => '06.90.11.22.33', 'address' => '5 place des Navigateurs, 83110 Sanary'],
            ['first' => 'Damien', 'last' => 'Girard', 'level' => 'niveau3', 'email' => 'damien.girard@email.com', 'phone' => '06.01.12.23.34', 'address' => '16 chemin de l\'Aventure, 83150 Bandol'],
            ['first' => 'Elodie', 'last' => 'Joly', 'level' => 'niveau3', 'email' => 'elodie.joly@email.com', 'phone' => '06.12.13.24.35', 'address' => '30 rue des Explorateurs, 83000 Toulon'],
            ['first' => 'Fabien', 'last' => 'Riviere', 'level' => 'niveau3', 'email' => 'fabien.riviere@email.com', 'phone' => '06.23.14.25.36', 'address' => '22 avenue des Aventuriers, 83100 Toulon'],
            ['first' => 'Aurélie', 'last' => 'Lucas', 'level' => 'niveau3', 'email' => 'aurelie.lucas@email.com', 'phone' => '06.34.15.26.37', 'address' => '7 impasse des Plongeurs, 83000 Toulon'],
            ['first' => 'Sébastien', 'last' => 'Brunet', 'level' => 'niveau3', 'email' => 'sebastien.brunet@email.com', 'phone' => '06.45.16.27.38', 'address' => '35 chemin des Trésors, 83140 Six-Fours'],
            ['first' => 'Mélanie', 'last' => 'Colin', 'level' => 'niveau3', 'email' => 'melanie.colin@email.com', 'phone' => '06.56.17.28.39', 'address' => '11 rue des Découvertes, 83110 Sanary'],

            // Niveau 4 (3 membres)
            ['first' => 'Laurent', 'last' => 'Barbier', 'level' => 'niveau4', 'email' => 'laurent.barbier@email.com', 'phone' => '06.67.18.29.40', 'address' => '25 boulevard des Maîtres, 83150 Bandol'],
            ['first' => 'Virginie', 'last' => 'Arnaud', 'level' => 'niveau4', 'email' => 'virginie.arnaud@email.com', 'phone' => '06.78.19.30.41', 'address' => '13 avenue de l\'Excellence, 83000 Toulon'],
            ['first' => 'David', 'last' => 'Marchand', 'level' => 'niveau4', 'email' => 'david.marchand@email.com', 'phone' => '06.89.20.31.42', 'address' => '19 rue de la Maîtrise, 83140 Six-Fours'],

            // Niveau 5 (2 membres)
            ['first' => 'Catherine', 'last' => 'Lemaire', 'level' => 'niveau5', 'email' => 'catherine.lemaire@email.com', 'phone' => '06.90.21.32.43', 'address' => '6 place des Experts, 83110 Sanary'],
            ['first' => 'Philippe', 'last' => 'Dufour', 'level' => 'niveau5', 'email' => 'philippe.dufour@email.com', 'phone' => '06.01.22.33.44', 'address' => '27 chemin de l\'Elite, 83150 Bandol'],
        ];

        // Create all diving members
        foreach ($divingMembers as $memberData) {
            $user = new User();
            $user->setEmail($memberData['email'])
                 ->setFirstName($memberData['first'])
                 ->setLastName($memberData['last'])
                 ->setPassword($this->passwordHasher->hashPassword($user, 'plongeur123'))
                 ->setRoles(['ROLE_USER'])
                 ->setActive(true)
                 ->setUserType($userTypes['plongeur']);
            
            $manager->persist($user);
            $manager->flush(); // Flush to get the ID
            
            // Create user attributes using EAV system
            $this->attributeManager->setAttribute($user, 'niveau_plongee', $memberData['level'], 'select');
            $this->attributeManager->setAttribute($user, 'telephone', $memberData['phone'], 'text');
            $this->attributeManager->setAttribute($user, 'adresse', $memberData['address'], 'textarea');
            $this->attributeManager->setAttribute($user, 'date_validite_certificat', (new \DateTime('+1 year'))->format('Y-m-d'), 'date');
            
            // Add license number for advanced divers
            if (in_array($memberData['level'], ['niveau2', 'niveau3', 'niveau4', 'niveau5'])) {
                $this->attributeManager->setAttribute($user, 'numero_licence', 'FFESSM-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT), 'text');
            }

            // Add pilot attribute for some members (20% with higher levels)
            $isPilot = in_array($memberData['level'], ['niveau3', 'niveau4', 'niveau5']) && rand(1, 5) === 1;
            $this->attributeManager->setAttribute($user, 'pilote', $isPilot ? 'oui' : 'non', 'select');
            
            // Add Nitrox certification for some advanced divers
            if (in_array($memberData['level'], ['niveau3', 'niveau4', 'niveau5']) && rand(1, 3) === 1) {
                $nitroxLevels = ['nitrox_confirme', 'nitrox_elementaire'];
                $this->attributeManager->setAttribute($user, 'niveau_nitrox', $nitroxLevels[array_rand($nitroxLevels)], 'select');
            } else {
                $this->attributeManager->setAttribute($user, 'niveau_nitrox', 'aucun', 'select');
            }
            
            $users[] = $user;
        }

        // Create 5 instructors
        $instructors = [
            ['first' => 'Jean', 'last' => 'Instructeur', 'level' => 'e1', 'email' => 'jean.instructeur@email.com'],
            ['first' => 'Marie', 'last' => 'Formatrice', 'level' => 'e2', 'email' => 'marie.formatrice@email.com'],
            ['first' => 'Pierre', 'last' => 'Moniteur', 'level' => 'niveau4', 'email' => 'pierre.moniteur@email.com'],
            ['first' => 'Sophie', 'last' => 'Enseignante', 'level' => 'e1', 'email' => 'sophie.enseignante@email.com'],
            ['first' => 'Paul', 'last' => 'Formateur', 'level' => 'e3', 'email' => 'paul.formateur@email.com'],
        ];

        foreach ($instructors as $instructorData) {
            $user = new User();
            $user->setEmail($instructorData['email'])
                 ->setFirstName($instructorData['first'])
                 ->setLastName($instructorData['last'])
                 ->setPassword($this->passwordHasher->hashPassword($user, 'instructeur123'))
                 ->setRoles(['ROLE_USER'])
                 ->setActive(true)
                 ->setUserType($userTypes['instructeur']);
            
            $manager->persist($user);
            $manager->flush();
            
            // Create instructor attributes using EAV system
            $this->attributeManager->setAttribute($user, 'niveau_plongee', $instructorData['level'], 'select');
            $this->attributeManager->setAttribute($user, 'telephone', '06.' . rand(10, 99) . '.' . rand(10, 99) . '.' . rand(10, 99) . '.' . rand(10, 99), 'text');
            $this->attributeManager->setAttribute($user, 'adresse', rand(1, 50) . ' rue de l\'Enseignement, 8300' . rand(0, 9) . ' Var', 'textarea');
            $this->attributeManager->setAttribute($user, 'date_validite_certificat', (new \DateTime('+2 years'))->format('Y-m-d'), 'date');
            $this->attributeManager->setAttribute($user, 'numero_licence', 'FFESSM-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT), 'text');
            $this->attributeManager->setAttribute($user, 'pilote', 'oui', 'select'); // All instructors are pilots
            $this->attributeManager->setAttribute($user, 'niveau_nitrox', 'nitrox_confirme', 'select'); // All instructors have Nitrox
            
            $users[] = $user;
        }

        // Create 2 directors
        $directors = [
            ['first' => 'Michel', 'last' => 'Directeur', 'level' => 'e4', 'email' => 'michel.directeur@email.com'],
            ['first' => 'Francine', 'last' => 'Responsable', 'level' => 'e3', 'email' => 'francine.responsable@email.com'],
        ];

        foreach ($directors as $directorData) {
            $user = new User();
            $user->setEmail($directorData['email'])
                 ->setFirstName($directorData['first'])
                 ->setLastName($directorData['last'])
                 ->setPassword($this->passwordHasher->hashPassword($user, 'directeur123'))
                 ->setRoles(['ROLE_ADMIN'])
                 ->setActive(true)
                 ->setUserType($userTypes['directeur']);
            
            $manager->persist($user);
            $manager->flush();
            
            // Create director attributes using EAV system
            $this->attributeManager->setAttribute($user, 'niveau_plongee', $directorData['level'], 'select');
            $this->attributeManager->setAttribute($user, 'telephone', '06.' . rand(10, 99) . '.' . rand(10, 99) . '.' . rand(10, 99) . '.' . rand(10, 99), 'text');
            $this->attributeManager->setAttribute($user, 'adresse', rand(1, 30) . ' avenue de la Direction, 8300' . rand(0, 9) . ' Var', 'textarea');
            $this->attributeManager->setAttribute($user, 'date_validite_certificat', (new \DateTime('+2 years'))->format('Y-m-d'), 'date');
            $this->attributeManager->setAttribute($user, 'numero_licence', 'FFESSM-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT), 'text');
            $this->attributeManager->setAttribute($user, 'pilote', 'oui', 'select'); // All directors are pilots
            $this->attributeManager->setAttribute($user, 'niveau_nitrox', 'nitrox_confirme', 'select'); // All directors have Nitrox
            
            $users[] = $user;
        }

        $manager->flush();
        return $users;
    }

    private function loadEavAttributeDefinitions(ObjectManager $manager): void
    {
        // Define EAV attributes for all entities - specific to diving club
        $attributeDefinitions = [
            // User attributes - diving focused
            ['entity' => 'User', 'key' => 'niveau_plongee', 'name' => 'Niveau de plongée', 'type' => 'select', 'options' => ['debutant', 'niveau1', 'niveau2', 'niveau3', 'niveau4', 'niveau5', 'initiateur', 'e1', 'e2', 'e3', 'e4']],
            ['entity' => 'User', 'key' => 'numero_licence', 'name' => 'Numéro licence FFESSM', 'type' => 'text'],
            ['entity' => 'User', 'key' => 'date_validite_certificat', 'name' => 'Validité certificat médical', 'type' => 'date'],
            ['entity' => 'User', 'key' => 'telephone', 'name' => 'Téléphone', 'type' => 'text'],
            ['entity' => 'User', 'key' => 'telephone_urgence', 'name' => 'Téléphone d\'urgence', 'type' => 'text'],
            ['entity' => 'User', 'key' => 'adresse', 'name' => 'Adresse', 'type' => 'textarea'],
            ['entity' => 'User', 'key' => 'date_naissance', 'name' => 'Date de naissance', 'type' => 'date'],
            ['entity' => 'User', 'key' => 'profession', 'name' => 'Profession', 'type' => 'text'],
            ['entity' => 'User', 'key' => 'pilote', 'name' => 'Pilote de bateau', 'type' => 'select', 'options' => ['non', 'oui']],
            ['entity' => 'User', 'key' => 'niveau_nitrox', 'name' => 'Niveau Nitrox', 'type' => 'select', 'options' => ['aucun', 'nitrox_confirme', 'nitrox_elementaire']],
            
            // Event attributes - diving specific
            ['entity' => 'Event', 'key' => 'niveau_requis', 'name' => 'Niveau minimum requis', 'type' => 'select', 'options' => ['debutant', 'niveau1', 'niveau2', 'niveau3', 'niveau4', 'niveau5']],
            ['entity' => 'Event', 'key' => 'profondeur_max', 'name' => 'Profondeur maximale (m)', 'type' => 'number'],
            ['entity' => 'Event', 'key' => 'prix', 'name' => 'Prix de la sortie (€)', 'type' => 'number'],
            ['entity' => 'Event', 'key' => 'materiel_fourni', 'name' => 'Matériel fourni', 'type' => 'boolean'],
            ['entity' => 'Event', 'key' => 'site_plongee', 'name' => 'Site de plongée', 'type' => 'text'],
            ['entity' => 'Event', 'key' => 'conditions_mer', 'name' => 'Conditions de mer', 'type' => 'select', 'options' => ['calme', 'petite_houle', 'houle', 'forte_houle']],
            ['entity' => 'Event', 'key' => 'visibilite', 'name' => 'Visibilité (m)', 'type' => 'number'],
            ['entity' => 'Event', 'key' => 'temperature_eau', 'name' => 'Température eau (°C)', 'type' => 'number'],
            ['entity' => 'Event', 'key' => 'type_plongee', 'name' => 'Type de plongée', 'type' => 'select', 'options' => ['exploration', 'formation', 'technique', 'epave', 'nuit', 'profonde']],
            
            // Service attributes - diving school focused
            ['entity' => 'Service', 'key' => 'duree_formation', 'name' => 'Durée formation', 'type' => 'text'],
            ['entity' => 'Service', 'key' => 'niveau_prerequis', 'name' => 'Niveau prérequis', 'type' => 'select', 'options' => ['aucun', 'debutant', 'niveau1', 'niveau2', 'niveau3', 'niveau4']],
            ['entity' => 'Service', 'key' => 'certificat_delivre', 'name' => 'Certificat FFESSM délivré', 'type' => 'text'],
            ['entity' => 'Service', 'key' => 'tarif_membre', 'name' => 'Tarif membre (€)', 'type' => 'number'],
            ['entity' => 'Service', 'key' => 'tarif_externe', 'name' => 'Tarif externe (€)', 'type' => 'number'],
            ['entity' => 'Service', 'key' => 'nb_plongees', 'name' => 'Nombre de plongées', 'type' => 'number'],
            
            // Gallery attributes - diving photos
            ['entity' => 'Gallery', 'key' => 'date_sortie', 'name' => 'Date de la sortie', 'type' => 'date'],
            ['entity' => 'Gallery', 'key' => 'site_plongee', 'name' => 'Site de plongée', 'type' => 'text'],
            ['entity' => 'Gallery', 'key' => 'profondeur', 'name' => 'Profondeur (m)', 'type' => 'number'],
            ['entity' => 'Gallery', 'key' => 'photographe', 'name' => 'Photographe', 'type' => 'text'],
            ['entity' => 'Gallery', 'key' => 'conditions_visibilite', 'name' => 'Conditions visibilité', 'type' => 'select', 'options' => ['excellente', 'bonne', 'moyenne', 'faible']],
        ];

        foreach ($attributeDefinitions as $attrDef) {
            $definition = new AttributeDefinition();
            $definition->setEntityType($attrDef['entity']);
            $definition->setAttributeName($attrDef['key']);
            $definition->setDisplayName($attrDef['name']);
            $definition->setAttributeType($attrDef['type']);
            $definition->setRequired(false);
            $definition->setActive(true);
            
            // Set options for select types
            if (isset($attrDef['options'])) {
                $definition->setOptions($attrDef['options']);
            }
            
            $manager->persist($definition);
        }

        $manager->flush();
    }

    private function loadPages(ObjectManager $manager, array $users): void
    {
        $pages = [
            [
                'title' => 'Accueil',
                'slug' => 'accueil',
                'content' => '<h1>Bienvenue au Club de Plongée des Vénètes</h1><p>Découvrez les merveilles sous-marines de la Méditerranée avec notre club de plongée situé à Toulon. Depuis 1985, nous formons des plongeurs passionnés et organisons des sorties exceptionnelles sur les plus beaux sites de la Côte d\'Azur.</p><h2>Nos activités</h2><ul><li>Formations FFESSM du débutant au Niveau 5</li><li>Sorties plongée tous les week-ends</li><li>Plongées de nuit au Cap Sicié</li><li>Exploration d\'épaves (Donator, Togo)</li><li>Stages de photographie sous-marine</li><li>Plongées techniques et Nitrox</li></ul><h2>Nos sites de plongée</h2><p>Explorez avec nous les sites emblématiques de la région : Les Deux Frères, le Sec du Langoustier, les épaves du Donator et du Togo, les îles d\'Embiez et Porquerolles.</p>',
                'status' => 'published'
            ],
            [
                'title' => 'À propos',
                'slug' => 'a-propos',
                'content' => '<h1>Notre Histoire</h1><p>Le Club de Plongée des Vénètes a été fondé en 1985 par un groupe de passionnés de plongée sous-marine. Affilié à la FFESSM (Fédération Française d\'Études et de Sports Sous-Marins), nous comptons aujourd\'hui plus de 200 membres actifs de tous niveaux.</p><h2>Notre philosophie</h2><p>Notre club privilégie la sécurité, la convivialité et le respect de l\'environnement marin. Nous organisons régulièrement des actions de nettoyage des fonds marins et sensibilisons nos membres à la protection des écosystèmes méditerranéens.</p><h2>Notre équipe</h2><p>Notre équipe d\'instructeurs qualifiés (E1 à E4) vous accompagne dans votre progression, que vous soyez débutant ou plongeur confirmé souhaitant passer vos niveaux avancés.</p><h2>Nos installations</h2><p>Basés au port de Toulon, nous disposons d\'un local équipé, d\'un bateau semi-rigide de 12 places et de tout le matériel pédagogique nécessaire aux formations.</p>',
                'status' => 'published'
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'content' => '<h1>Nous contacter</h1><h2>Adresse</h2><p>Club de Plongée des Vénètes<br>15 Quai du Port<br>83000 Toulon</p><h2>Téléphone</h2><p>04.94.12.34.56<br>Mobile : 06.12.34.56.78</p><h2>Email</h2><p>contact@plongee-venetes.fr<br>formation@plongee-venetes.fr</p><h2>Horaires d\'ouverture</h2><p>Local du club :<br>Mardi et Jeudi : 19h00 - 21h00<br>Samedi (hors sortie) : 14h00 - 18h00</p><p>Sorties plongée :<br>Week-ends selon planning mensuel<br>Départs généralement à 8h00 du port</p><h2>Réseaux sociaux</h2><p>Suivez nos actualités sur Facebook : Club Plongée Vénètes<br>Instagram : @plongee_venetes</p>',
                'status' => 'published'
            ]
        ];

        foreach ($pages as $pageData) {
            $page = new Page();
            $page->setTitle($pageData['title']);
            $page->setSlug($pageData['slug']);
            $page->setExcerpt(substr($pageData['content'], 0, 200));
            $page->setTemplatePath('pages/' . $pageData['slug'] . '.html.twig');
            $page->setStatus($pageData['status']);
            $page->setPublishedAt(new \DateTimeImmutable());
            $page->setAuthor($users[0]);
            $manager->persist($page);
        }
    }

    private function loadArticles(ObjectManager $manager, array $users): void
    {
        $articles = [
            [
                'title' => 'Nouvelle saison de plongée 2024',
                'slug' => 'nouvelle-saison-plongee-2024',
                'excerpt' => 'La nouvelle saison de plongée commence avec de nombreuses sorties programmées',
                'content' => '<p>La saison de plongée 2024 s\'annonce exceptionnelle ! Nous avons prévu de nombreuses sorties vers les plus beaux sites de la région...</p>',
                'category' => 'actualites',
                'tags' => ['saison', 'sorties', '2024']
            ],
            [
                'title' => 'Formation Niveau 1 - Session Mars 2024',
                'slug' => 'formation-niveau-1-mars-2024',
                'excerpt' => 'Inscriptions ouvertes pour la formation Niveau 1 de mars',
                'content' => '<p>Une nouvelle session de formation Niveau 1 débute en mars. Cette formation vous permettra de plonger jusqu\'à 20 mètres...</p>',
                'category' => 'formations',
                'tags' => ['niveau1', 'formation', 'mars']
            ],
            [
                'title' => 'Sortie épave du Donator',
                'slug' => 'sortie-epave-donator',
                'excerpt' => 'Découvrez l\'épave mythique du Donator lors de notre prochaine sortie',
                'content' => '<p>L\'épave du Donator est l\'une des plus belles plongées de la région. Située à 52 mètres de profondeur...</p>',
                'category' => 'sorties',
                'tags' => ['epave', 'donator', 'niveau3']
            ],
            [
                'title' => 'Nouveaux équipements disponibles',
                'slug' => 'nouveaux-equipements-disponibles',
                'excerpt' => 'Le club s\'équipe de nouveau matériel de plongée',
                'content' => '<p>Nous venons d\'acquérir de nouveaux équipements : combinaisons, détendeurs, gilets stabilisateurs...</p>',
                'category' => 'materiel',
                'tags' => ['equipement', 'materiel', 'nouveau']
            ],
            [
                'title' => 'Championnat régional de plongée',
                'slug' => 'championnat-regional-plongee',
                'excerpt' => 'Nos membres participent au championnat régional',
                'content' => '<p>Plusieurs membres de notre club participent cette année au championnat régional de plongée sportive...</p>',
                'category' => 'competitions',
                'tags' => ['championnat', 'competition', 'regional']
            ],
            [
                'title' => 'Plongée de nuit à Porquerolles',
                'slug' => 'plongee-nuit-porquerolles',
                'excerpt' => 'Une expérience unique en plongée nocturne',
                'content' => '<p>La plongée de nuit offre une toute autre vision du monde sous-marin. Nos sorties à Porquerolles...</p>',
                'category' => 'sorties',
                'tags' => ['nuit', 'porquerolles', 'experience']
            ],
            [
                'title' => 'Formation Nitrox confirmé',
                'slug' => 'formation-nitrox-confirme',
                'excerpt' => 'Apprenez à plonger au Nitrox pour étendre vos temps de plongée',
                'content' => '<p>La formation Nitrox vous permet d\'utiliser des mélanges gazeux enrichis en oxygène...</p>',
                'category' => 'formations',
                'tags' => ['nitrox', 'formation', 'technique']
            ],
            [
                'title' => 'Assemblée générale 2024',
                'slug' => 'assemblee-generale-2024',
                'excerpt' => 'Invitation à l\'assemblée générale annuelle du club',
                'content' => '<p>L\'assemblée générale annuelle du club aura lieu le samedi 15 juin 2024 à 14h...</p>',
                'category' => 'vie-club',
                'tags' => ['ag', 'assemblee', 'generale']
            ]
        ];

        foreach ($articles as $index => $articleData) {
            $article = new Article();
            $article->setTitle($articleData['title']);
            $article->setSlug($articleData['slug']);
            $article->setExcerpt($articleData['excerpt']);
            $article->setContent($articleData['content']);
            $article->setCategory($articleData['category']);
            $article->setTags($articleData['tags']);
            $article->setStatus('published');
            $article->setPublishedAt(new \DateTimeImmutable('-' . ($index * 7) . ' days'));
            $article->setAuthor($users[rand(2, count($users) - 1)]);
            $manager->persist($article);
        }
    }

    private function loadServices(ObjectManager $manager): void
    {
        $services = [
            [
                'name' => 'Formation Niveau 1',
                'slug' => 'formation-niveau-1',
                'short_description' => 'Première certification de plongée, plongée jusqu\'à 20m accompagné',
                'description' => 'La formation Niveau 1 est votre première étape vers l\'autonomie en plongée. Vous apprendrez les bases de la sécurité et de la technique.',
                'price' => '280.00',
                'duration' => 1200, // 20 heures
                'category' => 'formations',
                'features' => ['Cours théoriques', 'Piscine', '6 plongées', 'Certification FFESSM'],
                'bookable' => true
            ],
            [
                'name' => 'Formation Niveau 2',
                'slug' => 'formation-niveau-2',
                'short_description' => 'Plongée jusqu\'à 20m en autonomie, 40m accompagné',
                'description' => 'Le Niveau 2 vous donne accès à l\'autonomie à 20m et aux plongées accompagnées jusqu\'à 40m.',
                'price' => '350.00',
                'duration' => 1800, // 30 heures
                'category' => 'formations',
                'features' => ['10 plongées', 'Technique avancée', 'Autonomie 20m', 'Accompagné 40m'],
                'bookable' => true
            ],
            [
                'name' => 'Formation Niveau 3',
                'slug' => 'formation-niveau-3',
                'short_description' => 'Plongée jusqu\'à 60m en autonomie',
                'description' => 'Le Niveau 3 est le niveau d\'excellence en plongée loisir, autonomie complète jusqu\'à 60m.',
                'price' => '450.00',
                'duration' => 2400, // 40 heures
                'category' => 'formations',
                'features' => ['15 plongées', 'Plongée profonde', 'Autonomie 60m', 'Planification'],
                'bookable' => true
            ],
            [
                'name' => 'Sortie plongée découverte',
                'slug' => 'sortie-plongee-decouverte',
                'short_description' => 'Première plongée en mer pour découvrir la plongée',
                'description' => 'Découvrez la plongée sous-marine lors d\'une sortie encadrée par nos instructeurs.',
                'price' => '65.00',
                'duration' => 180, // 3 heures
                'category' => 'sorties',
                'features' => ['Encadrement pro', 'Matériel fourni', 'Baptême mer', 'Photos incluses'],
                'bookable' => true
            ],
            [
                'name' => 'Location équipement complet',
                'slug' => 'location-equipement-complet',
                'short_description' => 'Location de l\'équipement complet de plongée',
                'description' => 'Louez un équipement complet : combinaison, masque, palmes, détendeur, gilet.',
                'price' => '35.00',
                'duration' => 480, // 8 heures
                'category' => 'location',
                'features' => ['Équipement récent', 'Tailles variées', 'Maintenance pro', 'Assurance incluse'],
                'bookable' => true
            ],
            [
                'name' => 'Formation Nitrox',
                'slug' => 'formation-nitrox',
                'short_description' => 'Formation à la plongée au mélange Nitrox',
                'description' => 'Apprenez à utiliser les mélanges Nitrox pour étendre vos temps de plongée.',
                'price' => '180.00',
                'duration' => 480, // 8 heures
                'category' => 'formations',
                'features' => ['Théorie gaz', 'Analyseur', 'Planification', 'Certification'],
                'bookable' => true
            ],
            [
                'name' => 'Stage photo sous-marine',
                'slug' => 'stage-photo-sous-marine',
                'short_description' => 'Apprenez la photographie sous-marine',
                'description' => 'Stage d\'initiation à la photographie sous-marine avec matériel professionnel.',
                'price' => '220.00',
                'duration' => 960, // 16 heures
                'category' => 'stages',
                'features' => ['Matériel pro', '4 plongées', 'Retouche photo', 'Composition'],
                'bookable' => true
            ],
            [
                'name' => 'Révision annuelle détendeur',
                'slug' => 'revision-annuelle-detendeur',
                'short_description' => 'Révision et maintenance de votre détendeur',
                'description' => 'Révision complète de votre détendeur par notre technicien agréé.',
                'price' => '85.00',
                'duration' => 120, // 2 heures
                'category' => 'maintenance',
                'features' => ['Technicien agréé', 'Pièces incluses', 'Test complet', 'Garantie 1 an'],
                'bookable' => true
            ]
        ];

        foreach ($services as $serviceData) {
            $service = new Service();
            $service->setName($serviceData['name']);
            $service->setSlug($serviceData['slug']);
            $service->setShortDescription($serviceData['short_description']);
            $service->setDescription($serviceData['description']);
            $service->setPrice($serviceData['price']);
            $service->setPricingType('fixed');
            $service->setCurrency('EUR');
            $service->setDuration($serviceData['duration']);
            $service->setCategory($serviceData['category']);
            $service->setFeatures($serviceData['features']);
            $service->setBookable($serviceData['bookable']);
            $service->setStatus('active');
            $service->setDisplayOrder(0);
            $manager->persist($service);
        }
    }

    private function loadEventTypes(ObjectManager $manager): array
    {
        $eventTypes = [];
        
        $eventTypeData = [
            ['slug' => 'plongee', 'name' => 'Plongée', 'description' => 'Sorties de plongée en mer', 'color' => '#3B82F6', 'active' => true, 'sortOrder' => 1],
            ['slug' => 'formation', 'name' => 'Formation', 'description' => 'Cours et formations certifiantes', 'color' => '#10B981', 'active' => true, 'sortOrder' => 2],
            ['slug' => 'reunion', 'name' => 'Réunion', 'description' => 'Réunions du club et assemblées', 'color' => '#F59E0B', 'active' => true, 'sortOrder' => 3],
            ['slug' => 'evenement', 'name' => 'Événement', 'description' => 'Événements spéciaux du club', 'color' => '#EF4444', 'active' => true, 'sortOrder' => 4],
        ];
        
        foreach ($eventTypeData as $data) {
            $eventType = new EventType();
            $eventType->setSlug($data['slug']);
            $eventType->setName($data['name']);
            $eventType->setDescription($data['description']);
            $eventType->setColor($data['color']);
            $eventType->setActive($data['active']);
            $eventType->setSortOrder($data['sortOrder']);
            
            $manager->persist($eventType);
            $eventTypes[$data['slug']] = $eventType;
        }
        
        $manager->flush();
        return $eventTypes;
    }

    private function loadEvents(ObjectManager $manager, array $users, array $eventTypes): array
    {
        $events = [];
        $eventData = [
            [
                'title' => 'Plongée découverte Les Deux Frères - Toulon',
                'slug' => 'plongee-decouverte-deux-freres-toulon',
                'description' => 'Découverte du site emblématique des Deux Frères à Toulon, parfait pour les débutants et niveau 1. Site protégé avec une faune méditerranéenne exceptionnelle.',
                'short_description' => 'Plongée découverte Deux Frères',
                'start_date' => new \DateTimeImmutable('+5 days 09:00'),
                'end_date' => new \DateTimeImmutable('+5 days 17:00'),
                'location' => 'Les Deux Frères, Toulon',
                'address' => 'Port de Toulon, 83000 Toulon',
                'status' => 'published',
                'type_slug' => 'plongee',
                'max_participants' => 12,
                'niveau_requis' => 'debutant',
                'profondeur_max' => 15,
                'prix' => 45,
                'site_plongee' => 'Les Deux Frères',
                'conditions_mer' => 'calme',
                'type_plongee' => 'exploration'
            ],
            [
                'title' => 'Formation théorique Niveau 2',
                'slug' => 'formation-theorique-niveau-2',
                'description' => 'Session de formation théorique pour les candidats Niveau 2. Révision des tables de plongée, sécurité, et préparation à l\'examen.',
                'short_description' => 'Cours théorique N2',
                'start_date' => new \DateTimeImmutable('+8 days 19:00'),
                'end_date' => new \DateTimeImmutable('+8 days 21:00'),
                'location' => 'Local du club - Toulon',
                'address' => '15 quai du Port, 83000 Toulon',
                'status' => 'published',
                'type_slug' => 'formation',
                'max_participants' => 20
            ],
            [
                'title' => 'Plongée épave Donator - Niveau 3 minimum',
                'slug' => 'plongee-epave-donator-niveau-3',
                'description' => 'Plongée technique sur l\'épave mythique du Donator à 52m de profondeur. Site exceptionnel au large du Lavandou, réservé aux plongeurs Niveau 3 minimum.',
                'short_description' => 'Épave Donator 52m - N3 requis',
                'start_date' => new \DateTimeImmutable('+10 days 08:00'),
                'end_date' => new \DateTimeImmutable('+10 days 16:00'),
                'location' => 'Épave Donator - Le Lavandou',
                'address' => 'Port du Lavandou, 83980 Le Lavandou',
                'status' => 'published',
                'type_slug' => 'plongee',
                'max_participants' => 8,
                'niveau_requis' => 'niveau3',
                'profondeur_max' => 52,
                'prix' => 75,
                'site_plongee' => 'Épave Donator',
                'conditions_mer' => 'calme',
                'type_plongee' => 'epave'
            ],
            [
                'title' => 'Plongée de nuit Cap Sicié - Niveau 2 minimum',
                'slug' => 'plongee-nuit-cap-sicie-niveau-2',
                'description' => 'Plongée nocturne au Cap Sicié pour découvrir la faune de nuit méditerranéenne. Langoustes, congres et poulpes au rendez-vous !',
                'short_description' => 'Plongée nocturne Cap Sicié',
                'start_date' => new \DateTimeImmutable('+15 days 20:00'),
                'end_date' => new \DateTimeImmutable('+15 days 22:30'),
                'location' => 'Cap Sicié - Six-Fours',
                'address' => 'Port de Six-Fours, 83140 Six-Fours-les-Plages',
                'status' => 'published',
                'type_slug' => 'plongee',
                'max_participants' => 10,
                'niveau_requis' => 'niveau2',
                'profondeur_max' => 25,
                'prix' => 55,
                'site_plongee' => 'Cap Sicié',
                'conditions_mer' => 'calme',
                'type_plongee' => 'nuit'
            ],
            [
                'title' => 'Nettoyage des fonds marins - Îles d\'Embiez',
                'slug' => 'nettoyage-fonds-marins-embiez',
                'description' => 'Action écologique de nettoyage des fonds marins aux îles d\'Embiez. Participation à la protection de notre environnement marin. Ouvert à tous niveaux.',
                'short_description' => 'Action environnementale Embiez',
                'start_date' => new \DateTimeImmutable('+20 days 09:00'),
                'end_date' => new \DateTimeImmutable('+20 days 15:00'),
                'location' => 'Îles d\'Embiez - Six-Fours',
                'address' => 'Embarcadère Embiez, 83140 Six-Fours-les-Plages',
                'status' => 'published',
                'type_slug' => 'evenement',
                'max_participants' => 25,
                'niveau_requis' => 'debutant',
                'profondeur_max' => 20,
                'prix' => 30,
                'site_plongee' => 'Îles d\'Embiez',
                'type_plongee' => 'exploration'
            ],
            [
                'title' => 'Stage photo macro - Porquerolles',
                'slug' => 'stage-photo-macro-porquerolles',
                'description' => 'Week-end stage de photographie macro sous-marine à Porquerolles. Apprentissage des techniques de macro, réglages et composition.',
                'short_description' => 'Stage photo macro',
                'start_date' => new \DateTimeImmutable('+25 days 08:00'),
                'end_date' => new \DateTimeImmutable('+26 days 17:00'),
                'location' => 'Porquerolles',
                'address' => 'Port de Porquerolles, 83400 Hyères',
                'status' => 'published',
                'type_slug' => 'formation',
                'max_participants' => 8,
                'niveau_requis' => 'niveau2',
                'profondeur_max' => 30,
                'prix' => 120,
                'type_plongee' => 'technique'
            ],
            [
                'title' => 'Assemblée générale annuelle',
                'slug' => 'assemblee-generale-annuelle',
                'description' => 'Assemblée générale annuelle du Club de Plongée des Vénètes avec rapport d\'activité, bilan financier et élections du bureau.',
                'short_description' => 'AG annuelle',
                'start_date' => new \DateTimeImmutable('+30 days 14:00'),
                'end_date' => new \DateTimeImmutable('+30 days 17:00'),
                'location' => 'Salle des fêtes - Toulon',
                'address' => 'Salle des fêtes municipale, 83000 Toulon',
                'status' => 'published',
                'type_slug' => 'reunion',
                'max_participants' => 150
            ],
            [
                'title' => 'Plongée Sec du Langoustier - Niveau 1',
                'slug' => 'plongee-sec-langoustier-niveau-1',
                'description' => 'Belle plongée sur le célèbre sec du Langoustier au large du Lavandou. Site accessible aux Niveau 1 avec une faune abondante.',
                'short_description' => 'Sec du Langoustier',
                'start_date' => new \DateTimeImmutable('+35 days 09:00'),
                'end_date' => new \DateTimeImmutable('+35 days 17:00'),
                'location' => 'Sec du Langoustier - Le Lavandou',
                'address' => 'Port du Lavandou, 83980 Le Lavandou',
                'status' => 'published',
                'type_slug' => 'plongee',
                'max_participants' => 16,
                'niveau_requis' => 'niveau1',
                'profondeur_max' => 20,
                'prix' => 50,
                'site_plongee' => 'Sec du Langoustier',
                'conditions_mer' => 'petite_houle',
                'type_plongee' => 'exploration'
            ],
            [
                'title' => 'Formation RIFAP - Premiers secours en plongée',
                'slug' => 'formation-rifap-premiers-secours',
                'description' => 'Formation RIFAP (Réactions et Intervention Face à un Accident de Plongée). Certification indispensable pour les futurs cadres.',
                'short_description' => 'Formation RIFAP',
                'start_date' => new \DateTimeImmutable('+40 days 09:00'),
                'end_date' => new \DateTimeImmutable('+41 days 17:00'),
                'location' => 'Piscine + Centre de formation',
                'address' => 'Centre aquatique, 83000 Toulon',
                'status' => 'published',
                'type_slug' => 'formation',
                'max_participants' => 12,
                'niveau_requis' => 'niveau2',
                'prix' => 150
            ],
            [
                'title' => 'Plongée épave Togo - Niveau 4 requis',
                'slug' => 'plongee-epave-togo-niveau-4',
                'description' => 'Plongée technique d\'exception sur l\'épave du Togo à 70m de profondeur au large de Toulon. Réservée aux plongeurs Niveau 4 et plus avec expérience de la plongée profonde.',
                'short_description' => 'Épave Togo 70m',
                'start_date' => new \DateTimeImmutable('+45 days 07:00'),
                'end_date' => new \DateTimeImmutable('+45 days 18:00'),
                'location' => 'Épave Togo - Large de Toulon',
                'address' => 'Port de Toulon, 83000 Toulon',
                'status' => 'published',
                'type_slug' => 'plongee',
                'max_participants' => 6,
                'niveau_requis' => 'niveau4',
                'profondeur_max' => 70,
                'prix' => 95,
                'site_plongee' => 'Épave Togo',
                'conditions_mer' => 'calme',
                'type_plongee' => 'epave'
            ]
        ];

        foreach ($eventData as $data) {
            $event = new Event();
            $event->setTitle($data['title']);
            $event->setSlug($data['slug']);
            $event->setDescription($data['description']);
            $event->setShortDescription($data['short_description']);
            $event->setStartDate($data['start_date']);
            $event->setEndDate($data['end_date']);
            $event->setLocation($data['location']);
            $event->setStatus($data['status']);
            
            // Set type using slug (for now, until EventType relation is added to Event entity)
            if (isset($data['type_slug'])) {
                $event->setType($data['type_slug']);
            }
            
            $event->setMaxParticipants($data['max_participants']);
            $event->setRequiresRegistration(true);
            $event->setOrganizer($users[rand(2, 5)]); // Random user as organizer
            
            $manager->persist($event);
            $manager->flush(); // Flush to get ID for EAV attributes
            
            // Add EAV attributes for diving-specific data
            if (isset($data['niveau_requis'])) {
                $this->attributeManager->setAttribute($event, 'niveau_requis', $data['niveau_requis'], 'select');
            }
            if (isset($data['profondeur_max'])) {
                $this->attributeManager->setAttribute($event, 'profondeur_max', $data['profondeur_max'], 'number');
            }
            if (isset($data['prix'])) {
                $this->attributeManager->setAttribute($event, 'prix', $data['prix'], 'number');
            }
            if (isset($data['site_plongee'])) {
                $this->attributeManager->setAttribute($event, 'site_plongee', $data['site_plongee'], 'text');
            }
            if (isset($data['conditions_mer'])) {
                $this->attributeManager->setAttribute($event, 'conditions_mer', $data['conditions_mer'], 'select');
            }
            if (isset($data['type_plongee'])) {
                $this->attributeManager->setAttribute($event, 'type_plongee', $data['type_plongee'], 'select');
            }
            
            $events[] = $event;
        }

        $manager->flush();
        return $events;
    }

    private function loadEventRegistrations(ObjectManager $manager, array $events, array $users): void
    {
        // Create random registrations for events
        foreach ($events as $event) {
            $numRegistrations = rand(2, min($event->getMaxParticipants(), 8));
            $registeredUsers = array_rand(array_slice($users, 2), $numRegistrations); // Skip super admin and admin
            
            if (!is_array($registeredUsers)) {
                $registeredUsers = [$registeredUsers];
            }
            
            foreach ($registeredUsers as $userIndex) {
                $user = $users[$userIndex + 2]; // Adjust for slice offset
                
                $registration = new EventRegistration();
                $registration->setEvent($event);
                $registration->setUser($user);
                $registration->setStatus('registered');
                $registration->setRegisteredAt(new \DateTimeImmutable('-' . rand(1, 30) . ' days'));
                $registration->setNotes('Inscription via fixtures');
                
                $manager->persist($registration);
            }
        }
    }

    private function loadGalleries(ObjectManager $manager, array $users): void
    {
        $galleries = [
            [
                'title' => 'Plongée Lavandou 2024',
                'slug' => 'plongee-lavandou-2024',
                'description' => 'Galerie photos de notre sortie au Lavandou',
                'visibility' => 'public',
                'author' => $users[3]
            ],
            [
                'title' => 'Formation Niveau 1 - Groupe Mars',
                'slug' => 'formation-niveau-1-mars',
                'description' => 'Photos de la formation N1 de mars 2024',
                'visibility' => 'public',
                'author' => $users[4]
            ],
            [
                'title' => 'Épave du Donator',
                'slug' => 'epave-donator',
                'description' => 'Exploration de l\'épave du Donator',
                'visibility' => 'public',
                'author' => $users[2]
            ],
            [
                'title' => 'Galerie Privée - Client Dupont',
                'slug' => 'galerie-privee-dupont',
                'description' => 'Galerie privée pour M. et Mme Dupont',
                'visibility' => 'private',
                'author' => $users[5]
            ]
        ];

        // Get all available images from upload directory
        $imageDir = __DIR__ . '/../../public/uploads/images/';
        $coverDir = __DIR__ . '/../../public/uploads/covers/';
        $availableImages = [];
        $availableCovers = [];
        
        if (is_dir($imageDir)) {
            $availableImages = array_filter(scandir($imageDir), function($file) {
                return !in_array($file, ['.', '..']) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
            });
        }
        
        if (is_dir($coverDir)) {
            $availableCovers = array_filter(scandir($coverDir), function($file) {
                return !in_array($file, ['.', '..']) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
            });
        }

        foreach ($galleries as $index => $galleryData) {
            $gallery = new Gallery();
            $gallery->setTitle($galleryData['title']);
            $gallery->setSlug($galleryData['slug']);
            $gallery->setDescription($galleryData['description']);
            $gallery->setVisibility($galleryData['visibility']);
            $gallery->setAuthor($galleryData['author']);
            
            // Set cover image if available
            if (!empty($availableCovers)) {
                $coverIndex = $index % count($availableCovers);
                $gallery->setCoverImage('/uploads/covers/' . array_values($availableCovers)[$coverIndex]);
            }
            
            $manager->persist($gallery);
            
            // Add random images to this gallery (between 5-15 images per gallery)
            if (!empty($availableImages)) {
                $imageCount = rand(5, 15);
                $shuffledImages = $availableImages;
                shuffle($shuffledImages);
                
                for ($i = 0; $i < min($imageCount, count($shuffledImages)); $i++) {
                    $imageFilename = $shuffledImages[$i];
                    $imagePath = $imageDir . $imageFilename;
                    
                    if (file_exists($imagePath)) {
                        $image = new Image();
                        $image->setFilename($imageFilename);
                        $image->setOriginalName($imageFilename);
                        $image->setMimeType(mime_content_type($imagePath));
                        $image->setSize(filesize($imagePath));
                        
                        // Try to get image dimensions
                        $imageInfo = getimagesize($imagePath);
                        if ($imageInfo) {
                            $image->setWidth($imageInfo[0]);
                            $image->setHeight($imageInfo[1]);
                        }
                        
                        // Generate alt text and caption based on filename
                        $baseName = pathinfo($imageFilename, PATHINFO_FILENAME);
                        $image->setAlt('Photo ' . $baseName);
                        $image->setCaption('Photo prise lors de ' . strtolower($galleryData['title']));
                        $image->setPosition($i + 1);
                        $image->setGallery($gallery);
                        $image->setUploadedBy($galleryData['author']);
                        
                        $manager->persist($image);
                    }
                }
            }
        }
    }
}