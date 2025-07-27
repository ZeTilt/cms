<?php

// Script to find specifically French user-facing text (not CSS classes or technical strings)

function findFrenchText($dir) {
    $patterns = [
        // French text in HTML content (between tags)
        '/>\s*([^<{%]*(?:Gestion|Administration|Utilisateur|Créer|Modifier|Supprimer|Sauvegarder|Annuler|Retour|Suivant|Précédent|Rechercher|Filtrer|Total|Actif|Inactif|Aucun|Tous|Description|Date|Type|Statut|Configuration|Page|Article|Contenu|Titre|Nom|Email|Tableau de bord|Bienvenue|Liste|Voir|Détails|Ajouter|Nouveau|Éditer|Paramètres|Langue|Modules|Aide|Support|Documentation|Contact|Connexion|Déconnexion|Profil|Chargement|Données|Résultats|Erreur|Succès|Information|Confirmer|Fermer|Enregistrer|Valider|Ouvrir|Télécharger|Importer|Exporter|Archiver|Restaurer|Activer|Désactiver|Rafraîchir|Recharger|Enlever|Copier|Dupliquer|Déplacer|Partager|Publier|Brouillon|En attente|Approuvé|Rejeté|Archivé|Supprimé|Traitement|Terminé|Échoué|Avertissement|Champ requis|Format invalide|Fichier trop|Type de fichier|non autorisé|Requis|Optionnel|Choisir|fichier|sélectionné|Glisser|déposer|Parcourir|Effacer|sélection|Affichage|vers|entrées|Premier|Dernier|Aller|Aucune|données|tableau|Trier|croissant|décroissant|Créé|Mis|jour|Supprimé|Publié|Aujourd|hui|Hier|Demain|Maintenant|Jamais|Général|Avancé|Sécurité|Apparence|Rétablir|défaut|Actifs|Inactifs|Installer|Désinstaller|Configurer|Version|Auteur|Dépendances|FAQ|Raccourcis|clavier|Conseils|utilisateurs|certifications|statuts|niveaux|type|Sélectionner|prérequis|certification|Choisir|utilisateur|Supprimer|attribution|Rechercher|Notes|optionnelles|exemple|Technologie|Voyage|Style|vie|vous|sûr|vouloir|supprimer|Cette|action|peut|être|annulée|inscription|attribut|image|déjà|sélectionné|Approuver|Erreur|lors|suppression|ajout|Échec|inconnue|Configuration|Créé|jour|Filtre|détaillé|Montrant|élément|Texte|Zone|texte|Nombre|Booléen|Sélection|JSON|Non|défini|aperçu|rapide|activité|récente|actions|rapides|système|initialisé|aucun|module|actuellement|actif|description|gérer|créer|page|pages|article|articles|galerie|galeries|CMS|initialisé|succès|terminé|blog|galeries|inscriptions|services|événements|affaires|réservations|témoignages|navigation|contenu|modules|paramètres|déconnexion|mon|profil|traductions|voir|site|barre|latérale|basculer|réduire|développer|gestion|contenu|gestion|utilisateurs|gestion|affaires|système|ouvrir|barre|latérale|tête|recherche|notifications|aucune|notification|tout|fil|ariane|accueil|administration|actions|statut|messages|formulaires|pagination|tableau|date|heure|paramètres|modules|aide|modal|options|espaces|réservés|confirmation|erreurs|champs|filtres|types|valeurs)[^<{%]*)\s*</',
        
        // French text in Twig content blocks
        '/\{\%\s*block\s+[^%]*\%\}([^{%]*(?:Gestion|Administration|Utilisateur|Créer|Modifier|Supprimer|Sauvegarder|Annuler|Retour|Suivant|Précédent|Rechercher|Filtrer|Total|Actif|Inactif|Aucun|Tous|Description|Date|Type|Statut|Configuration|Page|Article|Contenu|Titre|Nom|Email|Tableau de bord|Bienvenue|Liste|Voir|Détails|Ajouter|Nouveau|Éditer|Paramètres|Langue|Modules|Aide|Support|Documentation|Contact|Connexion|Déconnexion|Profil|Chargement|Données|Résultats|Erreur|Succès|Information|Confirmer|Fermer|Enregistrer|Valider|Ouvrir|Télécharger|Importer|Exporter|Archiver|Restaurer|Activer|Désactiver|Rafraîchir|Recharger|Enlever|Copier|Dupliquer|Déplacer|Partager|Publier|Brouillon|En attente|Approuvé|Rejeté|Archivé|Supprimé|Traitement|Terminé|Échoué|Avertissement|Champ requis|Format invalide|Fichier trop|Type de fichier|non autorisé|Requis|Optionnel|Choisir|fichier|sélectionné|Glisser|déposer|Parcourir|Effacer|sélection|Affichage|vers|entrées|Premier|Dernier|Aller|Aucune|données|tableau|Trier|croissant|décroissant|Créé|Mis|jour|Supprimé|Publié|Aujourd|hui|Hier|Demain|Maintenant|Jamais|Général|Avancé|Sécurité|Apparence|Rétablir|défaut|Actifs|Inactifs|Installer|Désinstaller|Configurer|Version|Auteur|Dépendances|FAQ|Raccourcis|clavier|Conseils|utilisateurs|certifications|statuts|niveaux|type|Sélectionner|prérequis|certification|Choisir|utilisateur|Supprimer|attribution|Rechercher|Notes|optionnelles|exemple|Technologie|Voyage|Style|vie|vous|sûr|vouloir|supprimer|Cette|action|peut|être|annulée|inscription|attribut|image|déjà|sélectionné|Approuver|Erreur|lors|suppression|ajout|Échec|inconnue|Configuration|Créé|jour|Filtre|détaillé|Montrant|élément|Texte|Zone|texte|Nombre|Booléen|Sélection|JSON|Non|défini|aperçu|rapide|activité|récente|actions|rapides|système|initialisé|aucun|module|actuellement|actif|description|gérer|créer|page|pages|article|articles|galerie|galeries|CMS|initialisé|succès|terminé|blog|galeries|inscriptions|services|événements|affaires|réservations|témoignages|navigation|contenu|modules|paramètres|déconnexion|mon|profil|traductions|voir|site|barre|latérale|basculer|réduire|développer|gestion|contenu|gestion|utilisateurs|gestion|affaires|système|ouvrir|barre|latérale|tête|recherche|notifications|aucune|notification|tout|fil|ariane|accueil|administration|actions|statut|messages|formulaires|pagination|tableau|date|heure|paramètres|modules|aide|modal|options|espaces|réservés|confirmation|erreurs|champs|filtres|types|valeurs)[^{%]*)\s*\{\%/',
        
        // French strings in quotes (but exclude CSS classes and paths)
        '/["\']([^"\']*(?:Gestion des|Type d|Aucun|Aucune|Pas de|Il n|C est|Ce sont|Cette|Ceci|Cela|Voici|Voilà|Bonjour|Bonsoir|Merci|S il vous plaît|Désolé|Excusez-moi|Bienvenue|Au revoir|création|modification|suppression|sauvegarde|annulation|retour|suivant|précédent|recherche|filtrage|total|actif|inactif|aucun|tous|description|date|type|statut|configuration|page|article|contenu|titre|nom|email|tableau|bord|bienvenue|liste|voir|détails|ajouter|nouveau|édition|paramètres|langue|modules|aide|support|documentation|contact|connexion|déconnexion|profil|chargement|données|résultats|erreur|succès|information|confirmation|fermeture|enregistrement|validation|ouverture|téléchargement|importation|exportation|archivage|restauration|activation|désactivation|rafraîchissement|rechargement|suppression|copie|duplication|déplacement|partage|publication|brouillon|attente|approbation|rejet|archivage|suppression|traitement|terminaison|échec|avertissement|champ requis|format invalide|fichier volumineux|type fichier|autorisé|requis|optionnel|choix|fichier|sélection|glissement|dépôt|parcours|effacement|affichage|vers|entrées|premier|dernier|aller|aucune|données|tableau|tri|croissant|décroissant|création|mise|jour|suppression|publication|aujourd|demain|maintenant|jamais|général|avancé|sécurité|apparence|rétablissement|défaut|actifs|inactifs|installation|désinstallation|configuration|version|auteur|dépendances|questions|raccourcis|clavier|conseils|utilisateurs|certifications|statuts|niveaux|sélection|prérequis|certification|choix|utilisateur|suppression|attribution|recherche|notes|optionnelles|exemple|technologie|voyage|style|vie|sûreté|volonté|suppression|action|annulation|inscription|attribut|image|sélection|approbation|erreur|suppression|ajout|échec|inconnue|filtre|détaillé|montrant|élément|texte|zone|nombre|booléen|sélection|défini|aperçu|rapide|activité|récente|actions|rapides|système|initialisation|module|actuel|actif|description|gestion|création|pages|articles|galerie|galeries|initialisation|succès|terminaison|blogs|galeries|inscriptions|services|événements|affaires|réservations|témoignages|navigation|contenu|modules|paramètres|déconnexion|profil|traductions|site|barre|latérale|basculement|réduction|développement|gestion|contenu|gestion|utilisateurs|gestion|affaires|système|ouverture|barre|latérale|tête|recherche|notifications|notification|tout|fil|ariane|accueil|administration|actions|statut|messages|formulaires|pagination|tableau|date|heure|paramètres|modules|aide|modal|options|espaces|réservés|confirmation|erreurs|champs|filtres|types|valeurs)[^"\']*)["\']/',
        
        // Direct French words/phrases that shouldn't be there
        '/\b(de|du|des|le|la|les|un|une|et|ou|pour|par|avec|dans|sur|sous|entre|pendant|avant|après|chez|depuis|vers|jusqu|sans|selon|contre|malgré|grâce|être|avoir|faire|dire|aller|voir|savoir|pouvoir|vouloir|venir|prendre|donner)\b.*(?:utilisateur|admin|gestion|page|article)/i',
    ];
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $issues = [];
    $fileCount = 0;
    $totalIssues = 0;
    
    foreach ($files as $file) {
        if (!$file->isFile()) continue;
        
        $extension = $file->getExtension();
        if (!in_array($extension, ['php', 'twig'])) continue;
        
        // Skip vendor, var, tests and other directories
        $path = $file->getPathname();
        if (strpos($path, '/vendor/') !== false || 
            strpos($path, '/var/') !== false ||
            strpos($path, '/.git/') !== false ||
            strpos($path, '/node_modules/') !== false ||
            strpos($path, '/tests/') !== false ||
            basename($path) === 'detect_hardcoded.php' ||
            basename($path) === 'find_french_text.php' ||
            basename($path) === 'test_locale.php' ||
            basename($path) === 'fix_all_hardcoded.php' ||
            basename($path) === 'find_user_facing_hardcoded.php' ||
            basename($path) === 'detect_all_hardcoded.php') {
            continue;
        }
        
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $fileIssues = [];
        
        foreach ($lines as $lineNum => $line) {
            // Skip comment lines, translation calls, and CSS classes
            if (preg_match('/^\s*(?:\/\/|#|\*|\/\*)/', trim($line)) ||
                preg_match('/\|trans\(/', $line) ||
                preg_match('/->trans\(/', $line) ||
                preg_match('/__\(/', $line) ||
                preg_match('/class=/', $line) ||
                preg_match('/href=/', $line) ||
                preg_match('/id=/', $line) ||
                preg_match('/src=/', $line)) {
                continue;
            }
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    // Only include if it contains French words
                    $match = $matches[1] ?? $matches[0];
                    if (preg_match('/\b(Gestion|Administration|Utilisateur|Créer|Modifier|Supprimer|Sauvegarder|Annuler|Retour|Suivant|Précédent|Rechercher|Filtrer|Total|Actif|Inactif|Aucun|Tous|Description|Date|Type|Statut|Configuration|Page|Article|Contenu|Titre|Nom|Email|Tableau de bord|Bienvenue|Liste|Voir|Détails|Ajouter|Nouveau|Éditer|Paramètres|Langue|Modules|Aide|Support|Documentation|Contact|Connexion|Déconnexion|Profil|Chargement|Données|Résultats|Erreur|Succès|Information|Confirmer|Fermer|Enregistrer|Valider|Ouvrir|Télécharger|Importer|Exporter|Archiver|Restaurer|Activer|Désactiver|Rafraîchir|Recharger|Enlever|Copier|Dupliquer|Déplacer|Partager|Publier|Brouillon|En attente|Approuvé|Rejeté|Archivé|Supprimé|Traitement|Terminé|Échoué|Avertissement)\b/i', $match)) {
                        $fileIssues[] = [
                            'line' => $lineNum + 1,
                            'text' => trim($line),
                            'match' => $match
                        ];
                        $totalIssues++;
                        break; // Only count one issue per line
                    }
                }
            }
        }
        
        if (!empty($fileIssues)) {
            $issues[$path] = $fileIssues;
            $fileCount++;
        }
    }
    
    return [
        'issues' => $issues,
        'fileCount' => $fileCount,
        'totalIssues' => $totalIssues
    ];
}

echo "Searching for French user-facing text...\n";
echo "=======================================\n\n";

$result = findFrenchText(__DIR__);

echo "Summary:\n";
echo "Files with French text: {$result['fileCount']}\n";
echo "Total instances: {$result['totalIssues']}\n\n";

if ($result['fileCount'] > 0) {
    echo "Files with French user-facing text:\n";
    echo "===================================\n";
    
    foreach ($result['issues'] as $file => $issues) {
        $relativePath = str_replace(__DIR__ . '/', '', $file);
        echo "\n📁 $relativePath (" . count($issues) . " issues)\n";
        
        foreach ($issues as $issue) {
            echo "   Line {$issue['line']}: French text found\n";
            echo "   → " . trim(substr($issue['text'], 0, 100)) . (strlen($issue['text']) > 100 ? '...' : '') . "\n";
            echo "   🔍 Match: {$issue['match']}\n\n";
        }
    }
} else {
    echo "✅ No French user-facing text found!\n";
}

echo "\nDone.\n";