<?php
session_start();

define('USERS_FILE', 'users.json');

// Charger les utilisateurs
function get_users() {
    if (!file_exists(USERS_FILE)) return [];
    $data = file_get_contents(USERS_FILE);
    return json_decode($data, true) ?? [];
}

// Sauvegarder un utilisateur
function save_user($new_user) {
    $users = get_users();
    $users[] = $new_user;
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

// Vérifier si un email existe déjà
function email_exists($email) {
    $users = get_users();
    foreach ($users as $user) {
        if ($user['email'] === $email) return true;
    }
    return false;
}
?>