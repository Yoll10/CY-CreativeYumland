<?php
require_once 'functions.php';
exiger_role('livreur');

$mon_email = $_SESSION['user']['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['commande_id'];
    $action = $_POST['action'];
    
    if ($id !== '') {
        if ($action === 'accepter') {
            update_statut_commande($id, 'en_livraison', $mon_email);
            ajouter_log('livraison_acceptee', "Le livreur $mon_email a pris en charge la commande $id");
        } else if ($action === 'livree') {
            update_statut_commande($id, 'livree');
        }
    }
    header('Location: livraison.php');
    exit();
}

$commandes_dispo = get_commandes_disponibles_livraison();
$mes_livraisons = get_commandes_livreur($mon_email);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Livreur — L'Étoile</title>
    <link href="css/stylecommon.css" rel="stylesheet">
    <link rel="stylesheet" href="css/stylelivraison.css">
    <script src="js/scriptjs.js" defer></script>
    <script src="js/livraison.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<main class="livraison-page">

    <section class="admin-header" style="text-align:center; padding:1.5rem 1rem 1rem;">
        <h1>Espace Livraisons</h1>
        <p>Gérez vos courses en temps réel</p>
    </section>

    <section class="livraison-section" style="margin-bottom: 3rem;">
        <h2 style="padding: 0 1rem; color: #bca374;">🛒 Commandes disponibles (<?= count($commandes_dispo) ?>)</h2>
        
        <?php if (count($commandes_dispo) === 0) { ?>
            <div class="msg-vide" style="background: #f9f9f9; border: 1px dashed #ccc; padding: 1.5rem; text-align: center; margin: 1rem;">
                 Aucune nouvelle commande à pourvoir pour le moment.
            </div>
        <?php } ?>

        <?php foreach ($commandes_dispo as $cmd) {
            $client = get_user_by_email($cmd['user_email']);
            $nom_client = $client ? $client['prenom'] . ' ' . $client['nom'] : $cmd['user_email'];
        ?>
            <div class="livraison-card" style="border-left: 5px solid #bca374;">
                <div class="lc-header">
                    <h2>Commande #<?= h($cmd['id']) ?></h2>
                    <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
                </div>
                <div class="lc-body">
                    <div class="lc-info"><strong>Client :</strong> En attente de prise en charge</div>
                    <div class="lc-info"><strong>Ville / Adresse de livraison :</strong> <?= h($cmd['adresse']) ?></div>
                </div>
                <div class="lc-actions">
                    <form method="POST" style="width: 100%;">
                        <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                        <input type="hidden" name="action" value="accepter">
                        <button type="submit" class="btn-livree-gros" style="background-color: #28a745; color: white;">
                             Prendre en charge cette commande
                        </button>
                    </form>
                </div>
            </div>
        <?php } ?>
    </section>

    <hr style="border: 0; height: 1px; background: #eee; margin: 2rem 0;">

    <section class="livraison-section">
        <h2 style="padding: 0 1rem; color: #1a73e8;">🚴 Mes livraisons en cours (<?= count($mes_livraisons) ?>)</h2>

        <?php if (count($mes_livraisons) === 0) { ?>
            <div class="msg-vide">
                 Vous n'avez aucune commande en cours de livraison.<br>
                <small>Choisissez une commande ci-dessus pour commencer.</small>
            </div>
        <?php } ?>

        <?php foreach ($mes_livraisons as $cmd) {
            $client = get_user_by_email($cmd['user_email']);
            $nom_client = $client ? $client['prenom'] . ' ' . $client['nom'] : $cmd['user_email'];
            $tel_client = ($client && isset($client['telephone'])) ? $client['telephone'] : '—';
            $adresse_maps = 'https://maps.google.com/?q=' . urlencode($cmd['adresse']);
        ?>
            <div class="livraison-card" style="border-left: 5px solid #1a73e8;">

                <div class="lc-header">
                    <h2>Commande #<?= h($cmd['id']) ?></h2>
                    <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
                </div>

                <div class="lc-body">
                    <div class="lc-info">
                        <strong>Client :</strong> <?= h($nom_client) ?>
                    </div>
                    <div class="lc-info">
                        <strong>Téléphone :</strong>
                        <a href="tel:<?= preg_replace('/\s/', '', $tel_client) ?>" style="color:#1a73e8;">
                            <?= h($tel_client) ?>
                        </a>
                    </div>
                    <div class="lc-info">
                        <strong>Adresse :</strong> <?= h($cmd['adresse']) ?>
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