<?php
require_once 'functions.php';
exiger_connexion();

$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if ($id === '') {
    header('Location: profil.php');
    exit();
}

$commande = get_commande_by_id($id);

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

$tous_plats = get_plats();

$plats_commande = [];
$menus_commande = [];
foreach ($commande['plats'] as $p) {
    if (strpos($p['id'], 'menu_') === 0) {
        $menus_commande[$p['id']] = $p;
    } else {
        $plats_commande[$p['id']] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la commande #<?= h($id) ?> — L'Étoile</title>
    <link rel="stylesheet" href="css/stylecommon.css">
    <link rel="stylesheet" href="css/stylemodif.css">
    
    <script src="js/scriptjs.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<main class="modif-page">
    <h1>Modifier la commande #<?= h($id) ?></h1>
    <p class="modif-subtitle">
        Date : <?= h(date('d/m/Y H:i', strtotime($commande['date']))) ?> —
        Mode : <?= $commande['mode'] === 'livraison' ? '🚚 Livraison' : '🏠 À emporter' ?>
    </p>

    <div id="msg-modif-commande"></div>

    <form id="form-modif-commande">
        <input type="hidden" id="modif-commande-id" value="<?= h($id) ?>">

        <?php if (!empty($menus_commande)): ?>
        <h2>Menus dans la commande</h2>
        <p class="modif-info-menus">
            ℹ️ Les menus ne peuvent pas être modifiés individuellement. Ils sont comptabilisés dans le total.
        </p>
        <?php foreach ($menus_commande as $menu_item): ?>
            <div class="modif-plat-item modif-menu-item"
                 data-plat-id="<?= h($menu_item['id']) ?>"
                 data-prix="<?= h($menu_item['prix']) ?>">
                <div class="modif-plat-nom">
                    <?= h($menu_item['nom']) ?>
                    <small class="modif-menu-small">— menu</small>
                </div>
                <div class="modif-plat-prix"><?= number_format($menu_item['prix'], 2) ?> €</div>
                <div class="modif-controles">
                    <button type="button" class="btn-quantite-moins">−</button>
                    <input type="number" class="quantite-input"
                           value="<?= $menu_item['quantite'] ?>"
                           min="0" max="99">
                    <button type="button" class="btn-quantite-plus">+</button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

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
                    <small class="modif-plat-small">
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

        <div id="modif-ancien-total"
             data-total="<?= number_format($commande['total'], 2, '.', '') ?>"
             class="modif-hidden"></div>

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


</body>
</html>
