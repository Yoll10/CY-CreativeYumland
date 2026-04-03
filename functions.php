<?php
// ============================================================
//  FUNCTIONS.PHP — Bibliothèque centrale du site L'Étoile
//  Gestion : utilisateurs, plats, menus, commandes
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// CHEMINS DES FICHIERS DE DONNÉES
// ------------------------------------------------------------
define('USERS_FILE',     __DIR__ . '/users.json');
define('PLATS_FILE',     __DIR__ . '/plats.json');
define('MENUS_FILE',     __DIR__ . '/menus.json');
define('COMMANDES_FILE', __DIR__ . '/commandes.json');


// ============================================================
// FONCTIONS GÉNÉRIQUES JSON
// ============================================================

function lire_json(string $fichier): array {
    if (!file_exists($fichier)) return [];
    $contenu = file_get_contents($fichier);
    return json_decode($contenu, true) ?? [];
}

function ecrire_json(string $fichier, array $data): bool {
    return file_put_contents(
        $fichier,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}


// ============================================================
// GESTION DES UTILISATEURS
// ============================================================

function get_users(): array {
    return lire_json(USERS_FILE);
}

function get_user_by_email(string $email): ?array {
    foreach (get_users() as $user) {
        if ($user['email'] === $email) return $user;
    }
    return null;
}

function email_exists(string $email): bool {
    return get_user_by_email($email) !== null;
}

function save_user(array $new_user): bool {
    $users = get_users();
    $users[] = $new_user;
    return ecrire_json(USERS_FILE, $users);
}

function update_user(string $email, array $champs): bool {
    $users = get_users();
    foreach ($users as &$user) {
        if ($user['email'] === $email) {
            foreach ($champs as $cle => $valeur) {
                $user[$cle] = $valeur;
            }
            return ecrire_json(USERS_FILE, $users);
        }
    }
    return false;
}

function delete_user(string $email): bool {
    $users = get_users();
    $users = array_values(array_filter($users, fn($u) => $u['email'] !== $email));
    return ecrire_json(USERS_FILE, $users);
}

function get_users_ayant_commande(): array {
    $commandes = get_commandes();
    $emails    = array_unique(array_column($commandes, 'user_email'));
    $users     = get_users();
    return array_values(array_filter($users, fn($u) => in_array($u['email'], $emails)));
}


// ============================================================
// GESTION DE LA SESSION
// ============================================================

function est_connecte(): bool {
    return isset($_SESSION['user']);
}

function utilisateur_courant(): ?array {
    return $_SESSION['user'] ?? null;
}

function est_admin(): bool {
    return est_connecte() && $_SESSION['user']['role'] === 'admin';
}

function est_restaurateur(): bool {
    return est_connecte() && $_SESSION['user']['role'] === 'restaurateur';
}

function est_livreur(): bool {
    return est_connecte() && $_SESSION['user']['role'] === 'livreur';
}

function est_client(): bool {
    return est_connecte() && $_SESSION['user']['role'] === 'client';
}

function exiger_connexion(string $redirect = 'connexion.php'): void {
    if (!est_connecte()) {
        header("Location: $redirect");
        exit();
    }
}

function exiger_role(string $role, string $redirect = 'accueil.php'): void {
    exiger_connexion();
    if ($_SESSION['user']['role'] !== $role) {
        header("Location: $redirect");
        exit();
    }
}


// ============================================================
// GESTION DES PLATS
// ============================================================

function get_plats(): array {
    return lire_json(PLATS_FILE);
}

function get_plat_by_id(string $id): ?array {
    foreach (get_plats() as $plat) {
        if ($plat['id'] === $id) return $plat;
    }
    return null;
}

function get_plats_par_categorie(string $categorie): array {
    return array_values(array_filter(get_plats(), fn($p) => $p['categorie'] === $categorie));
}

function get_plats_populaires(int $nb = 3): array {
    $commandes = get_commandes();
    $compteur  = [];
    foreach ($commandes as $cmd) {
        foreach ($cmd['plats'] as $plat) {
            $compteur[$plat['id']] = ($compteur[$plat['id']] ?? 0) + $plat['quantite'];
        }
    }
    arsort($compteur);
    $top_ids = array_slice(array_keys($compteur), 0, $nb);

    $result = [];
    foreach ($top_ids as $id) {
        $p = get_plat_by_id($id);
        if ($p) $result[] = $p;
    }
    return $result;
}


// ============================================================
// GESTION DES MENUS
// ============================================================

function get_menus(): array {
    return lire_json(MENUS_FILE);
}

function get_menu_by_id(string $id): ?array {
    foreach (get_menus() as $menu) {
        if ($menu['id'] === $id) return $menu;
    }
    return null;
}


// ============================================================
// GESTION DES COMMANDES
// ============================================================

function get_commandes(): array {
    return lire_json(COMMANDES_FILE);
}

function get_commande_by_id(string $id): ?array {
    foreach (get_commandes() as $cmd) {
        if ($cmd['id'] === $id) return $cmd;
    }
    return null;
}

function get_commandes_par_statut(string $statut): array {
    return array_values(array_filter(get_commandes(), fn($c) => $c['statut'] === $statut));
}

function get_commandes_user(string $email): array {
    $cmds = array_filter(get_commandes(), fn($c) => $c['user_email'] === $email);
    usort($cmds, fn($a, $b) => strcmp($b['date'], $a['date']));
    return array_values($cmds);
}

function get_commandes_livreur(string $email): array {
    return array_values(array_filter(
        get_commandes(),
        fn($c) => $c['livreur_email'] === $email && $c['statut'] === 'en_livraison'
    ));
}

function generer_id_commande(): string {
    $commandes = get_commandes();
    if (empty($commandes)) return 'c001';
    $ids = array_map(fn($c) => intval(substr($c['id'], 1)), $commandes);
    return 'c' . str_pad(max($ids) + 1, 3, '0', STR_PAD_LEFT);
}

function save_commande(array $commande): bool {
    $commandes   = get_commandes();
    $commandes[] = $commande;
    return ecrire_json(COMMANDES_FILE, $commandes);
}

function update_statut_commande(string $id, string $nouveau_statut, ?string $livreur_email = null): bool {
    $commandes = get_commandes();
    foreach ($commandes as &$cmd) {
        if ($cmd['id'] === $id) {
            $cmd['statut'] = $nouveau_statut;
            if ($livreur_email !== null) {
                $cmd['livreur_email'] = $livreur_email;
            }
            return ecrire_json(COMMANDES_FILE, $commandes);
        }
    }
    return false;
}

function save_notation(string $commande_id, int $note_produits, int $note_livraison, string $commentaire): bool {
    $commandes = get_commandes();
    foreach ($commandes as &$cmd) {
        if ($cmd['id'] === $commande_id) {
            $cmd['note_produits']  = $note_produits;
            $cmd['note_livraison'] = $note_livraison;
            $cmd['commentaire']    = htmlspecialchars($commentaire);
            return ecrire_json(COMMANDES_FILE, $commandes);
        }
    }
    return false;
}


// ============================================================
// FONCTIONS UTILITAIRES D'AFFICHAGE
// ============================================================

/**
 * Échappe une valeur pour l'affichage HTML.
 * BUG CORRIGÉ : accepte null sans planter (retourne chaîne vide).
 */
function h(?string $str): string {
    if ($str === null) return '';
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function afficher_etoiles(int $note): string {
    return str_repeat('★', $note) . str_repeat('☆', 5 - $note);
}

function libelle_statut(string $statut): string {
    return match($statut) {
        'en_attente'     => 'En attente',
        'en_preparation' => 'En préparation',
        'en_livraison'   => 'En livraison',
        'livree'         => 'Livrée',
        default          => ucfirst($statut)
    };
}

function libelle_role(string $role): string {
    return match($role) {
        'client'       => 'Client',
        'admin'        => 'Administrateur',
        'restaurateur' => 'Restaurateur',
        'livreur'      => 'Livreur',
        default        => ucfirst($role)
    };
}

/**
 * Retourne le nombre total d'articles dans le panier session.
 */
function nb_articles_panier(): int {
    if (!isset($_SESSION['panier'])) return 0;
    return array_sum(array_column($_SESSION['panier'], 'quantite'));
}
