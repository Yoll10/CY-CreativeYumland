<?php
require_once 'functions.php';
exiger_connexion();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if ($data === null || !isset($data['champ']) || !isset($data['valeur'])) {
    echo json_encode(['succes' => false, 'message' => 'Données invalides.']);
    exit();
}

$champ  = trim($data['champ']);
$valeur = trim($data['valeur']);
$email  = $_SESSION['user']['email'];

$champs_autorises = ['nom', 'prenom', 'adresse', 'telephone', 'email'];

if (!in_array($champ, $champs_autorises)) {
    echo json_encode(['succes' => false, 'message' => 'Champ non modifiable.']);
    exit();
}

// 
if ($champ === 'nom' && strlen($valeur) < 2) {
    echo json_encode(['succes' => false, 'message' => 'Le nom doit contenir au moins 2 caractères.']);
    exit();
}

if ($champ === 'prenom' && strlen($valeur) < 2) {
    echo json_encode(['succes' => false, 'message' => 'Le prénom doit contenir au moins 2 caractères.']);
    exit();
}

if ($champ === 'email') {
    if (!filter_var($valeur, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['succes' => false, 'message' => 'Adresse e-mail invalide.']);
        exit();
    }
    if ($valeur !== $email && email_exists($valeur)) {
        echo json_encode(['succes' => false, 'message' => 'Cette adresse e-mail est déjà utilisée.']);
        exit();
    }
}

if ($champ === 'telephone' && $valeur !== '') {
    $tel_sans_espaces = preg_replace('/\s/', '', $valeur);
    if (!preg_match('/^(\+33|0)[0-9]{9}$/', $tel_sans_espaces)) {
        echo json_encode(['succes' => false, 'message' => 'Numéro de téléphone invalide.']);
        exit();
    }
}

$ok = update_user($email, [$champ => $valeur]);

if ($ok) {
    $_SESSION['user'] = get_user_by_email(
        $champ === 'email' ? $valeur : $email
    );
    echo json_encode(['succes' => true, 'message' => 'Modification enregistrée avec succès.']);
} else {
    echo json_encode(['succes' => false, 'message' => 'Erreur lors de la sauvegarde. Réessayez.']);
}
