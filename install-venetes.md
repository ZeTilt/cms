# 🤿 Installation Club Subaquatique des Vénètes

## Problème actuel
❌ **Erreur d'authentification MySQL** - Les identifiants dans `.env.prod.local` sont incorrects ou la base n'existe pas

## Actions requises côté O2switch

### 1. Créer/vérifier la base de données MySQL
1. Connectez-vous à votre panneau O2switch
2. Allez dans "Bases de données MySQL" 
3. Créez une nouvelle base (ou vérifiez l'existante) :
   - **Nom** : `empo8897_venetes_preprod` (ou autre nom de votre choix)
   - **Utilisateur** : `empo8897_venetes_preprod` 
   - **Mot de passe** : généré par O2switch ou choisi par vous

### 2. Mettre à jour `.env.prod.local`
Modifiez la ligne DATABASE_URL avec les vrais identifiants :
```bash
DATABASE_URL="mysql://VRAIUSER:VRAIMOTDEPASSE@localhost:3306/VRAIEBASE?serverVersion=8.0&charset=utf8mb4"
```

### 3. Une fois la base accessible, exécuter dans l'ordre :

```bash
# 1. Corriger la base de données
php bin/console app:fix-database --env=prod

# 2. Créer un utilisateur administrateur
php bin/console app:create-admin-user admin@venetes.fr motdepasse Admin Vénètes --env=prod

# 3. Initialiser la configuration du site (si pas fait)
php bin/console app:init-site-config --env=prod

# 4. Vider le cache
php bin/console cache:clear --env=prod

# 5. Créer des événements de test (optionnel)
php bin/console app:create-plongee-events --env=prod
```

## Fonctionnalités développées ✅

### **Système de conditions d'inscription aux événements**
- ✅ **Entity Event enrichie** avec champs de conditions (niveau plongée, âge, certificat médical, test natation)
- ✅ **Entity EventParticipation** pour gérer les inscriptions utilisateurs  
- ✅ **Interface admin complète** pour définir les conditions par événement
- ✅ **Affichage public** des conditions sur les pages d'événements
- ✅ **Vérification automatique** d'éligibilité avant inscription
- ✅ **Messages détaillés** expliquant les prérequis manquants

### **Configuration email fonctionnelle**
- ✅ **SMTP configuré** avec `no-reply@venetes.dhuicque.fr`
- ✅ **Emails de vérification** lors de l'inscription utilisateur
- ✅ **Templates email** aux couleurs du club

### **Gestion utilisateurs avancée**
- ✅ **Inscription publique** avec validation admin
- ✅ **Vérification email** obligatoire 
- ✅ **Statuts utilisateur** : pending, approved, rejected
- ✅ **System EAV** pour attributs flexibles (niveau plongée, apnéiste, etc.)

## Structure des nouvelles tables

### `diving_levels` - Niveaux de plongée FFESSM
- N1, N2, N3, N4, N5 (plongeurs)
- E1, E2, E3, E4 (encadrement) 
- RIFAP (secours)

### `event_participation` - Inscriptions aux événements
- Statuts : registered, confirmed, cancelled, no_show, completed
- Dates d'inscription et confirmation
- Notes optionnelles

### Colonnes ajoutées à `event`
- `min_diving_level` - Niveau minimum requis
- `min_age` / `max_age` - Limites d'âge
- `requires_medical_certificate` - Certificat médical obligatoire
- `medical_certificate_validity_days` - Durée de validité
- `requires_swimming_test` - Test de natation requis
- `additional_requirements` - Conditions supplémentaires en texte libre

### Colonnes ajoutées à `users`
- `status` - Statut de validation (pending/approved/rejected)
- `email_verified` - Email vérifié (oui/non)
- `email_verification_token` - Token de vérification

## Test après installation

### 1. **Test interface admin**
- Aller sur `https://venetes.dhuicque.fr/admin`
- Se connecter avec le compte admin créé
- Créer un événement avec conditions spécifiques

### 2. **Test inscription utilisateur**  
- Aller sur `https://venetes.dhuicque.fr/inscription`
- Créer un compte utilisateur
- Vérifier réception email de vérification

### 3. **Test inscription événement**
- Voir un événement avec conditions
- Vérifier affichage des prérequis
- Tester inscription (éligible/non-éligible)

## Support
Une fois la base de données configurée, tout devrait fonctionner automatiquement !

**Email transactionnel configuré :** `no-reply@venetes.dhuicque.fr`
**Serveur SMTP :** `venetes.dhuicque.fr:465` (SSL)

Le système de conditions d'inscription est entièrement fonctionnel et permet aux administrateurs de définir précisément qui peut s'inscrire à chaque événement de plongée.