<?php

// =====================================================
// PROCESS CHECKOUT
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

// =====================================================
// ONLY POST REQUESTS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: cart.php');
    exit;
}

// =====================================================
// CHECK LOGIN
// =====================================================

if (!isset($_SESSION['user_id'])) {

    $_SESSION['checkout_error'] =
        'Please log in before checking out.';

    header('Location: cart.php');
    exit;
}

// =====================================================
// CSRF VALIDATION
// =====================================================

$csrfToken =
    $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    empty($csrfToken) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $csrfToken
    )
) {

    $_SESSION['checkout_error'] =
        'Invalid security token. Please try again.';

    header('Location: cart.php');
    exit;
}

// =====================================================
// USER ID
// =====================================================

$userId =
    (int)$_SESSION['user_id'];


// =====================================================
// CHECKOUT TYPE
// =====================================================

$checkoutType =
    $_POST['checkout_type'] ?? 'cart';

$isBuyNowRequest =
    $checkoutType === 'buy_now';

function checkoutErrorResponse(string $message, bool $isBuyNowRequest): void
{
    if ($isBuyNowRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }

    $_SESSION['checkout_error'] = $message;
    header('Location: cart.php');
    exit;
}


// =====================================================
// ADDRESS
// =====================================================

$address =
    trim($_POST['address'] ?? '');


// =====================================================
// PAYMENT METHOD
// =====================================================

$paymentMethod =
    trim(
        $_POST['payment_method']
        ?? 'Cash on Delivery'
    );


// =====================================================
// VALIDATE ADDRESS
// =====================================================

if (empty($address)) {
    checkoutErrorResponse('Please enter your delivery address.', $isBuyNowRequest);
}


// =====================================================
// VALIDATE PAYMENT METHOD
// =====================================================

if (!in_array($paymentMethod, ['Cash on Delivery', 'GCash', 'Card'], true)) {
    checkoutErrorResponse('Invalid payment method.', $isBuyNowRequest);
}


// =====================================================
// GET REGISTERED USER INFORMATION
// =====================================================

try {

    $userStmt = $pdo->prepare("
        SELECT
            id,
            first_name,
            last_name,
            phone,
            email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $userStmt->execute([
        $userId
    ]);

    $user =
        $userStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    checkoutErrorResponse('Unable to load your account information.', $isBuyNowRequest);
}


// =====================================================
// USER NOT FOUND
// =====================================================

if (!$user) {
    checkoutErrorResponse('User account could not be found.', $isBuyNowRequest);
}


// =====================================================
// FULL NAME
// =====================================================

$fullName = trim(
    ($user['first_name'] ?? '') .
    ' ' .
    ($user['last_name'] ?? '')
);


// =====================================================
// PHONE
// =====================================================

$phone =
    trim($_POST['phone'] ?? ($user['phone'] ?? ''));


// =====================================================
// VALIDATE USER INFORMATION
// =====================================================

if (empty($fullName)) {
    checkoutErrorResponse('Your registered name is incomplete.', $isBuyNowRequest);
}


if (!preg_match('/^09[0-9]{9}$/', $phone)) {
    checkoutErrorResponse('Phone number must be 11 digits and start with 09.', $isBuyNowRequest);
}


// =====================================================
// PREPARE ORDER ITEMS
// =====================================================

$orderItems = [];


// =====================================================
// CART CHECKOUT
// =====================================================

if ($checkoutType === 'cart') {

    if (
        empty($_SESSION['cart']) ||
        !is_array($_SESSION['cart'])
    ) {

        $_SESSION['checkout_error'] =
            'Your cart is empty.';

        header('Location: cart.php');
        exit;
    }


    foreach (
        $_SESSION['cart']
        as $productId => $item
    ) {

        $productId =
            (int)$productId;

        if ($productId <= 0) {
            continue;
        }


        if (is_array($item)) {

            $quantity =
                (int)($item['quantity'] ?? 1);

        } else {

            $quantity = 1;
        }


        if ($quantity < 1) {
            continue;
        }


        $orderItems[] = [

            'product_id' =>
                $productId,

            'quantity' =>
                $quantity
        ];
    }


    if (empty($orderItems)) {

        $_SESSION['checkout_error'] =
            'Your cart is empty.';

        header('Location: cart.php');
        exit;
    }

}


// =====================================================
// BUY NOW CHECKOUT
// =====================================================

elseif ($checkoutType === 'buy_now') {

    $productId =
        filter_input(
            INPUT_POST,
            'product_id',
            FILTER_VALIDATE_INT
        );


    $quantity =
        filter_input(
            INPUT_POST,
            'quantity',
            FILTER_VALIDATE_INT
        );


    if (
        !$productId ||
        !$quantity ||
        $quantity < 1
    ) {

        if ($isBuyNowRequest) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid product or quantity.'
            ]);
            exit;
        }

        $_SESSION['checkout_error'] =
            'Invalid product or quantity.';

        header('Location: index.php');
        exit;
    }


    $orderItems[] = [

        'product_id' =>
            (int)$productId,

        'quantity' =>
            (int)$quantity
    ];

}


// =====================================================
// INVALID CHECKOUT TYPE
// =====================================================

else {

    $_SESSION['checkout_error'] =
        'Invalid checkout request.';

    header('Location: cart.php');
    exit;
}


// =====================================================
// PROCESS ORDER
// =====================================================

try {

    $pdo->beginTransaction();


    $orderIds = [];

    $grandTotal = 0.00;


    // =================================================
    // PROCESS EACH PRODUCT
    // =================================================

    foreach (
        $orderItems as $orderItem
    ) {

        $productId =
            (int)$orderItem['product_id'];

        $quantity =
            (int)$orderItem['quantity'];


        // ---------------------------------------------
        // GET PRODUCT
        // ---------------------------------------------

        $productStmt =
            $pdo->prepare("
                SELECT
                    id,
                    name,
                    price,
                    stock
                FROM products
                WHERE id = ?
                FOR UPDATE
            ");

        $productStmt->execute([
            $productId
        ]);

        $product =
            $productStmt->fetch(
                PDO::FETCH_ASSOC
            );


        // ---------------------------------------------
        // PRODUCT NOT FOUND
        // ---------------------------------------------

        if (!$product) {

            throw new Exception(
                'Product not found.'
            );
        }


        // ---------------------------------------------
        // STOCK
        // ---------------------------------------------

        $stock =
            (int)$product['stock'];


        if ($stock <= 0) {

            throw new Exception(
                $product['name'] .
                ' is out of stock.'
            );
        }


        if ($quantity > $stock) {

            throw new Exception(
                'Not enough stock for ' .
                $product['name'] .
                '. Only ' .
                $stock .
                ' available.'
            );
        }


        // ---------------------------------------------
        // PRICE
        // ---------------------------------------------

        $price =
            (float)$product['price'];


        $totalAmount =
            $price * $quantity;


        $grandTotal +=
            $totalAmount;


        // ---------------------------------------------
        // DEDUCT STOCK
        // ---------------------------------------------

        $stockStmt =
            $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
                  AND stock >= ?
            ");

        $stockStmt->execute([

            $quantity,

            $productId,

            $quantity

        ]);


        if (
            $stockStmt->rowCount() !== 1
        ) {

            throw new Exception(
                'Unable to update stock for ' .
                $product['name'] .
                '.'
            );
        }


        // ---------------------------------------------
        // INSERT ORDER
        // ---------------------------------------------

        $orderStmt =
            $pdo->prepare("
                INSERT INTO orders
                (
                    users_id,
                    products_id,
                    quantity,
                    total_amount,
                    full_name,
                    phone,
                    address,
                    payment_method,
                    status,
                    created_at,
                    user_id,
                    product_id
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    ?,
                    ?
                )
            ");


        $orderStmt->execute([

            // users_id
            $userId,

            // products_id
            $productId,

            // quantity
            $quantity,

            // total_amount
            $totalAmount,

            // full_name
            $fullName,

            // phone
            $phone,

            // address
            $address,

            // payment_method
            $paymentMethod,

            // status
            'Pending',

            // user_id
            $userId,

            // product_id
            $productId
        ]);


        $orderIds[] =
            $pdo->lastInsertId();
    }


    // =================================================
    // COMMIT
    // =================================================

    $pdo->commit();


    // =================================================
    // CLEAR CART
    // =================================================

    if ($checkoutType === 'cart') {

        $_SESSION['cart'] = [];
    }


    // =================================================
    // SUCCESS MESSAGE
    // =================================================

    $_SESSION['checkout_success'] =
        'Order placed successfully!';


    $_SESSION['checkout_order_ids'] =
        $orderIds;

    if ($isBuyNowRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!'
        ]);
        exit;
    }


    // =================================================
    // REDIRECT
    // =================================================

    if ($checkoutType === 'cart') {

        header('Location: cart.php');

    } else {

        header('Location: index.php');
    }

    exit;


} catch (Exception $e) {

    // =================================================
    // ROLLBACK
    // =================================================

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    // =================================================
    // ERROR
    // =================================================

    $_SESSION['checkout_error'] =
        $e->getMessage();

    if ($isBuyNowRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }


    // =================================================
    // REDIRECT
    // =================================================

    if ($checkoutType === 'cart') {

        header('Location: cart.php');

    } else {

        header('Location: index.php');
    }

    exit;
}
?>