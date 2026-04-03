<?php
require_once 'functions.php';
exiger_role('restaurateur');

// ---- ACTIONS POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = $_POST['commande_id'] ?? '';
    $action = $_POST['action']      ?? '';

    if ($id) {
        switch ($action) {
            case 'preparer':
                update_statut_commande($id, 'en_preparation');
                break;

            case 'prete_emporter':
                // BUG CORRIGÉ : commande à emporter prête → statut "livree"
                // (pas de livreur pour l'emporter, le client vient chercher)
                update_statut_commande($id, 'livree');
                break;

            case 'envoyer_livraison':
                // Attribuer le premier livreur actif disponible
                $livreurs = array_values(array_filter(
    get_users(),
    fn($u) => $u['role'] === 'livreur' && $u['statut'] === 'actif'
));

if (!empty($livreurs)) {
    $livreur_email = $livreurs[0]['email']; // premier livreur dispo
    update_statut_commande($id, 'en_livraison', $livreur_email);
} else {
    // aucun livreur dispo → on met quand même en livraison
    update_statut_commande($id, 'en_livraison');
}
                break;
        }
    }
    header('Location: commandes-cuisine.php');
    exit();
}

$en_attente   = get_commandes_par_statut('en_attente');
$en_prep      = get_commandes_par_statut('en_preparation');
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

    <!-- =============================== -->
    <!-- EN ATTENTE                      -->
    <!-- =============================== -->
    <section class="commandes-section">
        <h2>En attente (<?= count($en_attente) ?>)</h2>

        <?php if (empty($en_attente)): ?>
            <p class="empty-msg">Aucune commande en attente.</p>
        <?php endif; ?>

        <?php foreach ($en_attente as $cmd):
            $client = get_user_by_email($cmd['user_email']); ?>
        <div class="commande-card">
            <div class="commande-info">
                <h3>Commande #<?= h($cmd['id']) ?>
                    <span class="badge-mode <?= $cmd['mode'] === 'livraison' ? 'badge-livraison' : 'badge-emporter' ?>">
                        <?= $cmd['mode'] === 'livraison' ? '🚚 Livraison' : '🏠 Emporter' ?>
                    </span>
                </h3>
                <p>Client : <strong><?= $client ? h($client['prenom'] . ' ' . $client['nom']) : h($cmd['user_email']) ?></strong></p>
                <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
                <p>Commandé le : <?= h(date('d/m/Y à H:i', strtotime($cmd['date']))) ?></p>
                <?php if ($cmd['mode'] === 'livraison'): ?>
                    <p>📍 <?= h($cmd['adresse']) ?></p>
                <?php endif; ?>
            </div>

            <div class="actions-menu">
                <button class="actions-btn">Détails</button>
                <div class="actions-dropdown">
                    <?php foreach ($cmd['plats'] as $p): ?>
                        <span class="action">
                            <?= h($p['nom']) ?> × <?= $p['quantite'] ?>
                            — <?= number_format($p['prix'] * $p['quantite'], 2) ?> €
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="POST" style="display:inline;">
                <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                <input type="hidden" name="action"      value="preparer">
                <button type="submit" class="btn-action">Prendre en charge</button>
            </form>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- =============================== -->
    <!-- EN PRÉPARATION                  -->
    <!-- =============================== -->
    <section class="commandes-section">
        <h2>En préparation (<?= count($en_prep) ?>)</h2>

        <?php if (empty($en_prep)): ?>
            <p class="empty-msg">Aucune commande en préparation.</p>
        <?php endif; ?>

        <?php foreach ($en_prep as $cmd):
            $client = get_user_by_email($cmd['user_email']); ?>
        <div class="commande-card">
            <div class="commande-info">
                <h3>Commande #<?= h($cmd['id']) ?>
                    <span class="badge-mode <?= $cmd['mode'] === 'livraison' ? 'badge-livraison' : 'badge-emporter' ?>">
                        <?= $cmd['mode'] === 'livraison' ? '🚚 Livraison' : '🏠 Emporter' ?>
                    </span>
                </h3>
                <p>Client : <strong><?= $client ? h($client['prenom'] . ' ' . $client['nom']) : h($cmd['user_email']) ?></strong></p>
                <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
                <?php if ($cmd['mode'] === 'livraison'): ?>
                    <p>📍 <?= h($cmd['adresse']) ?></p>
                <?php endif; ?>
            </div>

            <div class="actions-menu">
                <button class="actions-btn">Détails</button>
                <div class="actions-dropdown">
                    <?php foreach ($cmd['plats'] as $p): ?>
                        <span class="action"><?= h($p['nom']) ?> × <?= $p['quantite'] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($cmd['mode'] === 'livraison'): ?>
                <!-- Livraison : passer en livraison avec attribution livreur -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                    <input type="hidden" name="action"      value="envoyer_livraison">
                    <button type="submit" class="btn-action secondaire">Envoyer en livraison</button>
                </form>
            <?php else: ?>
                <!-- BUG CORRIGÉ : à emporter → marquer comme prête/livrée -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                    <input type="hidden" name="action"      value="prete_emporter">
                    <button type="submit" class="btn-action secondaire">Marquer prête à emporter</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- =============================== -->
    <!-- EN LIVRAISON                    -->
    <!-- =============================== -->
    <section class="commandes-section">
        <h2>En livraison (<?= count($en_livraison) ?>)</h2>

        <?php if (empty($en_livraison)): ?>
            <p class="empty-msg">Aucune commande en livraison.</p>
        <?php endif; ?>

        <?php foreach ($en_livraison as $cmd):
            $client  = get_user_by_email($cmd['user_email']);
            $livreur = $cmd['livreur_email'] ? get_user_by_email($cmd['livreur_email']) : null; ?>
        <div class="commande-card livraison">
            <div class="commande-info">
                <h3>Commande #<?= h($cmd['id']) ?></h3>
                <p>Client : <strong><?= $client ? h($client['prenom'] . ' ' . $client['nom']) : h($cmd['user_email']) ?></strong></p>
                <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
                <p>Livreur : <?= $livreur ? h($livreur['prenom'] . ' ' . $livreur['nom']) : '<em>Non attribué</em>' ?></p>
                <p>📍 <?= h($cmd['adresse']) ?></p>
            </div>

            <div class="actions-menu">
                <button class="actions-btn">Détails</button>
                <div class="actions-dropdown">
                    <?php foreach ($cmd['plats'] as $p): ?>
                        <span class="action"><?= h($p['nom']) ?> × <?= $p['quantite'] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
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
