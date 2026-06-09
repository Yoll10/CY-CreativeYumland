<?php
require_once 'functions.php';
exiger_role('restaurateur');

$message = '';
$erreur  = '';

// ── TRAITEMENT POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── SUPPRIMER ──
    if ($action === 'supprimer') {
        $id = trim($_POST['plat_id'] ?? '');
        if ($id !== '') {
            $plats = get_plats();
            $nouveaux = array_values(array_filter($plats, fn($p) => $p['id'] !== $id));
            if (ecrire_json(PLATS_FILE, $nouveaux)) {
                $message = "Plat supprimé avec succès.";
            } else {
                $erreur = "Erreur lors de la suppression.";
            }
        }
    }

    // ── AJOUTER ──
    if ($action === 'ajouter') {
        $nom        = trim($_POST['nom']        ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $prix       = floatval($_POST['prix']   ?? 0);
        $categorie  = trim($_POST['categorie']  ?? '');
        $allergenes = trim($_POST['allergenes'] ?? '');
        $image      = trim($_POST['image']      ?? '');
        $calories   = intval($_POST['calories'] ?? 0);

        if ($nom === '' || $prix <= 0 || !in_array($categorie, ['entree','plat','dessert'])) {
            $erreur = "Veuillez remplir tous les champs obligatoires (nom, prix, catégorie).";
        } else {
            // Générer un id unique
            $plats  = get_plats();
            $max_id = 0;
            foreach ($plats as $p) {
                $num = intval(ltrim($p['id'], 'p'));
                if ($num > $max_id) $max_id = $num;
            }
            $nouvel_id = 'p' . str_pad($max_id + 1, 2, '0', STR_PAD_LEFT);

            $nouveau_plat = [
                'id'          => $nouvel_id,
                'nom'         => $nom,
                'description' => $desc,
                'prix'        => round($prix, 2),
                'categorie'   => $categorie,
                'allergenes'  => $allergenes !== '' ? $allergenes : 'Aucun',
                'image'       => $image !== '' ? $image : 'images/plats/default.jpg',
                'calories'    => $calories,
            ];

            $plats[] = $nouveau_plat;
            if (ecrire_json(PLATS_FILE, $plats)) {
                $message = "Plat \"$nom\" ajouté avec succès (ID : $nouvel_id).";
            } else {
                $erreur = "Erreur lors de l'ajout.";
            }
        }
    }

    // ── MODIFIER ──
    if ($action === 'modifier') {
        $id         = trim($_POST['plat_id']     ?? '');
        $nom        = trim($_POST['nom']         ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $prix       = floatval($_POST['prix']    ?? 0);
        $categorie  = trim($_POST['categorie']   ?? '');
        $allergenes = trim($_POST['allergenes']  ?? '');
        $image      = trim($_POST['image']       ?? '');
        $calories   = intval($_POST['calories']  ?? 0);

        if ($id === '' || $nom === '' || $prix <= 0 || !in_array($categorie, ['entree','plat','dessert'])) {
            $erreur = "Données invalides pour la modification.";
        } else {
            $plats = get_plats();
            $trouve = false;
            for ($i = 0; $i < count($plats); $i++) {
                if ($plats[$i]['id'] === $id) {
                    $plats[$i]['nom']         = $nom;
                    $plats[$i]['description'] = $desc;
                    $plats[$i]['prix']        = round($prix, 2);
                    $plats[$i]['categorie']   = $categorie;
                    $plats[$i]['allergenes']  = $allergenes !== '' ? $allergenes : 'Aucun';
                    $plats[$i]['image']       = $image !== '' ? $image : $plats[$i]['image'];
                    $plats[$i]['calories']    = $calories;
                    $trouve = true;
                    break;
                }
            }
            if ($trouve && ecrire_json(PLATS_FILE, $plats)) {
                $message = "Plat modifié avec succès.";
            } else {
                $erreur = "Erreur lors de la modification.";
            }
        }
    }
}

$plats_tous = get_plats();
$filtre_cat = $_GET['cat'] ?? 'tous';
if ($filtre_cat !== 'tous') {
    $plats_affiches = array_values(array_filter($plats_tous, fn($p) => $p['categorie'] === $filtre_cat));
} else {
    $plats_affiches = $plats_tous;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des plats — L'Étoile</title>
    <link rel="stylesheet" href="css/stylecommon.css">
    <link rel="stylesheet" href="css/stylegestionplats.css">
    <script src="js/scriptjs.js" defer></script>
    <script src="js/gestion-plats.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<main class="gp-page">

    <section class="gp-header">
        <h1>Gestion des plats</h1>
        <p>Ajouter, modifier ou supprimer des plats de la carte</p>
        <button class="btn-ajouter-plat" id="btn-ouvrir-ajout">＋ Ajouter un nouveau plat</button>
    </section>

    <?php if ($message !== ''): ?>
        <div class="gp-msg gp-succes">✅ <?= h($message) ?></div>
    <?php endif; ?>
    <?php if ($erreur !== ''): ?>
        <div class="gp-msg gp-erreur">❌ <?= h($erreur) ?></div>
    <?php endif; ?>

    <!-- Filtres catégorie -->
    <nav class="gp-filtres">
        <a href="gestion-plats.php?cat=tous"    class="<?= $filtre_cat === 'tous'    ? 'actif' : '' ?>">Tous (<?= count($plats_tous) ?>)</a>
        <a href="gestion-plats.php?cat=entree"  class="<?= $filtre_cat === 'entree'  ? 'actif' : '' ?>">Entrées</a>
        <a href="gestion-plats.php?cat=plat"    class="<?= $filtre_cat === 'plat'    ? 'actif' : '' ?>">Plats</a>
        <a href="gestion-plats.php?cat=dessert" class="<?= $filtre_cat === 'dessert' ? 'actif' : '' ?>">Desserts</a>
    </nav>

    <!-- Tableau des plats -->
    <div class="gp-table-wrapper">
        <table class="gp-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Allergènes</th>
                    <th>Calories</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plats_affiches)): ?>
                    <tr><td colspan="6" class="vide-td">Aucun plat.</td></tr>
                <?php endif; ?>
                <?php foreach ($plats_affiches as $plat): ?>
                <tr>
                    <td><code><?= h($plat['id']) ?></code></td>
                    <td>
                        <strong><?= h($plat['nom']) ?></strong>
                        <?php if (!empty($plat['description'])): ?>
                            <br><small><?= h(mb_substr($plat['description'], 0, 60)) ?>…</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-cat badge-<?= h($plat['categorie']) ?>"><?= h(ucfirst($plat['categorie'])) ?></span></td>
                    <td><?= number_format($plat['prix'], 2) ?> €</td>
                    <td><small><?= h($plat['allergenes']) ?></small></td>
                    <td><?= isset($plat['calories']) ? $plat['calories'] . ' kcal' : '—' ?></td>
                    <td>
                        <div class="gp-actions">
                            <button class="btn-edit-plat"
                                    data-id="<?= h($plat['id']) ?>"
                                    data-nom="<?= h($plat['nom']) ?>"
                                    data-description="<?= h($plat['description'] ?? '') ?>"
                                    data-prix="<?= h($plat['prix']) ?>"
                                    data-categorie="<?= h($plat['categorie']) ?>"
                                    data-allergenes="<?= h($plat['allergenes']) ?>"
                                    data-image="<?= h($plat['image'] ?? '') ?>"
                                    data-calories="<?= h($plat['calories'] ?? '') ?>">
                                ✏️ Modifier
                            </button>
                            <form method="POST"
                                  onsubmit="return confirm('Supprimer «<?= h($plat['nom']) ?>» ? Cette action est irréversible.')">
                                <input type="hidden" name="action"   value="supprimer">
                                <input type="hidden" name="plat_id"  value="<?= h($plat['id']) ?>">
                                <button type="submit" class="btn-del-plat">🗑️ Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- ═══ MODAL AJOUT ═══ -->
<div class="gp-modal-overlay" id="modal-ajout">
    <div class="gp-modal">
        <h2>Ajouter un plat</h2>
        <form method="POST">
            <input type="hidden" name="action" value="ajouter">
            <label>Nom <span class="req">*</span></label>
            <input type="text" name="nom" required placeholder="Ex : Risotto aux truffes">

            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Description courte du plat..."></textarea>

            <div class="gp-row">
                <div>
                    <label>Prix (€) <span class="req">*</span></label>
                    <input type="number" name="prix" step="0.01" min="0.01" required placeholder="Ex : 24.00">
                </div>
                <div>
                    <label>Catégorie <span class="req">*</span></label>
                    <select name="categorie" required>
                        <option value="">— Choisir —</option>
                        <option value="entree">Entrée</option>
                        <option value="plat">Plat</option>
                        <option value="dessert">Dessert</option>
                    </select>
                </div>
            </div>

            <label>Allergènes</label>
            <input type="text" name="allergenes" placeholder="Ex : gluten, lait, œufs">

            <label>Calories (kcal)</label>
            <input type="number" name="calories" min="0" placeholder="Ex : 450">

            <label>Chemin image</label>
            <input type="text" name="image" placeholder="images/plats/mon-plat.jpg">

            <div class="gp-modal-actions">
                <button type="button" class="btn-annuler" onclick="fermerModalGP('modal-ajout')">Annuler</button>
                <button type="submit" class="btn-sauver">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ MODAL MODIFICATION ═══ -->
<div class="gp-modal-overlay" id="modal-modif">
    <div class="gp-modal">
        <h2>Modifier le plat</h2>
        <form method="POST">
            <input type="hidden" name="action"  value="modifier">
            <input type="hidden" name="plat_id" id="modif-id">

            <label>Nom <span class="req">*</span></label>
            <input type="text" name="nom" id="modif-nom" required>

            <label>Description</label>
            <textarea name="description" id="modif-description" rows="3"></textarea>

            <div class="gp-row">
                <div>
                    <label>Prix (€) <span class="req">*</span></label>
                    <input type="number" name="prix" id="modif-prix" step="0.01" min="0.01" required>
                </div>
                <div>
                    <label>Catégorie <span class="req">*</span></label>
                    <select name="categorie" id="modif-categorie" required>
                        <option value="entree">Entrée</option>
                        <option value="plat">Plat</option>
                        <option value="dessert">Dessert</option>
                    </select>
                </div>
            </div>

            <label>Allergènes</label>
            <input type="text" name="allergenes" id="modif-allergenes">

            <label>Calories (kcal)</label>
            <input type="number" name="calories" id="modif-calories" min="0">

            <label>Chemin image</label>
            <input type="text" name="image" id="modif-image">

            <div class="gp-modal-actions">
                <button type="button" class="btn-annuler" onclick="fermerModalGP('modal-modif')">Annuler</button>
                <button type="submit" class="btn-sauver">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

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
