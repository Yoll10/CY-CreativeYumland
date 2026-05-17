<?php
require_once 'functions.php';
exiger_connexion();

$commande_id = '';
if (isset($_GET['commande'])) {
    $commande_id = $_GET['commande'];
}

$commande = null;
if ($commande_id !== '') {
    $commande = get_commande_by_id($commande_id);
}

$message = '';
$erreur = '';
if ($commande !== null && !est_admin()) {
    if ($commande['user_email'] !== $_SESSION['user']['email']) {
        header('Location: accueil.php');
        exit();
    }
}

if ($commande !== null && $commande['statut'] !== 'livree') {
    $erreur = "Vous ne pouvez noter qu'une commande livrée.";
}

$peut_noter = false;
if ($commande !== null && $erreur === '' && $commande['note_produits'] === null && $commande['user_email'] === $_SESSION['user']['email']) {
    $peut_noter = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $peut_noter) {
    $note_produits = intval($_POST['rating-produit']);
    $note_livraison = intval($_POST['rating-livraison']);
    $commentaire = trim($_POST['commentaire']);

    if ($note_produits < 1 || $note_produits > 5 || $note_livraison < 1 || $note_livraison > 5) {
        $erreur = "Veuillez attribuer une note entre 1 et 5 étoiles pour chaque critère.";
    } else {
        $ok = save_notation($commande_id, $note_produits, $note_livraison, $commentaire);
        if ($ok) {
            $message = "Merci pour votre avis ! Il a bien été enregistré.";
            $commande = get_commande_by_id($commande_id);
            $peut_noter = false;
        } else {
            $erreur = "Une erreur est survenue lors de l'enregistrement.";
        }
    }
}

$deja_note = false;
if ($commande !== null && $commande['note_produits'] !== null) {
    $deja_note = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Avis — L'Étoile</title>
    <link rel="stylesheet" href="stylecommon.css">
    <link rel="stylesheet" href="stylenotation.css">
</head>
<body>

<?php include 'header.php'; ?>
<script src="scriptjs.js"></script>
<main class="avis-page">
    <section class="avis-container">
        <div class="avis-header">
            <h1>Votre expérience</h1>
            <p>Nous espérons que votre dégustation a été à la hauteur de vos attentes.</p>
        </div>

        <?php if ($message !== '') { ?>
            <p class="msg-succes">✅ <?= h($message) ?></p>
        <?php } ?>

        <?php if ($erreur !== '') { ?>
            <p class="msg-erreur">❌ <?= h($erreur) ?></p>
        <?php } ?>

        <?php if ($commande === null) { ?>
            <p style="text-align:center;">
                Commande introuvable. <a href="profil.php">Retour au profil</a>
            </p>

        <?php } else if ($deja_note) { ?>
            <div style="text-align:center; padding: 2rem;">
                <h3>Avis pour la commande #<?= h($commande_id) ?></h3>
                <p>Qualité des produits : <strong><?= afficher_etoiles($commande['note_produits']) ?></strong></p>
                <p>Service de livraison : <strong><?= afficher_etoiles($commande['note_livraison']) ?></strong></p>
                <?php if ($commande['commentaire'] !== '') { ?>
                    <p><em>"<?= h($commande['commentaire']) ?>"</em></p>
                <?php } ?>
                <a href="profil.php" class="btn-submit" style="display:inline-block; margin-top:1rem;">
                    ← Retour au profil
                </a>
            </div>

        <?php } else if ($peut_noter) { ?>
        <div class="details-commande-notation">
            <p>Vous notez votre commande qui contenait :</p>
            <ul>
                <?php foreach ($commande['plats'] as $plat) : ?>
                    <li>
                        <?= $plat['quantite'] ?> × <?= h($plat['nom']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
            <form class="avis-form" method="POST" action="notation.php?commande=<?= h($commande_id) ?>">

                <p style="text-align:center; color:#666; margin-bottom:1rem;">
                    Commande #<?= h($commande_id) ?> du <?= h(date('d/m/Y', strtotime($commande['date']))) ?>
                </p>

                <div class="rating-group">
                    <h3>Qualité des produits</h3>
                    <div class="stars">
                        <input type="radio" name="rating-produit" id="p-5" value="5" required>
                        <label for="p-5">★</label>
                        <input type="radio" name="rating-produit" id="p-4" value="4">
                        <label for="p-4">★</label>
                        <input type="radio" name="rating-produit" id="p-3" value="3">
                        <label for="p-3">★</label>
                        <input type="radio" name="rating-produit" id="p-2" value="2">
                        <label for="p-2">★</label>
                        <input type="radio" name="rating-produit" id="p-1" value="1">
                        <label for="p-1">★</label>
                    </div>
                </div>

                <div class="rating-group">
                    <h3>Service de livraison</h3>
                    <div class="stars">
                        <input type="radio" name="rating-livraison" id="l-5" value="5" required>
                        <label for="l-5">★</label>
                        <input type="radio" name="rating-livraison" id="l-4" value="4">
                        <label for="l-4">★</label>
                        <input type="radio" name="rating-livraison" id="l-3" value="3">
                        <label for="l-3">★</label>
                        <input type="radio" name="rating-livraison" id="l-2" value="2">
                        <label for="l-2">★</label>
                        <input type="radio" name="rating-livraison" id="l-1" value="1">
                        <label for="l-1">★</label>
                    </div>
                </div>

                <div class="comment-group">
                    <h3>Commentaire (optionnel)</h3>
                    <textarea id="avis-commentaire" name="commentaire" maxlength="100"
                              data-max-length="100" data-counter="char-counter"
                              placeholder="Dites-nous en plus sur votre expérience..." rows="5"></textarea>
                    <p id="char-counter" style="text-align: right; font-size: 0.8rem; color: var(--gris-texte); margin-top: 5px;">
                        100 caractères restants
                    </p>
                </div>

                <button type="submit" class="btn-submit">Envoyer mon avis</button>
            </form>

        <?php } else { ?>
            <p style="text-align:center;">
                Cette commande n'est pas encore livrée.
                <a href="profil.php">Retour au profil</a>
            </p>
        <?php } ?>

    </section>
</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-column">
            <h3>Contact</h3>
            <p>06 49 00 96 90</p>
            <p>supportclient@gmail.com</p>
            <p>10 chemin de la Vie, Paris</p>
        </div>
        <div class="footer-column">
            <h3>Récompenses</h3>
            <p>3 étoiles Michelin</p>
            <p>Maitre Restaurateur</p>
        </div>
        <div class="footer-column">
            <h3>Horaires</h3>
            <p>Lundi – Vendredi : 10h – 19h</p>
            <p>Samedi & Dimanche : Fermé</p>
        </div>
    </div>
    <div class="footer-legal">
        <p>© 2026 — L'Étoile. Tous droits réservés.</p>
        <p>Mentions légales</p>
    </div>
</footer>

<script src="scriptjs.js"></script>
</body>
</html>
