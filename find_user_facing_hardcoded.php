<?php

echo "DETECTION DE TEXTE HARDCODE VISIBLE UTILISATEUR\n";
echo "================================================\n\n";

$totalIssues = 0;

// Parcourir tous les fichiers Twig
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/templates'),
    RecursiveIteratorIterator::LEAVES_ONLY
);

// Patterns spécifiques pour le texte visible dans Twig
$userFacingPatterns = [
    // Texte français dans du HTML visible
    '/>\s*([^<{%{#]*(?:bonjour|salut|merci|bienvenue|au revoir|oui|non|peut-être|toujours|jamais|maintenant|avant|après|pendant|déjà|encore|aussi|très|plus|moins|beaucoup|peu|tout|rien|quelque|chaque|plusieurs|être|avoir|faire|dire|aller|voir|savoir|pouvoir|vouloir|venir|prendre|donner|et|ou|mais|donc|car|si|que|qui|quoi|comment|pourquoi|quand|où|combien|le|la|les|un|une|des|du|de|en|dans|pour|avec|sans|sur|sous|par|cette|ce|ces|cet|mon|ma|mes|ton|ta|tes|son|sa|ses|notre|nos|votre|vos|leur|leurs)[^<{%{#]*)\s*</i',
    
    // Texte anglais dans du HTML visible
    '/>\s*([^<{%{#]*(?:hello|hi|goodbye|bye|thank|thanks|welcome|yes|no|maybe|always|never|now|before|after|during|already|still|again|also|very|more|less|much|little|all|some|any|each|every|other|same|different|be|have|do|say|go|see|know|can|will|would|could|should|may|might|must|get|make|take|come|give|tell|work|call|try|ask|need|feel|become|leave|put|mean|keep|let|begin|seem|help|talk|turn|start|show|hear|play|run|move|live|believe|hold|bring|happen|write|provide|the|a|an|and|or|but|so|because|if|when|where|how|why|what|who|which|that|this|these|those|my|your|his|her|its|our|their|me|you|him|her|it|us|them|I|we|they|he|she)[^<{%{#]*)\s*</i',
    
    // Texte dans des attributs title, placeholder, alt, value
    '/(?:title|placeholder|alt|value)\s*=\s*[\'"]([^\'\"]*(?:bonjour|salut|merci|bienvenue|au revoir|oui|non|peut-être|hello|hi|goodbye|bye|thank|thanks|welcome|yes|no|maybe|learn more|read more|click here|get started|sign in|log in|log out|create|edit|delete|save|cancel|submit|back|next|previous|home|about|contact|search|filter|all|none|more|less)[^\'\"]*)[\'\"]/i',
    
    // Texte dans des éléments de titre
    '/<h[1-6][^>]*>\s*([^<{%{#]*(?:bonjour|salut|merci|bienvenue|au revoir|hello|hi|goodbye|bye|thank|thanks|welcome|learn more|read more|click here|get started|sign in|log in|log out|create|edit|delete|save|cancel|submit|back|next|previous|home|about|contact|search|filter|all|none|more|less)[^<{%{#]*)\s*<\/h[1-6]>/i',
    
    // Texte dans des liens et boutons
    '/<(?:a|button)[^>]*>\s*([^<{%{#]*(?:bonjour|salut|merci|bienvenue|au revoir|hello|hi|goodbye|bye|thank|thanks|welcome|learn more|read more|click here|get started|sign in|log in|log out|create|edit|delete|save|cancel|submit|back|next|previous|home|about|contact|search|filter|all|none|more|less)[^<{%{#]*)\s*<\/(?:a|button)>/i',
    
    // Texte dans des labels et spans
    '/<(?:label|span|p|div)[^>]*>\s*([^<{%{#]*(?:bonjour|salut|merci|bienvenue|au revoir|hello|hi|goodbye|bye|thank|thanks|welcome|learn more|read more|click here|get started|sign in|log in|log out|create|edit|delete|save|cancel|submit|back|next|previous|home|about|contact|search|filter|all|none|more|less)[^<{%{#]*)\s*<\/(?:label|span|p|div)>/i',
    
    // Messages d'erreur et alertes
    '/(?:alert|confirm|prompt)\s*\(\s*[\'"]([^\'\"]*(?:bonjour|salut|merci|bienvenue|au revoir|hello|hi|goodbye|bye|thank|thanks|welcome|error|success|warning|info|delete|remove|save|cancel|confirm|are you sure)[^\'\"]*)[\'\"]/i',
    
    // Phrases complètes en français
    '/[>\s]([A-ZÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞSS][a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþßÿ\s]{10,}[.!?])\s*[<{]/i',
    
    // Phrases complètes en anglais
    '/[>\s]([A-Z][a-z\s]{10,}[.!?])\s*[<{]/i'
];

// Patterns à ignorer (code, variables, etc.)
$ignorePatterns = [
    // Variables Twig/PHP
    '/\{\{\s*[^}]+\s*\}\}/',
    '/\{\%\s*[^%]+\s*\%\}/',
    '/\{\#\s*[^#]+\s*\#\}/',
    // Chemins et URLs
    '/https?:\/\/[^\s\'"]+|path\([^)]+\)|url\([^)]+\)/',
    // Classes CSS et IDs
    '/class\s*=\s*[\'"][^\'"]*[\'"]|id\s*=\s*[\'"][^\'"]*[\'"]/',
    // Attributs techniques
    '/(?:method|type|name|data-[^=]+)\s*=\s*[\'"][^\'"]*[\'"]/',
    // Code JavaScript
    '/<script[^>]*>[\s\S]*?<\/script>/',
    // Commentaires
    '/<!--[\s\S]*?-->|{\#[\s\S]*?\#}/',
    // Extensions de fichiers
    '/\.(css|js|php|twig|html|xml|json|yaml|yml|md|txt|log|pdf|jpg|jpeg|png|gif|svg|webp|mp4|avi|mov|pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar|tar|gz)/',
    // Filtres Twig
    '/\|\s*(?:trans|date|format|slice|length|upper|lower|title|capitalize|trim|raw|escape|nl2br|striptags|markdown|default)/',
    // Fonctions techniques
    '/(?:function|var|const|let|class|id|name|type|method)\s/',
];

function shouldIgnoreContext($line, $ignorePatterns) {
    foreach ($ignorePatterns as $pattern) {
        if (preg_match($pattern, $line)) {
            return true;
        }
    }
    return false;
}

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'twig') {
        $filepath = $file->getPathname();
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);
        $issues = [];
        
        foreach ($lines as $lineNumber => $line) {
            $lineNumber++; // 1-indexed
            
            // Ignorer certains contextes
            if (shouldIgnoreContext($line, $ignorePatterns)) {
                continue;
            }
            
            // Vérifier chaque pattern
            foreach ($userFacingPatterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $issues[] = [
                        'line' => $lineNumber,
                        'match' => isset($matches[1]) ? trim($matches[1]) : trim($matches[0]),
                        'context' => trim($line)
                    ];
                    break; // Un seul problème par ligne
                }
            }
        }
        
        if (!empty($issues)) {
            $totalIssues += count($issues);
            $relativePath = str_replace(__DIR__ . '/', '', $filepath);
            echo "📁 {$relativePath}\n";
            echo str_repeat('-', strlen($relativePath) + 2) . "\n";
            
            foreach ($issues as $issue) {
                echo "  Ligne {$issue['line']}: \"{$issue['match']}\"\n";
                echo "    Contexte: {$issue['context']}\n\n";
            }
        }
    }
}

echo "\n========================================\n";
echo "TOTAL: {$totalIssues} instances de texte hardcodé visible utilisateur détectées\n";
echo "========================================\n";