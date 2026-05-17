<?php
require_once 'functions.php';

$erreur = "";

if (isset($_GET['bloque'])) {
    $erreur = "Votre compte a été bloqué. Vous avez été déconnecté. Contactez l'administrateur.";
} else if (isset($_GET['success'])) {
    $erreur = "Inscription réussie ! Connectez-vous.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mdp = $_POST['mdp'];

    $user = get_user_by_email($email);

    if ($user !== null && $mdp == $user['mdp']) {
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

<form action="connexion.php" method="post" class="forms" id="form-connexion">
    <fieldset>
        <legend>Vos identifiants</legend>

        <?php if ($erreur != "") { ?>
            <?php if (isset($_GET['success'])) { ?>
                <p style="color: #3a6b3a; font-weight: bold;"><?= h($erreur) ?></p>
            <?php } else if (isset($_GET['bloque'])) { ?>
                <p style="color: #b07b2c; font-weight: bold; background:#fff3cd; padding:10px; border-radius:6px;"><?= h($erreur) ?></p>
            <?php } else { ?>
                <p style="color: #a43a3a; font-weight: bold;"><?= h($erreur) ?></p>
            <?php } ?>
        <?php } ?>

        <p>
            <label for="conn-email">Adresse e-mail :</label>
            <input type="email" id="conn-email" name="email" required>
            <span class="msg-erreur-js" id="err-conn-email"></span>
        </p>

        <p>
            <label for="conn-mdp">Mot de passe :</label>
            <div class="input-wrapper">
                <input type="password" id="conn-mdp" name="mdp" required>
                <button type="button" class="toggle-password" data-target="conn-mdp"
                        aria-label="Afficher le mot de passe">👁️</button>
            </div>
            <span class="msg-erreur-js" id="err-conn-mdp"></span>
        </p>

        <p>
            <button class="buttonco" type="submit">Vous connecter</button>
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

<script src="scriptjs.js"></script>
</body>
</html>
