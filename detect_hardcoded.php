<?php

// Script to detect hardcoded French text in PHP and Twig files

function detectHardcodedText($dir) {
    $patterns = [
        // French user-facing text patterns
        '/["\'](?:.*(?:Utilisateur|Gestion|Administration|Créer|Modifier|Supprimer|Sauvegarder|Annuler|Retour|Suivant|Précédent|Rechercher|Filtrer|Total|Actif|Inactif|Aucun|Tous|Description|Date|Type|Statut|Configuration|Page|Article|Contenu|Titre|Nom|Email|Mot de passe|Connexion|Déconnexion|Profil|Paramètres|Langue|Fuseau horaire|Aide|Documentation|Support|Contact|Enregistrer|Valider|Confirmer|Fermer|Ouvrir|Télécharger|Importer|Exporter|Archiver|Restaurer|Activer|Désactiver|Rafraîchir|Recharger|Nouveau|Éditer|Voir|Détails|Ajouter|Enlever|Copier|Dupliquer|Déplacer|Partager|Publier|Brouillon|En attente|Approuvé|Rejeté|Archivé|Supprimé|Traitement|Terminé|Échoué|Succès|Erreur|Avertissement|Information|Chargement|Données|Résultats|Confirmer la suppression|Modifications non sauvegardées|Opération réussie|Opération échouée|Accès refusé|Élément introuvable|Données invalides|Champ requis|Format invalide|Fichier trop volumineux|Type de fichier non autorisé|Requis|Optionnel|Choisir un fichier|Aucun fichier sélectionné|Glisser-déposer|ou|Parcourir|Effacer la sélection|Affichage|vers|de|entrées|Premier|Dernier|Précédent|Suivant|Page|Par page|Aller à la page|Aucune donnée|Trier croissant|Trier décroissant|Rechercher dans le tableau|Afficher|entrées|Créé le|Mis à jour le|Supprimé le|Publié le|Date|Heure|Aujourd hui|Hier|Demain|Maintenant|Jamais|Général|Avancé|Sécurité|Apparence|Enregistrer les paramètres|Rétablir par défaut|Actifs|Inactifs|Installer|Désinstaller|Configurer|Version|Auteur|Dépendances|FAQ|Raccourcis clavier|Conseils|Fermer|Confirmer|Annuler|Tous|Tous les utilisateurs|Toutes les certifications|Tous les statuts|Tous les niveaux|Aucun type par défaut|Sélectionner un prérequis|Sélectionner une certification|Sélectionner le type|Choisir un utilisateur|Supprimer l attribution de type|Rechercher les certifications|Notes optionnelles|par exemple|Technologie|Voyage|Style de vie|Êtes-vous sûr de vouloir supprimer|Cette action ne peut pas être annulée|Supprimer cette inscription|Supprimer cet attribut|Supprimer cette image|Ce prérequis est déjà sélectionné|Approuver cet utilisateur|Erreur lors de la suppression|Erreur lors de l ajout|Échec de la suppression|Erreur inconnue|Total|Configuration|Actif|Créé|Mis à jour|Filtre|Affichage détaillé|Montrant|à|de|élément|Texte|Zone de texte|Nombre|Booléen|Sélection|JSON|Oui|Non|Non défini|tableau de bord|bienvenue|aperçu rapide|activité récente|modules actifs|actions rapides|système initialisé|aucun module actuellement actif|aucune description|gérer|créer une page|gérer les pages|créer un article|gérer les articles|créer une galerie|gérer les galeries|le système ZeTilt CMS a été initialisé avec succès|terminé|blog|galeries|utilisateurs|inscriptions|services|événements|affaires|réservations|témoignages|navigation|tableau de bord|utilisateurs|contenu|modules|paramètres|déconnexion|mon profil|traductions|voir le site|barre latérale|basculer|réduire|développer|gestion de contenu|gestion des utilisateurs|gestion des affaires|système|ouvrir la barre latérale|en-tête|recherche|notifications|aucune notification|voir tout|fil d ariane|accueil|administration|actions|statut|messages|formulaires|pagination|tableau|date et heure|paramètres|modules|aide|modal|options|espaces réservés|confirmation|erreurs|champs|filtres|types|valeurs).*)["\']/',
        
        // Common French text patterns
        '/["\'][^"\']*(?:de|du|des|le|la|les|un|une|et|ou|pour|par|avec|dans|sur|sous|entre|pendant|avant|après|chez|depuis|vers|jusqu|sans|selon|contre|malgré|grâce)[^"\']*["\']/',
        
        // French month names
        '/["\'](?:janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)["\']/',
        
        // French day names  
        '/["\'](?:lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche)["\']/',
        
        // Direct French phrases
        '/["\'](?:Gestion des|Type d|Aucun|Aucune|Pas de|Il n|C est|Ce sont|Cette|Cette|Ceci|Cela|Voici|Voilà|Bonjour|Bonsoir|Merci|S il vous plaît|Désolé|Excusez-moi|Bienvenue|Au revoir)[^"\']*["\']/',
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
        
        // Skip vendor, var, and other directories
        $path = $file->getPathname();
        if (strpos($path, '/vendor/') !== false || 
            strpos($path, '/var/') !== false ||
            strpos($path, '/.git/') !== false ||
            strpos($path, '/node_modules/') !== false) {
            continue;
        }
        
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $fileIssues = [];
        
        foreach ($lines as $lineNum => $line) {
            // Skip comment lines and translation calls
            if (preg_match('/^\s*(?:\/\/|#|\*|\/\*)/', trim($line)) ||
                preg_match('/\|trans\(/', $line) ||
                preg_match('/->trans\(/', $line) ||
                preg_match('/__\(/', $line)) {
                continue;
            }
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $fileIssues[] = [
                        'line' => $lineNum + 1,
                        'text' => trim($line),
                        'match' => $matches[0] ?? ''
                    ];
                    $totalIssues++;
                    break; // Only count one issue per line
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

echo "Scanning for hardcoded French text...\n";
echo "=====================================\n\n";

$result = detectHardcodedText(__DIR__);

echo "Summary:\n";
echo "Files with issues: {$result['fileCount']}\n";
echo "Total instances: {$result['totalIssues']}\n\n";

if ($result['fileCount'] > 0) {
    echo "Files with hardcoded text:\n";
    echo "==========================\n";
    
    foreach ($result['issues'] as $file => $issues) {
        $relativePath = str_replace(__DIR__ . '/', '', $file);
        echo "\n📁 $relativePath (" . count($issues) . " issues)\n";
        
        foreach (array_slice($issues, 0, 3) as $issue) { // Show first 3 issues per file
            echo "   Line {$issue['line']}: {$issue['match']}\n";
            echo "   → " . trim(substr($issue['text'], 0, 80)) . (strlen($issue['text']) > 80 ? '...' : '') . "\n";
        }
        
        if (count($issues) > 3) {
            echo "   ... (" . (count($issues) - 3) . " more issues)\n";
        }
    }
} else {
    echo "✅ No hardcoded French text found!\n";
}

echo "\nDone.\n";