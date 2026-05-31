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
    $mdp   = $_POST['mdp'];
    $ip    = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';

    // Vérifier si l'IP est bloquée
    if (est_ip_bloquee($ip)) {
        $minutes = ceil(temps_restant_blocage($ip) / 60);
        $erreur  = "Trop de tentatives échouées. Réessayez dans $minutes minute(s).";
    } else {
        $user = get_user_by_email($email);

        if ($user !== null && password_verify($mdp, $user['mdp'])) {
            if ($user['statut'] === 'bloque') {
                $erreur = "Votre compte a été bloqué. Contactez l'administrateur.";
                ajouter_log('connexion_compte_bloque', "Tentative de connexion sur le compte bloqué : $email");
            } else {
                // Connexion réussie : on remet les tentatives à zéro
                reinitialiser_tentatives($ip);
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
            // Mauvais identifiants
            enregistrer_tentative_echouee($ip, $email);
            ajouter_log('mauvais_mdp', "Échec de connexion pour l'email : $email");

            $nb_restantes = MAX_TENTATIVES - (get_tentatives()[$ip]['count'] ?? 0);
            if ($nb_restantes > 0) {
                $erreur = "Identifiants incorrects. ($nb_restantes tentative(s) restante(s) avant blocage)";
            } else {
                $erreur = "Trop de tentatives échouées. Réessayez dans 10 minute(s).";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — L'Étoile</title>
    <link href="css/styleinscription.css" rel="stylesheet">
    <link rel="stylesheet" href="css/stylecommon.css">

    <script src="js/scriptjs.js" defer></script>
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
            Livreur1 : livreur1@letoile.fr<br>
            Livreur2 : livreur2@letoile.fr
        </p>
    </fieldset>
</form>


</body>
</html>
