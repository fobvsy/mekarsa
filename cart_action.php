<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';
$redirect = $_SERVER['HTTP_REFERER'] ?? 'menu.php';

if ($action === 'add') {
    $id = (int)($_POST['product_id'] ?? 0);
    $name = $_POST['product_name'] ?? '';
    $price = (float)($_POST['product_price'] ?? 0);
    
    if ($id > 0 && $name && $price >= 0) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$id] = [
                'name' => $name,
                'price' => $price,
                'quantity' => 1
            ];
        }
    }
} elseif ($action === 'remove') {
    $id = (int)($_POST['product_id'] ?? 0);
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
} elseif ($action === 'update') {
    $id = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    
    if ($qty > 0 && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['quantity'] = $qty;
    } elseif ($qty <= 0 && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
} elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
}

header("Location: " . $redirect);
exit;
