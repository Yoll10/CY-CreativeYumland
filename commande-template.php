<?php
require_once 'functions.php';
require_once 'getapikey.php'; 

define('CYBANK_VENDEUR', 'MI-4_E');                                         
define('CYBANK_URL', 'https://www.plateforme-smc.fr/cybank/index.php'); 

exiger_connexion();

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$erreur_cmd = '';
$succes_cmd = '';

if (isset($_GET['cmd_id'], $_GET['transaction'], $_GET['status'],
          $_GET['montant'], $_GET['vendeur'], $_GET['control'])) {

    $cmd_id      = $_GET['cmd_id'];
    $transaction = $_GET['transaction'];
    $montant_ret = $_GET['montant'];
    $vendeur_ret = $_GET['vendeur'];
    $statut_ret  = $_GET['status'];
    $control_recu = $_GET['control'];

    $api_key        = getAPIKey($vendeur_ret);
    $control_calcul = md5($api_key
                        . "#" . $transaction
                        . "#" . $montant_ret
                        . "#" . $vendeur_ret
                        . "#" . $statut_ret . "#");

    if (preg_match('/^[0-9a-zA-Z]{15}$/', $api_key)
        && ($control_calcul == $control_recu)) {

        if ($statut_ret === 'accepted') {
            update_statut_commande($cmd_id, 'en_attente');
            $_SESSION['panier'] = [];
            $succes_cmd = "Paiement accepté ! Votre commande #" . $cmd_id
                        . " est confirmée et en cours de préparation.";
        } else {
            update_statut_commande($cmd_id, 'paiement_refuse');
            $erreur_cmd = "Paiement refusé pour la commande #" . $cmd_id
                        . ". Veuillez réessayer ou contacter votre banque.";
        }

    } else {
        $erreur_cmd = "Erreur de contrôle CYBank : les données de retour sont invalides.";
    }
}

// POINT 1 — Redirection vers CYBank avec auto-submit du formulaire
if (isset($_GET['payer']) && !empty($_SESSION['cybank_pending'])) {
    $params = $_SESSION['cybank_pending'];
    unset($_SESSION['cybank_pending']);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Redirection vers le paiement sécurisé</title>
</head>
<body>
    <p>Vous allez être redirigé vers l'interface de paiement sécurisé CYBank...</p>
    <form method="POST" action="<?= CYBANK_URL ?>" id="form-cybank">
        <input type="hidden" name="transaction" value="<?= h($params['transaction']) ?>">
        <input type="hidden" name="montant"     value="<?= h($params['montant']) ?>">
        <input type="hidden" name="vendeur"     value="<?= h($params['vendeur']) ?>">
        <input type="hidden" name="retour"      value="<?= h($params['retour']) ?>">
        <input type="hidden" name="control"     value="<?= h($params['control']) ?>">
        <button type="submit">Continuer vers le paiement →</button>
    </form>
    <script>
        document.getElementById('form-cybank').submit();
    </script>
</body>
</html>
    <?php
    exit();
}

if (isset($_GET['ajouter'])) {
    $id   = $_GET['ajouter'];
    $plat = get_plat_by_id($id);
    if ($plat) {
        if (isset($_SESSION['panier'][$id])) {
            $_SESSION['panier'][$id]['quantite']++;
        } else {
            $_SESSION['panier'][$id] = [
                'id'       => $plat['id'],
                'nom'      => $plat['nom'],
                'prix'     => $plat['prix'],
                'calories' => $plat['calories'] ?? 0,
                'quantite' => 1
            ];
        }
    }
    header('Location: commande-template.php');
    exit();
}

if (isset($_GET['menu'])) {
    $menu = get_menu_by_id($_GET['menu']);
    if ($menu) {
        $menu_key = 'menu_' . $menu['id'];
        if (isset($_SESSION['panier'][$menu_key])) {
            $_SESSION['panier'][$menu_key]['quantite']++;
        } else {
            $_SESSION['panier'][$menu_key] = [
                'id'       => $menu_key,
                'nom'      => $menu['nom'] . ' (menu)',
                'prix'     => $menu['prix'],
                'calories' => $menu['calories'] ?? 0,
                'quantite' => 1
            ];
        }
    }
    header('Location: commande-template.php');
    exit();
}

if (isset($_GET['vider'])) {
    $_SESSION['panier'] = [];
    header('Location: commande-template.php');
    exit();
}

// Recommander une ancienne commande : remet tous ses plats dans le panier
if (isset($_GET['recommander'])) {
    $cmd_rec = get_commande_by_id($_GET['recommander']);
    if ($cmd_rec !== null && $cmd_rec['user_email'] === $_SESSION['user']['email']) {
        foreach ($cmd_rec['plats'] as $item) {
            $key = $item['id'];
            if (strpos($key, 'menu_') === 0) {
                // Menu : on essaie de récupérer les calories depuis menus.json, sinon 0
                $menu_id = substr($key, 5);
                $menu_data = get_menu_by_id($menu_id);
                $calories = $menu_data['calories'] ?? 0;
            } else {
                // Plat : on essaie de récupérer les calories depuis plats.json, sinon 0
                $plat_data = get_plat_by_id($key);
                $calories = $plat_data['calories'] ?? 0;
            }
            if (isset($_SESSION['panier'][$key])) {
                $_SESSION['panier'][$key]['quantite'] += $item['quantite'];
            } else {
                $_SESSION['panier'][$key] = [
                    'id'       => $item['id'],
                    'nom'      => $item['nom'],
                    'prix'     => $item['prix'],
                    'calories' => $calories,
                    'quantite' => $item['quantite']
                ];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['ajouter_plat'])) {
        $id   = $_POST['ajouter_plat'];
        $plat = get_plat_by_id($id);
        if ($plat) {
            if (isset($_SESSION['panier'][$id])) {
                $_SESSION['panier'][$id]['quantite']++;
            } else {
                $_SESSION['panier'][$id] = [
                    'id'       => $plat['id'],
                    'nom'      => $plat['nom'],
                    'prix'     => $plat['prix'],
                    'calories' => $plat['calories'] ?? 0,
                    'quantite' => 1
                ];
            }
        }
        header('Location: commande-template.php');
        exit();
    }

    if (isset($_POST['ajouter_menu'])) {
        $menu_id = $_POST['ajouter_menu'];
        $menu    = get_menu_by_id($menu_id);
        if ($menu) {
            $menu_key = 'menu_' . $menu['id'];
            if (isset($_SESSION['panier'][$menu_key])) {
                $_SESSION['panier'][$menu_key]['quantite']++;
            } else {
                $_SESSION['panier'][$menu_key] = [
                    'id'       => $menu_key,
                    'nom'      => $menu['nom'] . ' (menu)',
                    'prix'     => $menu['prix'],
                    'calories' => $menu['calories'] ?? 0,
                    'quantite' => 1
                ];
            }
        }
        header('Location: commande-template.php');
        exit();
    }

    if (isset($_POST['retirer'])) {
        $id = $_POST['retirer'];
        if (isset($_SESSION['panier'][$id])) {
            if ($_SESSION['panier'][$id]['quantite'] > 1) {
                $_SESSION['panier'][$id]['quantite']--;
            } else {
                unset($_SESSION['panier'][$id]);
            }
        }
        header('Location: commande-template.php');
        exit();
    }

    if (isset($_POST['valider'])) {
        $mode = $_POST['mode'] ?? 'emporter';
        if ($mode === 'livraison') {
            $adresse = trim($_POST['adresse'] ?? '');
        } else {
            $adresse = '';
        }

        if (empty($_SESSION['panier'])) {
            $erreur_cmd = "Votre panier est vide.";
        } elseif ($mode === 'livraison' && empty($adresse)) {
            $erreur_cmd = "Veuillez renseigner une adresse de livraison.";
        } else {
            $plats_cmd = [];
            $total     = 0;
            foreach ($_SESSION['panier'] as $item) {
                $plats_cmd[] = [
                    'id'       => $item['id'],
                    'nom'      => $item['nom'],
                    'prix'     => $item['prix'],
                    'quantite' => $item['quantite']
                ];
                $total += $item['prix'] * $item['quantite'];
            }

            $commande_id        = generer_id_commande();
            $transaction_cybank = 'TXN' . date('YmdHis') . rand(100, 999);
            $montant_cybank     = number_format(round($total, 2), 2, '.', '');

            $nouvelle_commande = [
                'id'                 => $commande_id,
                'user_email'         => $_SESSION['user']['email'],
                'plats'              => $plats_cmd,
                'mode'               => $mode,
                'adresse'            => $adresse,
                'statut'             => 'en_attente_paiement',
                'date'               => date('Y-m-d H:i:s'),
                'total'              => round($total, 2),
                'transaction_cybank' => $transaction_cybank,
                'livreur_email'      => null,
                'note_produits'      => null,
                'note_livraison'     => null,
                'commentaire'        => ''
            ];

            if (save_commande($nouvelle_commande)) {

                $protocole  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                              ? 'https' : 'http';
                $host       = $_SERVER['HTTP_HOST'];
                $path       = strtok($_SERVER['REQUEST_URI'], '?');
                $retour_url = $protocole . '://' . $host . $path
                            . '?cmd_id=' . urlencode($commande_id);

                $api_key = getAPIKey(CYBANK_VENDEUR);
                $control = md5($api_key
                             . "#" . $transaction_cybank
                             . "#" . $montant_cybank
                             . "#" . CYBANK_VENDEUR
                             . "#" . $retour_url . "#");

                $_SESSION['cybank_pending'] = [
                    'transaction' => $transaction_cybank,
                    'montant'     => $montant_cybank,
                    'vendeur'     => CYBANK_VENDEUR,
                    'retour'      => $retour_url,
                    'control'     => $control,
                ];

                header('Location: commande-template.php?payer=1');
                exit();

            } else {
                $erreur_cmd = "Erreur lors de l'enregistrement. Vérifiez les droits d'écriture sur data/.";
            }
        }
    }
}

$total_panier = 0;
foreach ($_SESSION['panier'] as $item) {
    $total_panier += $item['prix'] * $item['quantite'];
}

$menus   = get_menus();
$entrees = get_plats_par_categorie('entree');
$plats_p = get_plats_par_categorie('plat');
$desserts = get_plats_par_categorie('dessert');
$user    = utilisateur_courant();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander — L'Étoile</title>
    <link rel="stylesheet" href="css/stylecommon.css">
    <link rel="stylesheet" href="css/stylecommandetemplate.css">
    <script src="js/scriptjs.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<main class="commande">

    <section class="commande-header">
        <h1>Passer commande</h1>
        <p>Une expérience simple, élégante et maîtrisée.</p>
    </section>

    <?php if ($succes_cmd): ?>
        <div class="alerte alerte-succes">
            ✅ <?= h($succes_cmd) ?> — <a href="profil.php">Voir mes commandes</a>
        </div>
    <?php endif; ?>

    <?php if ($erreur_cmd): ?>
        <div class="alerte alerte-erreur">
            ❌ <?= h($erreur_cmd) ?>
        </div>
    <?php endif; ?>

    <div class="commande-content">

        <section class="plats">
            <h2>Votre sélection</h2>

            <div class="categorie">
                <h3>Menus</h3>
                <?php foreach ($menus as $menu): ?>
                <div class="plat">
                    <div>
                        <h4><?= h($menu['nom']) ?></h4>
                        <p><?= h($menu['description']) ?></p>
                        <?php if (isset($menu['calories'])): ?>
                            <small class="calories-label">🔥 <?= $menu['calories'] ?> kcal</small>
                        <?php endif; ?>
                    </div>
                    <span><?= number_format($menu['prix'], 2) ?> €</span>
                    <form method="POST">
                        <input type="hidden" name="ajouter_menu" value="<?= h($menu['id']) ?>">
                        <button type="submit" class="btn-ajouter">+ Ajouter</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="categorie">
                <h3>Entrées</h3>
                <?php foreach ($entrees as $p): ?>
                <div class="plat">
                    <div>
                        <h4><?= h($p['nom']) ?></h4>
                        <p><?= h($p['description']) ?></p>
                        <small><em>Allergènes : <?= h($p['allergenes']) ?></em></small>
                        <?php if (isset($p['calories'])): ?>
                            <small class="calories-label">🔥 <?= $p['calories'] ?> kcal</small>
                        <?php endif; ?>
                    </div>
                    <span><?= number_format($p['prix'], 2) ?> €</span>
                    <form method="POST">
                        <input type="hidden" name="ajouter_plat" value="<?= h($p['id']) ?>">
                        <button type="submit" class="btn-ajouter">+ Ajouter</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="categorie">
                <h3>Plats</h3>
                <?php foreach ($plats_p as $p): ?>
                <div class="plat">
                    <div>
                        <h4><?= h($p['nom']) ?></h4>
                        <p><?= h($p['description']) ?></p>
                        <small><em>Allergènes : <?= h($p['allergenes']) ?></em></small>
                        <?php if (isset($p['calories'])): ?>
                            <small class="calories-label">🔥 <?= $p['calories'] ?> kcal</small>
                        <?php endif; ?>
                    </div>
                    <span><?= number_format($p['prix'], 2) ?> €</span>
                    <form method="POST">
                        <input type="hidden" name="ajouter_plat" value="<?= h($p['id']) ?>">
                        <button type="submit" class="btn-ajouter">+ Ajouter</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="categorie">
                <h3>Desserts</h3>
                <?php foreach ($desserts as $p): ?>
                <div class="plat">
                    <div>
                        <h4><?= h($p['nom']) ?></h4>
                        <p><?= h($p['description']) ?></p>
                        <small><em>Allergènes : <?= h($p['allergenes']) ?></em></small>
                        <?php if (isset($p['calories'])): ?>
                            <small class="calories-label">🔥 <?= $p['calories'] ?> kcal</small>
                        <?php endif; ?>
                    </div>
                    <span><?= number_format($p['prix'], 2) ?> €</span>
                    <form method="POST">
                        <input type="hidden" name="ajouter_plat" value="<?= h($p['id']) ?>">
                        <button type="submit" class="btn-ajouter">+ Ajouter</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="panier" id="panier">
            <h2>Article(s)
                <?php $nb = nb_articles_panier(); if ($nb > 0): ?>
                    <span class="badge-panier"><?= $nb ?></span>
                <?php endif; ?>
            </h2>

            <?php if (empty($_SESSION['panier'])): ?>
                <p class="panier-vide">Votre panier est vide.</p>
            <?php else: ?>

                <?php foreach ($_SESSION['panier'] as $key => $item): ?>
                <div class="panier-item">
                    <div class="panier-item-nom">
                        <?= h($item['nom']) ?>
                        <span class="panier-item-prix"><?= number_format($item['prix'], 2) ?> € / u</span>
                        <?php if (isset($item['calories'])): ?>
                            <span class="panier-item-cal">🔥 <?= $item['calories'] ?> kcal / u</span>
                        <?php endif; ?>
                    </div>
                    <div class="panier-item-qte">
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="retirer" value="<?= h($key) ?>">
                            <button type="submit" class="btn-qte btn-moins" title="Retirer un">−</button>
                        </form>
                        <span class="qte"><?= $item['quantite'] ?></span>
                        <form method="POST" class="form-inline">
                            <?php if (strpos($key, 'menu_') === 0): ?>
                                <input type="hidden" name="ajouter_menu" value="<?= h(substr($key, 5)) ?>">
                            <?php else: ?>
                                <input type="hidden" name="ajouter_plat" value="<?= h($key) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn-qte btn-plus" title="Ajouter un">+</button>
                        </form>
                    </div>
                    <span class="panier-item-total">
                        <?= number_format($item['prix'] * $item['quantite'], 2) ?> €
                    </span>
                </div>
                <?php endforeach; ?>

                <div class="panier-total">
                    <strong>Total</strong>
                    <strong><?= number_format($total_panier, 2) ?> €</strong>
                </div>

                <?php
                // Calculer le total calorique du panier
                $total_calories = 0;
                foreach ($_SESSION['panier'] as $item) {
                    if (isset($item['calories'])) {
                        $total_calories += $item['calories'] * $item['quantite'];
                    }
                }
                ?>
                <?php if ($total_calories > 0): ?>
                <div class="panier-calories">
                    <span>🔥 Total estimé</span>
                    <span class="cal-valeur <?= $total_calories > 2000 ? 'cal-eleve' : ($total_calories > 1200 ? 'cal-moyen' : 'cal-ok') ?>">
                        <?= $total_calories ?> kcal
                    </span>
                </div>
                <p class="cal-info">Apport journalier recommandé : ~2000 kcal</p>
                <?php endif; ?>

                <form method="GET">
                    <input type="hidden" name="vider" value="1">
                    <button type="submit" class="btn-vider">🗑️ Vider le panier</button>
                </form>

            <?php endif; ?>

            <form method="POST" action="commande-template.php" class="form-validation">
                <h3>Valider la commande</h3>

                <div class="commande-mode">
                    <label>
                        <input type="radio" name="mode" value="emporter" id="mode-emporter" checked>
                        À emporter
                    </label>
                    <label>
                        <input type="radio" name="mode" value="livraison" id="mode-livraison">
                        Livraison à domicile
                    </label>
                </div>

                <div id="bloc-adresse" class="bloc-adresse-wrap hidden">
                    <label for="adresse">Adresse de livraison :</label>
                    <input type="text" name="adresse" id="adresse"
                        placeholder="Ex : 12 rue de la Paix, 75001 Paris"
                        value="<?= h($user['adresse'] ?? '') ?>">
                    <small>(requis uniquement pour une livraison à domicile)</small>
                </div>

                <p class="info-client">
                    Connecté en tant que :
                    <strong><?= h(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?></strong>
                </p>

                <button type="submit" name="valider" class="btn-secondary"
                        <?= empty($_SESSION['panier']) ? 'disabled' : '' ?>>
                    Procéder au paiement
                </button>
            </form>

        </aside>
    </div>
</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-column">
            <h3>Récompenses</h3>
            <p>3 étoiles Michelin</p>
            <p>Maitre Restaurateur</p>
        </div>
        <div class="footer-column">
            <h3>Contact</h3>
            <p>06 49 00 96 90</p>
            <p>supportclient@gmail.com</p>
            <p>10 chemin de la Vie, Paris</p>
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