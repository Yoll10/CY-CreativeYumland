<?php
require_once 'functions.php';

$recherche = trim($_GET['q'] ?? '');
$plats_all = get_plats();
$menus     = get_menus();

$resultats = [];
if ($recherche !== '') {
    foreach ($plats_all as $p) {
        if (stripos($p['nom'], $recherche) !== false
         || stripos($p['description'], $recherche) !== false
         || stripos($p['allergenes'], $recherche) !== false) {
            $resultats[] = $p;
        }
    }
}

function afficher_plats_grille(array $liste): void {
    $groupes = array_chunk($liste, 3);
    foreach ($groupes as $groupe): ?>
        <div class="colonnes">
            <?php foreach ($groupe as $plat): ?>
            <div>
                <p class="titre3"><?= h($plat['nom']) ?></p>
                <div class="plat">
                    <img class="img" src="<?= h($plat['image']) ?>" alt="<?= h($plat['nom']) ?>"
                         onerror="this.src='images/plats/default.jpg'">
                    <div class="overlay-texte">
                        Allergènes : <?= h($plat['allergenes']) ?><br>
                        Prix : <?= number_format($plat['prix'], 2) ?> €
                    </div>
                </div>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="commande-template.php?ajouter=<?= h($plat['id']) ?>" class="bouton-discover">Ajouter au panier</a>
                <?php else: ?>
                    <a href="connexion.php" class="bouton-discover">Se connecter pour commander</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Carte — L'Étoile</title>
    <link rel="stylesheet" href="css/stylecommon.css">
    <link href="css/stylecarte.css" rel="stylesheet">
        
    <script src="js/scriptjs.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<div class="titre1">Notre carte</div>

<form method="GET" action="carte.php">
    <div class="barre">
        <span class="loupe">🔍</span>
        <input type="search" class="maRecherche" name="q"
               placeholder="Rechercher..." value="<?= h($recherche) ?>">
        <button type="submit" class="btn-invisible"></button>
    </div>
</form>

<?php if ($recherche !== ''): ?>

    <div class="titre2">Résultats pour "<?= h($recherche) ?>"</div>
    <?php if (empty($resultats)): ?>
        <p style="text-align:center; padding: 2rem;">Aucun plat trouvé.</p>
    <?php else: ?>
        <?php afficher_plats_grille($resultats); ?>
    <?php endif; ?>

<?php else: ?>

    <nav class="nav-categories">
        <a href="#menus"    class="btn-primary">Menus</a>
        <a href="#filtres"  class="btn-primary">🔎 Filtrer les plats</a>
        <a href="#entrees"  class="btn-primary">Entrées</a>
        <a href="#plats"    class="btn-primary">Plats</a>
        <a href="#desserts" class="btn-primary">Desserts</a>
    </nav>

    <img class="dividing" src="images/divider.png" alt="separateur">
    <div id="menus" class="titre2">Nos Menus</div>
    <div class="colonnes">
        <?php foreach ($menus as $menu): ?>
        <div style="background-color: #e2d3c3; min-height: 350px; padding: 1rem;">
            <p class="titre3"><?= h($menu['nom']) ?></p>
            <p style="font-style: italic; padding: 0.5rem 0;"><?= h($menu['description']) ?></p>
            <p class="titre3" style="font-size: 1.4rem;"><?= number_format($menu['prix'], 2) ?> €</p>
            <?php if (est_connecte()): ?>
                <a href="commande-template.php?menu=<?= h($menu['id']) ?>" class="bouton-discover">Choisir ce menu</a>
            <?php else: ?>
                <a href="connexion.php" class="bouton-discover">Se connecter pour commander</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <img class="dividing" src="images/divider.png" alt="separateur">
    <div id="filtres" class="titre2">Filtrer les plats</div>

    <div class="filtres-wrapper">

        <div class="filtres-groupe">
            <label>Catégorie :</label>
            <button class="btn-filtre filtre-categorie actif" data-categorie="tous">Tous</button>
            <button class="btn-filtre filtre-categorie" data-categorie="entree">Entrées</button>
            <button class="btn-filtre filtre-categorie" data-categorie="plat">Plats</button>
            <button class="btn-filtre filtre-categorie" data-categorie="dessert">Desserts</button>
        </div>

        <div class="filtres-groupe">
            <label>Allergènes :</label>
            <button class="btn-filtre filtre-allergene actif" data-allergene="tous">Tous</button>
            <button class="btn-filtre filtre-allergene" data-allergene="sans-gluten">Sans gluten</button>
            <button class="btn-filtre filtre-allergene" data-allergene="sans-lait">Sans lait</button>
            <button class="btn-filtre filtre-allergene" data-allergene="sans-oeuf">Sans œuf</button>
        </div>

        <div class="filtres-groupe">
            <label>Trier par :</label>
            <select id="select-tri" class="select-tri">
                <option value="defaut">— Par défaut —</option>
                <option value="prix-asc">Prix croissant</option>
                <option value="prix-desc">Prix décroissant</option>
                <option value="popularite">Popularité</option>
                <option value="nom-asc">Nom A → Z</option>
            </select>
        </div>

    </div>

    <div id="carte-resultats"
         data-connecte="<?= isset($_SESSION['user']) ? '1' : '0' ?>">
        <p class="carte-vide">Chargement des plats…</p>
    </div>

    <img class="dividing" src="images/divider.png" alt="separateur">
    <div id="entrees" class="titre2">Nos Entrées</div>
    <?php afficher_plats_grille(get_plats_par_categorie('entree')); ?>

    <img class="dividing" src="images/divider.png" alt="separateur">
    <div id="plats" class="titre2">Nos Plats</div>
    <?php afficher_plats_grille(get_plats_par_categorie('plat')); ?>

    <img class="dividing" src="images/divider.png" alt="separateur">
    <div id="desserts" class="titre2">Nos Desserts</div>
    <?php afficher_plats_grille(get_plats_par_categorie('dessert')); ?>

<?php endif; ?>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-column"><h3>Contact</h3><p>06 49 00 96 90</p><p>supportclient@gmail.com</p><p>10 chemin de la Vie, Paris</p></div>
        <div class="footer-column"><h3>Récompenses</h3><p>3 étoiles Michelin</p><p>Maitre Restaurateur</p></div>
        <div class="footer-column"><h3>Horaires</h3><p>Lundi – Vendredi : 10h – 19h</p><p>Samedi & Dimanche : Fermé</p></div>
    </div>
    <div class="footer-legal"><p>© 2026 — L'Étoile. Tous droits réservés.</p><p>Mentions légales</p></div>
</footer>


</body>
</html>
