<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

// Normalize database connection object
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

// Resolve logged-in user ID
$userId = $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? $_SESSION['user']['id']
    ?? $_SESSION['user_id_pk']
    ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

// Load functions/auth helpers if available
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
}

if (file_exists('includes/auth.php')) {
    require_once 'includes/auth.php';
}

$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| Handle Cancel / Delete Order
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete_id']) && isset($pdo)) {

    $deleteId = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);

    if ($deleteId) {

        try {

            $pdo->beginTransaction();

            // IMPORTANT:
            // Orders table uses users_id and products_id
            $stmt = $pdo->prepare("
                SELECT products_id, quantity
                FROM orders
                WHERE id = ?
                  AND users_id = ?
                  AND status = 'Pending'
                FOR UPDATE
            ");

            $stmt->execute([$deleteId, $userId]);

            $orderToCancel = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($orderToCancel) {

                // Delete only this user's pending order
                $stmtDel = $pdo->prepare("
                    DELETE FROM orders
                    WHERE id = ?
                      AND users_id = ?
                      AND status = 'Pending'
                ");

                $stmtDel->execute([$deleteId, $userId]);

                if ($stmtDel->rowCount() > 0) {

                    // Restore stock
                    if (
                        !empty($orderToCancel['products_id']) &&
                        (int)$orderToCancel['quantity'] > 0
                    ) {

                        $updateStock = $pdo->prepare("
                            UPDATE products
                            SET stock = stock + ?
                            WHERE id = ?
                        ");

                        $updateStock->execute([
                            (int)$orderToCancel['quantity'],
                            (int)$orderToCancel['products_id']
                        ]);
                    }

                    $pdo->commit();

                    $message = "Order successfully cancelled and inventory restored.";
                    $messageType = "success";

                } else {

                    $pdo->rollBack();

                    $message = "Cannot cancel this order. It may already be processed or completed.";
                    $messageType = "error";
                }

            } else {

                $pdo->rollBack();

                $message = "Order not found or cannot be cancelled.";
                $messageType = "error";
            }

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = "Error cancelling order: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Handle Edit Order
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'edit_order' &&
    isset($pdo)
) {

    $orderId = filter_input(
        INPUT_POST,
        'order_id',
        FILTER_VALIDATE_INT
    );

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $quantity = filter_input(
        INPUT_POST,
        'quantity',
        FILTER_VALIDATE_INT
    );

    if (
        !$orderId ||
        empty($fullName) ||
        empty($phone) ||
        empty($address) ||
        !$quantity ||
        $quantity < 1
    ) {

        $message = "All fields are required and quantity must be at least 1.";
        $messageType = "error";

    } else {

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Get existing order
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | users_id and products_id match process_checkout.php
            */

            $checkStmt = $pdo->prepare("
                SELECT
                    o.*,
                    p.price AS product_price,
                    p.stock AS current_stock
                FROM orders o
                LEFT JOIN products p
                    ON o.products_id = p.id
                WHERE o.id = ?
                  AND o.users_id = ?
                  AND o.status = 'Pending'
                FOR UPDATE
            ");

            $checkStmt->execute([
                $orderId,
                $userId
            ]);

            $existingOrder = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingOrder) {

                $pdo->rollBack();

                $message = "Order not found or cannot be edited.";
                $messageType = "error";

            } else {

                $oldQuantity = (int)($existingOrder['quantity'] ?? 1);

                $quantityDiff = $quantity - $oldQuantity;

                $currentStock = (int)(
                    $existingOrder['current_stock'] ?? 0
                );

                /*
                |--------------------------------------------------------------------------
                | Check additional stock when increasing quantity
                |--------------------------------------------------------------------------
                */

                if (
                    $quantityDiff > 0 &&
                    $quantityDiff > $currentStock
                ) {

                    $pdo->rollBack();

                    $message =
                        "Cannot increase quantity by {$quantityDiff}. " .
                        "Only {$currentStock} items left in stock.";

                    $messageType = "error";

                } else {

                    $unitPrice = (float)(
                        $existingOrder['product_price']
                        ?? (
                            (float)$existingOrder['total_amount']
                            / max($oldQuantity, 1)
                        )
                    );

                    $newTotalAmount = $quantity * $unitPrice;

                    /*
                    |--------------------------------------------------------------------------
                    | Update order information
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare("
                        UPDATE orders
                        SET
                            full_name = ?,
                            phone = ?,
                            address = ?,
                            quantity = ?,
                            total_amount = ?
                        WHERE id = ?
                          AND users_id = ?
                          AND status = 'Pending'
                    ");

                    $stmt->execute([
                        $fullName,
                        $phone,
                        $address,
                        $quantity,
                        $newTotalAmount,
                        $orderId,
                        $userId
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Adjust stock
                    |--------------------------------------------------------------------------
                    |
                    | Example:
                    | Old quantity = 1
                    | New quantity = 3
                    | Difference = +2
                    | Stock decreases by 2
                    |
                    | Old quantity = 3
                    | New quantity = 1
                    | Difference = -2
                    | Stock increases by 2
                    |
                    */

                    if (
                        $quantityDiff !== 0 &&
                        !empty($existingOrder['products_id'])
                    ) {

                        $updateStock = $pdo->prepare("
                            UPDATE products
                            SET stock = stock - ?
                            WHERE id = ?
                        ");

                        $updateStock->execute([
                            $quantityDiff,
                            (int)$existingOrder['products_id']
                        ]);
                    }

                    $pdo->commit();

                    $message =
                        "Order #{$orderId} updated successfully!";

                    $messageType = "success";
                }
            }

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message =
                "Database Error: " . $e->getMessage();

            $messageType = "error";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fetch User's Orders
|--------------------------------------------------------------------------
|
| IMPORTANT:
| This directly queries the orders table using:
|   orders.users_id
|   orders.products_id
|
| This matches process_checkout.php.
|--------------------------------------------------------------------------
*/

$orders = [];

if (isset($pdo)) {

    try {

        $stmt = $pdo->prepare("
            SELECT
                o.*,
                p.name AS product_name,
                p.image AS product_image,
                p.price AS product_price
            FROM orders o
            LEFT JOIN products p
                ON o.products_id = p.id
            WHERE o.users_id = ?
            ORDER BY o.id ASC
        ");

        $stmt->execute([$userId]);

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {

        $orders = [];

        // Uncomment temporarily for debugging if needed:
        // die("Order History Error: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>My Order History - Bencalo MotoWorks</title>

    <link rel="stylesheet" href="styles/index/style.css">
    <link rel="stylesheet" href="styles/index/components.css">
    <link rel="stylesheet" href="styles/index/layout.css">
    <link rel="stylesheet" href="styles/index/modals.css">
    <link rel="stylesheet" href="styles/order_history.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

</head>

<body>

<?php

if (file_exists('components/header.php')) {
    require_once 'components/header.php';
}

?>

<div class="history-wrapper">

    <div class="history-container">

        <h2>My Order History</h2>

        <?php if (!empty($message)): ?>

            <div class="history-alert
                <?= $messageType === 'success'
                    ? 'history-alert-success'
                    : 'history-alert-error'
                ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <?php if (empty($orders)): ?>

            <p style="color: #9ca3af;">
                You haven't placed any orders yet.
            </p>

            <a
                href="shop.php"
                class="btn-row-action btn-edit"
                style="
                    margin-top: 15px;
                    display: inline-block;
                    text-decoration: none;
                "
            >
                Browse Shop
            </a>

        <?php else: ?>

            <div class="order-table-wrapper">

                <table class="styled-table">

                    <thead>

                        <tr>

                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date Placed</th>
                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($orders as $ord): ?>

                        <?php

                        $qty = (int)($ord['quantity'] ?? 1);

                        $unitPrice = (float)(
                            $ord['product_price']
                            ?? (
                                (float)$ord['total_amount']
                                / max($qty, 1)
                            )
                        );

                        $productName =
                            $ord['product_name']
                            ?? 'Custom Product';

                        $status =
                            $ord['status']
                            ?? 'Pending';

                        $createdAt =
                            $ord['created_at']
                            ?? '';

                        ?>

                        <tr>

                            <td>
                                #<?= (int)$ord['id'] ?>
                            </td>


                            <td>

                                <div
                                    style="
                                        display: flex;
                                        align-items: center;
                                        gap: 10px;
                                    "
                                >

                                    <?php if (!empty($ord['product_image'])): ?>

                                        <img
                                            src="<?= htmlspecialchars($ord['product_image']) ?>"
                                            alt=""
                                            style="
                                                width: 40px;
                                                height: 40px;
                                                object-fit: cover;
                                                border-radius: 6px;
                                                border: 1px solid #374151;
                                            "
                                        >

                                    <?php endif; ?>

                                    <span>
                                        <?= htmlspecialchars($productName) ?>
                                    </span>

                                </div>

                            </td>


                            <td>
                                <?= $qty ?>
                            </td>


                            <td>
                                ₱<?= number_format($unitPrice, 2) ?>
                            </td>


                            <td>
                                ₱<?= number_format(
                                    (float)$ord['total_amount'],
                                    2
                                ) ?>
                            </td>


                            <td>

                                <?php
                                $statusClass =
                                    strtolower($status) === 'pending'
                                        ? 'badge-pending'
                                        : 'badge-completed';
                                ?>

                                <span
                                    class="badge <?= $statusClass ?>"
                                >
                                    <?= htmlspecialchars($status) ?>
                                </span>

                            </td>


                            <td>
                                <?= htmlspecialchars($createdAt) ?>
                            </td>


                            <td>

                                <div class="table-actions">

                                    <!-- VIEW -->

                                    <button
                                        type="button"
                                        class="btn-row-action btn-view"
                                        onclick="openViewModal(
                                            <?= (int)$ord['id'] ?>,
                                            <?= htmlspecialchars(
                                                json_encode($productName),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= $qty ?>,
                                            <?= htmlspecialchars(
                                                json_encode(
                                                    '₱' .
                                                    number_format(
                                                        $unitPrice,
                                                        2
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= htmlspecialchars(
                                                json_encode(
                                                    '₱' .
                                                    number_format(
                                                        (float)$ord['total_amount'],
                                                        2
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= htmlspecialchars(
                                                json_encode($status),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= htmlspecialchars(
                                                json_encode($createdAt),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= htmlspecialchars(
                                                json_encode(
                                                    $ord['full_name'] ?? ''
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= htmlspecialchars(
                                                json_encode(
                                                    $ord['phone'] ?? ''
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= htmlspecialchars(
                                                json_encode(
                                                    $ord['address'] ?? ''
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        )"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </button>


                                    <!-- EDIT -->

                                    <?php if ($status === 'Pending'): ?>

                                        <button
                                            type="button"
                                            class="btn-row-action btn-edit"
                                            onclick="openEditModal(
                                                <?= (int)$ord['id'] ?>,
                                                <?= htmlspecialchars(
                                                    json_encode(
                                                        $ord['full_name'] ?? ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>,
                                                <?= htmlspecialchars(
                                                    json_encode(
                                                        $ord['phone'] ?? ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>,
                                                <?= htmlspecialchars(
                                                    json_encode(
                                                        $ord['address'] ?? ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>,
                                                <?= $qty ?>,
                                                <?= $unitPrice ?>
                                            )"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            Edit
                                        </button>


                                        <!-- CANCEL -->

                                        <button
                                            type="button"
                                            class="btn-row-action btn-cancel"
                                            onclick="
                                                if (
                                                    confirm(
                                                        'Are you sure you want to cancel and delete this order?'
                                                    )
                                                ) {
                                                    window.location.href =
                                                        'order_history.php?delete_id=<?= (int)$ord['id'] ?>';
                                                }
                                            "
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                            Cancel
                                        </button>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>


        <a href="shop.php" class="back-link">

            <i class="fa-solid fa-arrow-left"></i>

            Back to Shop

        </a>

    </div>

</div>


<!-- =========================================================
     VIEW ORDER MODAL
========================================================= -->

<div class="modal-overlay" id="viewModal">

    <div class="modal-card">

        <div class="modal-header">

            <h3>
                Order Details
                <span id="viewModalOrderId"></span>
            </h3>

            <button
                type="button"
                class="modal-close"
                onclick="closeViewModal()"
            >
                &times;
            </button>

        </div>


        <div
            style="
                display: flex;
                flex-direction: column;
                gap: 12px;
                font-size: 0.95rem;
                color: #f0f6fc;
            "
        >

            <div>
                <strong style="color: #8b949e;">
                    Product:
                </strong>

                <span id="viewModalProduct"></span>
            </div>


            <div>
                <strong style="color: #8b949e;">
                    Quantity:
                </strong>

                <span id="viewModalQuantity"></span>
            </div>


            <div>
                <strong style="color: #8b949e;">
                    Price (Unit):
                </strong>

                <span id="viewModalUnitPrice"></span>
            </div>


            <div>
                <strong style="color: #8b949e;">
                    Total Amount:
                </strong>

                <span id="viewModalTotal"></span>
            </div>


            <div>
                <strong style="color: #8b949e;">
                    Status:
                </strong>

                <span id="viewModalStatus"></span>
            </div>


            <div>
                <strong style="color: #8b949e;">
                    Date Placed:
                </strong>

                <span id="viewModalDate"></span>
            </div>


            <div>
                <strong style="color: #8b949e;">
                    Full Name:
                </strong>

                <span id="viewModalFullName"></span>
            </div>


            <div>
                <strong style="color: #8b949e;">
                    Phone Number:
                </strong>

                <span id="viewModalPhone"></span>
            </div>


            <div>
                <strong style="color: #8b949e;">
                    Delivery Address:
                </strong>

                <span id="viewModalAddress"></span>
            </div>

        </div>


        <div
            class="modal-actions"
            style="margin-top: 24px;"
        >

            <button
                type="button"
                class="btn-modal-secondary"
                onclick="closeViewModal()"
            >
                Close
            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     EDIT ORDER MODAL
========================================================= -->

<div class="modal-overlay" id="editModal">

    <div class="modal-card">

        <div class="modal-header">

            <h3>
                Edit Order
                <span id="modalOrderIdDisplay"></span>
            </h3>

            <button
                type="button"
                class="modal-close"
                onclick="closeEditModal()"
            >
                &times;
            </button>

        </div>


        <form
            action="order_history.php"
            method="POST"
        >

            <input
                type="hidden"
                name="action"
                value="edit_order"
            >

            <input
                type="hidden"
                name="order_id"
                id="modalOrderId"
            >


            <div class="modal-form-group">

                <label for="modalFullName">
                    Full Name
                </label>

                <input
                    type="text"
                    id="modalFullName"
                    name="full_name"
                    class="modal-form-control"
                    required
                >

            </div>


            <div class="modal-form-group">

                <label for="modalPhone">
                    Phone Number
                </label>

                <input
                    type="text"
                    id="modalPhone"
                    name="phone"
                    class="modal-form-control"
                    required
                >

            </div>


            <div class="modal-form-group">

                <label for="modalQuantity">
                    Quantity
                </label>

                <input
                    type="number"
                    id="modalQuantity"
                    name="quantity"
                    min="1"
                    class="modal-form-control"
                    required
                    oninput="calculateEditTotal()"
                >

            </div>


            <div class="modal-form-group">

                <label>
                    Updated Total Price
                </label>

                <div
                    style="
                        font-size: 1.1rem;
                        font-weight: bold;
                        color: #10b981;
                    "
                    id="modalTotalDisplay"
                >
                    ₱0.00
                </div>

            </div>


            <div class="modal-form-group">

                <label for="modalAddress">
                    Delivery Address
                </label>

                <textarea
                    id="modalAddress"
                    name="address"
                    rows="3"
                    class="modal-form-control"
                    required
                ></textarea>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="btn-modal-secondary"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-modal-primary"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<?php

if (file_exists('components/footer.php')) {
    require_once 'components/footer.php';
}

?>


<script>

let currentUnitPrice = 0;


/*
|--------------------------------------------------------------------------
| View Modal
|--------------------------------------------------------------------------
*/

function openViewModal(
    id,
    product,
    quantity,
    unitPrice,
    total,
    status,
    date,
    fullName,
    phone,
    address
) {

    document.getElementById('viewModalOrderId').innerText =
        '#' + id;

    document.getElementById('viewModalProduct').innerText =
        product;

    document.getElementById('viewModalQuantity').innerText =
        quantity;

    document.getElementById('viewModalUnitPrice').innerText =
        unitPrice;

    document.getElementById('viewModalTotal').innerText =
        total;

    document.getElementById('viewModalStatus').innerText =
        status;

    document.getElementById('viewModalDate').innerText =
        date;

    document.getElementById('viewModalFullName').innerText =
        fullName;

    document.getElementById('viewModalPhone').innerText =
        phone;

    document.getElementById('viewModalAddress').innerText =
        address;

    document.getElementById('viewModal').style.display =
        'flex';
}


function closeViewModal() {

    document.getElementById('viewModal').style.display =
        'none';
}


/*
|--------------------------------------------------------------------------
| Edit Modal
|--------------------------------------------------------------------------
*/

function openEditModal(
    id,
    fullName,
    phone,
    address,
    quantity,
    unitPrice
) {

    document.getElementById('modalOrderId').value =
        id;

    document.getElementById('modalOrderIdDisplay').innerText =
        '#' + id;

    document.getElementById('modalFullName').value =
        fullName;

    document.getElementById('modalPhone').value =
        phone;

    document.getElementById('modalAddress').value =
        address;

    document.getElementById('modalQuantity').value =
        quantity;

    currentUnitPrice =
        parseFloat(unitPrice) || 0;

    calculateEditTotal();

    document.getElementById('editModal').style.display =
        'flex';
}


function calculateEditTotal() {

    const qty =
        parseInt(
            document.getElementById('modalQuantity').value
        ) || 0;

    const total =
        qty * currentUnitPrice;

    document.getElementById('modalTotalDisplay').innerText =
        '₱' +
        total.toLocaleString(
            'en-PH',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
}


function closeEditModal() {

    document.getElementById('editModal').style.display =
        'none';
}


/*
|--------------------------------------------------------------------------
| Close modal when clicking backdrop
|--------------------------------------------------------------------------
*/

window.addEventListener('click', function(event) {

    const editModal =
        document.getElementById('editModal');

    const viewModal =
        document.getElementById('viewModal');

    if (event.target === editModal) {
        closeEditModal();
    }

    if (event.target === viewModal) {
        closeViewModal();
    }

});

</script>

</body>
</html>