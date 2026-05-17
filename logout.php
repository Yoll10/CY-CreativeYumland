<?php
require_once 'functions.php';
$_SESSION['panier'] = []; 
session_destroy();
header('Location: accueil.php');
exit();
