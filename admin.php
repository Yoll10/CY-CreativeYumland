<?php
require_once 'functions.php';
exiger_role('admin');

// Actions POST (bloquer, débloquer, supprimer, promouvoir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']  ?? '';
    $email  = $_POST['email']   ?? '';

    if ($email && $email !== $_SESSION['user']['email']) { // protection : ne pas s'auto-modifier
        switch ($action) {
            case 'bloquer':
                update_user($email, ['statut' => 'bloque']);
                break;
            case 'debloquer':
                update_user($email, ['statut' => 'actif']);
                break;
            case 'supprimer':
                delete_user($email);
                break;
            case 'promouvoir':
                update_user($email, ['role' => 'admin']);
                break;
        }
    }
    header('Location: admin.php' . (isset($_GET['filtre']) ? '?filtre=' . $_GET['filtre'] : ''));
    exit();
}

// Filtre
$filtre = $_GET['filtre'] ?? 'tous';
$users  = ($filtre === 'commandes') ? get_users_ayant_commande() : get_users();
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
        <a href="admin.php?filtre=tous"
           class="<?= $filtre === 'tous' ? 'active' : '' ?>">
            Tous les utilisateurs (<?= count(get_users()) ?>)
        </a>
        <a href="admin.php?filtre=commandes"
           class="<?= $filtre === 'commandes' ? 'active' : '' ?>">
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
                <?php if (empty($users)): ?>
                <tr><td colspan="7" style="text-align:center;">Aucun utilisateur trouvé.</td></tr>
                <?php endif; ?>

                <?php foreach ($users as $u): ?>
                <tr class="<?= $u['statut'] === 'bloque' ? 'user-bloque' : '' ?>">
                    <td><?= h($u['nom'] . ' ' . $u['prenom']) ?></td>
                    <td><?= h($u['email']) ?></td>
                    <td><?= h(libelle_role($u['role'])) ?></td>
                    <td>
                        <span class="badge-statut statut-<?= h($u['statut']) ?>">
                            <?= $u['statut'] === 'actif' ? 'Actif' : 'Bloqué' ?>
                        </span>
                    </td>
                    <td><?= h($u['date_inscription'] ?? '—') ?></td>

                    <td>
                        <?php if ($u['email'] !== $_SESSION['user']['email']): ?>
                        <div class="actions-menu">
                            <button class="actions-btn">Actions</button>
                            <div class="actions-dropdown">

                                <?php if ($u['statut'] === 'actif'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="email"  value="<?= h($u['email']) ?>">
                                    <input type="hidden" name="action" value="bloquer">
                                    <button type="submit" class="action block">Bloquer</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="email"  value="<?= h($u['email']) ?>">
                                    <input type="hidden" name="action" value="debloquer">
                                    <button type="submit" class="action promote">Débloquer</button>
                                </form>
                                <?php endif; ?>

                                <?php if ($u['role'] !== 'admin'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="email"  value="<?= h($u['email']) ?>">
                                    <input type="hidden" name="action" value="promouvoir">
                                    <button type="submit" class="action promote">Promouvoir Admin</button>
                                </form>
                                <?php endif; ?>

                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    <input type="hidden" name="email"  value="<?= h($u['email']) ?>">
                                    <input type="hidden" name="action" value="supprimer">
                                    <button type="submit" class="action delete">Supprimer</button>
                                </form>

                            </div>
                        </div>
                        <?php else: ?>
                            <em>(vous-même)</em>
                        <?php endif; ?>
                    </td>

                    <td>
                        <a href="profil.php?email=<?= urlencode($u['email']) ?>">Voir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

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
