<?php
require_once 'functions.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est admin
if (!est_admin()) {
    echo json_encode(['succes' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if ($data === null || !isset($data['action']) || !isset($data['email'])) {
    echo json_encode(['succes' => false, 'message' => 'Données invalides.']);
    exit();
}

$action = $data['action'];
$email  = $data['email'];

// Impossible de se bloquer soi-même
if ($email === $_SESSION['user']['email']) {
    echo json_encode(['succes' => false, 'message' => 'Vous ne pouvez pas modifier votre propre statut.']);
    exit();
}

$user = get_user_by_email($email);
if ($user === null) {
    echo json_encode(['succes' => false, 'message' => 'Utilisateur introuvable.']);
    exit();
}

if ($action === 'bloquer') {
    $ok = update_user($email, ['statut' => 'bloque']);
    if ($ok) {
        // Détruire la session de l'utilisateur bloqué si possible
        // (en PHP natif sans session partagée, on marque le statut en JSON
        //  et on vérifie au prochain chargement de page côté client)
        echo json_encode(['succes' => true, 'message' => 'Utilisateur bloqué.']);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors du blocage.']);
    }

} else if ($action === 'debloquer') {
    $ok = update_user($email, ['statut' => 'actif']);
    if ($ok) {
        echo json_encode(['succes' => true, 'message' => 'Utilisateur débloqué.']);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors du déblocage.']);
    }

} else {
    echo json_encode(['succes' => false, 'message' => 'Action inconnue.']);
}
