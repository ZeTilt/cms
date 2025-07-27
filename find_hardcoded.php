<?php

// Script pour détecter tous les textes français hardcodés dans les templates

function findHardcodedText($directory) {
    $results = [];
    $frenchPatterns = [
        // Mots français communs
        '/\b(Créer|Modifier|Supprimer|Enregistrer|Annuler|Confirmer|Publier|Activer|Désactiver)\b/',
        '/\b(Auteur|Titre|Description|Catégorie|Statut|Date|Contenu|Article|Page|Utilisateur)\b/',
        '/\b(Aucun|Aucune|Nouveau|Nouvelle|Premier|Première|Dernier|Dernière)\b/',
        '/\b(Total|Actif|Inactif|Publié|Brouillon|En\s+attente|Terminé|Échoué)\b/',
        '/\b(Retour|Précédent|Suivant|Filtrer|Rechercher|Voir|Télécharger)\b/',
        '/\b(Gestion|Administration|Tableau\s+de\s+bord|Paramètres|Configuration)\b/',
        '/\b(Commencez|Contactez|Essayez|Choisissez|Sélectionnez)\b/',
        '/\b(Lecture\s+seule|Super\s+Admin|Type\s+d\'Utilisateur)\b/',
        // Phrases complètes
        '/Aucun\s+\w+\s+(trouvé|disponible)/',
        '/Commencez\s+par\s+créer/',
        '/Contactez\s+un\s+Super\s+Admin/',
        '/Êtes-vous\s+sûr/',
        '/Articles?\s+(de\s+la\s+catégorie|avec\s+le\s+tag)/',
        '/\.{3}$/', // Points de suspension
    ];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'twig') {
            $filepath = $file->getPathname();
            $content = file_get_contents($filepath);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNum => $line) {
                // Skip lines with trans() calls
                if (strpos($line, '|trans') !== false || strpos($line, 'trans(') !== false) {
                    continue;
                }
                
                // Skip comments and Twig syntax
                if (preg_match('/^\s*{#|^\s*{%\s*(if|for|set|endfor|endif|else|elseif)/', $line)) {
                    continue;
                }
                
                foreach ($frenchPatterns as $pattern) {
                    if (preg_match($pattern, $line, $matches)) {
                        $results[] = [
                            'file' => str_replace(__DIR__ . '/', '', $filepath),
                            'line' => $lineNum + 1,
                            'text' => trim($line),
                            'match' => $matches[0]
                        ];
                    }
                }
                
                // Search for quoted French text
                if (preg_match_all('/"([^"]*[àâäéèêëïîôöùûüÿçÀÂÄÉÈÊËÏÎÔÖÙÛÜŸÇ][^"]*)"/', $line, $matches)) {
                    foreach ($matches[1] as $match) {
                        if (strlen($match) > 2 && !preg_match('/^[a-z_.-]+$/', $match)) {
                            $results[] = [
                                'file' => str_replace(__DIR__ . '/', '', $filepath),
                                'line' => $lineNum + 1,
                                'text' => trim($line),
                                'match' => '"' . $match . '"'
                            ];
                        }
                    }
                }
                
                // Search for single-quoted French text
                if (preg_match_all("/'([^']*[àâäéèêëïîôöùûüÿçÀÂÄÉÈÊËÏÎÔÖÙÛÜŸÇ][^']*)'/", $line, $matches)) {
                    foreach ($matches[1] as $match) {
                        if (strlen($match) > 2 && !preg_match('/^[a-z_.-]+$/', $match)) {
                            $results[] = [
                                'file' => str_replace(__DIR__ . '/', '', $filepath),
                                'line' => $lineNum + 1,
                                'text' => trim($line),
                                'match' => "'" . $match . "'"
                            ];
                        }
                    }
                }
            }
        }
    }
    
    return $results;
}

$templatesDir = __DIR__ . '/templates';
$results = findHardcodedText($templatesDir);

echo "HARDCODED FRENCH TEXT DETECTION REPORT\n";
echo "=====================================\n\n";

if (empty($results)) {
    echo "✅ No hardcoded French text found!\n";
} else {
    echo "❌ Found " . count($results) . " instances of hardcoded French text:\n\n";
    
    $groupedResults = [];
    foreach ($results as $result) {
        $groupedResults[$result['file']][] = $result;
    }
    
    foreach ($groupedResults as $file => $fileResults) {
        echo "📁 " . $file . "\n";
        echo str_repeat("-", strlen($file) + 4) . "\n";
        
        foreach ($fileResults as $result) {
            echo sprintf("  Line %d: %s\n", $result['line'], $result['match']);
            echo sprintf("    Context: %s\n", mb_substr($result['text'], 0, 100));
            echo "\n";
        }
        echo "\n";
    }
}

echo "Total files scanned: " . count(glob($templatesDir . '/**/*.twig', GLOB_BRACE)) . "\n";