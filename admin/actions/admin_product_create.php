<?php
// /admin/actions/admin_product_create.php
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

    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    $imageName = null;
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
                $imageName = $newFileName;
            }
        }
    }

    if (!empty($name) && $price !== false && $price >= 0 && $stock !== false && $stock >= 0) {
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $price, $stock, $imageName]);

        header('Location: ../admin_products.php?msg=Product+successfully+created');
        exit();
    }

    header('Location: ../admin_products.php?msg=Invalid+product+parameters');
    exit();
} else {
    header('Location: ../admin_products.php');
    exit();
}
?>