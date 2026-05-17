<?php
require_once 'functions.php';

header('Content-Type: application/json');

$categorie = isset($_GET['categorie']) ? trim($_GET['categorie']) : 'tous';
$allergene = isset($_GET['allergene']) ? trim($_GET['allergene']) : 'tous';

$plats = get_plats();

// Filtre par catégorie
if ($categorie !== '' && $categorie !== 'tous') {
    $plats = array_filter($plats, function($p) use ($categorie) {
        return $p['categorie'] === $categorie;
    });
    $plats = array_values($plats);
}

// Filtre par allergène (exclusion)
if ($allergene !== '' && $allergene !== 'tous') {
    $motsCles = [];
    switch ($allergene) {
        case 'sans-gluten': $motsCles = ['gluten']; break;
        case 'sans-lait':   $motsCles = ['lait'];   break;
        case 'sans-oeuf':   $motsCles = ['oeuf', 'oeufs', 'œuf', 'œufs']; break;
    }

    if (!empty($motsCles)) {
        $plats = array_filter($plats, function($p) use ($motsCles) {
            $allergenes_plat = strtolower($p['allergenes']);
            foreach ($motsCles as $mot) {
                if (strpos($allergenes_plat, $mot) !== false) {
                    return false; // Contient l'allergène → exclure
                }
            }
            return true;
        });
        $plats = array_values($plats);
    }
}

// Ajouter le nombre de commandes pour le tri par popularité
$commandes = get_commandes();
$compteur  = [];
foreach ($commandes as $cmd) {
    foreach ($cmd['plats'] as $cp) {
        $id = $cp['id'];
        $compteur[$id] = ($compteur[$id] ?? 0) + $cp['quantite'];
    }
}

foreach ($plats as &$p) {
    $p['nb_commandes'] = $compteur[$p['id']] ?? 0;
}
unset($p);

echo json_encode(['plats' => $plats], JSON_UNESCAPED_UNICODE);
