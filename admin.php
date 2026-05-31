<?php
require_once 'functions.php';
exiger_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $email  = $_POST['email'];

    if ($action === 'modifier') {
        $champs = array();
        if ($_POST['nom']    !== '') $champs['nom']    = trim($_POST['nom']);
        if ($_POST['prenom'] !== '') $champs['prenom'] = trim($_POST['prenom']);
        if (isset($_POST['adresse']))   $champs['adresse']   = trim($_POST['adresse']);
        if ($_POST['telephone'] !== '') $champs['telephone'] = trim($_POST['telephone']);
        if ($_POST['role'] !== '' && $email !== $_SESSION['user']['email']) $champs['role'] = $_POST['role'];
        if (count($champs) > 0) {
            update_user($email, $champs);
            if ($email === $_SESSION['user']['email']) $_SESSION['user'] = get_user_by_email($email);
        }
    }

    if ($email !== '' && $email !== $_SESSION['user']['email']) {
        if ($action === 'supprimer')  delete_user($email);
        else if ($action === 'promouvoir') update_user($email, array('role' => 'admin'));
    }

    header('Location: admin.php' . (isset($_GET['filtre']) ? '?filtre=' . $_GET['filtre'] : ''));
    exit();
}

$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'tous';
$users  = ($filtre === 'commandes') ? get_users_ayant_commande() : get_users();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Administrateur — L'Étoile</title>
    <link rel="stylesheet" href="css/stylecommon.css">
    <link rel="stylesheet" href="css/styleadmin.css">
    <script src="js/scriptjs.js" defer></script>
    <script src="js/admin.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<main class="admin">
    <section class="admin-header">
        <h1>Espace Administrateur</h1>
        <p>Gestion des utilisateurs et suivi des comptes</p>
    </section>

    <section class="admin-filtres">
        <a href="admin.php?filtre=tous" class="<?php if ($filtre === 'tous') echo 'active'; ?>">
            Tous les utilisateurs (<?= count(get_users()) ?>)
        </a>
        <a href="admin.php?filtre=commandes" class="<?php if ($filtre === 'commandes') echo 'active'; ?>">
            Ayant passé commande (<?= count(get_users_ayant_commande()) ?>)
        </a>
    </section>
</main>

<section class="admin-utilisateurs-wrapper">
    <div class="admin-utilisateurs">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Inscription</th>
                    <th>Actions</th>
                    <th>Profil</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) === 0) { ?>
                    <tr><td colspan="7" style="text-align:center;">Aucun utilisateur trouvé.</td></tr>
                <?php } ?>

                <?php foreach ($users as $u) { ?>
                    <tr class="<?php if ($u['statut'] === 'bloque') echo 'user-bloque'; ?>"
                        data-email="<?= h($u['email']) ?>">
                        <td><?= h($u['nom']) ?> <?= h($u['prenom']) ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><?= h(libelle_role($u['role'])) ?></td>
                        <td>
                            <span class="badge-statut statut-<?= h($u['statut']) ?>">
                                <?= $u['statut'] === 'actif' ? 'Actif' : 'Bloqué' ?>
                            </span>
                        </td>
                        <td><?= isset($u['date_inscription']) ? h($u['date_inscription']) : '—' ?></td>

                        <td>
                            <div class="actions-menu">
                                <button class="actions-btn">Actions ▾</button>
                                <div class="actions-dropdown">

                                    
                                    <button type="button" class="action edit-btn"
                                            onclick="ouvrirModal(
                                                '<?= h($u['email']) ?>',
                                                '<?= h($u['nom']) ?>',
                                                '<?= h($u['prenom']) ?>',
                                                '<?= isset($u['adresse']) ? h($u['adresse']) : '' ?>',
                                                '<?= isset($u['telephone']) ? h($u['telephone']) : '' ?>',
                                                '<?= h($u['role']) ?>'
                                            )">Modifier</button>

                                    <?php if ($u['email'] !== $_SESSION['user']['email']) { ?>

                                        <?php if ($u['statut'] === 'actif') { ?>
                                            <button type="button"
                                                    class="action block btn-bloquer-async"
                                                    data-email="<?= h($u['email']) ?>"
                                                    data-action="bloquer"
                                                    style="display:inline-block;">Bloquer</button>
                                            <button type="button"
                                                    class="action promote btn-debloquer-async"
                                                    data-email="<?= h($u['email']) ?>"
                                                    data-action="debloquer"
                                                    style="display:none;">Débloquer</button>
                                        <?php } else { ?>
                                            <button type="button"
                                                    class="action block btn-bloquer-async"
                                                    data-email="<?= h($u['email']) ?>"
                                                    data-action="bloquer"
                                                    style="display:none;">Bloquer</button>
                                            <button type="button"
                                                    class="action promote btn-debloquer-async"
                                                    data-email="<?= h($u['email']) ?>"
                                                    data-action="debloquer"
                                                    style="display:inline-block;">Débloquer</button>
                                        <?php } ?>

                                        <?php if ($u['role'] !== 'admin') { ?>
                                            <form method="POST" style="display:block;">
                                                <input type="hidden" name="email" value="<?= h($u['email']) ?>">
                                                <input type="hidden" name="action" value="promouvoir">
                                                <button type="submit" class="action promote">Promouvoir Admin</button>
                                            </form>
                                        <?php } ?>

                                        <form method="POST" style="display:block;"
                                              onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                            <input type="hidden" name="email" value="<?= h($u['email']) ?>">
                                            <input type="hidden" name="action" value="supprimer">
                                            <button type="submit" class="action delete">Supprimer</button>
                                        </form>

                                    <?php } else { ?>
                                        <span style="padding:0.5rem 1rem; display:block; color:#888; font-size:0.8rem;">(vous-même)</span>
                                    <?php } ?>

                                </div>
                            </div>
                        </td>

                        <td><a href="profil.php?email=<?= urlencode($u['email']) ?>">Voir</a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal-overlay" id="modal-modif">
    <div class="modal-box">
        <h2>Modifier l'utilisateur</h2>
        <form method="POST">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="email" id="modal-email" value="">
            <label>Nom</label>
            <input type="text" name="nom" id="modal-nom" placeholder="Nom">
            <label>Prénom</label>
            <input type="text" name="prenom" id="modal-prenom" placeholder="Prénom">
            <label>Adresse</label>
            <input type="text" name="adresse" id="modal-adresse" placeholder="Adresse de livraison">
            <label>Téléphone</label>
            <input type="tel" name="telephone" id="modal-telephone" placeholder="06 00 00 00 00">
            <label>Rôle</label>
            <select name="role" id="modal-role">
                <option value="client">Client</option>
                <option value="admin">Administrateur</option>
                <option value="restaurateur">Restaurateur</option>
                <option value="livreur">Livreur</option>
            </select>
            <small style="color:#888; font-size:0.75rem;">Le rôle ne peut pas être changé sur votre propre compte.</small>
            <div class="modal-actions">
                <button type="button" class="btn-annuler" onclick="fermerModal()">Annuler</button>
                <button type="submit" class="btn-sauver">Enregistrer</button>
            </div>
        </form>
    </div>
</div>


<section class="admin-logs-wrapper">
    <div class="admin-logs">
        <h2>📋 Journal des incidents
            <span style="font-size:0.8rem; font-weight:normal; color:var(--gris-texte);">
                (50 derniers événements)
            </span>
        </h2>

        <?php
        $logs = array_reverse(get_logs());
        $logs = array_slice($logs, 0, 50);
        ?>

        <?php if (empty($logs)): ?>
            <p style="text-align:center; padding:2rem; color:var(--gris-texte);">Aucun incident enregistré.</p>
        <?php else: ?>
        <table class="admin-table" style="margin-top:1rem;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Détails</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <?php
                $badge_class = match($log['type']) {
                    'mauvais_mdp'              => 'log-warning',
                    'ip_bloquee'               => 'log-danger',
                    'connexion_compte_bloque'  => 'log-danger',
                    'compte_bloque'            => 'log-danger',
                    'compte_debloque'          => 'log-success',
                    default                    => 'log-info'
                };
                $label = match($log['type']) {
                    'mauvais_mdp'              => '🔑 Mauvais mot de passe',
                    'ip_bloquee'               => '🚫 IP bloquée',
                    'connexion_compte_bloque'  => '⛔ Compte bloqué tenté',
                    'compte_bloque'            => '🔒 Compte bloqué',
                    'compte_debloque'          => '🔓 Compte débloqué',
                    default                    => $log['type']
                };
                ?>
                <tr>
                    <td style="white-space:nowrap; font-size:0.82rem;"><?= h($log['date']) ?></td>
                    <td><span class="badge-log <?= $badge_class ?>"><?= $label ?></span></td>
                    <td style="font-size:0.85rem;"><?= h($log['details']) ?></td>
                    <td style="font-size:0.82rem; color:var(--gris-texte);"><?= h($log['ip']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</section>

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
