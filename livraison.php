<?php
require_once 'functions.php';
exiger_role('livreur');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['commande_id'];
    $action = $_POST['action'];
    if ($id !== '' && $action === 'livree') {
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

<main class="livraison-page">

    <section class="admin-header" style="text-align:center; padding:1.5rem 1rem 1rem;">
        <h1>Mes livraisons</h1>
        <p><?= count($mes_livraisons) ?> commande(s) à livrer</p>
    </section>

    <?php if (count($mes_livraisons) === 0) { ?>
        <div class="msg-vide">
             Aucune commande à livrer pour le moment.<br>
            <small>Revenez plus tard ou contactez le restaurateur.</small>
        </div>
    <?php } ?>

    <?php foreach ($mes_livraisons as $cmd) {
        $client = get_user_by_email($cmd['user_email']);

        $nom_client = '';
        if ($client !== null) {
            $nom_client = $client['prenom'] . ' ' . $client['nom'];
        } else {
            $nom_client = $cmd['user_email'];
        }

        $tel_client = '—';
        if ($client !== null && isset($client['telephone'])) {
            $tel_client = $client['telephone'];
        }

        $adresse_maps = 'https://maps.google.com/?q=' . urlencode($cmd['adresse']);
    ?>
        <div class="livraison-card">

            <div class="lc-header">
                <h2>Commande #<?= h($cmd['id']) ?></h2>
                <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
            </div>

            <div class="lc-body">
                <div class="lc-info">
                    <strong>Client</strong> <?= h($nom_client) ?>
                </div>
                <div class="lc-info">
                    <strong>Téléphone</strong>
                    <a href="tel:<?= preg_replace('/\s/', '', $tel_client) ?>" style="color:#1a73e8;">
                        <?= h($tel_client) ?>
                    </a>
                </div>
                <div class="lc-info">
                    <strong>Adresse</strong> <?= h($cmd['adresse']) ?>
                </div>

                <button class="lc-details-btn" onclick="toggleDetails('<?= h($cmd['id']) ?>')">
                     Voir le détail de la commande ▼
                </button>
                <ul class="lc-details-liste" id="details-<?= h($cmd['id']) ?>">
                    <?php foreach ($cmd['plats'] as $p) { ?>
                        <li>
                            <?= h($p['nom']) ?> <strong>× <?= $p['quantite'] ?></strong>
                            — <?= number_format($p['prix'] * $p['quantite'], 2) ?> €
                        </li>
                    <?php } ?>
                    <li><strong>Total : <?= number_format($cmd['total'], 2) ?> €</strong></li>
                </ul>
            </div>

            <div class="lc-actions">

                <a href="<?= $adresse_maps ?>" target="_blank" class="btn-maps-gros">
                    <span class="btn-maps-icone">🗺️</span>
                    Ouvrir dans Maps
                </a>

                <form method="POST">
                    <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                    <input type="hidden" name="action" value="livree">
                    <button type="submit" class="btn-livree-gros" onclick="return confirm('Confirmer la livraison ?')">
                         Marquer comme livrée
                    </button>
                </form>

            </div>
        </div>
    <?php } ?>

</main>

<script>
function toggleDetails(id) {
    var liste = document.getElementById('details-' + id);
    if (liste.classList.contains('visible')) {
        liste.classList.remove('visible');
    } else {
        liste.classList.add('visible');
    }
}
</script>

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
