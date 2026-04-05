<?php
require_once 'functions.php';

$recherche = "";
if (isset($_GET['q'])) {
    $recherche = trim($_GET['q']);
}

$resultats_recherche = array();

if ($recherche !== '') {
    $tous_plats = get_plats();
    foreach ($tous_plats as $plat) {
        if (stripos($plat['nom'], $recherche) !== false || stripos($plat['description'], $recherche) !== false) {
            $resultats_recherche[] = $plat;
        }
    }
}

$plats_populaires = get_plats_populaires(3);

if (count($plats_populaires) < 3) {
    $tous = get_plats();
    foreach ($tous as $p) {
        if (count($plats_populaires) >= 3) {
            break;
        }
        $deja_la = false;
        foreach ($plats_populaires as $pop) {
            if ($pop['id'] === $p['id']) {
                $deja_la = true;
            }
        }
        if (!$deja_la) {
            $plats_populaires[] = $p;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Étoile — Restaurant d'Exception</title>
    <link rel="stylesheet" href="stylecommon.css">
    <link rel="stylesheet" href="styleaccueil.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="hero">
    <div class="hero-content">
        <h1>L'Étoile</h1>
        <p>Restaurant gastronomique proposant une cuisine raffinée, inspirée des produits d'exception.</p>
        <a href="carte.php" class="btn-secondary">Découvrir la carte</a>
    </div>
</section>

<main>

    <section class="recherche">
        <h2>Rechercher un plat</h2>
        <form class="recherche-form" method="GET" action="accueil.php">
            <input type="text" name="q" placeholder="Ex : risotto, truffe, dessert..." value="<?= h($recherche) ?>">
            <button type="submit">Rechercher</button>
        </form>
    </section>

    <?php if ($recherche !== '') { ?>
        <section class="plats">
            <h2>Résultats pour "<?= h($recherche) ?>"</h2>
            <?php if (count($resultats_recherche) === 0) { ?>
                <p>Aucun plat trouvé pour cette recherche.</p>
            <?php } else { ?>
                <div class="plats-container">
                    <?php foreach ($resultats_recherche as $plat) { ?>
                        <article class="plat">
                            <h3><?= h($plat['nom']) ?></h3>
                            <p><?= h($plat['description']) ?></p>
                            <p><strong><?= number_format($plat['prix'], 2) ?> €</strong></p>
                            <p><em>Allergènes : <?= h($plat['allergenes']) ?></em></p>
                        </article>
                    <?php } ?>
                </div>
            <?php } ?>
        </section>
    <?php } else { ?>

        <section class="plats">
            <h2>Nos incontournables</h2>
            <div class="plats-container">
                <?php foreach ($plats_populaires as $plat) { ?>
                    <article class="plat">
                        <h3><?= h($plat['nom']) ?></h3>
                        <p><?= h($plat['description']) ?></p>
                        <p><strong><?= number_format($plat['prix'], 2) ?> €</strong></p>
                    </article>
                <?php } ?>
            </div>
        </section>

    <?php } ?>

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

</body>
</html>
