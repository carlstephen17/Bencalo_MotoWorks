<?php
// /admin/admin_orders.php
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Search and status filter parameters
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

// Build query with search and status filters
$query = "
    SELECT 
        o.*, 
        u.username AS customer_name,
        COALESCE(p1.name, p2.name) AS product_name,
        COALESCE(p1.id, p2.id) AS actual_product_id
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN products p1 ON o.product_id = p1.id
    LEFT JOIN products p2 ON o.products_id = p2.id
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $query .= " AND (
        o.id LIKE ?
        OR u.username LIKE ?
        OR o.full_name LIKE ?
        OR COALESCE(p1.name, p2.name) LIKE ?
    )";

    $term = "%$search%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($status_filter) && $status_filter !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY o.id ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// AJAX REQUEST FOR LIVE SEARCH / FILTERING
// ============================================================

if (isset($_GET['ajax'])) {

    if (empty($orders)) {

        echo '<tr>
                <td colspan="7" class="text-center" style="padding: 30px; color: #64748b;">
                    No orders found matching your criteria.
                </td>
              </tr>';

    } else {

        foreach ($orders as $order) {

            $formattedDateForInput = !empty($order['created_at'])
                ? date('Y-m-d\TH:i', strtotime($order['created_at']))
                : '';

            $totalAmountVal = $order['total_amount'] ?? $order['total'] ?? 0;

            // Use the full name stored in the order first
            $customerNameVal = !empty($order['full_name'])
                ? $order['full_name']
                : ($order['customer_name'] ?? 'Guest/Unknown');

            // Use product from either product_id or products_id
            $productNameVal = $order['product_name'] ?? 'N/A';

            // Correct product ID from either column
            $actualProductId = $order['actual_product_id']
                ?? $order['product_id']
                ?? $order['products_id']
                ?? '';

            $statusVal = $order['status'] ?? 'Pending';

            $createdAtVal = $order['created_at'] ?? 'N/A';


            // Status badge
            $badgeStyle = 'background: #ffebee; color: #c62828;';

            if ($statusVal === 'Completed') {

                $badgeStyle = 'background: #e8f5e9; color: #2e7d32;';

            } elseif ($statusVal === 'Pending') {

                $badgeStyle = 'background: #fff3e0; color: #f57c00;';

            } elseif ($statusVal === 'Confirmed') {

                $badgeStyle = 'background: #e3f2fd; color: #1565c0;';

            }


            echo '<tr>';

            echo '<td>#' . $order['id'] . '</td>';

            echo '<td>'
                . htmlspecialchars($customerNameVal)
                . '</td>';

            echo '<td>'
                . htmlspecialchars($productNameVal)
                . '</td>';

            echo '<td>₱'
                . number_format($totalAmountVal, 2)
                . '</td>';

            echo '<td>
                    <span class="badge"
                        style="padding: 4px 8px; border-radius: 4px; ' . $badgeStyle . '">
                        ' . htmlspecialchars($statusVal) . '
                    </span>
                  </td>';

            echo '<td>'
                . htmlspecialchars($createdAtVal)
                . '</td>';

            echo '<td>';

            echo '<div style="display: flex; gap: 6px; align-items: center;">';


            // VIEW BUTTON
            echo '<button
                    type="button"
                    class="btn-sm btn-view-order"
                    data-id="' . $order['id'] . '"
                    data-customer="' . htmlspecialchars($customerNameVal, ENT_QUOTES) . '"
                    data-product="' . htmlspecialchars($productNameVal, ENT_QUOTES) . '"
                    data-total="₱' . number_format($totalAmountVal, 2) . '"
                    data-status="' . htmlspecialchars($statusVal, ENT_QUOTES) . '"
                    data-date="' . htmlspecialchars($createdAtVal, ENT_QUOTES) . '"
                    style="background: #6c757d; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer;"
                    title="View Order">

                    <i class="fas fa-eye"></i>

                  </button>';


            // EDIT BUTTON
            if (strtoupper($statusVal) !== 'COMPLETED') {
                echo '<button
                    type="button"
                    class="btn-sm btn-edit-order"
                    data-id="' . $order['id'] . '"
                    data-userid="' . $order['user_id'] . '"
                    data-productid="' . $actualProductId . '"
                    data-total="' . $totalAmountVal . '"
                    data-status="' . htmlspecialchars($statusVal, ENT_QUOTES) . '"
                    data-date="' . $formattedDateForInput . '"
                    style="background: #1976d2; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer;"
                    title="Edit Order">

                    <i class="fas fa-edit"></i>

                </button>';
            }


            // DELETE BUTTON
            echo '<a
                    href="actions/admin_order_delete.php?id=' . $order['id'] . '"
                    class="btn-sm"
                    style="background: #dc3545; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none;"
                    onclick="return confirm(\'Are you sure you want to delete order #' . $order['id'] . '?\');"
                    title="Delete Order">

                    <i class="fas fa-trash"></i>

                  </a>';


            echo '</div>';

            echo '</td>';

            echo '</tr>';
        }
    }

    exit();
}


// ============================================================
// FETCH PRODUCTS
// ============================================================

$productsStmt = $pdo->query("
    SELECT id, name
    FROM products
    ORDER BY name ASC
");

$allProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// FETCH USERS
// ============================================================

$usersStmt = $pdo->query("
    SELECT id, username
    FROM users
    ORDER BY username ASC
");

$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MotoWorks Admin - Orders Management</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link
        rel="stylesheet"
        href="css/admin_layout.css">

    <link
        rel="stylesheet"
        href="css/admin_products.css">

</head>


<body>


<div class="admin-layout">


    <?php include 'includes/admin_sidebar.php'; ?>


    <!-- MAIN CONTENT -->

    <main class="admin-main">


        <header
            class="main-header"
            style="display: flex; justify-content: space-between; align-items: center;">

            <h2>Customer Orders</h2>

            <div class="admin-user-info">
                <span>Order Tracking</span>
            </div>

        </header>


        <div class="admin-content-body">


            <?php if (isset($_GET['msg'])): ?>

                <div
                    class="alert-toast"
                    style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 4px; margin-bottom: 20px;">

                    <?= htmlspecialchars($_GET['msg']) ?>

                </div>

            <?php endif; ?>


            <!-- SEARCH AND FILTER -->

            <div
                style="background: #fff; padding: 16px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">

                <form
                    method="GET"
                    action="admin_orders.php"
                    id="filterForm"
                    onsubmit="return false;"
                    style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between;">


                    <input
                        type="text"
                        name="search"
                        id="searchInput"
                        placeholder="Search by Order ID, customer username, or product..."
                        value="<?= htmlspecialchars($search) ?>"
                        class="form-control"
                        style="flex: 1; min-width: 260px; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;"
                        autocomplete="off">


                    <div>

                        <?php

                        $statuses = [
                            '' => 'All Statuses',
                            'Pending' => 'Pending',
                            'Confirmed' => 'Confirmed',
                            'Completed' => 'Completed',
                            'Cancelled' => 'Cancelled'
                        ];

                        ?>


                        <select
                            name="status"
                            id="statusSelect"
                            class="form-control"
                            style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #fff; cursor: pointer;">

                            <?php foreach ($statuses as $key => $label): ?>

                                <option
                                    value="<?= $key ?>"
                                    <?= ($status_filter === $key) ? 'selected' : '' ?>>

                                    <?= $label ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </form>

            </div>


            <!-- ORDERS TABLE -->

            <div class="table-responsive">

                <table class="admin-table">


                    <thead>

                        <tr>

                            <th>Order ID</th>

                            <th>Customer</th>

                            <th>Product</th>

                            <th>Total Amount</th>

                            <th>Status</th>

                            <th>Order Date</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody id="ordersTableBody">


                    <?php if (empty($orders)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center"
                                style="padding: 30px; color: #64748b;">

                                No orders found matching your criteria.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($orders as $order): ?>


                            <?php

                            $formattedDateForInput = !empty($order['created_at'])
                                ? date(
                                    'Y-m-d\TH:i',
                                    strtotime($order['created_at'])
                                )
                                : '';

                            $totalAmountVal =
                                $order['total_amount']
                                ?? $order['total']
                                ?? 0;


                            // FIXED CUSTOMER NAME
                            $customerNameVal =
                                !empty($order['full_name'])
                                ? $order['full_name']
                                : ($order['customer_name'] ?? 'Guest/Unknown');


                            // FIXED PRODUCT NAME
                            $productNameVal =
                                $order['product_name']
                                ?? 'N/A';


                            // FIXED PRODUCT ID
                            $actualProductId =
                                $order['actual_product_id']
                                ?? $order['product_id']
                                ?? $order['products_id']
                                ?? '';


                            $statusVal =
                                $order['status']
                                ?? 'Pending';


                            $createdAtVal =
                                $order['created_at']
                                ?? 'N/A';


                            $badgeStyle =
                                'background: #ffebee; color: #c62828;';


                            if ($statusVal === 'Completed') {

                                $badgeStyle =
                                    'background: #e8f5e9; color: #2e7d32;';

                            } elseif ($statusVal === 'Pending') {

                                $badgeStyle =
                                    'background: #fff3e0; color: #f57c00;';

                            } elseif ($statusVal === 'Confirmed') {

                                $badgeStyle =
                                    'background: #e3f2fd; color: #1565c0;';

                            }

                            ?>


                            <tr>


                                <td>
                                    #<?= $order['id'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($customerNameVal) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($productNameVal) ?>
                                </td>


                                <td>
                                    ₱<?= number_format($totalAmountVal, 2) ?>
                                </td>


                                <td>

                                    <span
                                        class="badge"
                                        style="padding: 4px 8px; border-radius: 4px; <?= $badgeStyle ?>">

                                        <?= htmlspecialchars($statusVal) ?>

                                    </span>

                                </td>


                                <td>
                                    <?= htmlspecialchars($createdAtVal) ?>
                                </td>


                                <td>


                                    <div
                                        style="display: flex; gap: 6px; align-items: center;">


                                        <!-- VIEW -->

                                        <button
                                            type="button"
                                            class="btn-sm btn-view-order"
                                            data-id="<?= $order['id'] ?>"
                                            data-customer="<?= htmlspecialchars($customerNameVal, ENT_QUOTES) ?>"
                                            data-product="<?= htmlspecialchars($productNameVal, ENT_QUOTES) ?>"
                                            data-total="₱<?= number_format($totalAmountVal, 2) ?>"
                                            data-status="<?= htmlspecialchars($statusVal, ENT_QUOTES) ?>"
                                            data-date="<?= htmlspecialchars($createdAtVal, ENT_QUOTES) ?>"
                                            style="background: #6c757d; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer;"
                                            title="View Order">

                                            <i class="fas fa-eye"></i>

                                        </button>


                                        <!-- EDIT -->
                                        <?php if (strtoupper($statusVal) !== 'COMPLETED'): ?>
                                        <button
                                            type="button"
                                            class="btn-sm btn-edit-order"
                                            data-id="<?= $order['id'] ?>"
                                            data-userid="<?= $order['user_id'] ?>"
                                            data-productid="<?= $actualProductId ?>"
                                            data-total="<?= $totalAmountVal ?>"
                                            data-status="<?= htmlspecialchars($statusVal, ENT_QUOTES) ?>"
                                            data-date="<?= $formattedDateForInput ?>"
                                            style="background: #1976d2; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer;"
                                            title="Edit Order">

                                            <i class="fas fa-edit"></i>

                                        </button>
                                        <?php endif; ?>


                                        <!-- DELETE -->

                                        <a
                                            href="actions/admin_order_delete.php?id=<?= $order['id'] ?>"
                                            class="btn-sm"
                                            style="background: #dc3545; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none;"
                                            onclick="return confirm('Are you sure you want to delete order #<?= $order['id'] ?>?');"
                                            title="Delete Order">

                                            <i class="fas fa-trash"></i>

                                        </a>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>



<!-- ============================================================
     VIEW ORDER MODAL
============================================================ -->

<div
    class="modal-overlay"
    id="viewOrderModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">


    <div
        class="modal-content"
        style="background: #fff; padding: 30px; border-radius: 8px; width: 450px; max-width: 90%;">


        <div
            class="modal-header"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">


            <h3 id="viewModalTitle">
                Order Details
            </h3>


            <button
                type="button"
                onclick="closeViewModal()"
                style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">

                &times;

            </button>

        </div>


        <div
            style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">


            <div>
                <strong>Order ID:</strong>
                <span id="view_order_id"></span>
            </div>


            <div>
                <strong>Customer:</strong>
                <span id="view_customer_name"></span>
            </div>


            <div>
                <strong>Product:</strong>
                <span id="view_product_name"></span>
            </div>


            <div>
                <strong>Total Amount:</strong>
                <span id="view_total_amount"></span>
            </div>


            <div>
                <strong>Status:</strong>
                <span id="view_status"></span>
            </div>


            <div>
                <strong>Order Date:</strong>
                <span id="view_created_at"></span>
            </div>


        </div>


        <div style="display: flex; justify-content: flex-end;">


            <button
                type="button"
                class="btn-secondary"
                onclick="closeViewModal()"
                style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">

                Close

            </button>


        </div>


    </div>

</div>



<!-- ============================================================
     EDIT ORDER MODAL
============================================================ -->

<div
    class="modal-overlay"
    id="editOrderModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">


    <div
        class="modal-content"
        style="background: #fff; padding: 30px; border-radius: 8px; width: 450px; max-width: 90%;">


        <div
            class="modal-header"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">


            <h3 id="modalOrderTitle">
                Edit Order
            </h3>


            <button
                type="button"
                onclick="closeEditModal()"
                style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">

                &times;

            </button>

        </div>


        <form
            action="actions/admin_order_update.php"
            method="POST">


            <input
                type="hidden"
                name="csrf_token"
                value="<?= generateCSRFToken() ?>">


            <input
                type="hidden"
                name="order_id"
                id="modal_order_id">


            <!-- CUSTOMER -->

            <div
                class="form-group"
                style="margin-bottom: 15px;">

                <label
                    style="display: block; margin-bottom: 5px; font-weight: 500;">

                    Customer:

                </label>


                <select
                    name="user_id"
                    id="modal_order_user_id"
                    class="form-control"
                    style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;"
                    required>


                    <option value="">
                        Select Customer
                    </option>


                    <?php foreach ($allUsers as $user): ?>

                        <option value="<?= $user['id'] ?>">

                            <?= htmlspecialchars($user['username']) ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- PRODUCT -->

            <div
                class="form-group"
                style="margin-bottom: 15px;">


                <label
                    style="display: block; margin-bottom: 5px; font-weight: 500;">

                    Product:

                </label>


                <select
                    name="product_id"
                    id="modal_order_product_id"
                    class="form-control"
                    style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;"
                    required>


                    <option value="">
                        Select Product
                    </option>


                    <?php foreach ($allProducts as $product): ?>

                        <option value="<?= $product['id'] ?>">

                            <?= htmlspecialchars($product['name']) ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- TOTAL -->

            <div
                class="form-group"
                style="margin-bottom: 15px;">


                <label
                    style="display: block; margin-bottom: 5px; font-weight: 500;">

                    Total Amount (₱):

                </label>


                <input
                    type="number"
                    step="0.01"
                    name="total_amount"
                    id="modal_order_total_amount"
                    class="form-control"
                    style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;"
                    required>

            </div>


            <!-- STATUS -->

            <div
                class="form-group"
                style="margin-bottom: 15px;">


                <label
                    style="display: block; margin-bottom: 5px; font-weight: 500;">

                    Status:

                </label>


                <select
                    name="status"
                    id="modal_order_status"
                    class="form-control"
                    style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;"
                    required>


                    <option value="Pending">
                        Pending
                    </option>

                    <option value="Confirmed">
                        Confirmed
                    </option>

                    <option value="Completed">
                        Completed
                    </option>

                    <option value="Cancelled">
                        Cancelled
                    </option>


                </select>

            </div>


            <!-- DATE -->

            <div
                class="form-group"
                style="margin-bottom: 20px;">


                <label
                    style="display: block; margin-bottom: 5px; font-weight: 500;">

                    Order Date & Time:

                </label>


                <input
                    type="datetime-local"
                    name="created_at"
                    id="modal_order_created_at"
                    class="form-control"
                    style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;"
                    required>

            </div>


            <!-- FOOTER -->

            <div
                class="modal-footer"
                style="display: flex; justify-content: flex-end; gap: 10px;">


                <button
                    type="button"
                    class="btn-secondary"
                    onclick="closeEditModal()"
                    style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">

                    Cancel

                </button>


                <button
                    type="submit"
                    class="btn-primary"
                    style="padding: 8px 15px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">

                    Save Changes

                </button>


            </div>


        </form>

    </div>

</div>



<script>


// ============================================================
// CLOSE VIEW MODAL
// ============================================================

function closeViewModal() {

    document.getElementById('viewOrderModal').style.display = 'none';

}


// ============================================================
// CLOSE EDIT MODAL
// ============================================================

function closeEditModal() {

    document.getElementById('editOrderModal').style.display = 'none';

}



// ============================================================
// DOM READY
// ============================================================

document.addEventListener('DOMContentLoaded', () => {


    const searchInput =
        document.getElementById('searchInput');


    const statusSelect =
        document.getElementById('statusSelect');


    const tableBody =
        document.getElementById('ordersTableBody');


    let searchTimeout;



    // ========================================================
    // FETCH FILTERED ORDERS
    // ========================================================

    function fetchFilteredOrders() {


        const searchValue =
            searchInput.value;


        const statusValue =
            statusSelect.value;


        const params =
            new URLSearchParams({

                search: searchValue,

                status: statusValue,

                ajax: '1'

            });


        const endpoint =
            window.location.pathname;


        fetch(endpoint + '?' + params.toString())


            .then(response => response.text())


            .then(html => {


                tableBody.innerHTML = html;


                const newUrl =
                    endpoint + '?' +
                    new URLSearchParams({

                        search: searchValue,

                        status: statusValue

                    }).toString();


                window.history.replaceState(
                    {},
                    '',
                    newUrl
                );


            });

    }



    // ========================================================
    // LIVE SEARCH
    // ========================================================

    if (searchInput) {


        searchInput.addEventListener(
            'input',
            function() {


                clearTimeout(searchTimeout);


                searchTimeout =
                    setTimeout(
                        fetchFilteredOrders,
                        100
                    );


            }
        );



        searchInput.addEventListener(
            'keydown',
            function(e) {


                if (e.key === 'Enter') {


                    e.preventDefault();


                    clearTimeout(searchTimeout);


                    fetchFilteredOrders();


                }

            }
        );

    }



    // ========================================================
    // STATUS FILTER
    // ========================================================

    if (statusSelect) {


        statusSelect.addEventListener(
            'change',
            fetchFilteredOrders
        );

    }



    // ========================================================
    // VIEW / EDIT BUTTONS
    // ========================================================

    document.addEventListener(
        'click',
        function(e) {


            // VIEW BUTTON

            const viewBtn =
                e.target.closest('.btn-view-order');


            if (viewBtn) {


                const ds =
                    viewBtn.dataset;


                document.getElementById(
                    'viewModalTitle'
                ).innerText =
                    'Order #' +
                    ds.id +
                    ' Details';


                document.getElementById(
                    'view_order_id'
                ).innerText =
                    '#' + ds.id;


                document.getElementById(
                    'view_customer_name'
                ).innerText =
                    ds.customer;


                document.getElementById(
                    'view_product_name'
                ).innerText =
                    ds.product;


                document.getElementById(
                    'view_total_amount'
                ).innerText =
                    ds.total;


                document.getElementById(
                    'view_status'
                ).innerText =
                    ds.status;


                document.getElementById(
                    'view_created_at'
                ).innerText =
                    ds.date;


                document.getElementById(
                    'viewOrderModal'
                ).style.display =
                    'flex';

            }



            // EDIT BUTTON

            const editBtn =
                e.target.closest('.btn-edit-order');


            if (editBtn) {


                const ds =
                    editBtn.dataset;


                document.getElementById(
                    'modal_order_id'
                ).value =
                    ds.id;


                document.getElementById(
                    'modalOrderTitle'
                ).innerText =
                    'Edit Order #' +
                    ds.id;


                document.getElementById(
                    'modal_order_user_id'
                ).value =
                    ds.userid;


                document.getElementById(
                    'modal_order_product_id'
                ).value =
                    ds.productid;


                document.getElementById(
                    'modal_order_total_amount'
                ).value =
                    ds.total;


                document.getElementById(
                    'modal_order_status'
                ).value =
                    ds.status;


                document.getElementById(
                    'modal_order_created_at'
                ).value =
                    ds.date;


                document.getElementById(
                    'editOrderModal'
                ).style.display =
                    'flex';

            }

        }
    );

});

</script>


</body>

</html>