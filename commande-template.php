<?php
require_once 'functions.php';
require_once 'getapikey.php'; 

define('CYBANK_VENDEUR', 'MI-4_E');                                         
define('CYBANK_URL',     'https://www.plateforme-smc.fr/cybank/index.php'); // URL de l'interface CYBank

exiger_connexion();

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}


$erreur_cmd = '';
$succes_cmd = '';

// RETOUR CYBANK
// Déclenché quand CYBank redirige vers ce script avec :
// cmd_id (notre param dans l'URL retour) + transaction, montant,
// vendeur, status, control (ajoutés par CYBank)
if (isset($_GET['cmd_id'], $_GET['transaction'], $_GET['status'],
          $_GET['montant'], $_GET['vendeur'], $_GET['control'])) {

    $cmd_id       = $_GET['cmd_id'];
    $transaction  = $_GET['transaction'];
    $montant_ret  = $_GET['montant'];
    $vendeur_ret  = $_GET['vendeur'];
    $statut_ret   = $_GET['status']; // 'accepted' ou 'declined'
    $control_recu = $_GET['control'];

    // Calcul du hash attendu (règle de hachage retour CYBank)
    $api_key        = getAPIKey($vendeur_ret);
    $control_calcul = md5($api_key
                        . "#" . $transaction
                        . "#" . $montant_ret
                        . "#" . $vendeur_ret
                        . "#" . $statut_ret . "#");

    // Vérifier que la clé API est valide ET que le contrôle correspond
    if (preg_match('/^[0-9a-zA-Z]{15}$/', $api_key)
        && hash_equals($control_calcul, $control_recu)) {

        if ($statut_ret === 'accepted') {
            // Paiement accepté : commande prête pour la cuisine
            update_statut_commande($cmd_id, 'en_attente');
            $_SESSION['panier'] = [];
            $succes_cmd = "Paiement accepté ! Votre commande #" . $cmd_id
                        . " est confirmée et en cours de préparation.";
        } else {
            // Paiement refusé : on met à jour le statut
            update_statut_commande($cmd_id, 'paiement_refuse');
            $erreur_cmd = "Paiement refusé pour la commande #" . $cmd_id
                        . ". Veuillez réessayer ou contacter votre banque.";
        }

    } else {
        // Hash invalide : données potentiellement falsifiées
        $erreur_cmd = "Erreur de contrôle CYBank : les données de retour sont invalides.";
    }
}

// ============================================================
// PAGE D'ENVOI VERS CYBANK (formulaire auto-soumis)
// Déclenché après la validation du panier (?payer=1)
// ============================================================
if (isset($_GET['payer']) && !empty($_SESSION['cybank_pending'])) {
    $params = $_SESSION['cybank_pending'];
    unset($_SESSION['cybank_pending']); // On consomme les données de session
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Redirection vers le paiement sécurisé…</title>
    <style>
        body { font-family: sans-serif; text-align: center; margin-top: 5rem; color: #444; }
        p    { font-size: 1.1rem; }
    </style>
</head>
<body>
    <p>⏳ Vous allez être redirigé vers l'interface de paiement sécurisé CYBank.</p>
    <form method="POST" action="<?= CYBANK_URL ?>">
        <input type="hidden" name="transaction" value="<?= h($params['transaction']) ?>">
        <input type="hidden" name="montant"     value="<?= h($params['montant']) ?>">
        <input type="hidden" name="vendeur"     value="<?= h($params['vendeur']) ?>">
        <input type="hidden" name="retour"      value="<?= h($params['retour']) ?>">
        <input type="hidden" name="control"     value="<?= h($params['control']) ?>">
        <button type="submit">Continuer vers le paiement →</button>
    </form>
</body>
</html>
    <?php
    exit();
}

// ============================================================
// ACTIONS GET SIMPLES (ajouter, retirer, vider)
// Toutes les actions GET redirigent avec un ancre #panier
// pour éviter de perdre la position de l'utilisateur
// ============================================================

// Ajouter un plat via GET (lien depuis carte.php)
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
                'quantite' => 1
            ];
        }
    }
    header('Location: commande-template.php#panier');
    exit();
}

// Ajouter un menu via GET (lien depuis carte.php)
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
                'quantite' => 1
            ];
        }
    }
    header('Location: commande-template.php#panier');
    exit();
}

// Vider le panier
if (isset($_GET['vider'])) {
    $_SESSION['panier'] = [];
    header('Location: commande-template.php#panier');
    exit();
}

// ============================================================
// ACTIONS POST (ajouter depuis la page, retirer, valider)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -- Ajouter un plat depuis les boutons de la page --
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
                    'quantite' => 1
                ];
            }
        }
        header('Location: commande-template.php#panier');
        exit();
    }

    // -- Ajouter un menu depuis les boutons de la page --
    if (isset($_POST['ajouter_menu'])) {
        $menu_id  = $_POST['ajouter_menu'];
        $menu     = get_menu_by_id($menu_id);
        if ($menu) {
            $menu_key = 'menu_' . $menu['id'];
            if (isset($_SESSION['panier'][$menu_key])) {
                $_SESSION['panier'][$menu_key]['quantite']++;
            } else {
                $_SESSION['panier'][$menu_key] = [
                    'id'       => $menu_key,
                    'nom'      => $menu['nom'] . ' (menu)',
                    'prix'     => $menu['prix'],
                    'quantite' => 1
                ];
            }
        }
        header('Location: commande-template.php#panier');
        exit();
    }

    // -- Retirer un article du panier --
    if (isset($_POST['retirer'])) {
        $id = $_POST['retirer'];
        if (isset($_SESSION['panier'][$id])) {
            if ($_SESSION['panier'][$id]['quantite'] > 1) {
                $_SESSION['panier'][$id]['quantite']--;
            } else {
                unset($_SESSION['panier'][$id]);
            }
        }
        header('Location: commande-template.php#panier');
        exit();
    }

    // -- Valider la commande et initier le paiement CYBank --
    if (isset($_POST['valider'])) {
        $mode    = $_POST['mode']    ?? 'emporter';
        // On ne récupère l'adresse que si mode = livraison
        $adresse = ($mode === 'livraison') ? trim($_POST['adresse'] ?? '') : '';

        if (empty($_SESSION['panier'])) {
            $erreur_cmd = "Votre panier est vide.";
        } elseif ($mode === 'livraison' && empty($adresse)) {
            $erreur_cmd = "Veuillez renseigner une adresse de livraison.";
        } else {
            // Construire la liste des plats commandés et calculer le total
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

            // ------------------------------------------------
            // Identifiants internes et CYBank
            // ------------------------------------------------
            $commande_id = generer_id_commande();

            // Transaction CYBank : format [0-9a-zA-Z]{10,24}
            $transaction_cybank = 'TXN' . date('YmdHis') . rand(100, 999);

            // Montant au format décimal avec '.' comme séparateur
            $montant_cybank = number_format(round($total, 2), 2, '.', '');

            // ------------------------------------------------
            // Enregistrer la commande (statut : en_attente_paiement)
            // Le panier sera vidé uniquement après confirmation CYBank
            // ------------------------------------------------
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

                // ------------------------------------------------
                // Construire l'URL de retour
                // CYBank ajoutera ses paramètres à la suite de ?cmd_id=…
                // ------------------------------------------------
                $protocole  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                              ? 'https' : 'http';
                $host       = $_SERVER['HTTP_HOST'];
                $path       = strtok($_SERVER['REQUEST_URI'], '?'); // chemin sans query string
                $retour_url = $protocole . '://' . $host . $path
                            . '?cmd_id=' . urlencode($commande_id);

                // ------------------------------------------------
                // Calculer le hash de contrôle pour l'envoi (règle CYBank)
                // md5($api_key."#".$transaction."#".$montant."#".$vendeur."#".$retour."#")
                // ------------------------------------------------
                $api_key = getAPIKey(CYBANK_VENDEUR);
                $control = md5($api_key
                             . "#" . $transaction_cybank
                             . "#" . $montant_cybank
                             . "#" . CYBANK_VENDEUR
                             . "#" . $retour_url . "#");

                // ------------------------------------------------
                // Stocker les paramètres en session pour la page
                // de redirection (?payer=1)
                // ------------------------------------------------
                $_SESSION['cybank_pending'] = [
                    'transaction' => $transaction_cybank,
                    'montant'     => $montant_cybank,
                    'vendeur'     => CYBANK_VENDEUR,
                    'retour'      => $retour_url,
                    'control'     => $control,
                ];

                // Rediriger vers la page de soumission du formulaire CYBank
                header('Location: commande-template.php?payer=1');
                exit();

            } else {
                $erreur_cmd = "Erreur lors de l'enregistrement. Vérifiez les droits d'écriture sur data/.";
            }
        }
    }
}

// ---- CALCUL DU TOTAL PANIER ----
$total_panier = 0;
foreach ($_SESSION['panier'] as $item) {
    $total_panier += $item['prix'] * $item['quantite'];
}

// ---- DONNÉES POUR L'AFFICHAGE ----
$menus    = get_menus();
$entrees  = get_plats_par_categorie('entree');
$plats_p  = get_plats_par_categorie('plat');
$desserts = get_plats_par_categorie('dessert');
$user     = utilisateur_courant();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander — L'Étoile</title>
    <link rel="stylesheet" href="stylecommon.css">
    <link rel="stylesheet" href="stylecommandetemplate.css">
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

        <!-- ================================================ -->
        <!-- LISTE DES PLATS (colonne gauche)                 -->
        <!-- BUG CORRIGÉ : boutons POST au lieu de liens GET  -->
        <!-- ================================================ -->
        <section class="plats">
            <h2>Votre sélection</h2>

            <!-- Menus -->
            <div class="categorie">
                <h3>Menus</h3>
                <?php foreach ($menus as $menu): ?>
                <div class="plat">
                    <div>
                        <h4><?= h($menu['nom']) ?></h4>
                        <p><?= h($menu['description']) ?></p>
                        <small>Disponibilité : <?= h($menu['disponibilite']) ?></small>
                    </div>
                    <span><?= number_format($menu['prix'], 2) ?> €</span>
                    <form method="POST">
                        <input type="hidden" name="ajouter_menu" value="<?= h($menu['id']) ?>">
                        <button type="submit" class="btn-ajouter">+ Ajouter</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Entrées -->
            <div class="categorie">
                <h3>Entrées</h3>
                <?php foreach ($entrees as $p): ?>
                <div class="plat">
                    <div>
                        <h4><?= h($p['nom']) ?></h4>
                        <p><?= h($p['description']) ?></p>
                        <small><em>Allergènes : <?= h($p['allergenes']) ?></em></small>
                    </div>
                    <span><?= number_format($p['prix'], 2) ?> €</span>
                    <form method="POST">
                        <input type="hidden" name="ajouter_plat" value="<?= h($p['id']) ?>">
                        <button type="submit" class="btn-ajouter">+ Ajouter</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Plats -->
            <div class="categorie">
                <h3>Plats</h3>
                <?php foreach ($plats_p as $p): ?>
                <div class="plat">
                    <div>
                        <h4><?= h($p['nom']) ?></h4>
                        <p><?= h($p['description']) ?></p>
                        <small><em>Allergènes : <?= h($p['allergenes']) ?></em></small>
                    </div>
                    <span><?= number_format($p['prix'], 2) ?> €</span>
                    <form method="POST">
                        <input type="hidden" name="ajouter_plat" value="<?= h($p['id']) ?>">
                        <button type="submit" class="btn-ajouter">+ Ajouter</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Desserts -->
            <div class="categorie">
                <h3>Desserts</h3>
                <?php foreach ($desserts as $p): ?>
                <div class="plat">
                    <div>
                        <h4><?= h($p['nom']) ?></h4>
                        <p><?= h($p['description']) ?></p>
                        <small><em>Allergènes : <?= h($p['allergenes']) ?></em></small>
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

        <!-- ================================================ -->
        <!-- PANIER (colonne droite)                          -->
        <!-- ================================================ -->
        <aside class="panier" id="panier">
            <h2>Récapitulatif
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
                    </div>
                    <div class="panier-item-qte">
                        <!-- Bouton retirer (POST) -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="retirer" value="<?= h($key) ?>">
                            <button type="submit" class="btn-qte btn-moins" title="Retirer un">−</button>
                        </form>
                        <span class="qte"><?= $item['quantite'] ?></span>
                        <!-- Bouton ajouter (POST) -->
                        <form method="POST" style="display:inline;">
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

                <form method="GET">
                    <input type="hidden" name="vider" value="1">
                    <button type="submit" class="btn-vider">🗑️ Vider le panier</button>
                </form>

            <?php endif; ?>

            <!-- ---- FORMULAIRE DE VALIDATION ---- -->
            <form method="POST" action="commande-template.php#panier" class="form-validation">
                <h3>Valider la commande</h3>

                <div class="commande-mode">
                    <label>
                        <input type="radio" name="mode" value="emporter"
                               id="mode-emporter" checked>
                        À emporter
                    </label>
                    <label>
                        <input type="radio" name="mode" value="livraison"
                               id="mode-livraison">
                        Livraison à domicile
                    </label>
                </div>

                <div style="margin-top:0.6rem;">
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
                    💳 Procéder au paiement
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