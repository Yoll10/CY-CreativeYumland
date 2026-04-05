<?php
require_once 'functions.php';
exiger_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $email = $_POST['email'];

    if ($action === 'modifier') {
        $champs = array();
        if ($_POST['nom'] !== '') {
            $champs['nom'] = trim($_POST['nom']);
        }
        if ($_POST['prenom'] !== '') {
            $champs['prenom'] = trim($_POST['prenom']);
        }
        if (isset($_POST['adresse'])) {
            $champs['adresse'] = trim($_POST['adresse']);
        }
        if ($_POST['telephone'] !== '') {
            $champs['telephone'] = trim($_POST['telephone']);
        }
        if ($_POST['role'] !== '' && $email !== $_SESSION['user']['email']) {
            $champs['role'] = $_POST['role'];
        }
        if (count($champs) > 0) {
            update_user($email, $champs);
            if ($email === $_SESSION['user']['email']) {
                $_SESSION['user'] = get_user_by_email($email);
            }
        }
    }

    if ($email !== '' && $email !== $_SESSION['user']['email']) {
        if ($action === 'bloquer') {
            update_user($email, array('statut' => 'bloque'));
        } else if ($action === 'debloquer') {
            update_user($email, array('statut' => 'actif'));
        } else if ($action === 'supprimer') {
            delete_user($email);
        } else if ($action === 'promouvoir') {
            update_user($email, array('role' => 'admin'));
        }
    }

    if (isset($_GET['filtre'])) {
        header('Location: admin.php?filtre=' . $_GET['filtre']);
    } else {
        header('Location: admin.php');
    }
    exit();
}

$filtre = 'tous';
if (isset($_GET['filtre'])) {
    $filtre = $_GET['filtre'];
}

if ($filtre === 'commandes') {
    $users = get_users_ayant_commande();
} else {
    $users = get_users();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Administrateur — L'Étoile</title>
    <link rel="stylesheet" href="stylecommon.css">
    <link rel="stylesheet" href="styleadmin.css">
</head>
<body>

<?php include 'header.php'; ?>

<main class="admin">
    <section class="admin-header">
        <h1>Espace Administrateur</h1>
        <p>Gestion des utilisateurs et suivi des comptes</p>
    </section>

    <section class="admin-filtres">
        <a href="admin.php?filtre=tous" class="<?php if ($filtre === 'tous') { echo 'active'; } ?>">
            Tous les utilisateurs (<?= count(get_users()) ?>)
        </a>
        <a href="admin.php?filtre=commandes" class="<?php if ($filtre === 'commandes') { echo 'active'; } ?>">
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
                    <tr class="<?php if ($u['statut'] === 'bloque') { echo 'user-bloque'; } ?>">
                        <td><?= h($u['nom']) ?> <?= h($u['prenom']) ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><?= h(libelle_role($u['role'])) ?></td>
                        <td>
                            <span class="badge-statut statut-<?= h($u['statut']) ?>">
                                <?php if ($u['statut'] === 'actif') { ?>
                                    Actif
                                <?php } else { ?>
                                    Bloqué
                                <?php } ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            if (isset($u['date_inscription'])) {
                                echo h($u['date_inscription']);
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>

                        <td>
                            <div class="actions-menu">
                                <button class="actions-btn">Actions ▾</button>
                                <div class="actions-dropdown">

                                    <button type="button" class="action edit-btn"
                                            onclick="ouvrirModal(
                                                '<?= h($u['email']) ?>',
                                                '<?= h($u['nom']) ?>',
                                                '<?= h($u['prenom']) ?>',
                                                '<?php if (isset($u['adresse'])) { echo h($u['adresse']); } ?>',
                                                '<?php if (isset($u['telephone'])) { echo h($u['telephone']); } ?>',
                                                '<?= h($u['role']) ?>'
                                            )">Modifier</button>

                                    <?php if ($u['email'] !== $_SESSION['user']['email']) { ?>

                                        <?php if ($u['statut'] === 'actif') { ?>
                                            <form method="POST" style="display:block;">
                                                <input type="hidden" name="email" value="<?= h($u['email']) ?>">
                                                <input type="hidden" name="action" value="bloquer">
                                                <button type="submit" class="action block">Bloquer</button>
                                            </form>
                                        <?php } else { ?>
                                            <form method="POST" style="display:block;">
                                                <input type="hidden" name="email" value="<?= h($u['email']) ?>">
                                                <input type="hidden" name="action" value="debloquer">
                                                <button type="submit" class="action promote">Débloquer</button>
                                            </form>
                                        <?php } ?>

                                        <?php if ($u['role'] !== 'admin') { ?>
                                            <form method="POST" style="display:block;">
                                                <input type="hidden" name="email" value="<?= h($u['email']) ?>">
                                                <input type="hidden" name="action" value="promouvoir">
                                                <button type="submit" class="action promote">Promouvoir Admin</button>
                                            </form>
                                        <?php } ?>

                                        <form method="POST" style="display:block;" onsubmit="return confirm('Supprimer cet utilisateur ?');">
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

                        <td>
                            <a href="profil.php?email=<?= urlencode($u['email']) ?>">Voir</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal-overlay" id="modal-modif">
    <div class="modal-box">
        <h2> Modifier l'utilisateur</h2>
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

<script>
function ouvrirModal(email, nom, prenom, adresse, telephone, role) {
    document.getElementById('modal-email').value = email;
    document.getElementById('modal-nom').value = nom;
    document.getElementById('modal-prenom').value = prenom;
    document.getElementById('modal-adresse').value = adresse;
    document.getElementById('modal-telephone').value = telephone;
    document.getElementById('modal-role').value = role;
    document.getElementById('modal-modif').classList.add('actif');
}

function fermerModal() {
    document.getElementById('modal-modif').classList.remove('actif');
}

document.getElementById('modal-modif').addEventListener('click', function(e) {
    if (e.target === this) {
        fermerModal();
    }
});
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
