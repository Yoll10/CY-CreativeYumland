<?php
require_once 'functions.php';
exiger_role('admin');

// ============================================================
// ACTIONS POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email  = $_POST['email']  ?? '';

    // -- Modifier les infos d'un utilisateur (y compris soi-même) --
    if ($action === 'modifier') {
        $champs = [];
        if (!empty($_POST['nom']))      $champs['nom']      = trim($_POST['nom']);
        if (!empty($_POST['prenom']))   $champs['prenom']   = trim($_POST['prenom']);
        if (isset($_POST['adresse']))   $champs['adresse']  = trim($_POST['adresse']);
        if (!empty($_POST['telephone'])) $champs['telephone'] = trim($_POST['telephone']);
        if (!empty($_POST['role']) && $email !== $_SESSION['user']['email']) {
            $champs['role'] = $_POST['role']; // on ne peut pas changer son propre rôle
        }
        if (!empty($champs)) {
            update_user($email, $champs);
            // Si c'est l'utilisateur connecté, mettre à jour la session
            if ($email === $_SESSION['user']['email']) {
                $_SESSION['user'] = get_user_by_email($email);
            }
        }
    }

    // -- Actions rapides (bloquer, débloquer, supprimer, promouvoir) --
    if ($email && $email !== $_SESSION['user']['email']) {
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

    $filtre_redirect = isset($_GET['filtre']) ? '?filtre=' . $_GET['filtre'] : '';
    header('Location: admin.php' . $filtre_redirect);
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
    <style>
        /* ---- BOUTON ACTIONS centré et stylé ---- */
        .actions-menu {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .actions-btn {
            background: #2c2c2c;
            color: #fff;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            letter-spacing: 0.04em;
            transition: background 0.2s;
        }

        .actions-btn:hover { background: #444; }

        .actions-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
            min-width: 160px;
            z-index: 100;
            overflow: hidden;
        }

        .actions-menu:hover .actions-dropdown,
        .actions-menu:focus-within .actions-dropdown {
            display: block;
        }

        .actions-dropdown button,
        .actions-dropdown .action {
            display: block;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 0.6rem 1rem;
            cursor: pointer;
            font-size: 0.9rem;
            color: #222;
            transition: background 0.15s;
            text-decoration: none;
        }

        .actions-dropdown button:hover,
        .actions-dropdown .action:hover { background: #f5f5f5; }

        .actions-dropdown button.block  { color: #c0392b; }
        .actions-dropdown button.delete { color: #c0392b; font-weight:bold; }
        .actions-dropdown button.promote { color: #2980b9; }
        .actions-dropdown button.edit-btn { color: #27ae60; }

        /* ---- MODAL MODIFICATION ---- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 200;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.actif { display: flex; }

        .modal-box {
            background: #fff;
            border-radius: 10px;
            padding: 2rem;
            width: 90%;
            max-width: 480px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }

        .modal-box h2 {
            margin-top: 0;
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.6rem;
        }

        .modal-box label {
            display: block;
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 0.2rem;
            margin-top: 0.8rem;
        }

        .modal-box input,
        .modal-box select {
            width: 100%;
            padding: 0.5rem 0.7rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.8rem;
            margin-top: 1.5rem;
        }

        .modal-actions button {
            padding: 0.5rem 1.2rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-annuler { background: #eee; color: #333; }
        .btn-sauver  { background: #2c2c2c; color: #fff; }
        .btn-annuler:hover { background: #ddd; }
        .btn-sauver:hover  { background: #444; }

        /* ---- BADGE STATUT ---- */
        .badge-statut { padding: 0.2rem 0.6rem; border-radius: 12px; font-size:0.8rem; font-weight:bold; }
        .statut-actif  { background:#d4edda; color:#155724; }
        .statut-bloque { background:#f8d7da; color:#721c24; }
        .user-bloque td { opacity: 0.65; }
    </style>
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
                        <div class="actions-menu">
                            <button class="actions-btn">Actions ▾</button>
                            <div class="actions-dropdown">

                                <!-- Modifier (disponible pour tout le monde y compris soi-même) -->
                                <button type="button" class="action edit-btn"
                                        onclick="ouvrirModal(
                                            <?= json_encode($u['email']) ?>,
                                            <?= json_encode($u['nom']) ?>,
                                            <?= json_encode($u['prenom']) ?>,
                                            <?= json_encode($u['adresse'] ?? '') ?>,
                                            <?= json_encode($u['telephone'] ?? '') ?>,
                                            <?= json_encode($u['role']) ?>
                                        )">
                                    ✏️ Modifier
                                </button>

                                <?php if ($u['email'] !== $_SESSION['user']['email']): ?>

                                    <?php if ($u['statut'] === 'actif'): ?>
                                    <form method="POST" style="display:block;">
                                        <input type="hidden" name="email"  value="<?= h($u['email']) ?>">
                                        <input type="hidden" name="action" value="bloquer">
                                        <button type="submit" class="action block">🚫 Bloquer</button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="display:block;">
                                        <input type="hidden" name="email"  value="<?= h($u['email']) ?>">
                                        <input type="hidden" name="action" value="debloquer">
                                        <button type="submit" class="action promote">✅ Débloquer</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($u['role'] !== 'admin'): ?>
                                    <form method="POST" style="display:block;">
                                        <input type="hidden" name="email"  value="<?= h($u['email']) ?>">
                                        <input type="hidden" name="action" value="promouvoir">
                                        <button type="submit" class="action promote">⬆️ Promouvoir Admin</button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display:block;"
                                          onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');">
                                        <input type="hidden" name="email"  value="<?= h($u['email']) ?>">
                                        <input type="hidden" name="action" value="supprimer">
                                        <button type="submit" class="action delete">🗑️ Supprimer</button>
                                    </form>

                                <?php else: ?>
                                    <span style="padding:0.5rem 1rem; display:block; color:#888; font-size:0.8rem;">(vous-même)</span>
                                <?php endif; ?>

                            </div>
                        </div>
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

<!-- ============================================================ -->
<!-- MODAL MODIFICATION UTILISATEUR                               -->
<!-- ============================================================ -->
<div class="modal-overlay" id="modal-modif">
    <div class="modal-box">
        <h2>✏️ Modifier l'utilisateur</h2>
        <form method="POST" action="admin.php<?= $filtre !== 'tous' ? '?filtre='.$filtre : '' ?>">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="email"  id="modal-email" value="">

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
            <small style="color:#888; font-size:0.75rem;">
                (Le rôle ne peut pas être modifié pour votre propre compte.)
            </small>

            <div class="modal-actions">
                <button type="button" class="btn-annuler" onclick="fermerModal()">Annuler</button>
                <button type="submit" class="btn-sauver">💾 Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function ouvrirModal(email, nom, prenom, adresse, telephone, role) {
    document.getElementById('modal-email').value     = email;
    document.getElementById('modal-nom').value       = nom;
    document.getElementById('modal-prenom').value    = prenom;
    document.getElementById('modal-adresse').value   = adresse;
    document.getElementById('modal-telephone').value = telephone;
    document.getElementById('modal-role').value      = role;
    document.getElementById('modal-modif').classList.add('actif');
}

function fermerModal() {
    document.getElementById('modal-modif').classList.remove('actif');
}

// Fermer en cliquant à l'extérieur
document.getElementById('modal-modif').addEventListener('click', function(e) {
    if (e.target === this) fermerModal();
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
