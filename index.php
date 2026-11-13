<?php
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($uri, '/public') === false) {
    $base = explode('?', $uri)[0];
    $query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
    $redirect = rtrim($base, '/') . '/public/' . $query;
    header("Location: $redirect", true, 302);
    exit;
}
require_once __DIR__ . '/public/index.php';
