<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !est_connecte()) {
    http_response_code(403);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$theme = isset($data['theme']) && $data['theme'] === 'dark' ? 'dark' : 'light';

update_user($_SESSION['user']['email'], array('theme' => $theme));
$_SESSION['user']['theme'] = $theme;

echo json_encode(array('succes' => true));
