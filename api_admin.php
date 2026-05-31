<?php
require_once 'functions.php';

header('Content-Type: application/json');

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
        ajouter_log('compte_bloque', "Compte bloqué par l'admin " . $_SESSION['user']['email'] . " : $email");
        echo json_encode(['succes' => true, 'message' => 'Utilisateur bloqué.']);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors du blocage.']);
    }

} else if ($action === 'debloquer') {
    $ok = update_user($email, ['statut' => 'actif']);
    if ($ok) {
        ajouter_log('compte_debloque', "Compte débloqué par l'admin " . $_SESSION['user']['email'] . " : $email");
        echo json_encode(['succes' => true, 'message' => 'Utilisateur débloqué.']);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors du déblocage.']);
    }

} else {
    echo json_encode(['succes' => false, 'message' => 'Action inconnue.']);
}
