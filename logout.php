<?php
require_once 'functions.php';
$_SESSION['panier'] = [];
session_destroy();

setcookie('theme', '', time() - 3600, '/');

header('Location: accueil.php');
exit();
