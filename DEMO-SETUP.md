# ZeTilt CMS - Configuration Démo Vénètes

## 🎯 Status de la Démo

✅ **CMS Initialisé avec succès !**

La démo est maintenant prête à être utilisée avec :
- Base de données configurée (SQLite)
- Utilisateur admin créé
- Modules activés
- Contenu de démonstration généré
- Assets configurés

---

## 🔐 Accès Admin

**URL Admin :** `/admin`

**Identifiants :**
- **Email :** `admin@zetilt.cms`
- **Mot de passe :** `admin123`

⚠️ **Important :** Changez le mot de passe par défaut après la première connexion.

---

## 📊 Contenu Créé

### Pages Statiques
- **About** - Page de présentation
- **Services** - Services proposés
- **Contact** - Informations de contact

### Articles de Blog
- **Welcome to Our Blog** - Article d'introduction
- **The Art of Portrait Photography** - Guide technique
- **Building Your Creative Portfolio** - Conseils portfolio

### Galeries Photo
**Galeries Publiques :**
- Nature Photography
- Portrait Sessions
- Wedding Photography  
- Urban Exploration

**Galeries Privées :**
- Client Gallery - Smith Wedding
- Commercial Shoot - Local Business

---

## 🚀 Démarrer le Serveur

### Option 1: Serveur Symfony (Recommandé)
```bash
symfony serve --no-tls
```

### Option 2: Serveur PHP Built-in
```bash
php -S localhost:8000 -t public/
```

### Option 3: Docker (si disponible)
```bash
docker-compose up
```

---

## 🌐 URLs d'Accès

Une fois le serveur démarré :

- **Site Public :** `http://localhost:8000`
- **Administration :** `http://localhost:8000/admin`
- **Pages :** `http://localhost:8000/about`, `/services`, `/contact`
- **Blog :** `http://localhost:8000/blog`
- **Galeries :** `http://localhost:8000/galleries`

---

## 🔧 Modules Disponibles

### Modules Actifs
- ✅ **Pages Module** - Gestion des pages statiques
- ✅ **Gallery Module** - Galeries photos
- ✅ **UserPlus Module** - Gestion utilisateurs avancée
- ✅ **Events Module** - Gestion d'événements
- ✅ **Business Module** - Fonctionnalités business
- ✅ **Blog Module** - Articles et actualités

### Activation/Désactivation
Utilisez l'interface Super Admin ou les commandes console :
```bash
# Lister les modules
php bin/console zetilt:module:list

# Activer un module
php bin/console zetilt:module:activate [module-name]
```

---

## 📁 Structure des Fichiers

```
mon-site-plongee/
├── src/
│   ├── Controller/     # Contrôleurs (Admin, Public, API)
│   ├── Entity/         # Entités Doctrine
│   ├── Repository/     # Repositories
│   ├── Service/        # Services métier
│   └── Command/        # Commandes console
├── templates/
│   ├── admin/          # Templates administration
│   ├── blog/           # Templates blog
│   ├── pages/          # Templates pages
│   └── galleries/      # Templates galeries
├── public/
│   ├── uploads/        # Fichiers uploadés
│   └── assets/         # Assets statiques
├── var/
│   └── demo.db         # Base de données SQLite
└── config/             # Configuration Symfony
```

---

## 🛠 Commandes Utiles

### Base de Données
```bash
# Status des migrations
php bin/console doctrine:migrations:status

# Nouvelle migration
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate
```

### Cache
```bash
# Vider le cache
php bin/console cache:clear

# Réchauffer le cache
php bin/console cache:warmup
```

### Développement
```bash
# Créer une nouvelle entité
php bin/console make:entity

# Créer un contrôleur
php bin/console make:controller

# Créer un formulaire
php bin/console make:form
```

---

## 📧 Support

Pour toute question ou problème :
- **Documentation :** Consultez le README.md principal
- **Issues :** Utilisez le système d'issues GitHub
- **Email :** contact@zetilt.com

---

## 🎨 Personnalisation

### Templates
Les templates Twig sont dans `templates/` et peuvent être personnalisés selon vos besoins.

### Styles
Les fichiers CSS sont dans `assets/css/` et `public/assets/`.

### Uploads
Les images uploadées sont stockées dans `public/uploads/`.

---

**🎉 Votre démo ZeTilt CMS est prête !**

Lancez le serveur et commencez à explorer les fonctionnalités.