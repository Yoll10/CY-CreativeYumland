<?php
require_once 'functions.php';
exiger_connexion();

$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if ($id === '') {
    header('Location: profil.php');
    exit();
}

$commande = get_commande_by_id($id);

// Vérifications de sécurité
if ($commande === null) {
    header('Location: profil.php');
    exit();
}
if ($commande['user_email'] !== $_SESSION['user']['email']) {
    header('Location: profil.php');
    exit();
}
if ($commande['statut'] !== 'en_attente') {
    header('Location: profil.php');
    exit();
}

// Récupérer tous les plats pour permettre d'en ajouter
$tous_plats = get_plats();

// Construire un tableau id => quantite actuelle dans la commande
$plats_commande = [];
foreach ($commande['plats'] as $p) {
    $plats_commande[$p['id']] = $p;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la commande #<?= h($id) ?> — L'Étoile</title>
    <link rel="stylesheet" href="stylecommon.css">
    <link rel="stylesheet" href="stylemodif.css">
    
</head>
<body>

<?php include 'header.php'; ?>

<main class="modif-page">
    <h1>Modifier la commande #<?= h($id) ?></h1>
    <p style="text-align:center; color:var(--gris-texte);">
        Date : <?= h(date('d/m/Y H:i', strtotime($commande['date']))) ?> —
        Mode : <?= $commande['mode'] === 'livraison' ? '🚚 Livraison' : '🏠 À emporter' ?>
    </p>

    <div id="msg-modif-commande"></div>

    <form id="form-modif-commande">
        <input type="hidden" id="modif-commande-id" value="<?= h($id) ?>">

        <h2>Plats dans la commande</h2>

        <?php foreach ($tous_plats as $plat): ?>
            <?php
            $qte_actuelle = isset($plats_commande[$plat['id']]) ? $plats_commande[$plat['id']]['quantite'] : 0;
            ?>
            <div class="modif-plat-item"
                 data-plat-id="<?= h($plat['id']) ?>"
                 data-prix="<?= h($plat['prix']) ?>">

                <div class="modif-plat-nom">
                    <?= h($plat['nom']) ?>
                    <small style="font-weight:normal; color:var(--gris-texte);">
                        — <?= h($plat['categorie']) ?>
                    </small>
                </div>
                <div class="modif-plat-prix"><?= number_format($plat['prix'], 2) ?> €</div>
                <div class="modif-controles">
                    <button type="button" class="btn-quantite-moins">−</button>
                    <input type="number" class="quantite-input"
                           value="<?= $qte_actuelle ?>"
                           min="0" max="99">
                    <button type="button" class="btn-quantite-plus">+</button>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="modif-total-bloc">
            <span>Total :</span>
            <span id="modif-total"
                  data-total="<?= number_format($commande['total'], 2, '.', '') ?>">
                <?= number_format($commande['total'], 2) ?> €
            </span>
        </div>

        <!-- Total de référence (original) pour calculer la différence -->
        <div id="modif-ancien-total"
             data-total="<?= number_format($commande['total'], 2, '.', '') ?>"
             style="display:none;"></div>

        <div id="modif-diff" class="modif-diff neutre">Aucun changement de montant.</div>

        <button type="submit" class="btn-valider-modif">✅ Valider les modifications</button>
    </form>

    <a href="profil.php" class="btn-annuler">← Annuler et retourner au profil</a>
</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-column"><h3>Contact</h3><p>06 49 00 96 90</p><p>supportclient@gmail.com</p><p>10 chemin de la Vie, Paris</p></div>
        <div class="footer-column"><h3>Récompenses</h3><p>3 étoiles Michelin</p><p>Maître Restaurateur</p></div>
        <div class="footer-column"><h3>Horaires</h3><p>Lundi – Vendredi : 10h – 19h</p><p>Samedi & Dimanche : Fermé</p></div>
    </div>
    <div class="footer-legal"><p>© 2026 — L'Étoile. Tous droits réservés.</p><p>Mentions légales</p></div>
</footer>

<script src="scriptjs.js"></script>
</body>
</html>
