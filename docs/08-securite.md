# Sécurité

[⬅️ Retour à l'index](README.md) | [⬅️ Interface](07-interface-utilisateur.md) | [➡️ Simplifications](09-simplifications.md)

## 🔒 Analyse de Sécurité

## ✅ Points Forts

### 1. Authentification & Autorisation

**Configuration :** `config/packages/security.yaml`

✅ **Form Login sécurisé**
```yaml
form_login:
    login_path: app_login
    check_path: app_login
    enable_csrf: true
```

✅ **Password Hashing**
```yaml
password_hashers:
    App\Entity\User:
        algorithm: auto  # bcrypt ou argon2i selon disponibilité
```

✅ **Hiérarchie des rôles**
```yaml
role_hierarchy:
    ROLE_DP: ROLE_USER
    ROLE_ADMIN: ROLE_DP
    ROLE_SUPER_ADMIN: ROLE_ADMIN
```

✅ **Access Control**
```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/dp, roles: ROLE_DP }
    - { path: ^/profile, roles: ROLE_USER }
```

### 2. Protection CSRF

✅ **Activée globalement**
```yaml
framework:
    csrf_protection: ~
```

✅ **Utilisée dans formulaires**
```php
$form = $this->createForm(EventType::class, $event);
// CSRF token automatique
```

### 3. Validation des Entrées

✅ **Symfony Validator**
```php
class User {
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[Assert\Length(min: 8)]
    private string $password;
}
```

✅ **Form Validation**
- Validation côté serveur
- Messages d'erreur appropriés

### 4. Protection XSS

✅ **Twig Auto-Escaping**
```twig
{{ user.name }}  {# Échappé automatiquement #}
{{ content|raw }} {# Explicit pour HTML sûr #}
```

✅ **HTMLPurifier**
```php
class ContentSanitizer {
    public function sanitize(string $html): string {
        return $this->purifier->purify($html);
    }
}
```

### 5. Protection SQL Injection

✅ **Doctrine ORM**
```php
$query = $em->createQuery('SELECT u FROM User u WHERE u.email = :email')
    ->setParameter('email', $email); // Paramètres bindés
```

✅ **Query Builder**
```php
$qb->where('u.status = :status')
   ->setParameter('status', $status);
```

### 6. User Checker

✅ **Validation à la connexion**

**Fichier :** `src/Security/UserChecker.php`

```php
public function checkPreAuth(UserInterface $user): void
{
    if (!$user instanceof User) {
        return;
    }

    if (!$user->isActive()) {
        throw new AccountStatusException('Compte inactif');
    }

    if ($user->getStatus() !== 'approved') {
        throw new AccountStatusException('Compte en attente d\'approbation');
    }

    if (!$user->isEmailVerified()) {
        throw new AccountStatusException('Email non vérifié');
    }
}
```

## ⚠️ Vulnérabilités et Risques

### 1. Codes d'Accès Galerie (CRITIQUE)

❌ **Problème :** Codes stockés en clair

**Fichier :** `src/Entity/Gallery.php`

```php
private ?string $accessCode = null;  // Stocké en clair !
```

**Impact :** Si base de données compromise, codes exposés

**Recommandation :**

```php
// Utiliser hashing comme pour mots de passe
class Gallery {
    private ?string $accessCodeHash = null;

    public function setAccessCode(string $code): void {
        $this->accessCodeHash = password_hash($code, PASSWORD_DEFAULT);
    }

    public function verifyAccessCode(string $code): bool {
        return password_verify($code, $this->accessCodeHash);
    }
}
```

### 2. Rate Limiting (MANQUANT)

⚠️ **Problème :** Pas de limitation tentatives login

**Impact :** Attaque brute force possible

**Recommandation :**

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            login_throttling:
                max_attempts: 5
                interval: '15 minutes'
```

### 3. Email Verification (INCOMPLET)

⚠️ **Problème :** Token généré mais infrastructure email incomplète

**Fichier :** `src/Controller/RegistrationController.php`

```php
$token = bin2hex(random_bytes(32));  // ✅ Bon
$user->setEmailVerificationToken($token);

// ⚠️ Email non envoyé (infrastructure manquante)
```

**Recommandation :**

```php
// Finaliser envoi email
$this->mailer->send((new Email())
    ->to($user->getEmail())
    ->subject('Vérifiez votre email')
    ->html($this->renderView('emails/verify.html.twig', [
        'token' => $token
    ]))
);
```

### 4. File Upload Validation (FAIBLE)

⚠️ **Problème :** Validation MIME type insuffisante

**Fichier :** `src/Service/ImageUploadService.php` (supposé)

**Recommandation :**

```php
public function validateImage(UploadedFile $file): void
{
    // Vérifier extension
    if (!in_array($file->guessExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
        throw new \Exception('Format non autorisé');
    }

    // Vérifier MIME type réel (pas juste l'extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file->getPathname());

    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
        throw new \Exception('Type MIME invalide');
    }

    // Vérifier taille
    if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
        throw new \Exception('Fichier trop volumineux');
    }

    // Re-encoder image pour supprimer metadata et scripts
    $image = imagecreatefromstring(file_get_contents($file->getPathname()));
    imagejpeg($image, $file->getPathname(), 90);
}
```

### 5. Session Security (CONFIGURATION MANQUANTE)

⚠️ **Problème :** Pas de configuration session sécurisée visible

**Recommandation :**

```yaml
# config/packages/framework.yaml
framework:
    session:
        cookie_secure: auto  # HTTPS uniquement en prod
        cookie_httponly: true
        cookie_samesite: lax
        gc_maxlifetime: 3600  # 1 heure
```

### 6. Headers de Sécurité (MANQUANTS)

⚠️ **Problème :** Headers HTTP de sécurité non configurés

**Recommandation :**

```php
// public/.htaccess ou Nginx config
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"

# HTTPS obligatoire en production
Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

Ou via bundle :

```bash
composer require nelmio/security-bundle
```

### 7. Logs Sensibles (RISQUE)

⚠️ **Problème :** Logs peuvent contenir données sensibles

**Recommandation :**

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
            formatter: monolog.formatter.json
            processor:
                - Monolog\Processor\PsrLogMessageProcessor
                - App\Logging\SensitiveDataProcessor  # Filtrer passwords, tokens
```

### 8. Pas de Content Security Policy (CSP)

⚠️ **Problème :** CSP non implémentée

**Impact :** XSS avancés possibles

**Recommandation :**

```yaml
# config/packages/nelmio_security.yaml
nelmio_security:
    content_security_policy:
        default-src: "'self'"
        script-src: "'self' 'unsafe-inline' https://cdn.tailwindcss.com"
        style-src: "'self' 'unsafe-inline'"
        img-src: "'self' data: https:"
        font-src: "'self' data:"
```

## 🛡️ Recommandations par Priorité

### 🔴 Priorité HAUTE (Immédiat)

1. **Hasher les codes d'accès galerie**
2. **Activer login throttling**
3. **Valider strictement uploads**
4. **Configurer session sécurisée**

### 🟠 Priorité MOYENNE (Court terme)

5. **Finaliser email verification**
6. **Ajouter headers sécurité**
7. **Implémenter CSP**
8. **Rate limiting API**

### 🟡 Priorité BASSE (Long terme)

9. **Audit sécurité complet**
10. **Penetration testing**
11. **Bug bounty programme**
12. **RGPD compliance audit**

## 🔍 Checklist Sécurité

### Authentification
- [x] Password hashing (bcrypt/argon2)
- [x] CSRF protection
- [x] User status validation
- [ ] Rate limiting login
- [ ] 2FA (optionnel)
- [ ] Password reset sécurisé

### Autorisation
- [x] Role-based access control
- [x] Symfony Security Voters (partiel)
- [x] Access control lists
- [ ] Object-level permissions

### Validation
- [x] Input validation (Symfony Validator)
- [x] Output escaping (Twig)
- [ ] File upload validation stricte
- [x] CSRF tokens

### Données
- [x] SQL injection protection (ORM)
- [x] XSS protection (escaping + sanitizer)
- [ ] Encryption données sensibles
- [ ] Codes d'accès hashés

### Infrastructure
- [ ] HTTPS forcé (production)
- [ ] Headers sécurité
- [ ] CSP
- [ ] Session sécurisée

### Monitoring
- [ ] Logs sécurité
- [ ] Alertes intrusion
- [ ] Audit trail
- [ ] Monitoring fichiers

## 📋 Tests de Sécurité Recommandés

### Tests Manuels

1. **Injection SQL**
```
email: ' OR 1=1--
password: anything
```
Résultat attendu : Échec login (protection Doctrine)

2. **XSS**
```
<script>alert('XSS')</script>
```
Résultat attendu : Échappé ou sanitizé

3. **CSRF**
Supprimer token CSRF d'un formulaire
Résultat attendu : Erreur 403

### Tests Automatisés

```bash
# Security checker Symfony
symfony check:security

# PHP Security Checker
composer require --dev enlightn/security-checker
vendor/bin/security-checker security:check

# Static analysis
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse src
```

---

[➡️ Suite : Simplifications](09-simplifications.md)
