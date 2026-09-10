<?php
// get_claim.php - Fetches promo claim details

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo '<p style="color:#ff6b6b;">Unauthorized access. Please log in.</p>';
    exit;
}

require_once 'includes/config.php';

$userId = intval($_SESSION['user_id']);
$isAdmin = $_SESSION['is_admin'] ?? false;

$claimId = intval($_GET['id'] ?? 0);
$mode = $_GET['mode'] ?? 'view';

if ($claimId <= 0) {
    echo '<p style="color:#ff6b6b;">Invalid claim ID provided.</p>';
    exit;
}

try {

    $sql = "
        SELECT
            pc.*,
            u.first_name,
            u.last_name,
            u.username,
            u.phone AS registered_phone
        FROM promo_claims pc
        LEFT JOIN users u
            ON pc.users_id = u.id
        WHERE pc.id = ?
    ";

    $params = [$claimId];

    if (!$isAdmin) {

        $sql .= " AND pc.users_id = ?";

        $params[] = $userId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $claim = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$claim) {

        echo '<p style="color:#ff6b6b;">
                Record not found or unauthorized.
              </p>';

        exit;
    }


    // Build registered user's full name
    $registeredFullname = trim(
        ($claim['first_name'] ?? '') . ' ' .
        ($claim['last_name'] ?? '')
    );


    $fullname =
        !empty($claim['fullname'])
        ? $claim['fullname']
        : $registeredFullname;


    $phone =
        !empty($claim['phone'])
        ? $claim['phone']
        : ($claim['registered_phone'] ?? '');


    $bundleSlug =
        htmlspecialchars(
            $claim['bundle_slug'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


    $selectedOption =
        htmlspecialchars(
            $claim['selected_option'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


    $appointmentDate =
        htmlspecialchars(
            $claim['appointment_date'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


    $appointmentTime =
        htmlspecialchars(
            $claim['appointment_time'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


    $status =
        htmlspecialchars(
            $claim['status'] ?? 'Pending',
            ENT_QUOTES,
            'UTF-8'
        );


    if ($mode === 'edit') {
?>

<form action="update_claim.php" method="POST">

    <input
        type="hidden"
        name="claim_id"
        value="<?= $claimId ?>"
    >

    <div class="form-group" style="margin-bottom:15px;">

        <label
            style="
                display:block;
                margin-bottom:5px;
                font-weight:500;
            "
        >
            Full Name
        </label>

        <input
            type="text"
            name="fullname"
            value="<?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') ?>"
            required
            style="
                width:100%;
                padding:8px;
            "
        >

    </div>


    <div class="form-group" style="margin-bottom:15px;">

        <label
            style="
                display:block;
                margin-bottom:5px;
                font-weight:500;
            "
        >
            Phone Number
        </label>

        <input
            type="text"
            name="phone"
            value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
            required
            style="
                width:100%;
                padding:8px;
            "
        >

    </div>


    <div class="form-group" style="margin-bottom:15px;">

        <label
            style="
                display:block;
                margin-bottom:5px;
                font-weight:500;
            "
        >
            Selected Option
        </label>

        <input
            type="text"
            name="selected_option"
            value="<?= $selectedOption ?>"
            required
            style="
                width:100%;
                padding:8px;
            "
        >

    </div>


    <div class="form-group" style="margin-bottom:15px;">

        <label
            style="
                display:block;
                margin-bottom:5px;
                font-weight:500;
            "
        >
            Appointment Date
        </label>

        <input
            type="date"
            name="appointment_date"
            value="<?= $appointmentDate ?>"
            required
            style="
                width:100%;
                padding:8px;
            "
        >

    </div>


    <div class="form-group" style="margin-bottom:15px;">

        <label
            style="
                display:block;
                margin-bottom:5px;
                font-weight:500;
            "
        >
            Appointment Time
        </label>

        <select
            name="appointment_time"
            required
            style="
                width:100%;
                padding:8px;
            "
        >

            <option
                value="09:00 AM - 10:30 AM"
                <?= $appointmentTime === '09:00 AM - 10:30 AM' ? 'selected' : '' ?>
            >
                09:00 AM - 10:30 AM
            </option>

            <option
                value="10:30 AM - 12:00 PM"
                <?= $appointmentTime === '10:30 AM - 12:00 PM' ? 'selected' : '' ?>
            >
                10:30 AM - 12:00 PM
            </option>

            <option
                value="01:00 PM - 02:30 PM"
                <?= $appointmentTime === '01:00 PM - 02:30 PM' ? 'selected' : '' ?>
            >
                01:00 PM - 02:30 PM
            </option>

            <option
                value="04:00 PM - 05:30 PM"
                <?= $appointmentTime === '04:00 PM - 05:30 PM' ? 'selected' : '' ?>
            >
                04:00 PM - 05:30 PM
            </option>

        </select>

    </div>


    <div
        class="modal-footer"
        style="
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-top:20px;
        "
    >

        <button
            type="button"
            class="btn btn-secondary"
            onclick="closeModal('editModal')"
        >
            Cancel
        </button>

        <button
            type="submit"
            class="btn btn-primary"
        >
            Save Changes
        </button>

    </div>

</form>

<?php

    } else {
?>

<div style="line-height:1.6;">

    <p>
        <strong>Bundle / Promo:</strong>
        <?= $bundleSlug ?>
    </p>

    <p>
        <strong>Full Name:</strong>
        <?= htmlspecialchars(
            $fullname,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </p>

    <p>
        <strong>Phone Number:</strong>
        <?= htmlspecialchars(
            $phone,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </p>

    <p>
        <strong>Selected Option:</strong>
        <?= $selectedOption ?: 'N/A' ?>
    </p>

    <p>
        <strong>Appointment Date:</strong>
        <?= $appointmentDate ?: 'N/A' ?>
    </p>

    <p>
        <strong>Appointment Time:</strong>
        <?= $appointmentTime ?: 'N/A' ?>
    </p>

    <p>
        <strong>Status:</strong>

        <span
            style="
                text-transform:uppercase;
                font-weight:600;
                color:var(--primary-accent);
            "
        >
            <?= $status ?>
        </span>

    </p>

</div>

<?php
    }

} catch (PDOException $e) {

    echo '<p style="color:#ff6b6b;">
        Database error: ' .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</p>';
}
?>