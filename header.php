<header class="header">
    <nav class="navbar">
        <div class="logo"><a href="accueil.php">L'Étoile</a></div>
        <ul class="links">
            <li><a class="cartenav" href="carte.php">Carte</a></li>
        </ul>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user'])): ?>

                <?php if ($_SESSION['user']['role'] === 'client'): ?>
                    <?php $nb = nb_articles_panier(); ?>
                    <a href="commande-template.php" class="btn-primary">
                        Commander<?= $nb > 0 ? ' (' . $nb . ')' : '' ?>
                    </a>
                    <a href="profil.php" class="btn-primary">Mon Profil</a>

                <?php elseif ($_SESSION['user']['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn-primary">Vue Admin</a>
                    <a href="profil.php" class="btn-primary">Mon Profil</a>

                <?php elseif ($_SESSION['user']['role'] === 'restaurateur'): ?>
                    <a href="commandes-cuisine.php" class="btn-primary">Commandes cuisine</a>

                <?php elseif ($_SESSION['user']['role'] === 'livreur'): ?>
                    <a href="livraison.php" class="btn-primary">Mes livraisons</a>

                <?php endif; ?>

                <a href="logout.php" class="btn-primary">Deconnexion</a>

            <?php else: ?>
                <a href="connexion.php" class="btn-primary">Se connecter</a>
                <a href="commande-template.php" class="btn-primary">Commander</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
