<?php
require_once 'functions.php';
exiger_connexion();

// Un admin peut voir le profil d'un autre utilisateur via ?email=...
if (est_admin() && isset($_GET['email'])) {
    $profil_user = get_user_by_email($_GET['email']);
    if (!$profil_user) {
        header('Location: admin.php');
        exit();
    }
} else {
    $profil_user = utilisateur_courant();
}

$commandes_user = get_commandes_user($profil_user['email']);
$vue_admin = est_admin() && isset($_GET['email']) && $_GET['email'] !== $_SESSION['user']['email'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — <?= h($profil_user['prenom'] . ' ' . $profil_user['nom']) ?></title>
    <link href="styleprofil.css" rel="stylesheet">
    <link rel="stylesheet" href="stylecommon.css">
</head>
<body>

<?php include 'header.php'; ?>

<main class="main-content">

    <?php if ($vue_admin): ?>
    <div style="background:#f5e6c8; padding:0.8rem 1.5rem; border-left: 4px solid #b8860b; margin-bottom:1rem;">
        <strong>Mode Admin</strong> — Vous consultez le profil de <?= h($profil_user['prenom'] . ' ' . $profil_user['nom']) ?>
        <a href="admin.php" style="margin-left:1rem;">← Retour</a>
    </div>
    <?php endif; ?>

    <form class="forms">
        <fieldset>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <h2 class="titre-profil">Votre profil</h2>
                    <img class="avatar"
                         src="https://www.shutterstock.com/image-vector/default-avatar-profile-icon-social-600nw-1906669723.jpg"
                         alt="photo de profil">
                </div>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Nom :</label>
                    <input type="text" value="<?= h($profil_user['nom']) ?>" readonly>
                </div>
                <?php if (!$vue_admin): ?><button class="edit" type="button">🖌️</button><?php endif; ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Prénom :</label>
                    <input type="text" value="<?= h($profil_user['prenom']) ?>" readonly>
                </div>
                <?php if (!$vue_admin): ?><button class="edit" type="button">🖌️</button><?php endif; ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Adresse e-mail :</label>
                    <input type="email" value="<?= h($profil_user['email']) ?>" readonly>
                </div>
                <?php if (!$vue_admin): ?><button class="edit" type="button">🖌️</button><?php endif; ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Adresse :</label>
                    <input type="text" value="<?= h($profil_user['adresse'] ?? '') ?>" readonly>
                </div>
                <?php if (!$vue_admin): ?><button class="edit" type="button">🖌️</button><?php endif; ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Numéro de téléphone :</label>
                    <input type="tel" value="<?= h($profil_user['telephone'] ?? '') ?>" readonly>
                </div>
                <?php if (!$vue_admin): ?><button class="edit" type="button">🖌️</button><?php endif; ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Rôle :</label>
                    <input type="text" value="<?= h(libelle_role($profil_user['role'])) ?>" readonly>
                </div>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Statut du compte :</label>
                    <input type="text" value="<?= $profil_user['statut'] === 'actif' ? 'Actif' : 'Bloqué' ?>" readonly>
                </div>
            </div>

        </fieldset>
    </form>

    <!-- HISTORIQUE DES COMMANDES -->
    <section class="admin-utilisateurs-wrapper">
        <div class="admin-utilisateurs">
            <table class="admin-table">
                <caption class="titre-profil">Historique des commandes</caption>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Mode</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commandes_user)): ?>
                    <tr><td colspan="6" style="text-align:center;">Aucune commande pour le moment.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($commandes_user as $cmd): ?>
                    <tr>
                        <td>#<?= h($cmd['id']) ?></td>
                        <td><?= h(date('d/m/Y H:i', strtotime($cmd['date']))) ?></td>
                        <td><?= number_format($cmd['total'], 2) ?> €</td>
                        <td><?= $cmd['mode'] === 'livraison' ? '🚚 Livraison' : '🏠 Emporter' ?></td>
                        <td><?= h(libelle_statut($cmd['statut'])) ?></td>
                        <td>
                            <?php if ($cmd['statut'] === 'livree' && $cmd['note_produits'] === null && !$vue_admin): ?>
                                <a href="notation.php?commande=<?= h($cmd['id']) ?>" class="btn-primary">Noter</a>
                            <?php elseif ($cmd['statut'] === 'livree' && $cmd['note_produits'] !== null): ?>
                                <span><?= afficher_etoiles($cmd['note_produits']) ?></span>
                            <?php else: ?>
                                <em>En cours</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
