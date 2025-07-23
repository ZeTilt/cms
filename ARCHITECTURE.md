# Architecture Technique - ZeTilt CMS

## 🏛 Vue d'Ensemble

ZeTilt CMS suit une architecture hexagonale adaptée avec Symfony, privilégiant la séparation des responsabilités et la maintenabilité.

## 🔄 Flow de Données

```
User Request → Controller → Service → Repository → Entity → Database
                    ↓
            Template ← Service ← Cache ← Security Layer
```

## 📦 Couches Applicatives

### 1. Couche Présentation (Controllers)

#### AdminController
- **Responsabilité** : Interface d'administration, gestion des modules
- **Sécurité** : `ROLE_ADMIN`, `ROLE_SUPER_ADMIN`
- **Routes** : `/admin/*`

#### PagesController
- **Responsabilité** : CRUD des pages, gestion des templates
- **Fonctionnalités** : Création automatique de templates Twig
- **Services** : `PageTemplateService`

#### ArticleController
- **Responsabilité** : CRUD des articles de blog
- **Fonctionnalités** : Éditeur WYSIWYG, validation, sanitisation
- **Services** : `ContentSanitizer`, `ArticleValidator`

#### BlogController (Public)
- **Responsabilité** : Affichage public du blog
- **Fonctionnalités** : Pagination, filtrage par catégorie/tag
- **Cache** : Cache intelligent des listes

### 2. Couche Métier (Services)

#### ModuleManager
```php
class ModuleManager
{
    public function activateModule(string $name): bool
    public function deactivateModule(string $name): bool
    public function isModuleActive(string $name): bool
    public function registerModule(string $name, ...): Module
}
```

**Usage** : Gestion dynamique des modules, activation/désactivation

#### PageTemplateService
```php
class PageTemplateService
{
    public function createTemplate(Page $page): string
    public function templateExists(string $path): bool
    public function getTemplatePath(string $path): string
    public function deleteTemplate(string $path): bool
}
```

**Usage** : Gestion des templates Twig pour les pages

#### ContentSanitizer
```php
class ContentSanitizer
{
    public function sanitizeContent(string $content): string
    public function generateExcerpt(string $content, int $length): string
    public function sanitizeUrl(string $url): ?string
}
```

**Sécurité** : HTMLPurifier, protection XSS, validation des données

#### CacheService
```php
class CacheService
{
    public function getCachedBlogArticles(int $page, callable $callback): array
    public function getCachedArticle(string $slug, callable $callback): ?object
    public function clearBlogCache(): void
}
```

**Performance** : Cache adaptatif avec TTL différenciés

### 3. Couche Données (Entities & Repositories)

#### Entités Principales

**Page**
```php
class Page
{
    private string $title;
    private string $slug;
    private string $template_path;  // Chemin vers template Twig
    private string $status;         // draft|published|archived
    private array $tags;
    private User $author;
}
```

**Article**
```php
class Article
{
    private string $title;
    private string $slug;
    private string $content;        // HTML sanitisé
    private string $excerpt;
    private string $category;
    private array $tags;
    private string $status;         // draft|published
    private \DateTime $published_at;
}
```

**Module**
```php
class Module
{
    private string $name;           // Identifiant unique
    private string $display_name;   // Nom affiché
    private string $description;
    private bool $active;
    private array $config;          // Configuration JSON
}
```

#### Repositories Optimisés

**ArticleRepository**
```php
// Méthodes optimisées avec cache
public function findPublished(int $limit = null, int $offset = null): array
public function findPublishedByCategory(string $category): array
public function countPublished(): int
```

## 🎨 Architecture Frontend

### Templates Structure
```
templates/
├── admin/              # Interface d'administration
│   ├── base.html.twig    # Layout admin avec navigation
│   ├── pages/           # CRUD pages
│   ├── articles/        # CRUD articles
│   └── modules.html.twig # Gestion modules
├── blog/               # Blog public
│   ├── index.html.twig   # Liste articles
│   ├── article.html.twig # Article unique
│   └── category.html.twig # Filtrage
├── pages/              # Templates pages personnalisés
│   └── [slug].html.twig  # Template généré automatiquement
└── components/         # Composants réutilisables
    └── pagination.html.twig
```

### Système de Templates Pages

**Workflow** :
1. Admin crée une page → `PageTemplateService::createTemplate()`
2. Template généré dans `templates/pages/[slug].html.twig`
3. Développeur édite le template avec IDE
4. `PublicPagesController` utilise le template personnalisé

**Template Type** :
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ page.title }} - ZeTilt CMS{% endblock %}

{% block body %}
    {# Contenu personnalisé par le développeur #}
{% endblock %}
```

## 🔒 Architecture Sécurité

### Niveaux d'Autorisation
```php
// Hiérarchie des rôles
ROLE_USER → ROLE_ADMIN → ROLE_SUPER_ADMIN
```

**Règles** :
- `ROLE_USER` : Accès de base (futur : commentaires, profil)
- `ROLE_ADMIN` : Gestion contenu (pages, articles, galeries)
- `ROLE_SUPER_ADMIN` : Administration système (modules, utilisateurs)

### Sanitisation Multi-Niveaux

1. **Validation Input** : `ArticleValidator`
2. **Sanitisation HTML** : `ContentSanitizer` + HTMLPurifier
3. **Échappement Output** : Twig auto-escape
4. **CSRF Protection** : Tokens sur tous les formulaires

## ⚡ Architecture Performance

### Cache Strategy

**Niveaux de Cache** :
```php
// Cache Articles (1h)
$articles = $cache->get("blog_articles_page_{$page}", $callback);

// Cache Métadonnées (6h)
$categories = $cache->get('blog_categories', $callback);

// Cache Templates (illimité, invalidation manuelle)
$template = $twig->getCacheService()->getCached($templateName);
```

**Invalidation** :
- **ArticleCacheListener** : Invalide automatiquement à la modification
- **Manual** : `CacheService::clearBlogCache()`

### Optimisations Base de Données

**Index Recommandés** :
```sql
-- Pages
CREATE INDEX idx_pages_slug ON pages(slug);
CREATE INDEX idx_pages_status ON pages(status);

-- Articles
CREATE INDEX idx_articles_slug ON articles(slug);
CREATE INDEX idx_articles_status_published ON articles(status, published_at);
CREATE INDEX idx_articles_category ON articles(category);

-- Modules
CREATE INDEX idx_modules_name ON modules(name);
CREATE INDEX idx_modules_active ON modules(active);
```

## 🧩 Système de Modules

### Architecture Modulaire

**Module Registration** :
```php
$moduleManager->registerModule(
    name: 'blog',
    displayName: 'Blog Management',
    description: 'Articles avec WYSIWYG et catégories',
    config: [
        'posts_per_page' => 10,
        'enable_comments' => false,
        'enable_categories' => true
    ]
);
```

**Conditional Features** :
```twig
{# Navigation conditionnelle #}
{% if is_module_active('blog') %}
    <a href="{{ path('admin_articles_list') }}">Articles</a>
{% endif %}

{# Templates #}
{% if is_module_active('gallery') %}
    <a href="{{ path('admin_galleries_list') }}">Galleries</a>
{% endif %}
```

### Activation/Désactivation

**JavaScript Module Toggle** :
```javascript
function toggleModule(moduleName, activate) {
    fetch(`/admin/modules/${moduleName}/toggle`, {
        method: 'POST',
        body: new FormData([['action', activate ? 'activate' : 'deactivate']])
    })
}
```

**Backend Processing** :
```php
public function toggleModule(string $moduleName, Request $request): JsonResponse
{
    $action = $request->request->get('action');
    $success = $action === 'activate' 
        ? $this->moduleManager->activateModule($moduleName)
        : $this->moduleManager->deactivateModule($moduleName);
    
    return new JsonResponse(['success' => $success]);
}
```

## 🔄 Event-Driven Architecture

### Listeners

**ArticleCacheListener** :
```php
#[AsEntityListener(event: Events::postPersist, entity: Article::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Article::class)]
class ArticleCacheListener
{
    public function postUpdate(Article $article): void
    {
        $this->cacheService->clearBlogCache();
        $this->cacheService->clearArticleCache($article->getSlug());
    }
}
```

## 🧪 Architecture Tests

### Structure Tests
```
tests/
├── Unit/               # Tests unitaires services
│   ├── Service/
│   │   ├── ContentSanitizerTest.php
│   │   ├── ModuleManagerTest.php
│   │   └── PageTemplateServiceTest.php
│   └── Entity/
│       ├── ArticleTest.php
│       └── PageTest.php
├── Functional/         # Tests fonctionnels contrôleurs
│   ├── Controller/
│   │   ├── AdminControllerTest.php
│   │   ├── BlogControllerTest.php
│   │   └── PagesControllerTest.php
│   └── Repository/
│       └── ArticleRepositoryTest.php
└── Integration/        # Tests d'intégration
    └── ModuleSystemTest.php
```

### Test Patterns

**Service Testing** :
```php
class ContentSanitizerTest extends TestCase
{
    public function testSanitizeRemovesMaliciousContent(): void
    {
        $sanitizer = new ContentSanitizer();
        $malicious = '<script>alert("xss")</script><p>Valid content</p>';
        $result = $sanitizer->sanitizeContent($malicious);
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('<p>Valid content</p>', $result);
    }
}
```

## 📊 Monitoring & Observabilité

### Logs Structure
```php
// Service logs
$this->logger->info('Article created', [
    'article_id' => $article->getId(),
    'author' => $article->getAuthor()->getEmail(),
    'title' => $article->getTitle()
]);

// Performance logs
$this->logger->debug('Cache miss for blog articles', [
    'page' => $page,
    'limit' => $limit,
    'execution_time' => $executionTime
]);
```

### Métriques Recommandées
- Temps de réponse par contrôleur
- Taux de cache hit/miss
- Nombre d'articles publiés/jour
- Erreurs 404 sur pages personnalisées

## 🚀 Extensibilité

### Ajouter un Nouveau Module

1. **Créer l'entité** :
```php
#[ORM\Entity]
class Product
{
    // Structure similaire à Article
}
```

2. **Créer le contrôleur** :
```php
#[Route('/admin/products')]
class ProductController extends AbstractController
{
    public function __construct(private ModuleManager $moduleManager) {}
    
    #[Route('', name: 'admin_products_list')]
    public function index(): Response
    {
        if (!$this->moduleManager->isModuleActive('products')) {
            throw $this->createNotFoundException('Products module not active');
        }
        // ...
    }
}
```

3. **Enregistrer le module** :
```php
// src/Command/InitProductsModuleCommand.php
$this->moduleManager->registerModule(
    'products', 
    'Products Management',
    'Gestion des produits e-commerce'
);
```

4. **Ajouter à la navigation** :
```twig
{% if is_module_active('products') %}
    <a href="{{ path('admin_products_list') }}">Products</a>
{% endif %}
```

Cette architecture garantit :
- ✅ **Maintenabilité** : Code organisé et documenté
- ✅ **Extensibilité** : Système de modules flexible
- ✅ **Performance** : Cache intelligent et optimisations
- ✅ **Sécurité** : Validation et sanitisation multi-niveaux
- ✅ **Testabilité** : Architecture découplée et testable