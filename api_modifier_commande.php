<?php
require_once 'functions.php';
require_once 'getapikey.php';

define('CYBANK_VENDEUR_MC', 'MI-4_E');
define('CYBANK_URL_MC', 'https://www.plateforme-smc.fr/cybank/index.php');

header('Content-Type: application/json');
exiger_connexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if ($data === null || !isset($data['commande_id']) || !isset($data['plats'])) {
    echo json_encode(['succes' => false, 'message' => 'Données invalides.']);
    exit();
}

$commande_id = trim($data['commande_id']);
$plats_new   = $data['plats']; // [{ id, quantite }, ...]

$commande = get_commande_by_id($commande_id);

if ($commande === null) {
    echo json_encode(['succes' => false, 'message' => 'Commande introuvable.']);
    exit();
}

// Sécurité : seul le propriétaire peut modifier
if ($commande['user_email'] !== $_SESSION['user']['email']) {
    echo json_encode(['succes' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

// Seules les commandes en_attente sont modifiables
if ($commande['statut'] !== 'en_attente') {
    echo json_encode(['succes' => false, 'message' => 'Cette commande ne peut plus être modifiée.']);
    exit();
}

// Construire la nouvelle liste de plats avec nom et prix à jour
$plats_final = [];
$nouveau_total = 0;
foreach ($plats_new as $item) {
    $q = intval($item['quantite']);
    if ($q <= 0) continue;

    // Gérer les menus (id commence par "menu_")
    if (strpos($item['id'], 'menu_') === 0) {
        $menu_id = substr($item['id'], 5); // retirer "menu_"
        $menu = get_menu_by_id($menu_id);
        if ($menu === null) continue;
        $plats_final[] = [
            'id'       => $item['id'],
            'nom'      => $menu['nom'] . ' (menu)',
            'prix'     => $menu['prix'],
            'quantite' => $q
        ];
        $nouveau_total += $menu['prix'] * $q;
    } else {
        $plat = get_plat_by_id($item['id']);
        if ($plat === null) continue;
        $plats_final[] = [
            'id'       => $plat['id'],
            'nom'      => $plat['nom'],
            'prix'     => $plat['prix'],
            'quantite' => $q
        ];
        $nouveau_total += $plat['prix'] * $q;
    }
}

if (empty($plats_final)) {
    echo json_encode(['succes' => false, 'message' => 'La commande ne peut pas être vide.']);
    exit();
}

$nouveau_total = round($nouveau_total, 2);
$ancien_total  = round($commande['total'], 2);
$difference    = round($nouveau_total - $ancien_total, 2);

// Sauvegarder les modifications
$ok = update_plats_commande($commande_id, $plats_final, $nouveau_total);

if (!$ok) {
    echo json_encode(['succes' => false, 'message' => 'Erreur lors de la sauvegarde.']);
    exit();
}

// Si le nouveau total est plus élevé → paiement complémentaire CYBank
if ($difference > 0.01) {
    $transaction_cybank = 'TXN' . date('YmdHis') . rand(100, 999);
    $montant_cybank = number_format($difference, 2, '.', '');

    $protocole       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host            = $_SERVER['HTTP_HOST'];
    $base_path       = rtrim(dirname(strtok($_SERVER['REQUEST_URI'], '?')), '/');
    $retour_url      = $protocole . '://' . $host . $base_path . '/commande-template.php?cmd_id=' . urlencode($commande_id);

    $api_key = getAPIKey(CYBANK_VENDEUR_MC);
    $control = md5($api_key
                 . "#" . $transaction_cybank
                 . "#" . $montant_cybank
                 . "#" . CYBANK_VENDEUR_MC
                 . "#" . $retour_url . "#");

    $_SESSION['cybank_pending'] = [
        'transaction' => $transaction_cybank,
        'montant'     => $montant_cybank,
        'vendeur'     => CYBANK_VENDEUR_MC,
        'retour'      => $retour_url,
        'control'     => $control,
    ];

    echo json_encode([
        'succes'    => true,
        'paiement'  => true,
        'message'   => 'Commande modifiée. Un supplément de ' . number_format($difference, 2) . ' € est à régler.',
        'redirect'  => 'commande-template.php?payer=1'
    ]);
} else {
    // Pas de supplément à payer
    echo json_encode([
        'succes'   => true,
        'paiement' => false,
        'message'  => 'Commande modifiée avec succès.',
        'redirect' => 'profil.php'
    ]);
}
