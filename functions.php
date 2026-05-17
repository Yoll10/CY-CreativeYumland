<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('USERS_FILE',     __DIR__ . '/data/users.json');
define('PLATS_FILE',     __DIR__ . '/data/plats.json');
define('MENUS_FILE',     __DIR__ . '/data/menus.json');
define('COMMANDES_FILE', __DIR__ . '/data/commandes.json');



function lire_json($fichier) {
    if (!file_exists($fichier)) {
        return array();
    }
    $contenu = file_get_contents($fichier);
    $data = json_decode($contenu, true);
    if ($data === null) {
        return array();
    }
    return $data;
}

function ecrire_json($fichier, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $result = file_put_contents($fichier, $json);
    if ($result === false) {
        return false;
    }
    return true;
}



function get_users() {
    return lire_json(USERS_FILE);
}

function get_user_by_email($email) {
    $users = get_users();
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

function email_exists($email) {
    $user = get_user_by_email($email);
    if ($user !== null) {
        return true;
    }
    return false;
}

function save_user($new_user) {
    $users = get_users();
    $users[] = $new_user;
    return ecrire_json(USERS_FILE, $users);
}

function update_user($email, $champs) {
    $users = get_users();
    for ($i = 0; $i < count($users); $i++) {
        if ($users[$i]['email'] === $email) {
            foreach ($champs as $cle => $valeur) {
                $users[$i][$cle] = $valeur;
            }
            return ecrire_json(USERS_FILE, $users);
        }
    }
    return false;
}

function delete_user($email) {
    $users = get_users();
    $nouveaux_users = array();
    foreach ($users as $user) {
        if ($user['email'] !== $email) {
            $nouveaux_users[] = $user;
        }
    }
    return ecrire_json(USERS_FILE, $nouveaux_users);
}

function get_users_ayant_commande() {
    $commandes = get_commandes();
    $emails_avec_commande = array();
    foreach ($commandes as $cmd) {
        if (!in_array($cmd['user_email'], $emails_avec_commande)) {
            $emails_avec_commande[] = $cmd['user_email'];
        }
    }
    $users = get_users();
    $result = array();
    foreach ($users as $user) {
        if (in_array($user['email'], $emails_avec_commande)) {
            $result[] = $user;
        }
    }
    return $result;
}



function est_connecte() {
    if (isset($_SESSION['user'])) {
        return true;
    }
    return false;
}

function utilisateur_courant() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

function est_admin() {
    if (!est_connecte()) {
        return false;
    }
    if ($_SESSION['user']['role'] === 'admin') {
        return true;
    }
    return false;
}

function est_restaurateur() {
    if (!est_connecte()) {
        return false;
    }
    if ($_SESSION['user']['role'] === 'restaurateur') {
        return true;
    }
    return false;
}

function est_livreur() {
    if (!est_connecte()) {
        return false;
    }
    if ($_SESSION['user']['role'] === 'livreur') {
        return true;
    }
    return false;
}

function est_client() {
    if (!est_connecte()) {
        return false;
    }
    if ($_SESSION['user']['role'] === 'client') {
        return true;
    }
    return false;
}

function exiger_connexion() {
    if (!est_connecte()) {
        header("Location: connexion.php");
        exit();
    }
    // Vérification en direct : si l'utilisateur a été bloqué depuis sa connexion,
    // on détruit sa session immédiatement et on le redirige.
    $user_frais = get_user_by_email($_SESSION['user']['email']);
    if ($user_frais === null || $user_frais['statut'] === 'bloque') {
        $_SESSION['panier'] = [];
        session_destroy();
        header("Location: connexion.php?bloque=1");
        exit();
    }
    // On resynchronise la session avec les données les plus récentes
    // (utile si un admin a modifié le rôle ou le nom entre-temps)
    $_SESSION['user'] = $user_frais;
}

function exiger_role($role) {
    exiger_connexion();
    if ($_SESSION['user']['role'] !== $role) {
        header("Location: accueil.php");
        exit();
    }
}



function get_plats() {
    return lire_json(PLATS_FILE);
}

function get_plat_by_id($id) {
    $plats = get_plats();
    foreach ($plats as $plat) {
        if ($plat['id'] === $id) {
            return $plat;
        }
    }
    return null;
}

function get_plats_par_categorie($categorie) {
    $plats = get_plats();
    $result = array();
    foreach ($plats as $plat) {
        if ($plat['categorie'] === $categorie) {
            $result[] = $plat;
        }
    }
    return $result;
}

function get_plats_populaires($nb) {
    $commandes = get_commandes();
    $compteur = array();
    foreach ($commandes as $cmd) {
        foreach ($cmd['plats'] as $plat) {
            if (isset($compteur[$plat['id']])) {
                $compteur[$plat['id']] = $compteur[$plat['id']] + $plat['quantite'];
            } else {
                $compteur[$plat['id']] = $plat['quantite'];
            }
        }
    }
    arsort($compteur);
    $top_ids = array_slice(array_keys($compteur), 0, $nb);
    $result = array();
    foreach ($top_ids as $id) {
        $p = get_plat_by_id($id);
        if ($p !== null) {
            $result[] = $p;
        }
    }
    return $result;
}



function get_menus() {
    return lire_json(MENUS_FILE);
}

function get_menu_by_id($id) {
    $menus = get_menus();
    foreach ($menus as $menu) {
        if ($menu['id'] === $id) {
            return $menu;
        }
    }
    return null;
}



function get_commandes() {
    return lire_json(COMMANDES_FILE);
}

function get_commande_by_id($id) {
    $commandes = get_commandes();
    foreach ($commandes as $cmd) {
        if ($cmd['id'] === $id) {
            return $cmd;
        }
    }
    return null;
}

function get_commandes_par_statut($statut) {
    $commandes = get_commandes();
    $result = array();
    foreach ($commandes as $cmd) {
        if ($cmd['statut'] === $statut) {
            $result[] = $cmd;
        }
    }
    return $result;
}

function get_commandes_user($email) {
    $commandes = get_commandes();
    $result = array();
    foreach ($commandes as $cmd) {
        if ($cmd['user_email'] === $email) {
            $result[] = $cmd;
        }
    }
    usort($result, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    return $result;
}

function get_commandes_livreur($email) {
    $commandes = get_commandes();
    $result = array();
    foreach ($commandes as $cmd) {
        if (isset($cmd['livreur_email']) && $cmd['livreur_email'] === $email && $cmd['statut'] === 'en_livraison') {
            $result[] = $cmd;
        }
    }
    return $result;
}

function generer_id_commande() {
    $commandes = get_commandes();
    if (count($commandes) === 0) {
        return 'c001';
    }
    $max = 0;
    foreach ($commandes as $cmd) {
        $num = intval(substr($cmd['id'], 1));
        if ($num > $max) {
            $max = $num;
        }
    }
    $nouveau = $max + 1;
    return 'c' . str_pad($nouveau, 3, '0', STR_PAD_LEFT);
}

function save_commande($commande) {
    $commandes = get_commandes();
    $commandes[] = $commande;
    return ecrire_json(COMMANDES_FILE, $commandes);
}

function update_statut_commande($id, $nouveau_statut, $livreur_email = null) {
    $commandes = get_commandes();
    for ($i = 0; $i < count($commandes); $i++) {
        if ($commandes[$i]['id'] === $id) {
            $commandes[$i]['statut'] = $nouveau_statut;
            if ($livreur_email !== null) {
                $commandes[$i]['livreur_email'] = $livreur_email;
            }
            return ecrire_json(COMMANDES_FILE, $commandes);
        }
    }
    return false;
}

function update_plats_commande($id, $plats, $nouveau_total) {
    $commandes = get_commandes();
    for ($i = 0; $i < count($commandes); $i++) {
        if ($commandes[$i]['id'] === $id) {
            $commandes[$i]['plats'] = $plats;
            $commandes[$i]['total'] = $nouveau_total;
            return ecrire_json(COMMANDES_FILE, $commandes);
        }
    }
    return false;
}

function save_notation($commande_id, $note_produits, $note_livraison, $commentaire) {
    $commandes = get_commandes();
    for ($i = 0; $i < count($commandes); $i++) {
        if ($commandes[$i]['id'] === $commande_id) {
            $commandes[$i]['note_produits'] = $note_produits;
            $commandes[$i]['note_livraison'] = $note_livraison;
            $commandes[$i]['commentaire'] = $commentaire;
            return ecrire_json(COMMANDES_FILE, $commandes);
        }
    }
    return false;
}



function h($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function afficher_etoiles($note) {
    $etoiles = '';
    for ($i = 0; $i < $note; $i++) {
        $etoiles .= '★';
    }
    for ($i = $note; $i < 5; $i++) {
        $etoiles .= '☆';
    }
    return $etoiles;
}

function libelle_statut($statut) {
    switch ($statut) {
        case 'en_attente':
            return 'En attente';
        case 'en_attente_paiement':
            return 'En attente de paiement';
        case 'paiement_refuse':
            return 'Paiement refusé';
        case 'en_preparation':
            return 'En préparation';
        case 'en_livraison':
            return 'En livraison';
        case 'livree':
            return 'Livrée';
        default:
            return $statut;
    }
}

function libelle_role($role) {
    switch ($role) {
        case 'client':
            return 'Client';
        case 'admin':
            return 'Administrateur';
        case 'restaurateur':
            return 'Restaurateur';
        case 'livreur':
            return 'Livreur';
        default:
            return $role;
    }
}

function nb_articles_panier() {
    if (!isset($_SESSION['panier'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['panier'] as $item) {
        $total = $total + $item['quantite'];
    }
    return $total;
}
