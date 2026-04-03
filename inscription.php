<?php
require_once 'functions.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['email']       ?? '');
    $mdp         = $_POST['mdp']              ?? '';
    $mdp_confirm = $_POST['mdp_confirm']      ?? '';
    $nom         = trim($_POST['nom']         ?? '');
    $prenom      = trim($_POST['prenom']      ?? '');
    $tel         = trim($_POST['telephone']   ?? '');
    $adresse     = trim($_POST['adresse']     ?? '');

    if ($mdp !== $mdp_confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mdp) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (email_exists($email)) {
        $message = "Cet email est déjà utilisé.";
    } else {
        $new_user = [
            "email"             => $email,
            "mdp"               => password_hash($mdp, PASSWORD_DEFAULT),
            "nom"               => $nom,
            "prenom"            => $prenom,
            "telephone"         => $tel,
            "adresse"           => $adresse,
            "role"              => "client",
            "statut"            => "actif",
            "date_inscription"  => date('Y-m-d')
        ];
        save_user($new_user);
        header('Location: connexion.php?success=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — L'Étoile</title>
    <link href="styleinscription.css" rel="stylesheet">
    <link rel="stylesheet" href="stylecommon.css">
</head>
<body>

<?php include 'header.php'; ?>

<form action="inscription.php" method="post" class="forms">
    <fieldset>
        <legend>Créer votre compte</legend>

        <?php if ($message): ?>
            <p style="color: #a43a3a; font-weight: bold;"><?= h($message) ?></p>
        <?php endif; ?>

        <p>
            <label for="email">Adresse e-mail :</label>
            <input type="email" name="email" placeholder="votre@email.com" required>
        </p>
        <p>
            <label for="nom">Nom :</label>
            <input type="text" name="nom" placeholder="Votre nom" required>
        </p>
        <p>
            <label for="prenom">Prénom :</label>
            <input type="text" name="prenom" placeholder="Votre prénom" required>
        </p>
        <p>
            <label for="adresse">Adresse de livraison :</label>
            <input type="text" name="adresse" placeholder="Ex : 12 rue de la Paix, 75001 Paris">
        </p>
        <p>
            <label for="telephone">Numéro de téléphone :</label>
            <input type="tel" name="telephone" placeholder="06 00 00 00 00">
        </p>
        <p>
            <label for="mdp">Mot de passe :</label>
            <input type="password" name="mdp" placeholder="Min. 6 caractères" required>
        </p>
        <p>
            <label for="mdp_confirm">Confirmer le mot de passe :</label>
            <input type="password" name="mdp_confirm" placeholder="Confirmer" required>
        </p>
        <p>
            <button type="submit">Créer mon compte</button>
        </p>
        <a href="connexion.php" class="bouton-lien">Déjà un compte ? Se connecter</a>
    </fieldset>
</form>

</body>
</html>
