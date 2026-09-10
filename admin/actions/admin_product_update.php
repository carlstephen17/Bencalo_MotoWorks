<?php
// /admin/actions/admin_product_update.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        header('Location: ../admin_products.php?msg=Invalid+Security+Token');
        exit();
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    if ($id && !empty($name) && $price !== false && $price >= 0 && $stock !== false && $stock >= 0) {
        // Check if a new image was uploaded
        $imageClause = "";
        $params = [$name, $price, $stock];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = '../../uploads/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $imageClause = ", image = ?";
                    $params[] = $newFileName;
                }
            }
        }

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?{$imageClause} WHERE id = ?");
        $stmt->execute($params);

        header('Location: ../admin_products.php?msg=Product+successfully+updated');
        exit();
    }

    header('Location: ../admin_products.php?msg=Invalid+product+parameters');
    exit();
} else {
    header('Location: ../admin_products.php');
    exit();
}
?>