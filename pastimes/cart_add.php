<?php
// cart_add.php - Adds an item to the session cart.
// Cart is keyed by clothes_id so adding the same item
// again increases the quantity instead of duplicating it.
session_start();
require_once 'DBConn.php';

$id  = (int)($_GET['id'] ?? 0);
$qty = (int)($_GET['qty'] ?? 1);
if ($qty < 1) $qty = 1;

$response = ['status' => 'error'];

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tblClothes WHERE clothes_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($item) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        // Cart is an associative array keyed by clothes_id
        if (isset($_SESSION['cart'][$id])) {
            // Item already in cart — increase quantity instead of duplicating
            $_SESSION['cart'][$id]['cart_qty'] += $qty;
        } else {
            $item['cart_qty'] = $qty;
            $_SESSION['cart'][$id] = $item;
        }

        // Don't let cart quantity exceed available stock
        if ($_SESSION['cart'][$id]['cart_qty'] > $item['quantity']) {
            $_SESSION['cart'][$id]['cart_qty'] = $item['quantity'];
        }

        $_SESSION['cart_msg'] = htmlspecialchars($item['item_name']) . " added to cart!";
        $response = [
            'status'    => 'ok',
            'cart_qty'  => $_SESSION['cart'][$id]['cart_qty'],
            'cart_count'=> count($_SESSION['cart'])
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>