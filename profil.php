<?php
require_once 'functions.php';
exiger_connexion();

if (est_admin() && isset($_GET['email'])) {
    $profil_user = get_user_by_email($_GET['email']);
    if ($profil_user === null) {
        header('Location: admin.php');
        exit();
    }
} else {
    $profil_user = utilisateur_courant();
}

$commandes_user = get_commandes_user($profil_user['email']);

$vue_admin = false;
if (est_admin() && isset($_GET['email']) && $_GET['email'] !== $_SESSION['user']['email']) {
    $vue_admin = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — <?= h($profil_user['prenom']) ?> <?= h($profil_user['nom']) ?></title>
    <link href="css/styleprofil.css" rel="stylesheet">
    <link rel="stylesheet" href="css/stylecommon.css">
    
    <script src="js/scriptjs.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<main class="main-content">

    <?php if ($vue_admin) { ?>
        <div style="background:#f5e6c8; padding:0.8rem 1.5rem; border-left: 4px solid #b8860b; margin-bottom:1rem; width:100%;">
            <strong>Mode Admin</strong> — Vous consultez le profil de <?= h($profil_user['prenom']) ?> <?= h($profil_user['nom']) ?>
            <a href="admin.php" style="margin-left:1rem;">← Retour</a>
        </div>
    <?php } ?>

    <?php if (!$vue_admin) { ?>
        <div id="msg-profil" style="width:100%;"></div>
    <?php } ?>

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
                    <input type="text" data-champ="nom" value="<?= h($profil_user['nom']) ?>" readonly>
                    <span class="msg-erreur-champ" id="err-champ-nom"></span>
                </div>
                <?php if (!$vue_admin) { ?><button class="edit" type="button" title="Modifier">🖌️</button><?php } ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Prénom :</label>
                    <input type="text" data-champ="prenom" value="<?= h($profil_user['prenom']) ?>" readonly>
                    <span class="msg-erreur-champ" id="err-champ-prenom"></span>
                </div>
                <?php if (!$vue_admin) { ?><button class="edit" type="button" title="Modifier">🖌️</button><?php } ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Adresse e-mail :</label>
                    <input type="email" data-champ="email" value="<?= h($profil_user['email']) ?>" readonly>
                    <span class="msg-erreur-champ" id="err-champ-email"></span>
                </div>
                <?php if (!$vue_admin) { ?><button class="edit" type="button" title="Modifier">🖌️</button><?php } ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Adresse :</label>
                    <input type="text" data-champ="adresse"
                           value="<?= h(isset($profil_user['adresse']) ? $profil_user['adresse'] : '') ?>" readonly>
                    <span class="msg-erreur-champ" id="err-champ-adresse"></span>
                </div>
                <?php if (!$vue_admin) { ?><button class="edit" type="button" title="Modifier">🖌️</button><?php } ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Numéro de téléphone :</label>
                    <input type="tel" data-champ="telephone"
                           value="<?= h(isset($profil_user['telephone']) ? $profil_user['telephone'] : '') ?>" readonly>
                    <span class="msg-erreur-champ" id="err-champ-telephone"></span>
                </div>
                <?php if (!$vue_admin) { ?><button class="edit" type="button" title="Modifier">🖌️</button><?php } ?>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Rôle :</label>
                    <input type="text" value="<?= h(libelle_role($profil_user['role'])) ?>" readonly>
                    <small style="color:var(--gris-texte); font-size:0.75rem;">Non modifiable</small>
                </div>
            </div>

            <div class="ligne-form">
                <div class="zone-contenu">
                    <label>Statut du compte :</label>
                    <input type="text" value="<?= $profil_user['statut'] === 'actif' ? 'Actif' : 'Bloqué' ?>" readonly>
                    <small style="color:var(--gris-texte); font-size:0.75rem;">Non modifiable</small>
                </div>
            </div>

        </fieldset>
    </form>

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
                    <?php if (count($commandes_user) === 0) { ?>
                        <tr><td colspan="6" style="text-align:center;">Aucune commande pour le moment.</td></tr>
                    <?php } ?>

                    <?php foreach ($commandes_user as $cmd) { ?>
                        <tr>
                            <td>#<?= h($cmd['id']) ?></td>
                            <td><?= h(date('d/m/Y H:i', strtotime($cmd['date']))) ?></td>
                            <td><?= number_format($cmd['total'], 2) ?> €</td>
                            <td>
                                <?php if ($cmd['mode'] === 'livraison') { ?>🚚 Livraison
                                <?php } else { ?>🏠 Emporter<?php } ?>
                            </td>
                            <td><?= h(libelle_statut($cmd['statut'])) ?></td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:6px;">
                                <?php if ($cmd['statut'] === 'en_attente' && !$vue_admin) { ?>
                                    <a href="modifier_commande.php?id=<?= h($cmd['id']) ?>" class="btn-modifier-commande">✏️ Modifier</a>
                                <?php } else if ($cmd['statut'] === 'livree' && $cmd['note_produits'] === null && !$vue_admin) { ?>
                                    <a href="notation.php?commande=<?= h($cmd['id']) ?>" class="btn-primary">⭐ Noter</a>
                                <?php } else if ($cmd['statut'] === 'livree' && $cmd['note_produits'] !== null) { ?>
                                    <span><?= afficher_etoiles($cmd['note_produits']) ?></span>
                                <?php } else { ?>
                                    <em>En cours</em>
                                <?php } ?>
                                <?php if (!$vue_admin): ?>
                                    <a href="commande-template.php?recommander=<?= h($cmd['id']) ?>"
                                       class="btn-recommander"
                                       onclick="return confirm('Ajouter les plats de cette commande dans votre panier ?')">
                                        🔁 Recommander
                                    </a>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-column"><h3>Contact</h3><p>06 49 00 96 90</p><p>supportclient@gmail.com</p><p>10 chemin de la Vie, Paris</p></div>
        <div class="footer-column"><h3>Récompenses</h3><p>3 étoiles Michelin</p><p>Maître Restaurateur</p></div>
        <div class="footer-column"><h3>Horaires</h3><p>Lundi – Vendredi : 10h – 19h</p><p>Samedi & Dimanche : Fermé</p></div>
    </div>
    <div class="footer-legal"><p>© 2026 — L'Étoile. Tous droits réservés.</p><p>Mentions légales</p></div>
</footer>


</body>
</html>
