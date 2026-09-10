<?php
// /admin/admin_appointment.php

session_start();

require_once '../includes/config.php';
/** @var PDO $pdo */

require_once '../includes/auth.php';

// ============================================================
// ADMIN ACCESS
// ============================================================

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}


// ============================================================
// SEARCH AND STATUS FILTER
// ============================================================

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';


// ============================================================
// BUILD APPOINTMENT QUERY
// ============================================================

$query = "
    SELECT
        sb.*,
        s.name AS service_name,
        s.price AS service_price,
        u.username AS customer_username
    FROM service_bookings sb

    LEFT JOIN services s
        ON sb.services_id = s.id

    LEFT JOIN users u
        ON sb.user_id = u.id

    WHERE 1=1
";

$params = [];


// ============================================================
// SEARCH
// ============================================================

if (!empty($search)) {

    $query .= "
        AND (
            sb.id LIKE ?
            OR sb.full_name LIKE ?
            OR sb.contact_number LIKE ?
            OR sb.vehicle_model LIKE ?
            OR s.name LIKE ?
            OR u.username LIKE ?
        )
    ";

    $term = "%$search%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}


// ============================================================
// STATUS FILTER
// ============================================================

if (!empty($status_filter) && $status_filter !== 'all') {

    $query .= " AND sb.status = ?";

    $params[] = $status_filter;
}


// ============================================================
// SORT BY DATE AND TIME ASCENDING
// ============================================================

$query .= "
    ORDER BY
        sb.booking_date ASC,
        sb.booking_time ASC
";


// ============================================================
// EXECUTE QUERY
// ============================================================

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// AJAX REQUEST
// ============================================================

if (isset($_GET['ajax'])) {

    if (empty($appointments)) {

        echo '
        <tr>
            <td colspan="9"
                class="text-center"
                style="padding: 30px; color: #64748b;">

                No appointments found matching your criteria.

            </td>
        </tr>';

    } else {

        $index = 1;

        foreach ($appointments as $appointment) {

            $customerName = !empty($appointment['full_name'])
                ? $appointment['full_name']
                : ($appointment['customer_username'] ?? 'Guest/Unknown');

            $contactNumber =
                $appointment['contact_number'] ?? 'N/A';

            $vehicleModel =
                $appointment['vehicle_model'] ?? 'N/A';

            $serviceName =
                $appointment['service_name'] ?? 'N/A';

            $bookingDate =
                $appointment['booking_date'] ?? 'N/A';

            $bookingTime =
                $appointment['booking_time'] ?? 'N/A';

            $status =
                $appointment['status'] ?? 'Pending';

            $notes =
                $appointment['notes'] ?? '';


            // ------------------------------------------------
            // STATUS BADGE
            // ------------------------------------------------

            $badgeStyle =
                'background: #ffebee; color: #c62828;';

            if ($status === 'Completed') {

                $badgeStyle =
                    'background: #e8f5e9; color: #2e7d32;';

            } elseif ($status === 'Pending') {

                $badgeStyle =
                    'background: #fff3e0; color: #f57c00;';

            } elseif ($status === 'Confirmed') {

                $badgeStyle =
                    'background: #e3f2fd; color: #1565c0;';

            } elseif ($status === 'Cancelled') {

                $badgeStyle =
                    'background: #ffebee; color: #c62828;';

            }


            echo '<tr>';


            // ------------------------------------------------
            // APPOINTMENT NUMBER
            // ------------------------------------------------

            echo '<td>#' . $index++ . '</td>';


            // ------------------------------------------------
            // CUSTOMER
            // ------------------------------------------------

            echo '<td>'
                . htmlspecialchars($customerName)
                . '</td>';


            // ------------------------------------------------
            // CONTACT
            // ------------------------------------------------

            echo '<td>'
                . htmlspecialchars($contactNumber)
                . '</td>';


            // ------------------------------------------------
            // VEHICLE
            // ------------------------------------------------

            echo '<td>'
                . htmlspecialchars($vehicleModel)
                . '</td>';


            // ------------------------------------------------
            // SERVICE
            // ------------------------------------------------

            echo '<td>'
                . htmlspecialchars($serviceName)
                . '</td>';


            // ------------------------------------------------
            // DATE
            // ------------------------------------------------

            echo '<td>'
                . htmlspecialchars($bookingDate)
                . '</td>';


            // ------------------------------------------------
            // TIME
            // ------------------------------------------------

            echo '<td>'
                . htmlspecialchars($bookingTime)
                . '</td>';


            // ------------------------------------------------
            // STATUS
            // ------------------------------------------------

            echo '<td>

                <span
                    class="badge"
                    style="
                        padding: 4px 8px;
                        border-radius: 4px;
                        ' . $badgeStyle . '
                    ">

                    ' . htmlspecialchars($status) . '

                </span>

            </td>';


            // ------------------------------------------------
            // ACTIONS
            // ------------------------------------------------

            echo '<td>

                <div
                    style="
                        display: flex;
                        gap: 6px;
                        align-items: center;
                    "
                >';


            // ------------------------------------------------
            // VIEW BUTTON
            // ------------------------------------------------

            echo '<button
                    type="button"
                    class="btn-sm btn-view-appointment"

                    data-id="' . $appointment['id'] . '"

                    data-customer="' .
                        htmlspecialchars(
                            $customerName,
                            ENT_QUOTES
                        )
                    . '"

                    data-contact="' .
                        htmlspecialchars(
                            $contactNumber,
                            ENT_QUOTES
                        )
                    . '"

                    data-vehicle="' .
                        htmlspecialchars(
                            $vehicleModel,
                            ENT_QUOTES
                        )
                    . '"

                    data-service="' .
                        htmlspecialchars(
                            $serviceName,
                            ENT_QUOTES
                        )
                    . '"

                    data-date="' .
                        htmlspecialchars(
                            $bookingDate,
                            ENT_QUOTES
                        )
                    . '"

                    data-time="' .
                        htmlspecialchars(
                            $bookingTime,
                            ENT_QUOTES
                        )
                    . '"

                    data-status="' .
                        htmlspecialchars(
                            $status,
                            ENT_QUOTES
                        )
                    . '"

                    data-notes="' .
                        htmlspecialchars(
                            $notes,
                            ENT_QUOTES
                        )
                    . '"

                    style="
                        background: #6c757d;
                        color: white;
                        padding: 6px 10px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    "

                    title="View Appointment"
                >

                    <i class="fas fa-eye"></i>

                </button>';


            // ------------------------------------------------
            // EDIT BUTTON
            // ------------------------------------------------

            if (strtoupper(trim((string) $status)) !== 'COMPLETED') {
                echo '<button
                    type="button"
                    class="btn-sm btn-edit-appointment"

                    data-id="' . $appointment['id'] . '"

                    data-userid="' . ($appointment['user_id'] ?? '') . '"

                    data-customer="' .
                        htmlspecialchars(
                            $customerName,
                            ENT_QUOTES
                        )
                    . '"

                    data-contact="' .
                        htmlspecialchars(
                            $contactNumber,
                            ENT_QUOTES
                        )
                    . '"

                    data-vehicle="' .
                        htmlspecialchars(
                            $vehicleModel,
                            ENT_QUOTES
                        )
                    . '"

                    data-service-id="' .
                        htmlspecialchars(
                            $appointment['services_id'] ?? '',
                            ENT_QUOTES
                        )
                    . '"

                    data-date="' .
                        htmlspecialchars(
                            $bookingDate,
                            ENT_QUOTES
                        )
                    . '"

                    data-time="' .
                        htmlspecialchars(
                            $bookingTime,
                            ENT_QUOTES
                        )
                    . '"

                    data-status="' .
                        htmlspecialchars(
                            $status,
                            ENT_QUOTES
                        )
                    . '"

                    data-notes="' .
                        htmlspecialchars(
                            $notes,
                            ENT_QUOTES
                        )
                    . '"

                    style="
                        background: #1976d2;
                        color: white;
                        padding: 6px 10px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    "

                    title="Edit Appointment"
                >

                    <i class="fas fa-edit"></i>

                </button>';
            }


            // ------------------------------------------------
            // DELETE BUTTON
            // ------------------------------------------------

            echo '<a
                    href="actions/admin_appointment_delete.php?id=' .
                        $appointment['id']
                    . '"

                    class="btn-sm"

                    style="
                        background: #dc3545;
                        color: white;
                        padding: 6px 10px;
                        border-radius: 4px;
                        text-decoration: none;
                    "

                    onclick="return confirm(\'Are you sure you want to delete appointment #' .
                        $appointment['id']
                    . '?\');"

                    title="Delete Appointment"
                >

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
// FETCH SERVICES
// ============================================================

$servicesStmt = $pdo->query("
    SELECT id, name, price
    FROM services
    ORDER BY name ASC
");

$allServices =
    $servicesStmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// FETCH USERS
// ============================================================

$usersStmt = $pdo->query("
    SELECT id, username
    FROM users
    ORDER BY username ASC
");

$allUsers =
    $usersStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        MotoWorks Admin - Appointment Management
    </title>


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="css/admin_layout.css"
    >


    <link
        rel="stylesheet"
        href="css/admin_products.css"
    >

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include 'includes/admin_sidebar.php'; ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="admin-main">


        <!-- =================================================
             HEADER
        ================================================== -->

        <header
            class="main-header"
            style="
                display: flex;
                justify-content: space-between;
                align-items: center;
            "
        >

            <h2>
                Service Appointments
            </h2>


            <div class="admin-user-info">

                <span>
                    Appointment Tracking
                </span>

            </div>

        </header>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <div class="admin-content-body">


            <!-- =================================================
                 MESSAGE
            ================================================== -->

            <?php if (isset($_GET['msg'])): ?>

                <div
                    class="alert-toast"
                    style="
                        background: #d4edda;
                        color: #155724;
                        padding: 10px 15px;
                        border-radius: 4px;
                        margin-bottom: 20px;
                    "
                >

                    <?= htmlspecialchars($_GET['msg']) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 SEARCH AND FILTER
            ================================================== -->

            <div
                style="
                    background: #fff;
                    padding: 16px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                    border: 1px solid #e2e8f0;
                "
            >

                <form
                    method="GET"
                    action="admin_appointment.php"
                    id="filterForm"
                    onsubmit="return false;"

                    style="
                        display: flex;
                        gap: 12px;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: space-between;
                    "
                >


                    <!-- SEARCH -->

                    <input
                        type="text"
                        name="search"
                        id="searchInput"

                        placeholder="Search by customer, vehicle, service, or appointment ID..."

                        value="<?= htmlspecialchars($search) ?>"

                        class="form-control"

                        style="
                            flex: 1;
                            min-width: 260px;
                            padding: 9px 12px;
                            border: 1px solid #cbd5e1;
                            border-radius: 6px;
                            font-size: 0.9rem;
                        "

                        autocomplete="off"
                    >


                    <!-- STATUS -->

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

                            style="
                                padding: 9px 12px;
                                border: 1px solid #cbd5e1;
                                border-radius: 6px;
                                font-size: 0.9rem;
                                background: #fff;
                                cursor: pointer;
                            "
                        >

                            <?php foreach ($statuses as $key => $label): ?>

                                <option
                                    value="<?= htmlspecialchars($key) ?>"

                                    <?= ($status_filter === $key)
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars($label) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- CREATE APPOINTMENT -->

                    <button
                        type="button"
                        id="openCreateAppointment"

                        style="
                            background: #1976d2;
                            color: white;
                            padding: 9px 14px;
                            border: none;
                            border-radius: 6px;
                            cursor: pointer;
                        "
                    >

                        <i class="fas fa-plus"></i>

                        Add Appointment

                    </button>

                </form>

            </div>


            <!-- =================================================
                 APPOINTMENTS TABLE
            ================================================== -->

            <div class="table-responsive">

                <table class="admin-table">


                    <thead>

                        <tr>

                            <th>Appointment ID</th>

                            <th>Customer</th>

                            <th>Contact</th>

                            <th>Vehicle</th>

                            <th>Service</th>

                            <th>Date</th>

                            <th>Time</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody id="appointmentsTableBody">


                    <?php if (empty($appointments)): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="text-center"
                                style="
                                    padding: 30px;
                                    color: #64748b;
                                "
                            >

                                No appointments found matching your criteria.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php $index = 1; ?>


                        <?php foreach ($appointments as $appointment): ?>


                            <?php

                            $customerName =
                                !empty($appointment['full_name'])
                                ? $appointment['full_name']
                                : (
                                    $appointment['customer_username']
                                    ?? 'Guest/Unknown'
                                );


                            $contactNumber =
                                $appointment['contact_number']
                                ?? 'N/A';


                            $vehicleModel =
                                $appointment['vehicle_model']
                                ?? 'N/A';


                            $serviceName =
                                $appointment['service_name']
                                ?? 'N/A';


                            $bookingDate =
                                $appointment['booking_date']
                                ?? 'N/A';


                            $bookingTime =
                                $appointment['booking_time']
                                ?? 'N/A';


                            $status =
                                $appointment['status']
                                ?? 'Pending';


                            $notes =
                                $appointment['notes']
                                ?? '';


                            // ------------------------------------
                            // STATUS BADGE
                            // ------------------------------------

                            $badgeStyle =
                                'background: #ffebee; color: #c62828;';


                            if ($status === 'Completed') {

                                $badgeStyle =
                                    'background: #e8f5e9; color: #2e7d32;';

                            } elseif ($status === 'Pending') {

                                $badgeStyle =
                                    'background: #fff3e0; color: #f57c00;';

                            } elseif ($status === 'Confirmed') {

                                $badgeStyle =
                                    'background: #e3f2fd; color: #1565c0;';

                            }

                            ?>


                            <tr>


                                <!-- ID -->

                                <td>
                                    #<?= $index++ ?>
                                </td>


                                <!-- CUSTOMER -->

                                <td>
                                    <?= htmlspecialchars($customerName) ?>
                                </td>


                                <!-- CONTACT -->

                                <td>
                                    <?= htmlspecialchars($contactNumber) ?>
                                </td>


                                <!-- VEHICLE -->

                                <td>
                                    <?= htmlspecialchars($vehicleModel) ?>
                                </td>


                                <!-- SERVICE -->

                                <td>
                                    <?= htmlspecialchars($serviceName) ?>
                                </td>


                                <!-- DATE -->

                                <td>
                                    <?= htmlspecialchars($bookingDate) ?>
                                </td>


                                <!-- TIME -->

                                <td>
                                    <?= htmlspecialchars($bookingTime) ?>
                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="badge"

                                        style="
                                            padding: 4px 8px;
                                            border-radius: 4px;
                                            <?= $badgeStyle ?>
                                        "
                                    >

                                        <?= htmlspecialchars($status) ?>

                                    </span>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div
                                        style="
                                            display: flex;
                                            gap: 6px;
                                            align-items: center;
                                        "
                                    >


                                        <!-- VIEW -->

                                        <button
                                            type="button"

                                            class="btn-sm btn-view-appointment"

                                            data-id="<?= $appointment['id'] ?>"

                                            data-customer="<?= htmlspecialchars($customerName, ENT_QUOTES) ?>"

                                            data-contact="<?= htmlspecialchars($contactNumber, ENT_QUOTES) ?>"

                                            data-vehicle="<?= htmlspecialchars($vehicleModel, ENT_QUOTES) ?>"

                                            data-service="<?= htmlspecialchars($serviceName, ENT_QUOTES) ?>"

                                            data-date="<?= htmlspecialchars($bookingDate, ENT_QUOTES) ?>"

                                            data-time="<?= htmlspecialchars($bookingTime, ENT_QUOTES) ?>"

                                            data-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>"

                                            data-notes="<?= htmlspecialchars($notes, ENT_QUOTES) ?>"

                                            style="
                                                background: #6c757d;
                                                color: white;
                                                padding: 6px 10px;
                                                border: none;
                                                border-radius: 4px;
                                                cursor: pointer;
                                            "

                                            title="View Appointment"
                                        >

                                            <i class="fas fa-eye"></i>

                                        </button>


                                        <!-- EDIT -->
                                        <?php if (strtoupper(trim((string) $status)) !== 'COMPLETED'): ?>
                                        <button
                                            type="button"

                                            class="btn-sm btn-edit-appointment"

                                            data-id="<?= $appointment['id'] ?>"

                                            data-userid="<?= $appointment['user_id'] ?? '' ?>"

                                            data-service-id="<?= $appointment['services_id'] ?? '' ?>"

                                            data-customer="<?= htmlspecialchars($customerName, ENT_QUOTES) ?>"

                                            data-contact="<?= htmlspecialchars($contactNumber, ENT_QUOTES) ?>"

                                            data-vehicle="<?= htmlspecialchars($vehicleModel, ENT_QUOTES) ?>"

                                            data-date="<?= htmlspecialchars($bookingDate, ENT_QUOTES) ?>"

                                            data-time="<?= htmlspecialchars($bookingTime, ENT_QUOTES) ?>"

                                            data-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>"

                                            data-notes="<?= htmlspecialchars($notes, ENT_QUOTES) ?>"

                                            style="
                                                background: #1976d2;
                                                color: white;
                                                padding: 6px 10px;
                                                border: none;
                                                border-radius: 4px;
                                                cursor: pointer;
                                            "

                                            title="Edit Appointment"
                                        >

                                            <i class="fas fa-edit"></i>

                                        </button>
                                        <?php endif; ?>


                                        <!-- DELETE -->

                                        <a
                                            href="actions/admin_appointment_delete.php?id=<?= $appointment['id'] ?>"

                                            class="btn-sm"

                                            style="
                                                background: #dc3545;
                                                color: white;
                                                padding: 6px 10px;
                                                border-radius: 4px;
                                                text-decoration: none;
                                            "

                                            onclick="return confirm('Are you sure you want to delete appointment #<?= $appointment['id'] ?>?');"

                                            title="Delete Appointment"
                                        >

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
     VIEW APPOINTMENT MODAL
============================================================ -->

<div
    class="modal-overlay"
    id="viewAppointmentModal"

    style="
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    "
>


    <div
        class="modal-content"

        style="
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            width: 450px;
            max-width: 90%;
        "
    >


        <div
            class="modal-header"

            style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            "
        >

            <h3 id="viewModalTitle">
                Appointment Details
            </h3>


            <button
                type="button"

                onclick="closeViewModal()"

                style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                "
            >

                &times;

            </button>

        </div>


        <div
            style="
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-bottom: 20px;
            "
        >

            <div>

                <strong>
                    Appointment ID:
                </strong>

                <span id="view_appointment_id"></span>

            </div>


            <div>

                <strong>
                    Customer:
                </strong>

                <span id="view_customer_name"></span>

            </div>


            <div>

                <strong>
                    Contact:
                </strong>

                <span id="view_contact"></span>

            </div>


            <div>

                <strong>
                    Vehicle:
                </strong>

                <span id="view_vehicle"></span>

            </div>


            <div>

                <strong>
                    Service:
                </strong>

                <span id="view_service"></span>

            </div>


            <div>

                <strong>
                    Date:
                </strong>

                <span id="view_date"></span>

            </div>


            <div>

                <strong>
                    Time:
                </strong>

                <span id="view_time"></span>

            </div>


            <div>

                <strong>
                    Status:
                </strong>

                <span id="view_status"></span>

            </div>


            <div>

                <strong>
                    Notes:
                </strong>

                <span id="view_notes"></span>

            </div>

        </div>


        <div
            style="
                display: flex;
                justify-content: flex-end;
            "
        >

            <button
                type="button"

                onclick="closeViewModal()"

                style="
                    padding: 8px 15px;
                    background: #6c757d;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                "
            >

                Close

            </button>

        </div>

    </div>

</div>


<!-- ============================================================
     EDIT APPOINTMENT MODAL
============================================================ -->

<div
    class="modal-overlay"
    id="editAppointmentModal"

    style="
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    "
>


    <div
        class="modal-content"

        style="
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            width: 450px;
            max-width: 90%;
        "
    >


        <div
            class="modal-header"

            style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            "
        >

            <h3 id="editModalTitle">
                Edit Appointment
            </h3>


            <button
                type="button"

                onclick="closeEditModal()"

                style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                "
            >

                &times;

            </button>

        </div>


        <form
            action="actions/admin_appointment_update.php"
            method="POST"
        >


            <input
                type="hidden"
                name="csrf_token"
                value="<?= generateCSRFToken() ?>"
            >


            <input
                type="hidden"
                name="appointment_id"
                id="modal_appointment_id"
            >


            <!-- CUSTOMER IS PRESERVED FROM THE SELECTED APPOINTMENT -->

            <input
                type="hidden"
                name="users_id"
                id="modal_users_id"
            >

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Customer:

                </label>

                <input
                    type="text"
                    id="modal_customer_display"
                    class="form-control"
                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                        background: #f8f9fa;
                    "
                    readonly
                >

            </div>


            <!-- SERVICE -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Service:

                </label>


                <select
                    name="services_id"
                    id="modal_services_id"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

                    <option value="">
                        Select Service
                    </option>


                    <?php foreach ($allServices as $service): ?>

                        <option value="<?= $service['id'] ?>">

                            <?= htmlspecialchars($service['name']) ?>

                            <?php if (isset($service['price'])): ?>

                                - ₱<?= number_format($service['price'], 2) ?>

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- CUSTOMER NAME -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Full Name:

                </label>


                <input
                    type="text"
                    name="full_name"
                    id="modal_full_name"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

            </div>


            <!-- CONTACT -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Contact Number:

                </label>


                <input
                    type="tel"
                    name="contact_number"
                    id="modal_contact_number"
                    inputmode="numeric"
                    pattern="09[0-9]{9}"
                    minlength="11"
                    maxlength="11"
                    title="Enter an 11-digit Philippine mobile number starting with 09"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

            </div>


            <!-- VEHICLE -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Vehicle Model:

                </label>


                <input
                    type="text"
                    name="vehicle_model"
                    id="modal_vehicle_model"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

            </div>


            <!-- DATE -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Booking Date:

                </label>


                <input
                    type="date"
                    name="booking_date"
                    id="modal_booking_date"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

            </div>


            <!-- TIME -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Booking Time:

                </label>


                <select
                    name="booking_time"
                    id="modal_booking_time"
                    class="form-control"
                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "
                    required
                >
                    <option value="" disabled selected>Select time slot</option>
                    <option value="09:00 AM - 10:30 AM">09:00 AM - 10:30 AM</option>
                    <option value="10:30 AM - 12:00 PM">10:30 AM - 12:00 PM</option>
                    <option value="01:00 PM - 02:30 PM">01:00 PM - 02:30 PM</option>
                    <option value="02:30 PM - 04:00 PM">02:30 PM - 04:00 PM</option>
                    <option value="04:00 PM - 05:30 PM">04:00 PM - 05:30 PM</option>
                </select>

                </select>

            </div>


            <!-- STATUS -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Status:

                </label>


                <select
                    name="status"
                    id="modal_status"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

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


            <!-- NOTES -->

            <div
                class="form-group"
                style="margin-bottom: 20px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Notes:

                </label>


                <textarea
                    name="notes"
                    id="modal_notes"

                    class="form-control"

                    rows="3"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                        resize: vertical;
                    "
                ></textarea>

            </div>


            <!-- FOOTER -->

            <div
                class="modal-footer"

                style="
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                "
            >

                <button
                    type="button"

                    onclick="closeEditModal()"

                    style="
                        padding: 8px 15px;
                        background: #6c757d;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    "
                >

                    Cancel

                </button>


                <button
                    type="submit"

                    style="
                        padding: 8px 15px;
                        background: #1976d2;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    "
                >

                    Save Changes

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ============================================================
     CREATE APPOINTMENT MODAL
============================================================ -->

<div
    class="modal-overlay"
    id="createAppointmentModal"

    style="
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    "
>


    <div
        class="modal-content"

        style="
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            width: 450px;
            max-width: 90%;
        "
    >


        <div
            class="modal-header"

            style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            "
        >

            <h3>
                Add Appointment
            </h3>


            <button
                type="button"

                onclick="closeCreateModal()"

                style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                "
            >

                &times;

            </button>

        </div>


        <form
            action="actions/admin_appointment_create.php"
            method="POST"
        >


            <input
                type="hidden"
                name="csrf_token"
                value="<?= generateCSRFToken() ?>"
            >


            <!-- CUSTOMER -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Customer:

                </label>


                <select
                    name="users_id"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

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


            <!-- SERVICE -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Service:

                </label>


                <select
                    name="services_id"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

                    <option value="">
                        Select Service
                    </option>


                    <?php foreach ($allServices as $service): ?>

                        <option value="<?= $service['id'] ?>">

                            <?= htmlspecialchars($service['name']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- FULL NAME -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Full Name:

                </label>


                <input
                    type="text"
                    name="full_name"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

            </div>


            <!-- CONTACT -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Contact Number:

                </label>


                <input
                    type="tel"
                    name="contact_number"
                    inputmode="numeric"
                    pattern="09[0-9]{9}"
                    minlength="11"
                    maxlength="11"
                    title="Enter an 11-digit Philippine mobile number starting with 09"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

            </div>


            <!-- VEHICLE -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Vehicle Model:

                </label>


                <input
                    type="text"
                    name="vehicle_model"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

            </div>


            <!-- DATE -->

            <div
                class="form-group"
                style="margin-bottom: 15px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Booking Date:

                </label>


                <input
                    type="date"
                    name="booking_date"

                    class="form-control"

                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "

                    required
                >

            </div>


            <!-- TIME -->

            <div
                class="form-group"
                style="margin-bottom: 20px;"
            >

                <label
                    style="
                        display: block;
                        margin-bottom: 5px;
                        font-weight: 500;
                    "
                >

                    Booking Time:

                </label>


                <select
                    name="booking_time"
                    class="form-control"
                    style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    "
                    required
                >
                    <option value="" disabled selected>Select time slot</option>
                    <option value="09:00 AM - 10:30 AM">09:00 AM - 10:30 AM</option>
                    <option value="10:30 AM - 12:00 PM">10:30 AM - 12:00 PM</option>
                    <option value="01:00 PM - 02:30 PM">01:00 PM - 02:30 PM</option>
                    <option value="02:30 PM - 04:00 PM">02:30 PM - 04:00 PM</option>
                    <option value="04:00 PM - 05:30 PM">04:00 PM - 05:30 PM</option>
                </select>

            </div>


            <!-- FOOTER -->

            <div
                style="
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                "
            >

                <button
                    type="button"

                    onclick="closeCreateModal()"

                    style="
                        padding: 8px 15px;
                        background: #6c757d;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    "
                >

                    Cancel

                </button>


                <button
                    type="submit"

                    style="
                        padding: 8px 15px;
                        background: #1976d2;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    "
                >

                    Add Appointment

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

    document.getElementById(
        'viewAppointmentModal'
    ).style.display = 'none';

}


// ============================================================
// CLOSE EDIT MODAL
// ============================================================

function closeEditModal() {

    document.getElementById(
        'editAppointmentModal'
    ).style.display = 'none';

}


// ============================================================
// CLOSE CREATE MODAL
// ============================================================

function closeCreateModal() {

    document.getElementById(
        'createAppointmentModal'
    ).style.display = 'none';

}


// ============================================================
// DOM READY
// ============================================================

document.addEventListener(
    'DOMContentLoaded',
    () => {

        const searchInput =
            document.getElementById('searchInput');

        const statusSelect =
            document.getElementById('statusSelect');

        const tableBody =
            document.getElementById(
                'appointmentsTableBody'
            );

        const createButton =
            document.getElementById(
                'openCreateAppointment'
            );

        let searchTimeout;


        // ====================================================
        // CREATE MODAL
        // ====================================================

        if (createButton) {

            createButton.addEventListener(
                'click',
                () => {

                    document.getElementById(
                        'createAppointmentModal'
                    ).style.display = 'flex';

                }
            );

        }


        // ====================================================
        // FETCH FILTERED APPOINTMENTS
        // ====================================================

        function fetchFilteredAppointments() {

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


            fetch(
                endpoint + '?' + params.toString()
            )

                .then(
                    response => response.text()
                )

                .then(
                    html => {

                        tableBody.innerHTML =
                            html;


                        const newUrl =
                            endpoint + '?' +

                            new URLSearchParams({

                                search:
                                    searchValue,

                                status:
                                    statusValue

                            }).toString();


                        window.history.replaceState(
                            {},
                            '',
                            newUrl
                        );

                    }
                )

                .catch(
                    error => {

                        console.error(
                            'Error loading appointments:',
                            error
                        );

                    }
                );

        }


        // ====================================================
        // LIVE SEARCH
        // ====================================================

        if (searchInput) {

            searchInput.addEventListener(
                'input',
                function() {

                    clearTimeout(
                        searchTimeout
                    );


                    searchTimeout =
                        setTimeout(
                            fetchFilteredAppointments,
                            100
                        );

                }
            );


            searchInput.addEventListener(
                'keydown',
                function(e) {

                    if (e.key === 'Enter') {

                        e.preventDefault();

                        clearTimeout(
                            searchTimeout
                        );

                        fetchFilteredAppointments();

                    }

                }
            );

        }


        // ====================================================
        // STATUS FILTER
        // ====================================================

        if (statusSelect) {

            statusSelect.addEventListener(
                'change',
                fetchFilteredAppointments
            );

        }


        // ====================================================
        // VIEW / EDIT BUTTONS
        // ====================================================

        document.addEventListener(
            'click',
            function(e) {


                // ============================================
                // VIEW
                // ============================================

                const viewBtn =
                    e.target.closest(
                        '.btn-view-appointment'
                    );


                if (viewBtn) {

                    const ds =
                        viewBtn.dataset;


                    document.getElementById(
                        'viewModalTitle'
                    ).innerText =
                        'Appointment #' +
                        ds.id +
                        ' Details';


                    document.getElementById(
                        'view_appointment_id'
                    ).innerText =
                        '#' + ds.id;


                    document.getElementById(
                        'view_customer_name'
                    ).innerText =
                        ds.customer;


                    document.getElementById(
                        'view_contact'
                    ).innerText =
                        ds.contact;


                    document.getElementById(
                        'view_vehicle'
                    ).innerText =
                        ds.vehicle;


                    document.getElementById(
                        'view_service'
                    ).innerText =
                        ds.service;


                    document.getElementById(
                        'view_date'
                    ).innerText =
                        ds.date;


                    document.getElementById(
                        'view_time'
                    ).innerText =
                        ds.time;


                    document.getElementById(
                        'view_status'
                    ).innerText =
                        ds.status;


                    document.getElementById(
                        'view_notes'
                    ).innerText =
                        ds.notes || 'None';


                    document.getElementById(
                        'viewAppointmentModal'
                    ).style.display =
                        'flex';

                }


                // ============================================
                // EDIT
                // ============================================

                const editBtn =
                    e.target.closest(
                        '.btn-edit-appointment'
                    );


                if (editBtn) {

                    const ds =
                        editBtn.dataset;


                    document.getElementById(
                        'modal_appointment_id'
                    ).value =
                        ds.id;


                    document.getElementById(
                        'editModalTitle'
                    ).innerText =
                        'Edit Appointment #' +
                        ds.id;


                    document.getElementById(
                        'modal_users_id'
                    ).value =
                        ds.userid;

                    document.getElementById(
                        'modal_customer_display'
                    ).value =
                        ds.customer;


                    document.getElementById(
                        'modal_services_id'
                    ).value =
                        ds.serviceId;


                    document.getElementById(
                        'modal_full_name'
                    ).value =
                        ds.customer;


                    document.getElementById(
                        'modal_contact_number'
                    ).value =
                        ds.contact;


                    document.getElementById(
                        'modal_vehicle_model'
                    ).value =
                        ds.vehicle;


                    document.getElementById(
                        'modal_booking_date'
                    ).value =
                        ds.date;


                    document.getElementById(
                        'modal_booking_time'
                    ).value =
                        ds.time;


                    document.getElementById(
                        'modal_status'
                    ).value =
                        ds.status;


                    document.getElementById(
                        'modal_notes'
                    ).value =
                        ds.notes;


                    document.getElementById(
                        'editAppointmentModal'
                    ).style.display =
                        'flex';

                }

            }
        );


        // ====================================================
        // CLOSE MODALS WHEN CLICKING OUTSIDE
        // ====================================================

        window.addEventListener(
            'click',
            function(e) {

                if (
                    e.target ===
                    document.getElementById(
                        'viewAppointmentModal'
                    )
                ) {

                    closeViewModal();

                }


                if (
                    e.target ===
                    document.getElementById(
                        'editAppointmentModal'
                    )
                ) {

                    closeEditModal();

                }


                if (
                    e.target ===
                    document.getElementById(
                        'createAppointmentModal'
                    )
                ) {

                    closeCreateModal();

                }

            }
        );

    }
);

</script>


</body>

</html>