<?php
require_once 'functions.php';
exiger_role('livreur');

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
    <style>
        /*
         * PAGE LIVREUR — Optimisée smartphone + gants de moto
         * Toutes les zones cliquables sont grandes (min 56px)
         */

        .livraison-page { max-width: 700px; margin: 0 auto; padding: 1rem; }

        .livraison-card {
            background: #fff;
            border: 1px solid #e0d5c5;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        /* EN-TÊTE DE CARTE */
        .lc-header {
            background: #2c2c2c;
            color: #fff;
            padding: 1rem 1.2rem;
        }

        .lc-header h2 { margin: 0; font-size: 1.1rem; }
        .lc-header p  { margin: 0.3rem 0 0; font-size: 0.9rem; opacity: 0.85; }

        /* CORPS DE CARTE */
        .lc-body { padding: 1rem 1.2rem; }

        .lc-info { margin-bottom: 0.5rem; font-size: 1rem; line-height: 1.5; }
        .lc-info strong { display: inline-block; min-width: 90px; color: #555; font-size:0.85rem; }

        /* DÉTAILS COMMANDE (accordéon) */
        .lc-details-btn {
            background: #f0ebe2;
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            cursor: pointer;
            margin-bottom: 0.6rem;
            width: 100%;
            text-align: left;
            font-weight: bold;
        }

        .lc-details-btn:hover { background: #e0d5c5; }

        .lc-details-liste {
            display: none;
            background: #faf8f5;
            border-radius: 6px;
            padding: 0.6rem 1rem;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        .lc-details-liste.visible { display: block; }

        .lc-details-liste li {
            padding: 0.3rem 0;
            border-bottom: 1px solid #ede5d8;
            list-style: none;
        }

        .lc-details-liste li:last-child { border-bottom: none; }

        /* ACTIONS : boutons GRANDS pour gants de moto */
        .lc-actions {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            padding: 0 1.2rem 1.2rem;
        }

        /*
         * BOUTON MAPS — TRÈS GRAND, très visible, facile à taper avec des gants
         */
        .btn-maps-gros {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            background: #1a73e8;
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            font-size: 1.2rem;
            font-weight: bold;
            min-height: 64px;
            box-shadow: 0 3px 10px rgba(26,115,232,0.3);
            transition: background 0.2s, transform 0.1s;
        }

        .btn-maps-gros:hover  { background: #1558b0; }
        .btn-maps-gros:active { transform: scale(0.97); }

        .btn-maps-icone { font-size: 1.6rem; }

        /*
         * BOUTON LIVRÉE — TRÈS GRAND, vert, impossible à rater
         */
        .btn-livree-gros {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            background: #27ae60;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            font-size: 1.2rem;
            font-weight: bold;
            min-height: 64px;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(39,174,96,0.3);
            transition: background 0.2s, transform 0.1s;
        }

        .btn-livree-gros:hover  { background: #1e8449; }
        .btn-livree-gros:active { transform: scale(0.97); }

        /* Message vide */
        .msg-vide {
            text-align: center;
            padding: 3rem 1rem;
            color: #888;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="livraison-page">

    <section class="admin-header" style="text-align:center; padding:1.5rem 1rem 1rem;">
        <h1>Mes livraisons</h1>
        <p><?= count($mes_livraisons) ?> commande(s) à livrer</p>
    </section>

    <?php if (empty($mes_livraisons)): ?>
        <div class="msg-vide">
            ✅ Aucune commande à livrer pour le moment.<br>
            <small>Revenez plus tard ou contactez le restaurateur.</small>
        </div>
    <?php endif; ?>

    <?php foreach ($mes_livraisons as $cmd):
        $client       = get_user_by_email($cmd['user_email']);
        $nom_client   = $client ? h($client['prenom'].' '.$client['nom']) : h($cmd['user_email']);
        $tel_client   = $client ? h($client['telephone'] ?? '—') : '—';
        $adresse_maps = 'https://maps.google.com/?q=' . urlencode($cmd['adresse']);
    ?>
    <div class="livraison-card">

        <!-- En-tête -->
        <div class="lc-header">
            <h2>Commande #<?= h($cmd['id']) ?></h2>
            <p>Total : <strong><?= number_format($cmd['total'], 2) ?> €</strong></p>
        </div>

        <!-- Informations -->
        <div class="lc-body">
            <div class="lc-info">
                <strong>Client</strong> <?= $nom_client ?>
            </div>
            <div class="lc-info">
                <strong>Téléphone</strong>
                <a href="tel:<?= preg_replace('/\s/', '', $tel_client) ?>" style="color:#1a73e8;">
                    <?= $tel_client ?>
                </a>
            </div>
            <div class="lc-info">
                <strong>Adresse</strong> <?= h($cmd['adresse']) ?>
            </div>

            <!--
                DÉTAILS COMMANDE CORRIGÉ :
                Le bouton "Détails" est séparé de la liste des plats.
                La liste s'affiche en dessous, pas à côté.
            -->
            <button class="lc-details-btn" onclick="toggleDetails('<?= h($cmd['id']) ?>')">
                📋 Voir le détail de la commande ▼
            </button>
            <ul class="lc-details-liste" id="details-<?= h($cmd['id']) ?>">
                <?php foreach ($cmd['plats'] as $p): ?>
                    <li><?= h($p['nom']) ?> <strong>× <?= $p['quantite'] ?></strong>
                        — <?= number_format($p['prix'] * $p['quantite'], 2) ?> €
                    </li>
                <?php endforeach; ?>
                <li><strong>Total : <?= number_format($cmd['total'], 2) ?> €</strong></li>
            </ul>
        </div>

        <!-- Actions GRANDES pour smartphone + gants -->
        <div class="lc-actions">

            <!--
                BOUTON MAPS GROS
                Facile à tapper avec des gants de moto
            -->
            <a href="<?= $adresse_maps ?>"
               target="_blank"
               class="btn-maps-gros">
                <span class="btn-maps-icone">🗺️</span>
                Ouvrir dans Maps
            </a>

            <!--
                BOUTON LIVRÉE GROS
            -->
            <form method="POST">
                <input type="hidden" name="commande_id" value="<?= h($cmd['id']) ?>">
                <input type="hidden" name="action"      value="livree">
                <button type="submit" class="btn-livree-gros"
                        onclick="return confirm('Confirmer la livraison de cette commande ?')">
                    ✅ Marquer comme livrée
                </button>
            </form>

        </div>
    </div>
    <?php endforeach; ?>

</main>

<script>
function toggleDetails(id) {
    const liste = document.getElementById('details-' + id);
    liste.classList.toggle('visible');
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
