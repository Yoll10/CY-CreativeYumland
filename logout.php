<?php
require_once 'functions.php';
$_SESSION['panier'] = []; // Vider le panier à la déconnexion
session_destroy();
header('Location: accueil.php');
exit();
