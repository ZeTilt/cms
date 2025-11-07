# Interface Utilisateur

[⬅️ Retour à l'index](README.md) | [⬅️ Services](06-services.md) | [➡️ Sécurité](08-securite.md)

## 🎨 Vue d'Ensemble

L'interface utilise **Tailwind CSS** via CDN et **JavaScript vanilla** pour l'interactivité.

**Total : 60+ templates Twig**

## 📁 Organisation des Templates

```
templates/
├── base.html.twig              # Base publique
├── admin/
│   ├── base.html.twig          # Base admin
│   ├── dashboard.html.twig
│   ├── event/
│   ├── user/
│   └── ...
├── dp/
│   ├── base.html.twig          # Base DP
│   └── events/
├── calendar/
│   ├── index.html.twig         # Calendrier mensuel
│   └── show.html.twig          # Détail événement
├── blog/
│   ├── index.html.twig         # Liste articles
│   └── show.html.twig          # Article
├── gallery/
│   ├── index.html.twig
│   └── show.html.twig
├── home/
│   └── index.html.twig
├── pages/                      # Templates générés
│   ├── qui-sommes-nous.html.twig
│   └── ...
└── security/
    ├── login.html.twig
    └── register.html.twig
```

## 🎨 Design System

### Couleurs (Tailwind)

```
Primary:   blue-600    (#2563EB)
Success:   green-600   (#16A34A)
Warning:   yellow-600  (#CA8A04)
Danger:    red-600     (#DC2626)
Gray:      gray-600    (#4B5563)
```

### Composants Communs

**Boutons :**
```html
<!-- Primary -->
<button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">

<!-- Secondary -->
<button class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">

<!-- Danger -->
<button class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
```

**Cards :**
```html
<div class="bg-white shadow-md rounded-lg p-6">
    <!-- Contenu -->
</div>
```

**Formulaires :**
```html
<input type="text" class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500">
```

## 📄 Templates Clés

### base.html.twig (Public)

**Structure :**
```twig
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{% block title %}Club Vénètes{% endblock %}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {% block stylesheets %}{% endblock %}
</head>
<body>
    {% include '_header.html.twig' %}

    <main>
        {% block body %}{% endblock %}
    </main>

    {% include '_footer.html.twig' %}

    {% block javascripts %}{% endblock %}
</body>
</html>
```

### admin/base.html.twig

**Particularités :**
- Sidebar navigation gauche
- Header avec user menu
- Breadcrumbs
- Flash messages

**Navigation :**
```twig
<nav class="sidebar">
    <a href="{{ path('admin_dashboard') }}">Dashboard</a>
    <a href="{{ path('admin_event_index') }}">Événements</a>
    <a href="{{ path('admin_user_index') }}">Utilisateurs</a>
    {% if is_granted('ROLE_SUPER_ADMIN') %}
        <a href="{{ path('admin_module_index') }}">Modules</a>
    {% endif %}
</nav>
```

### calendar/index.html.twig

**Fonctionnalités :**
- Grille mensuelle
- Navigation mois précédent/suivant
- Code couleur par type événement
- Clic → détail événement

**Structure :**
```twig
<div class="calendar-header">
    <button>← Mois précédent</button>
    <h2>{{ currentMonth|date('F Y') }}</h2>
    <button>Mois suivant →</button>
</div>

<div class="calendar-grid">
    {% for day in days %}
        <div class="day">
            <div class="date">{{ day.date|date('d') }}</div>
            {% for event in day.events %}
                <div class="event" style="background-color: {{ event.type.color }}">
                    {{ event.title }}
                </div>
            {% endfor %}
        </div>
    {% endfor %}
</div>
```

### calendar/show.html.twig (Détail Événement)

**Sections :**
1. Informations générales
2. Détails plongée (si applicable)
3. Places disponibles
4. Bouton inscription (si connecté et éligible)
5. Liste participants (si admin/DP)

## ⚙️ JavaScript

**Fichiers :** `public/js/`

### modules.js

**Responsabilité :** Toggle activation modules (admin)

```javascript
document.querySelectorAll('.module-toggle').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        const moduleId = e.target.dataset.moduleId;
        const response = await fetch(`/admin/modules/${moduleId}/toggle`, {
            method: 'POST'
        });
        // Refresh page ou update UI
    });
});
```

### gallery.js

**Responsabilité :** Lightbox et navigation images

```javascript
// Lightbox
document.querySelectorAll('.gallery-image').forEach(img => {
    img.addEventListener('click', () => {
        openLightbox(img.src);
    });
});

// Navigation
function openLightbox(src) {
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <img src="${src}">
        <button class="close">&times;</button>
    `;
    document.body.appendChild(lightbox);
}
```

### carousel.js

**Responsabilité :** Carrousel d'images

### youtube-thumbnails.js

**Responsabilité :** Génération thumbnails pour vidéos YouTube embedées

### page-editor.js

**Responsabilité :** Enhancements éditeur pages (admin)

## 📱 Responsive Design

**Breakpoints Tailwind :**
```
sm:  640px   (mobile)
md:  768px   (tablet)
lg:  1024px  (desktop)
xl:  1280px  (large desktop)
2xl: 1536px  (extra large)
```

**Exemples :**
```html
<!-- Stack mobile, grid desktop -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
```

## 🎯 Améliorations UI/UX Recommandées

### Actuellement Manquant

1. **Loading States**
   - Spinners lors chargement
   - Disabled states boutons

2. **Confirmations**
   - Modales confirmation suppression
   - Toast notifications

3. **Validation Inline**
   - Messages erreur près des champs
   - Validation temps réel

4. **Filtres Avancés**
   - Filtrage côté client
   - Recherche instantanée

5. **Dark Mode**
   - Option thème sombre
   - Sauvegarde préférence

### Propositions

**1. Utiliser Alpine.js**

Pour interactivité sans construire un frontend complexe :

```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Contenu</div>
</div>
```

**2. Build System (Webpack Encore)**

Remplacer CDN par :
```bash
npm install
npm run dev  # Développement
npm run build  # Production
```

Avantages :
- Minification
- Tree shaking
- Cache busting
- CSS Purge (Tailwind)

**3. Composants Réutilisables**

```twig
{# _components/button.html.twig #}
{% set classes = variant == 'primary' ? 'bg-blue-600' : 'bg-gray-200' %}
<button class="{{ classes }} px-4 py-2 rounded">
    {{ label }}
</button>

{# Usage #}
{% include '_components/button.html.twig' with {
    label: 'Enregistrer',
    variant: 'primary'
} %}
```

---

[➡️ Suite : Sécurité](08-securite.md)
