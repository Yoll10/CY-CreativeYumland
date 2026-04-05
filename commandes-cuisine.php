<?php
require_once 'functions.php';
exiger_role('restaurateur');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['commande_id'];
    $action = $_POST['action'];

    if ($id !== '') {
        if ($action === 'preparer') {
            update_statut_commande($id, 'en_preparation');

        } else if ($action === 'prete_emporter') {
            update_statut_commande($id, 'livree');

        } else if ($action === 'envoyer_livraison') {
            $tous_users = get_users();
            $livreurs = array();
            foreach ($tous_users as $u) {
                if ($u['role'] === 'livreur' && $u['statut'] === 'actif') {
                    $livreurs[] = $u;
                }
            }

            if (count($livreurs) > 0) {
                $livreur_email = $livreurs[0]['email'];
                update_statut_commande($id, 'en_livraison', $livreur_email);
            } else {
                update_statut_commande($id, 'en_livraison');
            }
        }
    }

    header('Location: commandes-cuisine.php');
    exit();
}

$en_attente = get_commandes_par_statut('en_attente');
$en_prep = get_commandes_par_statut('en_preparation');
$en_livraison = get_commandes_par_statut('en_livraison');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commandes — L'Étoile</title>
    <link rel="stylesheet" href="stylecommon.css">
    <link rel="stylesheet" href="stylecommandescuisine.css">
</head>
<body>

<?php include 'header.php'; ?>

<main class="commandes-page">

    <section class="page-header">
        <h1>Gestion des commandes</h1>
        <p>Suivi des commandes en attente et en cours de livraison</p>
    </section>

    <section class="commandes-section">
        <h2>En attente (<?= count($en_attente) ?>)</h2>

        <?php if (count($en_attente) === 0) { ?>
            <p class="empty-msg">Aucune commande en attente.</p>
        <?php } ?>

        <?php foreach ($en_attente as $cmd) {
            $client = get_user_by_email($cmd['user_email']);
            $nom_client = '';
            if ($client !== null) {
                $nom_client = $client['prenom'] . ' ' . $client['nom'];
            } else {
                $nom_client = $cmd['user_email'];
            }
        ?>
            <div class="commande-card">
                <div class="commande-info">
                    <h3>
                        Commande #<?= h($cmd['id']) ?>
                        <span class="badge-mode <?php if ($cmd['mode'] === 'livraison') { echo 'badge-livraison'; } else { echo 'badge-emporter'; } ?>">
                            <?php if ($cmd['mode'] === 'livraison') { ?>
                                🚚 Livraison
                            <?php } else { ?>
                                🏠 Emporter
                            <?php } ?>
                        </span>
                    </h3>
                    <p>Client : <strong><?= h($nom_client) ?></strong></p>
                    <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
                    <p>Commandé le : <?= h(date('d/m/Y à H:i', strtotime($cmd['date']))) ?></p>
                    <?php if ($cmd['mode'] === 'livraison') { ?>
                        <p>📍 <?= h($cmd['adresse']) ?></p>
                    <?php } ?>
                </div>

                <div class="actions-menu">
                    <button class="actions-btn">Détails</button>
                    <div class="actions-dropdown">
                        <?php foreach ($cmd['plats'] as $p) { ?>
                            <span class="action">
                                <?= h($p['nom']) ?> × <?= $p['quantite'] ?> — <?= number_format($p['prix'] * $p['quantite'], 2) ?> €
                            </span>
                        <?php } ?>
                    </div>
                </div>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                    <input type="hidden" name="action" value="preparer">
                    <button type="submit" class="btn-action">Prendre en charge</button>
                </form>
            </div>
        <?php } ?>
    </section>

    <section class="commandes-section">
        <h2>En préparation (<?= count($en_prep) ?>)</h2>

        <?php if (count($en_prep) === 0) { ?>
            <p class="empty-msg">Aucune commande en préparation.</p>
        <?php } ?>

        <?php foreach ($en_prep as $cmd) {
            $client = get_user_by_email($cmd['user_email']);
            $nom_client = '';
            if ($client !== null) {
                $nom_client = $client['prenom'] . ' ' . $client['nom'];
            } else {
                $nom_client = $cmd['user_email'];
            }
        ?>
            <div class="commande-card">
                <div class="commande-info">
                    <h3>
                        Commande #<?= h($cmd['id']) ?>
                        <span class="badge-mode <?php if ($cmd['mode'] === 'livraison') { echo 'badge-livraison'; } else { echo 'badge-emporter'; } ?>">
                            <?php if ($cmd['mode'] === 'livraison') { ?>
                                🚚 Livraison
                            <?php } else { ?>
                                🏠 Emporter
                            <?php } ?>
                        </span>
                    </h3>
                    <p>Client : <strong><?= h($nom_client) ?></strong></p>
                    <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
                    <?php if ($cmd['mode'] === 'livraison') { ?>
                        <p>📍 <?= h($cmd['adresse']) ?></p>
                    <?php } ?>
                </div>

                <div class="actions-menu">
                    <button class="actions-btn">Détails</button>
                    <div class="actions-dropdown">
                        <?php foreach ($cmd['plats'] as $p) { ?>
                            <span class="action"><?= h($p['nom']) ?> × <?= $p['quantite'] ?></span>
                        <?php } ?>
                    </div>
                </div>

                <?php if ($cmd['mode'] === 'livraison') { ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                        <input type="hidden" name="action" value="envoyer_livraison">
                        <button type="submit" class="btn-action secondaire">Envoyer en livraison</button>
                    </form>
                <?php } else { ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                        <input type="hidden" name="action" value="prete_emporter">
                        <button type="submit" class="btn-action secondaire">Marquer prête à emporter</button>
                    </form>
                <?php } ?>
            </div>
        <?php } ?>
    </section>

    <section class="commandes-section">
        <h2>En livraison (<?= count($en_livraison) ?>)</h2>

        <?php if (count($en_livraison) === 0) { ?>
            <p class="empty-msg">Aucune commande en livraison.</p>
        <?php } ?>

        <?php foreach ($en_livraison as $cmd) {
            $client = get_user_by_email($cmd['user_email']);
            $nom_client = '';
            if ($client !== null) {
                $nom_client = $client['prenom'] . ' ' . $client['nom'];
            } else {
                $nom_client = $cmd['user_email'];
            }

            $livreur = null;
            if (isset($cmd['livreur_email']) && $cmd['livreur_email'] !== null) {
                $livreur = get_user_by_email($cmd['livreur_email']);
            }
            $nom_livreur = '';
            if ($livreur !== null) {
                $nom_livreur = $livreur['prenom'] . ' ' . $livreur['nom'];
            } else {
                $nom_livreur = 'Non attribué';
            }
        ?>
            <div class="commande-card livraison">
                <div class="commande-info">
                    <h3>Commande #<?= h($cmd['id']) ?></h3>
                    <p>Client : <strong><?= h($nom_client) ?></strong></p>
                    <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
                    <p>Livreur : <?= h($nom_livreur) ?></p>
                    <p>📍 <?= h($cmd['adresse']) ?></p>
                </div>

                <div class="actions-menu">
                    <button class="actions-btn">Détails</button>
                    <div class="actions-dropdown">
                        <?php foreach ($cmd['plats'] as $p) { ?>
                            <span class="action"><?= h($p['nom']) ?> × <?= $p['quantite'] ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>
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

</body>
</html>
