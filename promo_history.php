<?php
// promo_history.php - Promo claim and appointment history

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/config.php';

$userId = intval($_SESSION['user_id']);
$isAdmin = $_SESSION['is_admin'] ?? false;

// Generate CSRF Token for modals/forms if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Session fallback details
$sessionUserName =
    $_SESSION['user_name'] ??
    $_SESSION['fullname'] ??
    $_SESSION['full_name'] ??
    $_SESSION['name'] ??
    '';

$sessionUserPhone =
    $_SESSION['phone'] ??
    $_SESSION['contact'] ??
    '';

// ----------------------------------------------------
// DELETE CLAIM
// ----------------------------------------------------
if (
    isset($_GET['action']) &&
    $_GET['action'] === 'delete' &&
    isset($_GET['id'])
) {
    $claimId = intval($_GET['id']);

    if ($claimId > 0) {

        if ($isAdmin) {
            $stmt = $pdo->prepare("
                DELETE FROM promo_claims
                WHERE id = ?
            ");

            $stmt->execute([$claimId]);

        } else {
            $stmt = $pdo->prepare("
                DELETE FROM promo_claims
                WHERE id = ?
                AND users_id = ?
            ");

            $stmt->execute([
                $claimId,
                $userId
            ]);
        }
    }

    header('Location: promo_history.php');
    exit;
}

// ----------------------------------------------------
// FETCH CLAIMS
// ----------------------------------------------------
if ($isAdmin) {

    $stmt = $pdo->query("
        SELECT pc.*, 
               u.username,
               u.first_name,
               u.last_name,
               u.email
        FROM promo_claims pc
        LEFT JOIN users u
            ON pc.users_id = u.id
        ORDER BY pc.appointment_date ASC,
                 pc.appointment_time ASC,
                 pc.id DESC
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT pc.*, 
               u.username,
               u.first_name,
               u.last_name,
               u.email
        FROM promo_claims pc
        LEFT JOIN users u
            ON pc.users_id = u.id
        WHERE pc.users_id = ?
        ORDER BY pc.appointment_date ASC,
                 pc.appointment_time ASC,
                 pc.id DESC
    ");

    $stmt->execute([$userId]);
}

$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Promo Claim & Appointment History - Bencalo MotoWorks</title>

    <link rel="stylesheet" href="styles/index/style.css">
    <link rel="stylesheet" href="styles/index/components.css">
    <link rel="stylesheet" href="styles/index/layout.css">
    <link rel="stylesheet" href="styles/promo_history.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >
</head>

<body>

<?php require_once 'components/header.php'; ?>

<div id="alertToast" class="alert-toast"></div>

<main class="history-wrapper">

    <div class="history-container">

        <h2 class="history-title">
            Promo Claim & Appointment History
        </h2>

        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Home
        </a>

        <div class="order-table-wrapper">

            <table class="styled-table">

                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Bundle Slug</th>
                        <th>Selected Option</th>
                        <th>Date & Time</th>
                        <th>Contact Phone</th>
                        <th>Status</th>
                        <?php if ($isAdmin): ?>
                        <th>Client / User</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($claims)): ?>

                    <tr>
                        <td colspan="<?= $isAdmin ? '8' : '7' ?>" class="empty-state">
                            No promo claims found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php
                    $index = 1;

                    foreach ($claims as $claim):

                        $claimJson = htmlspecialchars(
                            json_encode(
                                $claim,
                                JSON_HEX_TAG |
                                JSON_HEX_APOS |
                                JSON_HEX_QUOT |
                                JSON_HEX_AMP
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        $status = $claim['status'] ?? 'Pending';

                        $statusClass =
                            strtolower($status) === 'completed'
                            ? 'badge-completed'
                            : 'badge-pending';
                    ?>

                    <tr>

                        <td>
                            <strong><?= $index++ ?></strong>
                        </td>

                        <td>
                            <span class="bundle-name">
                                <?= htmlspecialchars(
                                    $claim['bundle_slug'] ?? 'N/A'
                                ) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $claim['selected_option'] ?? 'N/A'
                            ) ?>
                        </td>

                        <td>

                            <?php
                            if (!empty($claim['appointment_date'])) {
                                echo date(
                                    'F j, Y',
                                    strtotime($claim['appointment_date'])
                                );
                            } else {
                                echo 'N/A';
                            }
                            ?>

                            <span class="appointment-meta">
                                <?= htmlspecialchars(
                                    $claim['appointment_time'] ?? ''
                                ) ?>
                            </span>

                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $claim['phone'] ?? $sessionUserPhone ?: 'N/A'
                            ) ?>
                        </td>

                        <td>

                            <span class="badge <?= $statusClass ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>

                        </td>

                        <?php if ($isAdmin): ?>
                        <td>
                            <span style="font-size: 0.9rem; color: #aaa;">
                                <?= htmlspecialchars(trim(($claim['first_name'] ?? '') . ' ' . ($claim['last_name'] ?? '')) ?: ($claim['username'] ?? 'User #' . $claim['users_id'])) ?>
                            </span>
                        </td>
                        <?php endif; ?>

                        <td>

                            <div class="table-actions">

                                <button
                                    type="button"
                                    class="btn-view"
                                    onclick='openViewModal(<?= $claimJson ?>)'
                                >
                                    <i class="fa-solid fa-eye"></i>
                                    View
                                </button>

                                <button
                                    type="button"
                                    class="btn-edit"
                                    onclick='openEditModal(<?= $claimJson ?>)'
                                >
                                    <i class="fa-solid fa-pen"></i>
                                    Edit
                                </button>

                                <a
                                    href="promo_history.php?action=delete&id=<?= (int)$claim['id'] ?>"
                                    class="btn-cancel"
                                    onclick="return confirm('Are you sure you want to cancel and delete this promo claim?');"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                    Cancel
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


<!-- VIEW MODAL -->

<div
    id="viewModal"
    class="modal-overlay"
    style="
        display:none;
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,0.7);
        justify-content:center;
        align-items:center;
        z-index:1000;
    "
>

    <div
        class="modal-content"
        style="
            background:#1e2124;
            padding:30px;
            border-radius:8px;
            width:450px;
            color:#fff;
            position:relative;
        "
    >

        <h3 style="margin-bottom:20px;color:#00FFFF;">
            <i class="fa-solid fa-circle-info"></i>
            Promo Claim Details
        </h3>

        <div
            id="viewModalContent"
            style="
                background:#111;
                padding:20px;
                border-radius:6px;
                border:1px solid #333;
            "
        ></div>

        <div
            style="
                display:flex;
                justify-content:flex-end;
                margin-top:20px;
            "
        >

            <button
                type="button"
                onclick="closeViewModal()"
                style="
                    padding:10px 20px;
                    background:#333;
                    border:none;
                    color:#fff;
                    border-radius:4px;
                    cursor:pointer;
                "
            >
                Close
            </button>

        </div>

    </div>

</div>


<!-- EDIT MODAL -->

<div
    id="editModal"
    class="modal-overlay"
    style="
        display:none;
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,0.7);
        justify-content:center;
        align-items:center;
        z-index:1000;
    "
>

    <div
        class="modal-content"
        style="
            background:#1e2124;
            padding:30px;
            border-radius:8px;
            width:450px;
            color:#fff;
            position:relative;
            max-height: 90vh;
            overflow-y: auto;
        "
    >

        <h3 style="margin-bottom:15px;color:#00FFFF;">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Claim Appointment
        </h3>

        <form id="editForm" onsubmit="submitEditClaim(event)">

            <input
                type="hidden"
                id="editClaimId"
                name="claim_id"
            >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= $_SESSION['csrf_token'] ?>"
            >

            <div style="margin-bottom:15px;">

                <label>Full Name</label>

                <input
                    type="text"
                    id="editFullname"
                    name="fullname"
                    required
                    style="
                        width:100%;
                        padding:10px;
                        background:#111;
                        border:1px solid #333;
                        color:#fff;
                        border-radius:4px;
                    "
                >

            </div>


            <div style="margin-bottom:15px;">

                <label>Contact Phone</label>

                <input
                    type="text"
                    id="editPhone"
                    name="phone"
                    required
                    style="
                        width:100%;
                        padding:10px;
                        background:#111;
                        border:1px solid #333;
                        color:#fff;
                        border-radius:4px;
                    "
                >

            </div>


            <div style="margin-bottom:15px;">

                <label>Appointment Date</label>

                <input
                    type="date"
                    id="editDate"
                    name="appointment_date"
                    required
                    min="<?= date('Y-m-d') ?>"
                    style="
                        width:100%;
                        padding:10px;
                        background:#111;
                        border:1px solid #333;
                        color:#fff;
                        border-radius:4px;
                    "
                >

            </div>


            <div style="margin-bottom:15px;">

                <label>Preferred Time Slot</label>

                <select
                    id="editTime"
                    name="appointment_time"
                    required
                    style="
                        width:100%;
                        padding:10px;
                        background:#111;
                        border:1px solid #333;
                        color:#fff;
                        border-radius:4px;
                    "
                >

                    <option value="09:00 AM - 10:30 AM">
                        09:00 AM - 10:30 AM
                    </option>

                    <option value="10:30 AM - 12:00 PM">
                        10:30 AM - 12:00 PM
                    </option>

                    <option value="01:00 PM - 02:30 PM">
                        01:00 PM - 02:30 PM
                    </option>

                    <option value="04:00 PM - 05:30 PM">
                        04:00 PM - 05:30 PM
                    </option>

                </select>

            </div>

            <?php if ($isAdmin): ?>
            <div style="margin-bottom:20px;">

                <label>Status</label>

                <select
                    id="editStatus"
                    name="status"
                    required
                    style="
                        width:100%;
                        padding:10px;
                        background:#111;
                        border:1px solid #333;
                        color:#fff;
                        border-radius:4px;
                    "
                >
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

            </div>
            <?php else: ?>
            <input type="hidden" id="editStatusHidden" name="status" value="Pending">
            <?php endif; ?>


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                "
            >

                <button
                    type="button"
                    onclick="closeEditModal()"
                    style="
                        padding:10px 20px;
                        background:#333;
                        border:none;
                        color:#fff;
                        border-radius:4px;
                        cursor:pointer;
                    "
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="submitEditBtn"
                    style="
                        padding:10px 20px;
                        background:#00FFFF;
                        border:none;
                        color:#000;
                        font-weight:bold;
                        border-radius:4px;
                        cursor:pointer;
                    "
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<?php require_once 'components/footer.php'; ?>


<script>

const sessionDefaultName =
    <?= json_encode($sessionUserName) ?>;

const sessionDefaultPhone =
    <?= json_encode($sessionUserPhone) ?>;

const isAdminUser = <?= $isAdmin ? 'true' : 'false' ?>;


function showAlert(message, type) {

    const toast =
        document.getElementById('alertToast');

    toast.textContent = message;

    toast.className =
        'alert-toast ' +
        (type === 'success'
            ? 'alert-success'
            : 'alert-error');

    toast.style.display = 'block';

    setTimeout(function () {
        toast.style.display = 'none';
    }, 4000);
}


function openViewModal(claim) {

    const modalContent =
        document.getElementById('viewModalContent');

    modalContent.innerHTML = `
        <p>
            <strong>Bundle / Promo:</strong>
            ${claim.bundle_slug || 'N/A'}
        </p>

        <p>
            <strong>Selected Option:</strong>
            ${claim.selected_option || 'N/A'}
        </p>

        <hr style="border-color:#333;margin:10px 0;">

        <p>
            <strong>Full Name:</strong>
            ${claim.fullname || sessionDefaultName || 'N/A'}
        </p>

        <p>
            <strong>Contact Phone:</strong>
            ${claim.phone || sessionDefaultPhone || 'N/A'}
        </p>

        <p>
            <strong>Appointment Date:</strong>
            ${claim.appointment_date || 'N/A'}
        </p>

        <p>
            <strong>Time Slot:</strong>
            ${claim.appointment_time || 'N/A'}
        </p>

        <p>
            <strong>Current Status:</strong>
            <span style="color:#00FFFF;font-weight:bold;">
                ${claim.status || 'Pending'}
            </span>
        </p>
    `;

    document.getElementById('viewModal').style.display = 'flex';
}


function closeViewModal() {

    document.getElementById('viewModal').style.display = 'none';

}


function openEditModal(claim) {

    document.getElementById('editClaimId').value =
        claim.id;

    document.getElementById('editFullname').value =
        claim.fullname || sessionDefaultName;

    document.getElementById('editPhone').value =
        claim.phone || sessionDefaultPhone;

    document.getElementById('editDate').value =
        claim.appointment_date || '';

    document.getElementById('editTime').value =
        claim.appointment_time || '09:00 AM - 10:30 AM';

    if (isAdminUser) {
        const statusSelect = document.getElementById('editStatus');
        if (statusSelect) {
            statusSelect.value = claim.status || 'Pending';
        }
    } else {
        const statusHidden = document.getElementById('editStatusHidden');
        if (statusHidden) {
            statusHidden.value = claim.status || 'Pending';
        }
    }

    document.getElementById('editModal').style.display = 'flex';
}


function closeEditModal() {

    document.getElementById('editModal').style.display = 'none';

}


function submitEditClaim(event) {

    event.preventDefault();

    const btn =
        document.getElementById('submitEditBtn');

    btn.disabled = true;
    btn.textContent = 'Saving...';

    const formData =
        new FormData(
            document.getElementById('editForm')
        );

    fetch('update_claim.php', {
        method: 'POST',
        body: formData
    })

    .then(async response => {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (err) {
            throw new Error(text.trim() || 'Server returned invalid JSON.');
        }
    })

    .then(data => {

        btn.disabled = false;
        btn.textContent = 'Save Changes';

        if (data.success) {

            showAlert(data.message, 'success');

            closeEditModal();

            setTimeout(function () {
                window.location.reload();
            }, 1200);

        } else {

            showAlert(data.message, 'error');

        }

    })

    .catch(function (error) {

        btn.disabled = false;
        btn.textContent = 'Save Changes';

        showAlert(
            'Error: ' + error.message,
            'error'
        );

    });
}


window.onclick = function(event) {

    const viewModal =
        document.getElementById('viewModal');

    const editModal =
        document.getElementById('editModal');

    if (event.target === viewModal) {
        closeViewModal();
    }

    if (event.target === editModal) {
        closeEditModal();
    }

};

</script>

</body>
</html>