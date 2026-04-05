<?php
require_once 'functions.php';

$erreur = "";

if (isset($_GET['success'])) {
    $erreur = "Inscription réussie ! Connectez-vous.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mdp = $_POST['mdp'];

    $user = get_user_by_email($email);

    if ($user !== null && password_verify($mdp, $user['mdp'])) {
        if ($user['statut'] === 'bloque') {
            $erreur = "Votre compte a été bloqué. Contactez l'administrateur.";
        } else {
            $_SESSION['user'] = $user;

            if ($user['role'] === 'admin') {
                header('Location: admin.php');
                exit();
            } else if ($user['role'] === 'restaurateur') {
                header('Location: commandes-cuisine.php');
                exit();
            } else if ($user['role'] === 'livreur') {
                header('Location: livraison.php');
                exit();
            } else {
                header('Location: accueil.php');
                exit();
            }
        }
    } else {
        $erreur = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — L'Étoile</title>
    <link href="styleinscription.css" rel="stylesheet">
    <link rel="stylesheet" href="stylecommon.css">
</head>
<body>

<?php include 'header.php'; ?>

<form action="connexion.php" method="post" class="forms">
    <fieldset>
        <legend>Vos identifiants</legend>

        <?php if ($erreur != "") { ?>
            <?php if (isset($_GET['success'])) { ?>
                <p style="color: #3a6b3a; font-weight: bold;"><?= h($erreur) ?></p>
            <?php } else { ?>
                <p style="color: #a43a3a; font-weight: bold;"><?= h($erreur) ?></p>
            <?php } ?>
        <?php } ?>

        <p>
            <label for="email">Adresse e-mail :</label>
            <input type="email" id="email" name="email" required>
        </p>
        <p>
            <label for="mdp">Mot de passe :</label>
            <input type="password" id="mdp" name="mdp" required>
        </p>
        <p>
            <button type="submit">Vous connecter</button>
        </p>
        <a href="inscription.php" class="bouton-lien">Pas de compte ? Inscrivez-vous !</a>

        <p style="font-size:0.8rem; color:#888; margin-top:1rem;">
            <strong>Comptes de test</strong> (mdp : <em>password</em>) :<br>
            Client : jean.dupont@mail.com<br>
            Admin : admin@letoile.fr<br>
            Chef : chef@letoile.fr<br>
            Livreur : livreur@letoile.fr
        </p>
    </fieldset>
</form>

</body>
</html>
