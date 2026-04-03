<?php
require_once 'functions.php';
exiger_role('livreur');

// Action : marquer une commande comme livrée
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = $_POST['commande_id'] ?? '';
    $action = $_POST['action']      ?? '';
    if ($id && $action === 'livree') {
        update_statut_commande($id, 'livree');
    }
    header('Location: livraison.php');
    exit();
}

$mes_livraisons = get_commandes_livreur($_SESSION['user']['email']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes livraisons — L'Étoile</title>
    <link href="stylecommon.css" rel="stylesheet">
    <link rel="stylesheet" href="stylelivraison.css">
</head>
<body>

<?php include 'header.php'; ?>

<main class="admin">
    <section class="admin-header">
        <h1>Commandes à livrer</h1>
        <p><?= count($mes_livraisons) ?> commande(s) en attente de livraison</p>
    </section>
</main>

<div class="container">
    <?php if (empty($mes_livraisons)): ?>
        <p style="text-align:center; padding: 2rem;">Aucune commande à livrer pour le moment.</p>
    <?php else: ?>
    <table class="cartes">
        <thead>
            <tr>
                <th>N° Commande</th>
                <th>Nom client</th>
                <th>Adresse</th>
                <th>N. tél</th>
                <th>Commande</th>
                <th>État</th>
                <th>Maps</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mes_livraisons as $cmd):
                $client = get_user_by_email($cmd['user_email']);
                $adresse_maps = urlencode($cmd['adresse']);
            ?>
            <tr>
                <td data-label="N°">#<?= h($cmd['id']) ?></td>

                <td data-label="Nom">
                    <?= $client ? h($client['prenom'] . ' ' . $client['nom']) : h($cmd['user_email']) ?>
                </td>

                <td data-label="Adresse"><?= h($cmd['adresse']) ?></td>

                <td data-label="N. tél">
                    <?= $client ? h($client['telephone'] ?? '—') : '—' ?>
                </td>

                <td data-label="Commande">
                    <div class="actions-menu">
                        <button class="btn-primary">Détails</button>
                        <div class="actions-dropdown">
                            <?php foreach ($cmd['plats'] as $p): ?>
                                <span class="action"><?= h($p['nom']) ?> × <?= $p['quantite'] ?></span>
                            <?php endforeach; ?>
                            <span class="action"><strong>Total : <?= number_format($cmd['total'], 2) ?> €</strong></span>
                        </div>
                    </div>
                </td>

                <td data-label="État">
                    <form method="POST">
                        <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                        <input type="hidden" name="action"      value="livree">
                        <button type="submit" class="btn-primary">✅ Livrée</button>
                    </form>
                </td>

                <td data-label="Maps">
                    <a href="https://maps.google.com/?q=<?= $adresse_maps ?>"
                       class="btn-maps" target="_blank">Accéder</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

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
            <p>Maître Restaurateur</p>
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
