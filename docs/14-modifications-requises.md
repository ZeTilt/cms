# Modifications Requises - Simplification Architecture

[⬅️ Retour à l'index](README.md)

## 🎯 Objectif

Ce document liste les modifications à apporter au code pour simplifier l'architecture selon vos décisions :

1. ❌ **Supprimer les galeries privées** avec code d'accès
2. ❌ **Supprimer le système EAV** (Entity-Attribute-Value) → utiliser des entités classiques Symfony

---

## 🗑️ Modification #1 : Suppression Galeries Privées

### Justification

Les galeries privées avec code d'accès ajoutent :
- Complexité inutile
- Problème de sécurité (codes en clair)
- Peu d'utilité pratique pour un club

**Décision :** Toutes les galeries seront publiques.

### Fichiers à Modifier

#### 1. Entité Gallery

**Fichier :** `src/Entity/Gallery.php`

**Supprimer :**
```php
#[ORM\Column(type: 'string', length: 50, nullable: true)]
private ?string $visibility = 'public';

#[ORM\Column(type: 'string', length: 100, nullable: true)]
private ?string $accessCode = null;

public function getVisibility(): ?string { }
public function setVisibility(?string $visibility): self { }
public function getAccessCode(): ?string { }
public function setAccessCode(?string $accessCode): self { }
```

**Garder simplement :**
```php
// Toutes les galeries sont publiques, pas de champ visibility
```

#### 2. Migration Database

**Créer migration :**

```bash
php bin/console make:migration
```

**Contenu :**
```php
<?php
// migrations/VersionXXXXXXXXXXXX.php

public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE gallery DROP visibility');
    $this->addSql('ALTER TABLE gallery DROP access_code');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE gallery ADD visibility VARCHAR(50) DEFAULT "public"');
    $this->addSql('ALTER TABLE gallery ADD access_code VARCHAR(100) DEFAULT NULL');
}
```

#### 3. Contrôleur PublicGalleryController

**Fichier :** `src/Controller/PublicGalleryController.php`

**Supprimer la méthode :**
```php
public function unlock(string $slug, Request $request): Response
{
    // Toute la logique de déverrouillage
}
```

**Simplifier index() :**
```php
public function index(GalleryRepository $galleryRepo): Response
{
    // Avant :
    // $galleries = $galleryRepo->findPublicOrUnlocked($session);

    // Après :
    $galleries = $galleryRepo->findAll(); // Toutes publiques

    return $this->render('gallery/index.html.twig', [
        'galleries' => $galleries
    ]);
}
```

**Simplifier show() :**
```php
public function show(string $slug, GalleryRepository $galleryRepo): Response
{
    $gallery = $galleryRepo->findOneBy(['slug' => $slug]);

    if (!$gallery) {
        throw $this->createNotFoundException();
    }

    // Supprimer toute vérification d'accès

    return $this->render('gallery/show.html.twig', [
        'gallery' => $gallery
    ]);
}
```

#### 4. Templates

**Fichier :** `templates/gallery/show.html.twig`

**Supprimer :**
- Formulaire de saisie code d'accès
- Messages "Galerie privée"
- Logique conditionnelle d'affichage

**Garder simplement :**
```twig
<h1>{{ gallery.title }}</h1>
<p>{{ gallery.description }}</p>

<div class="gallery-grid">
    {% for image in gallery.images %}
        <img src="{{ image.url }}" alt="{{ image.caption }}">
    {% endfor %}
</div>
```

#### 5. Formulaire Admin

**Fichier :** `src/Form/GalleryType.php`

**Supprimer champs :**
```php
->add('visibility', ChoiceType::class, [
    'choices' => [
        'Public' => 'public',
        'Privé' => 'private'
    ]
])
->add('accessCode', TextType::class, [
    'required' => false
])
```

#### 6. Routes

**Supprimer route :**
```php
#[Route('/gallery/{slug}/unlock', name: 'gallery_unlock', methods: ['POST'])]
```

---

## 🗑️ Modification #2 : Suppression Système EAV

### Justification

Le système EAV (Entity-Attribute-Value) est :
- Complexe à maintenir
- Difficile à requêter
- Pas de validation native
- Pas nécessaire pour un club de taille moyenne

**Décision :** Utiliser des champs classiques dans les entités Symfony. Si besoin d'ajouter des champs, modifier l'entité + migration.

### Entités à Supprimer

#### 1. AttributeDefinition

**Fichier à supprimer :** `src/Entity/AttributeDefinition.php`

Cette entité définit les attributs personnalisés possibles.

#### 2. EntityAttribute

**Fichier à supprimer :** `src/Entity/EntityAttribute.php`

Cette entité stocke les valeurs des attributs custom.

### Contrôleurs à Supprimer

#### 1. AdminAttributeDefinitionController

**Fichier à supprimer :** `src/Controller/Admin/AdminAttributeDefinitionController.php`

Gestion des définitions d'attributs.

#### 2. AdminUserAttributeController

**Fichier à supprimer :** `src/Controller/Admin/AdminUserAttributeController.php`

Gestion des valeurs d'attributs utilisateur.

### Service AttributeManager

**Fichier à supprimer :** `src/Service/AttributeManager.php` (si existe)

### Migration Database

**Créer migration :**

```bash
php bin/console make:migration
```

**Contenu :**
```php
<?php
// migrations/VersionXXXXXXXXXXXX.php

public function up(Schema $schema): void
{
    // Supprimer tables EAV
    $this->addSql('DROP TABLE IF EXISTS entity_attribute');
    $this->addSql('DROP TABLE IF EXISTS attribute_definition');
}

public function down(Schema $schema): void
{
    // Recréer tables si besoin rollback
    $this->addSql('CREATE TABLE attribute_definition (...)');
    $this->addSql('CREATE TABLE entity_attribute (...)');
}
```

### Ajouter Champs Classiques à User

**Fichier :** `src/Entity/User.php`

**Ajouter les champs qui étaient en EAV :**

```php
<?php
namespace App\Entity;

class User
{
    // ... champs existants

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $licenceNumber = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $medicalCertificateDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $medicalCertificateExpiry = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $insuranceNumber = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $insuranceExpiry = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $emergencyContactName = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $emergencyContactPhone = null;

    // Getters et Setters
    public function getLicenceNumber(): ?string
    {
        return $this->licenceNumber;
    }

    public function setLicenceNumber(?string $licenceNumber): self
    {
        $this->licenceNumber = $licenceNumber;
        return $this;
    }

    // ... autres getters/setters
}
```

**Migration pour ajouter ces colonnes :**

```php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE user ADD licence_number VARCHAR(50) DEFAULT NULL');
    $this->addSql('ALTER TABLE user ADD medical_certificate_date DATE DEFAULT NULL');
    $this->addSql('ALTER TABLE user ADD medical_certificate_expiry DATE DEFAULT NULL');
    $this->addSql('ALTER TABLE user ADD insurance_number VARCHAR(100) DEFAULT NULL');
    $this->addSql('ALTER TABLE user ADD insurance_expiry DATE DEFAULT NULL');
    $this->addSql('ALTER TABLE user ADD emergency_contact_name VARCHAR(255) DEFAULT NULL');
    $this->addSql('ALTER TABLE user ADD emergency_contact_phone VARCHAR(20) DEFAULT NULL');
}
```

### Adapter EventConditionService

**Fichier :** `src/Service/EventConditionService.php`

**Avant (EAV) :**
```php
$value = $attributeManager->getAttribute('User', $user->getId(), 'medical_certificate_expiry');
```

**Après (entité classique) :**
```php
$value = $user->getMedicalCertificateExpiry();
```

### Adapter Formulaires

**Fichier :** `src/Form/UserProfileType.php` ou `AdminUserType.php`

**Ajouter champs classiques :**
```php
->add('licenceNumber', TextType::class, [
    'label' => 'Numéro de licence',
    'required' => false
])
->add('medicalCertificateDate', DateType::class, [
    'label' => 'Date du certificat médical',
    'required' => false,
    'widget' => 'single_text'
])
->add('medicalCertificateExpiry', DateType::class, [
    'label' => 'Date d\'expiration du certificat',
    'required' => false,
    'widget' => 'single_text'
])
->add('insuranceNumber', TextType::class, [
    'label' => 'Numéro d\'assurance',
    'required' => false
])
->add('insuranceExpiry', DateType::class, [
    'label' => 'Expiration assurance',
    'required' => false,
    'widget' => 'single_text'
])
->add('emergencyContactName', TextType::class, [
    'label' => 'Contact d\'urgence (nom)',
    'required' => false
])
->add('emergencyContactPhone', TelType::class, [
    'label' => 'Contact d\'urgence (téléphone)',
    'required' => false
])
```

### Nettoyer AdminEventConditionController

**Fichier :** `src/Controller/Admin/AdminEventConditionController.php`

Le système de conditions peut rester, mais simplifié :

**Avant :**
- Introspection dynamique de tous les attributs EAV
- Liste infinie d'attributs possibles

**Après :**
- Liste fixe d'attributs disponibles (propriétés de User)
- Plus simple, plus performant

```php
private function getAvailableAttributes(): array
{
    return [
        'highestDivingLevel.code' => 'Niveau de plongée (code)',
        'highestDivingLevel.sortOrder' => 'Niveau de plongée (ordre)',
        'licenceNumber' => 'Numéro de licence',
        'medicalCertificateExpiry' => 'Expiration certificat médical',
        'insuranceExpiry' => 'Expiration assurance',
        'emailVerified' => 'Email vérifié'
    ];
}
```

---

## 📋 Checklist des Modifications

### Galeries Privées

- [ ] Supprimer colonnes `visibility` et `access_code` de `Gallery`
- [ ] Créer et exécuter migration database
- [ ] Supprimer méthode `unlock()` de `PublicGalleryController`
- [ ] Simplifier `index()` et `show()` de `PublicGalleryController`
- [ ] Supprimer formulaire code d'accès des templates
- [ ] Supprimer champs du formulaire `GalleryType`
- [ ] Supprimer route `/gallery/{slug}/unlock`
- [ ] Tester : toutes galeries accessibles publiquement

### Système EAV

- [ ] Ajouter champs classiques à entité `User`
- [ ] Créer migration pour ajouter colonnes à `user`
- [ ] Migrer données EAV vers colonnes classiques (script)
- [ ] Supprimer `AttributeDefinition.php`
- [ ] Supprimer `EntityAttribute.php`
- [ ] Supprimer `AdminAttributeDefinitionController.php`
- [ ] Supprimer `AdminUserAttributeController.php`
- [ ] Supprimer `AttributeManager.php` (service)
- [ ] Créer migration pour supprimer tables EAV
- [ ] Adapter `EventConditionService` (accès direct propriétés)
- [ ] Simplifier `AdminEventConditionController` (liste fixe)
- [ ] Ajouter champs aux formulaires utilisateur
- [ ] Supprimer routes admin attributs
- [ ] Mettre à jour templates profil utilisateur
- [ ] Tester : conditions fonctionnent avec nouveaux champs

### Tests

- [ ] Exécuter suite de tests complète
- [ ] Vérifier pas de références EAV restantes
- [ ] Vérifier galeries toutes accessibles
- [ ] Vérifier conditions événements fonctionnent
- [ ] Tester profil utilisateur avec nouveaux champs

---

## 🚀 Script de Migration Données EAV

Pour migrer les données existantes de EAV vers colonnes :

```php
<?php
// src/Command/MigrateEavToColumnsCommand.php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateEavToColumnsCommand extends Command
{
    protected static $defaultName = 'app:migrate-eav';

    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->em->getConnection();

        // Récupérer tous les attributs EAV de type User
        $attributes = $conn->fetchAllAssociative(
            'SELECT entity_id, attribute_name, attribute_value
             FROM entity_attribute
             WHERE entity_type = ?',
            ['User']
        );

        $mapping = [
            'licence_number' => 'licenceNumber',
            'medical_certificate_date' => 'medicalCertificateDate',
            'medical_certificate_expiry' => 'medicalCertificateExpiry',
            'insurance_number' => 'insuranceNumber',
            'insurance_expiry' => 'insuranceExpiry',
            'emergency_contact_name' => 'emergencyContactName',
            'emergency_contact_phone' => 'emergencyContactPhone'
        ];

        foreach ($attributes as $attr) {
            $userId = $attr['entity_id'];
            $eavName = $attr['attribute_name'];
            $value = $attr['attribute_value'];

            if (isset($mapping[$eavName])) {
                $columnName = $this->camelToSnake($mapping[$eavName]);

                $conn->executeStatement(
                    "UPDATE user SET $columnName = ? WHERE id = ?",
                    [$value, $userId]
                );

                $output->writeln("Migré {$eavName} pour user {$userId}");
            }
        }

        $output->writeln('Migration terminée !');

        return Command::SUCCESS;
    }

    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
```

**Exécution :**
```bash
php bin/console app:migrate-eav
```

---

## 📊 Bénéfices Attendus

### Galeries Simplifiées

✅ Moins de code (suppression ~200 lignes)
✅ Pas de vulnérabilité codes d'accès
✅ Expérience utilisateur simplifiée
✅ Maintenance plus facile

### Suppression EAV

✅ **Performances** : Requêtes SQL plus simples et rapides
✅ **Validation** : Contraintes database + Symfony Validator
✅ **Typage** : Propriétés typées, moins d'erreurs
✅ **IDE** : Autocomplétion fonctionne
✅ **Requêtes** : Doctrine QueryBuilder standard
✅ **Maintenance** : Code plus simple
✅ **Tests** : Plus facile à tester

### Métriques

| Avant | Après | Gain |
|-------|-------|------|
| 14 entités | 12 entités | -2 |
| ~10,000 lignes | ~9,500 lignes | -5% |
| 28 contrôleurs | 26 contrôleurs | -2 |
| Complexité élevée | Complexité moyenne | ⬇️ |

---

## ⚠️ Points d'Attention

### Données Existantes

Si vous avez déjà des données en production :

1. **Backup database complet** avant migration
2. **Tester migration** sur copie de production
3. **Exécuter script migration EAV** avant suppression tables
4. **Vérifier** que toutes données migrées
5. **Seulement ensuite** supprimer tables EAV

### Conditions Événements

Les conditions existantes basées sur EAV devront être recréées :

**Avant :**
```
attributeName: "custom_medical_cert"  (EAV)
```

**Après :**
```
attributeName: "medicalCertificateExpiry"  (propriété)
```

→ **Recréer** toutes les conditions dans l'interface admin après migration

---

## 🎯 Ordre d'Exécution Recommandé

1. ✅ **Backup production** complet
2. ✅ **Créer branche git** `feature/simplify-architecture`
3. ✅ Ajouter colonnes à `User` (migration)
4. ✅ Exécuter script migration EAV → colonnes
5. ✅ Vérifier données migrées
6. ✅ Supprimer code EAV (entités, contrôleurs, services)
7. ✅ Supprimer tables EAV (migration)
8. ✅ Simplifier galeries (suppression code accès)
9. ✅ Tester sur environnement de dev
10. ✅ Exécuter cahier de recette complet
11. ✅ Code review
12. ✅ Merge vers `main`
13. ✅ Déployer en production

---

[⬅️ Retour à l'index](README.md)
