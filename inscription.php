<?php
require_once 'functions.php';

$email = $nom = $prenom = $tel = $adresse = "";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mdp = $_POST['mdp'];
    $mdp_confirm = $_POST['mdp_confirm'];
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $tel = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);

    if ($mdp !== $mdp_confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } else if (strlen($mdp) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
    } else if (email_exists($email)) {
        $message = "Cet email est déjà utilisé.";
    } else {
        $new_user = array(
            "email"            => $email,
            "mdp"              => password_hash($mdp, PASSWORD_DEFAULT),
            "nom"              => $nom,
            "prenom"           => $prenom,
            "telephone"        => $tel,
            "adresse"          => $adresse,
            "role"             => "client",
            "statut"           => "actif",
            "date_inscription" => date('Y-m-d')
        );
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
    <link href="css/styleinscription.css" rel="stylesheet">
    <link rel="stylesheet" href="css/stylecommon.css">
    <script src="js/scriptjs.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<form action="inscription.php" method="post" class="forms" id="form-inscription">
    <fieldset>
        <legend>Créer votre compte</legend>

        <?php if ($message != "") { ?>
            <p style="color: #a43a3a; font-weight: bold;"><?= h($message) ?></p>
        <?php } ?>

        <p>
            <label for="insc-nom">Nom :</label>
            <input type="text" id="insc-nom" name="nom"
                   placeholder="Votre nom" value="<?= h($nom) ?>" required>
            <span class="msg-erreur-js" id="err-insc-nom"></span>
        </p>

        <p>
            <label for="insc-prenom">Prénom :</label>
            <input type="text" id="insc-prenom" name="prenom"
                   placeholder="Votre prénom" value="<?= h($prenom) ?>" required>
            <span class="msg-erreur-js" id="err-insc-prenom"></span>
        </p>

        <p>
            <label for="insc-email">Adresse e-mail :</label>
            <input type="email" id="insc-email" name="email"
                   placeholder="votre@email.com" value="<?= h($email) ?>" required>
            <span class="msg-erreur-js" id="err-insc-email"></span>
        </p>

        <p>
            <label for="insc-adresse">Adresse de livraison :</label>
            <input type="text" id="insc-adresse" name="adresse"
                   placeholder="Ex : 12 rue de la Paix, 75001 Paris" value="<?= h($adresse) ?>">
            <span class="msg-erreur-js" id="err-insc-adresse"></span>
        </p>

        <p>
            <label for="insc-telephone">Numéro de téléphone :</label>
            <input type="tel" id="insc-telephone" name="telephone"
                   placeholder="06 00 00 00 00" value="<?= h($tel) ?>">
            <span class="msg-erreur-js" id="err-insc-telephone"></span>
        </p>

        <p>
            <label for="insc-mdp">Mot de passe :</label>
            <div class="input-wrapper">
                <input type="password" id="insc-mdp" name="mdp"
                       placeholder="Min. 6 caractères" required
                       maxlength="64"
                       data-max-length="64" data-counter="compteur-insc-mdp">
                <button type="button" class="toggle-password" data-target="insc-mdp"
                        aria-label="Afficher le mot de passe">👁️</button>
            </div>
            <span class="compteur-mdp" id="compteur-insc-mdp">64 caractères restants</span>
            <span class="msg-erreur-js" id="err-insc-mdp"></span>
        </p>

        <p>
            <label for="insc-mdp-confirm">Confirmer le mot de passe :</label>
            <div class="input-wrapper">
                <input type="password" id="insc-mdp-confirm" name="mdp_confirm"
                       placeholder="Confirmer" required>
                <button type="button" class="toggle-password" data-target="insc-mdp-confirm"
                        aria-label="Afficher le mot de passe">👁️</button>
            </div>
            <span class="msg-erreur-js" id="err-insc-mdp-confirm"></span>
        </p>

        <p>
            <button class="boutonsubmit" type="submit">Créer mon compte</button>
        </p>
    </fieldset>
</form>


</body>
</html>
