<?php

echo "CORRECTION AUTOMATIQUE DE TOUS LES TEXTES HARDCODES\n";
echo "===================================================\n\n";

// Ajouter les traductions communes manquantes en ajoutant au fichier existant
$commonFrTranslations = '
# Traductions communes
common:
  actions:
    edit: "Modifier"
    view: "Voir"
    delete: "Supprimer"
    create: "Créer"
    save: "Enregistrer"
    cancel: "Annuler"
    back: "Retour"
    next: "Suivant"
    previous: "Précédent"
    confirm: "Confirmer"
    activate: "Activer"
    deactivate: "Désactiver"
    publish: "Publier"
    unpublish: "Dépublier"
    search: "Rechercher"
    filter: "Filtrer"
    all: "Tous"
  status:
    active: "Actif"
    inactive: "Inactif"
    draft: "Brouillon"
    published: "Publié"
  messages:
    confirm_delete: "Êtes-vous sûr de vouloir supprimer cet élément ?"
    no_items: "Aucun élément trouvé"
    create_first: "Commencez par créer votre premier élément"
    select_option: "Sélectionner..."
    choose_entity: "Choisir une entité..."
    choose_attribute: "Choisir un attribut..."
    choose_operator: "Choisir un opérateur..."
    choose_value: "Choisir une valeur..."
    not_set: "Non défini"
    no_description: "Aucune description"
    total: "Total"
    permissions: "Permissions"
    yes: "Oui"
    no: "Non"
  placeholders:
    search: "Rechercher..."
    enter_value: "Saisissez une valeur..."
    numeric_value: "Valeur numérique"
    select_file: "Sélectionner un fichier"
    widget_content: "Contenu du widget..."
  forms:
    description: "Description"
    example: "Exemple"
    configuration: "Configuration"
    basic_info: "Informations de base"
    advanced: "Avancé"
    settings: "Paramètres"
  errors:
    remove_failed: "Erreur lors de la suppression"
    add_failed: "Erreur lors de l\'ajout"
';

$commonEnTranslations = '
# Common translations
common:
  actions:
    edit: "Edit"
    view: "View"
    delete: "Delete"
    create: "Create"
    save: "Save"
    cancel: "Cancel"
    back: "Back"
    next: "Next"
    previous: "Previous"
    confirm: "Confirm"
    activate: "Activate"
    deactivate: "Deactivate"
    publish: "Publish"
    unpublish: "Unpublish"
    search: "Search"
    filter: "Filter"
    all: "All"
  status:
    active: "Active"
    inactive: "Inactive"
    draft: "Draft"
    published: "Published"
  messages:
    confirm_delete: "Are you sure you want to delete this item?"
    no_items: "No items found"
    create_first: "Start by creating your first item"
    select_option: "Select..."
    choose_entity: "Choose entity..."
    choose_attribute: "Choose attribute..."
    choose_operator: "Choose operator..."
    choose_value: "Choose value..."
    not_set: "Not set"
    no_description: "No description"
    total: "Total"
    permissions: "Permissions"
    yes: "Yes"
    no: "No"
  placeholders:
    search: "Search..."
    enter_value: "Enter value..."
    numeric_value: "Numeric value"
    select_file: "Select file"
    widget_content: "Widget content..."
  forms:
    description: "Description"
    example: "Example"
    configuration: "Configuration"
    basic_info: "Basic information"
    advanced: "Advanced"
    settings: "Settings"
  errors:
    remove_failed: "Error removing"
    add_failed: "Error adding"
';

// Ajouter les traductions communes
$adminFrPath = __DIR__ . '/translations/admin.fr.yaml';
$adminEnPath = __DIR__ . '/translations/admin.en.yaml';

if (file_exists($adminFrPath)) {
    file_put_contents($adminFrPath, file_get_contents($adminFrPath) . $commonFrTranslations);
    echo "✅ Traductions communes ajoutées à admin.fr.yaml\n";
}

if (file_exists($adminEnPath)) {
    file_put_contents($adminEnPath, file_get_contents($adminEnPath) . $commonEnTranslations);
    echo "✅ Traductions communes ajoutées à admin.en.yaml\n";
}

// Liste des corrections critiques à appliquer
$corrections = [
    // Templates admin événements
    'templates/admin/events/edit.html.twig' => [
        '<option value="">Choisir une entité...</option>' => '<option value="">{{ \'common.messages.choose_entity\'|trans({}, \'admin\') }}</option>',
        '<option value="">Choisir un attribut...</option>' => '<option value="">{{ \'common.messages.choose_attribute\'|trans({}, \'admin\') }}</option>',
        '<option value="">Choisir un opérateur...</option>' => '<option value="">{{ \'common.messages.choose_operator\'|trans({}, \'admin\') }}</option>',
        '<option value="">Choisir une valeur...</option>' => '<option value="">{{ \'common.messages.choose_value\'|trans({}, \'admin\') }}</option>',
        '<option value="1" ${yesSelected}>Oui</option>' => '<option value="1" ${yesSelected}>{{ \'common.messages.yes\'|trans({}, \'admin\') }}</option>',
        '<option value="0" ${noSelected}>Non</option>' => '<option value="0" ${noSelected}>{{ \'common.messages.no\'|trans({}, \'admin\') }}</option>',
        'placeholder="Valeur numérique"' => 'placeholder="{{ \'common.placeholders.numeric_value\'|trans({}, \'admin\') }}"'
    ],
    
    // Templates admin utilisateurs
    'templates/admin/userplus/user_detail.html.twig' => [
        '<option value="">Not set</option>' => '<option value="">{{ \'common.messages.not_set\'|trans({}, \'admin\') }}</option>',
        '<option value="">Select a certification...</option>' => '<option value="">{{ \'common.messages.select_option\'|trans({}, \'admin\') }}</option>',
        'alert(\'Error removing certification\');' => 'alert(\'{{ \'common.errors.remove_failed\'|trans({}, \'admin\') }}\');',
        'alert(\'Error adding certification\');' => 'alert(\'{{ \'common.errors.add_failed\'|trans({}, \'admin\') }}\');'
    ],
    
    // Templates admin galeries  
    'templates/admin/galleries/show.html.twig' => [
        'if (!confirm(\'Are you sure you want to delete this image?\'))' => 'if (!confirm(\'{{ \'admin.confirm.delete_image\'|trans({}, \'galleries\') }}\'))',
        'alert(\'Failed to delete image: \' + (data.error || \'Unknown error\'));' => 'alert(\'{{ \'admin.errors.delete_failed\'|trans({}, \'galleries\') }}: \' + (data.error || \'{{ \'admin.errors.unknown_error\'|trans({}, \'galleries\') }}\'));',
        'alert(\'Failed to delete image: \' + error.message);' => 'alert(\'{{ \'admin.errors.delete_failed\'|trans({}, \'galleries\') }}: \' + error.message);'
    ],
    
    // Templates test roles
    'templates/test/roles.html.twig' => [
        '<strong>Permissions:</strong>' => '<strong>{{ \'common.messages.permissions\'|trans({}, \'admin\') }}:</strong>'
    ]
];

// Appliquer les corrections
$totalFixed = 0;
foreach ($corrections as $filePath => $replacements) {
    $fullPath = __DIR__ . '/' . $filePath;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $originalContent = $content;
        
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($fullPath, $content);
            $fixedCount = count($replacements);
            $totalFixed += $fixedCount;
            echo "✅ Corrigé $fixedCount textes dans $filePath\n";
        }
    } else {
        echo "⚠️  Fichier non trouvé: $filePath\n";
    }
}

echo "\n========================================\n";
echo "TOTAL: $totalFixed corrections appliquées\n";
echo "========================================\n";

// Vérifier les résultats
echo "\nVérification finale...\n";
exec('php find_user_facing_hardcoded.php 2>/dev/null', $output, $return_code);

if ($return_code === 0) {
    $remainingIssues = count(array_filter($output, function($line) {
        return strpos($line, 'Ligne ') !== false;
    }));
    
    echo "Textes hardcodés restants: $remainingIssues\n";
    
    if ($remainingIssues > 0) {
        echo "\n⚠️ Il reste encore des textes hardcodés à corriger manuellement.\n";
        echo "Utilisez le script find_user_facing_hardcoded.php pour les identifier.\n";
    } else {
        echo "\n✅ Tous les textes hardcodés critiques ont été corrigés !\n";
    }
} else {
    echo "⚠️ Impossible de vérifier automatiquement les textes restants.\n";
}