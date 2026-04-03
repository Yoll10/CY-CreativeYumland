<?php

$json_data = file_get_contents('data.json');
$data = json_decode($json_data, true);

$plats = $data['plats'];
$menus = $data['menus'];

function afficherCategorie($tous_les_plats, $categorie_cible) {
    $plats_filtres = array_filter($tous_les_plats, function($p) use ($categorie_cible) {
        return $p['categorie'] === $categorie_cible;
    });

    $groupes = array_chunk($plats_filtres, 3);

    foreach ($groupes as $groupe) {
        echo '<div class="colonnes">';
        foreach ($groupe as $plat) {
            ?>
            <div>
                <p class="titre3"><?php echo htmlspecialchars($plat['nom']); ?></p>
                <div class="plat">
                    <img class="img" src="<?php echo $plat['image']; ?>" alt="img-repas">
                    <div class="overlay-texte">
                        Allergie : <?php echo htmlspecialchars($plat['allergies']); ?><br>
                        Prix : <?php echo number_format($plat['prix'], 2); ?> €
                    </div>
                </div>
                <a href="commande-template.html?id=<?php echo $plat['id']; ?>" class="bouton-discover">Ajouter au panier</a>
            </div>
            <?php
        }
        echo '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Carte - L'Étoile</title>
    <link rel="stylesheet" href="stylecommon.css">
    <link href="stylecarte.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo"><a href="accueil.html">L’Étoile</a></div>
            <ul class="links">
                <li><a class="cartenav" href="carte.php">Carte</a></li>
            </ul>
            <div class="nav-actions">
                <a href="connexion.html" class="btn-primary">Se connecter</a>
                <a href="admin.html" class="btn-primary">Vue admin</a>
                <a href="commande-template.html" class="btn-primary">Commander</a>
                <a href="profil.html" class="btn-primary">Voir Profil</a>
            </div>
        </nav>
    </header>

    <div class="titre1">Notre carte</div>

    <form>
        <div class="barre">
            <span class="loupe">🔍</span>
            <input type="search" class="maRecherche" name="q" placeholder="Rechercher..." />
            <button type="submit" class="btn-invisible"></button> 
        </div>
    </form>

    <nav class="nav-categories">
        <a href="#menus" class="btn-primary">Nos Menus</a>
        <a href="#burgers" class="btn-primary">Burgers</a>
        <a href="#accompagnements" class="btn-primary">Accompagnements</a>
        <a href="#desserts" class="btn-primary">Desserts</a>
    </nav>

    <img class="dividing" src="divider.png" alt="separateur">
    <div id="menus" class="titre2">Nos Menus</div>
    <div class="colonnes">
        <?php foreach ($menus as $m): ?>
        <div style="background-color: #e2d3c3; min-height: 350px;">
            <p class="titre3"><?php echo htmlspecialchars($m['nom']); ?></p>
            <p style="padding: 10px; font-style: italic;"><?php echo htmlspecialchars($m['description']); ?></p>
            <p class="titre3" style="font-size: 25px;"><?php echo number_format($m['prix'], 2); ?> €</p>
            <a href="commande-template.html?menu=<?php echo $m['id']; ?>" class="bouton-discover">Choisir ce menu</a>
        </div>
        <?php endforeach; ?>
    </div>

    <img class="dividing" src="divider.png" alt="separateur">
    <div id="burgers" class="titre2">Nos Burgers</div>
    <?php afficherCategorie($plats, 'burger'); ?>

    <img class="dividing" src="divider.png" alt="separateur">
    <div id="accompagnements" class="titre2">Nos Accompagnements</div>
    <?php afficherCategorie($plats, 'accompagnement'); ?>

    <img class="dividing" src="divider.png" alt="separateur">
    <div id="desserts" class="titre2">Nos Desserts</div>
    <?php afficherCategorie($plats, 'dessert'); ?>

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
            <p>© 2026 — L’Étoile. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>