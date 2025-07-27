<?php

// Script complet pour remplacer TOUS les textes hardcodés détectés

$replacements = [
    // Template fixes - specific to contexts
    'templates/admin/userplus/dashboard.html.twig' => [
        'Total Attributs' => "{{ 'dashboard.total_attributes'|trans({}, 'userplus') }}",
    ],
    
    'templates/admin/pages/list.html.twig' => [
        'Aucune page pour le moment' => "{{ 'list.no_pages'|trans({}, 'pages') }}",
    ],
    
    'templates/admin/modules.html.twig' => [
        'Gestion des Modules' => "{{ 'modules.title'|trans({}, 'admin') }}",
        'Aucune description disponible' => "{{ 'modules.no_description'|trans({}, 'admin') }}",
    ],
    
    'templates/admin/translation/dashboard.html.twig' => [
        'Gestion des Traductions' => "{{ 'title'|trans({}, 'translations') }}",
        'Paramètres' => "{{ 'actions.settings'|trans({}, 'admin') }}",
        'Contenu' => "{{ 'fields.content'|trans({}, 'admin') }}",
        'Aucun contenu à traduire' => "{{ 'messages.no_content'|trans({}, 'translations') }}",
        'Commencez par créer des pages, articles ou autres contenus.' => "{{ 'messages.get_started'|trans({}, 'translations') }}",
    ],
    
    'templates/admin/translation/settings.html.twig' => [
        'Paramètres de Traduction' => "{{ 'settings.title'|trans({}, 'translations') }}",
        'Sélectionnez toutes les langues que vous voulez supporter sur votre site' => "{{ 'settings.help_text'|trans({}, 'translations') }}",
    ],
    
    'templates/admin/userplus/user_types.html.twig' => [
        'Gestion des Types d\'Utilisateurs' => "{{ 'user_types.title'|trans({}, 'userplus') }}",
        'Gestion des Utilisateurs' => "{{ 'title'|trans({}, 'userplus') }}",
        'Aucun type d\'utilisateur' => "{{ 'user_types.no_types'|trans({}, 'userplus') }}",
        'Lecture seule' => "{{ 'user_types.readonly'|trans({}, 'userplus') }}",
    ],
    
    'templates/blog/category.html.twig' => [
        'Articles de la catégorie' => "{{ 'messages.category_description'|trans({}, 'articles') }}",
    ],
    
    'templates/blog/tag.html.twig' => [
        'Articles avec le tag' => "{{ 'messages.tag_description'|trans({}, 'articles') }}",
    ],
    
    'templates/events/calendar.html.twig' => [
        'Aucun événement prévu ce jour' => "{{ 'messages.no_events_today'|trans({}, 'events') }}",
        'Voir tous les événements' => "{{ 'actions.view_all_events'|trans({}, 'events') }}",
    ],
    
    'templates/components/pagination.html.twig' => [
        'Précédent' => "{{ 'pagination.previous'|trans({}, 'admin') }}",
        'Suivant' => "{{ 'pagination.next'|trans({}, 'admin') }}",
        'Page' => "{{ 'pagination.page'|trans({}, 'admin') }}",
        'sur' => "{{ 'pagination.of'|trans({}, 'admin') }}",
    ],
];

function fixHardcodedText($filePath, $fileReplacements) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return false;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $changes = 0;
    
    foreach ($fileReplacements as $search => $replace) {
        $newContent = str_replace($search, $replace, $content);
        if ($newContent !== $content) {
            $changes += substr_count($content, $search);
            $content = $newContent;
        }
    }
    
    if ($changes > 0) {
        file_put_contents($filePath, $content);
        echo "Fixed $filePath - $changes replacements\n";
        return true;
    }
    
    return false;
}

$totalFixed = 0;
foreach ($replacements as $file => $fileReplacements) {
    $fullPath = __DIR__ . '/' . $file;
    if (fixHardcodedText($fullPath, $fileReplacements)) {
        $totalFixed++;
    }
}

echo "\nPhase 1 completed: Fixed $totalFixed files\n";

// Phase 2: Generic replacements for common terms
$genericReplacements = [
    // Common French words that should be translated
    '>Total<' => ">{{ 'fields.total'|trans({}, 'admin') }}<",
    '>Description<' => ">{{ 'fields.description'|trans({}, 'admin') }}<",
    '>Date<' => ">{{ 'fields.date'|trans({}, 'admin') }}<",
    '>Configuration<' => ">{{ 'fields.configuration'|trans({}, 'admin') }}<",
    '>Page<' => ">{{ 'fields.page'|trans({}, 'admin') }}<",
    '>Article<' => ">{{ 'fields.article'|trans({}, 'admin') }}<",
    
    // Skip JavaScript Date() constructors and similar
];

// Apply generic replacements to all templates
$templatesDir = __DIR__ . '/templates';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($templatesDir)
);

$phase2Fixed = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'twig') {
        $filepath = $file->getPathname();
        $content = file_get_contents($filepath);
        $originalContent = $content;
        
        foreach ($genericReplacements as $search => $replace) {
            // Skip JavaScript code
            if (strpos($content, 'new Date(') !== false && strpos($search, 'Date') !== false) {
                continue;
            }
            
            $content = str_replace($search, $replace, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($filepath, $content);
            $phase2Fixed++;
            echo "Applied generic fixes to: " . str_replace(__DIR__ . '/', '', $filepath) . "\n";
        }
    }
}

echo "\nPhase 2 completed: Applied generic fixes to $phase2Fixed files\n";
echo "\nTotal fixes applied. Run the detection script again to see remaining issues.\n";