<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle GET
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$delete_id = isset($_GET['delete']) ? intval($_GET['delete']) : 0;

// Delete product
if ($delete_id > 0) {
    $conn->query("DELETE FROM product_images WHERE product_id = $delete_id");
    $conn->query("DELETE FROM product_variants WHERE product_id = $delete_id");
    $conn->query("DELETE FROM products WHERE id = $delete_id");
    echo "<p style='color:red;'>❌ Product ID $delete_id deleted.</p>";
}

// Fetch data for editing
$edit_data = null;
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM products WHERE id = $edit_id");
    $edit_data = $res->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add / Edit Product</title>
</head>
<body>
    <h2><?php echo $edit_data ? 'Edit' : 'Add'; ?> Product</h2>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" value="<?php echo $edit_data['id'] ?? ''; ?>">

        <label>Title:</label><br>
        <input type="text" name="title" required value="<?php echo $edit_data['title'] ?? ''; ?>"><br><br>

        <label>Description:</label><br>
        <textarea name="description" required><?php echo $edit_data['description'] ?? ''; ?></textarea><br><br>

        <label>Sub Category:</label><br>
        <select name="sub_category_id" required>
            <option value="">-- Select Sub Category --</option>
            <?php
            $res = $conn->query("SELECT s.id, s.name, m.name as main_name FROM sub_categories s JOIN main_categories m ON m.id = s.main_category_id ORDER BY m.name, s.name");
            while ($row = $res->fetch_assoc()) {
                $selected = ($edit_data && $edit_data['sub_category_id'] == $row['id']) ? 'selected' : '';
                echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['main_name'] . " → " . $row['name']) . "</option>";
            }
            ?>
        </select><br><br>

        <label>Brand:</label><br>
        <input type="text" name="brand" value="<?php echo $edit_data['brand'] ?? ''; ?>"><br><br>

        <label>Customizable:</label>
        <input type="checkbox" name="is_customizable" value="1" <?php echo (!empty($edit_data['is_customizable'])) ? 'checked' : ''; ?>><br><br>

        <label>Upload Images (JPEG/PNG):</label><br>
        <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png"><br><br>

        <input type="submit" name="submit" value="<?php echo $edit_data ? 'Update' : 'Add'; ?> Product">
    </form>

    <hr><h3>All Products</h3>
    <?php
    $all = $conn->query("SELECT p.id, p.title, s.name AS sub_cat FROM products p JOIN sub_categories s ON p.sub_category_id = s.id ORDER BY p.id DESC");
    while ($row = $all->fetch_assoc()) {
        echo "<p>ID: {$row['id']} - {$row['title']} ({$row['sub_cat']}) 
        <a href='?edit={$row['id']}'>✏️</a> 
        <a href='?delete={$row['id']}' onclick='return confirm(`Delete this product?`)'>🗑️</a></p>";
    }
    ?>

<?php
if (isset($_POST['submit'])) {
    $edit_id = $_POST['edit_id'] ?? 0;
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $sub_category_id = $_POST['sub_category_id'];
    $brand = $_POST['brand'];
    $custom = isset($_POST['is_customizable']) ? 1 : 0;

    if ($edit_id > 0) {
        $stmt = $conn->prepare("UPDATE products SET title=?, description=?, sub_category_id=?, brand=?, is_customizable=? WHERE id=?");
        $stmt->bind_param("ssisii", $title, $desc, $sub_category_id, $brand, $custom, $edit_id);
        $stmt->execute();
        $product_id = $edit_id;
    } else {
        $stmt = $conn->prepare("INSERT INTO products (title, description, sub_category_id, brand, is_customizable) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisi", $title, $desc, $sub_category_id, $brand, $custom);
        $stmt->execute();
        $product_id = $stmt->insert_id;
    }
    $stmt->close();

    // ✅ Get category names for folder structure
    $cat_stmt = $conn->prepare("SELECT m.name AS main_cat, s.name AS sub_cat FROM sub_categories s JOIN main_categories m ON s.main_category_id = m.id WHERE s.id = ?");
    $cat_stmt->bind_param("i", $sub_category_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $cat_names = $cat_result->fetch_assoc();
    $cat_stmt->close();

    // ✅ Define folder path one level outside
    $base_folder = dirname(__DIR__) . '/product_images';
    $main_folder = $base_folder . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cat_names['main_cat']);
    $sub_folder = $main_folder . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cat_names['sub_cat']);
    if (!is_dir($main_folder)) mkdir($main_folder, 0777, true);
    if (!is_dir($sub_folder)) mkdir($sub_folder, 0777, true);

    // ✅ Upload images
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
            $original_name = basename($_FILES['images']['name'][$index]);
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            if (!in_array($file_ext, ['jpg', 'jpeg', 'png'])) continue;

            $unique_name = time() . "_" . uniqid() . "." . $file_ext;
            $target_path = $sub_folder . '/' . $unique_name;
            if (move_uploaded_file($tmp_name, $target_path)) {
                $is_primary = ($index === 0) ? 1 : 0;
                $url = str_replace(dirname(__DIR__) . '/', '', $target_path);
                $stmt_img = $conn->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, ?)");
                $stmt_img->bind_param("isi", $product_id, $url, $is_primary);
                $stmt_img->execute();
                $stmt_img->close();
            }
        }
    }

    echo "<script>location.href='add_product.php';</script>";
}
?>
</body>
</html>
