<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Main category and product variant management logic
$main_id = isset($_GET['main_id']) ? intval($_GET['main_id']) : 0;
$sub_id = isset($_GET['sub_id']) ? intval($_GET['sub_id']) : 0;
$series_id = isset($_GET['series_id']) ? intval($_GET['series_id']) : 0;
$edit_variant_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$delete_variant_id = isset($_GET['delete']) ? intval($_GET['delete']) : 0;

if ($delete_variant_id > 0) {
    $conn->query("DELETE FROM product_variants WHERE id = $delete_variant_id");
    echo "<p style='color:green;'>🗑️ Variant deleted successfully.</p>";
}

$main_categories = $conn->query("SELECT id, name FROM main_categories ORDER BY order_no");
$sub_categories = $main_id ? $conn->query("SELECT id, name FROM sub_categories WHERE main_category_id = $main_id ORDER BY order_no") : [];
$series = $sub_id ? $conn->query("SELECT id, name FROM series WHERE sub_category_id = $sub_id ORDER BY order_no") : [];
$series_options = $series_id ? $conn->query("SELECT id, name FROM series_options WHERE series_id = $series_id ORDER BY order_no") : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['variant_name'];
    $price = $_POST['price'];
    $original_price = $_POST['original_price'];
    $stock = $_POST['stock_qty'];
    $material = $_POST['material'];
    $lid = $_POST['lid_type'];
    $size = $_POST['size'];
    $shape = $_POST['shape'];
    $bottom = $_POST['bottom_type'];
    $series_option_id = $_POST['series_option_id'];
    $sku = $_POST['sku'];
    $weight = $_POST['weight'];
    $tax_rate = $_POST['tax_rate'];
    $dimensions = $_POST['dimensions'];
    $package_dimensions = $_POST['package_dimensions'];
    $warranty = $_POST['warranty'];
    $contents = $_POST['contents'];
    $hsn = $_POST['hsn'];

    if (isset($_POST['variant_id']) && $_POST['variant_id'] != '') {
        $variant_id = intval($_POST['variant_id']);
        $stmt = $conn->prepare("UPDATE product_variants SET sku=?, name=?, price=?, original_price=?, stock_qty=?, material=?, lid_type=?, size=?, shape=?, bottom_type=?, series_option_id=? WHERE id=?");
        $stmt->bind_param("ssddissssssi", $sku, $name, $price, $original_price, $stock, $material, $lid, $size, $shape, $bottom, $series_option_id, $variant_id);
        $stmt->execute();
        $stmt->close();
        echo "<p style='color:green;'>✅ Variant updated successfully.</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO product_variants (weight,tax_rate,dimensions,package_dimensions,warranty,contents,hsn,sku, name, price, original_price, stock_qty, material, lid_type, size, shape, bottom_type, series_option_id)
                                VALUES (?,?,?,?,?,?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssddisssssi", $weight,$tax_rate,$dimensions,$package_dimensions,$warranty,$contents,$hsn,$sku, $name, $price, $original_price, $stock, $material, $lid, $size, $shape, $bottom, $series_option_id);
        $stmt->execute();
        $variant_id = $stmt->insert_id;
        $stmt->close();
        echo "<p style='color:green;'>✅ Variant added successfully.</p>";
    }

    foreach ($_POST['attribute_key'] ?? [] as $i => $key) {
        $key = trim($key); $val = trim($_POST['attribute_value'][$i]);
        if ($key && $val) {
            $stmt = $conn->prepare("INSERT INTO product_attributes (variant_id, attribute_key, attribute_value) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $variant_id, $key, $val);
            $stmt->execute(); $stmt->close();
        }
    }

    foreach ($_POST['highlight_text'] ?? [] as $i => $text) {
        $text = trim($text); $icon = trim($_POST['highlight_icon'][$i]);
        if ($text) {
            $stmt = $conn->prepare("INSERT INTO product_highlights (variant_id, highlight_text, icon) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $variant_id, $text, $icon);
            $stmt->execute(); $stmt->close();
        }
    }

    foreach ($_POST['tab_title'] ?? [] as $i => $title) {
        $title = trim($title); $content = trim($_POST['tab_content'][$i]);
        if ($title && $content) {
            $stmt = $conn->prepare("INSERT INTO product_tabs (variant_id, tab_title, tab_content) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $variant_id, $title, $content);
            $stmt->execute(); $stmt->close();
        }
    }

    if (!empty($_FILES['variant_images']['name'][0])) {
        $res = $conn->query("SELECT mc.name AS main_cat, sc.name AS sub_cat, s.name AS series_name
            FROM series s
            JOIN sub_categories sc ON s.sub_category_id = sc.id
            JOIN main_categories mc ON sc.main_category_id = mc.id
            WHERE s.id = $series_id");
        $row = $res->fetch_assoc();

        $base = __DIR__ . "/variant_images";
        $path = $base . '/' . preg_replace('/\W+/', '_', $row['main_cat']) . '/' . preg_replace('/\W+/', '_', $row['sub_cat']) . '/' . preg_replace('/\W+/', '_', $row['series_name']) . "/variant_$variant_id";
        if (!is_dir($path)) mkdir($path, 0777, true);

        foreach ($_FILES['variant_images']['tmp_name'] as $i => $tmp) {
            $name = basename($_FILES['variant_images']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;
            $unique = time() . "_" . uniqid() . "." . $ext;
            $target = $path . '/' . $unique;
            move_uploaded_file($tmp, $target);
            $rel_path = str_replace(__DIR__ . '/', '', $target);

            $stmt = $conn->prepare("INSERT INTO variant_images (variant_id, url, is_primary) VALUES (?, ?, ?)");
            $is_primary = ($i === 0) ? 1 : 0;
            $stmt->bind_param("isi", $variant_id, $rel_path, $is_primary);
            $stmt->execute(); $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html><head><title>Variant Form</title></head>
<body>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="variant_id" value="<?= $edit_data['id'] ?? '' ?>">
<!-- Dependent Dropdowns -->
<select onchange="location.href='?main_id='+this.value;">
<option value="">Select Main</option>
<?php while ($r = $main_categories->fetch_assoc()): ?>
<option value="<?= $r['id'] ?>" <?= $main_id == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
<?php endwhile; ?>
</select><br>

<select onchange="location.href='?main_id=<?= $main_id ?>&sub_id='+this.value;">
<option value="">Select Sub</option>
<?php if ($sub_categories) while ($r = $sub_categories->fetch_assoc()): ?>
<option value="<?= $r['id'] ?>" <?= $sub_id == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
<?php endwhile; ?>
</select><br>

<select onchange="location.href='?main_id=<?= $main_id ?>&sub_id=<?= $sub_id ?>&series_id='+this.value;">
<option value="">Select Series</option>
<?php if ($series) while ($r = $series->fetch_assoc()): ?>
<option value="<?= $r['id'] ?>" <?= $series_id == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
<?php endwhile; ?>
</select><br>

<select name="series_option_id" required>
<option value="">Select Series Option</option>
<?php if ($series_options) while ($opt = $series_options->fetch_assoc()): ?>
<option value="<?= $opt['id'] ?>" <?= ($edit_data['series_option_id'] ?? '') == $opt['id'] ? 'selected' : '' ?>><?= $opt['name'] ?></option>
<?php endwhile; ?>
</select><br><br>

<input name="variant_name" placeholder="Variant Name" value="<?= $edit_data['name'] ?? '' ?>"><br>
<input name="sku" placeholder="sku" value="<?= $edit_data['sku'] ?? '' ?>"><br>
<input name="hsn" placeholder="hsn" value="<?= $edit_data['hsn'] ?? '' ?>"><br>
<input name="price" placeholder="Price" value="<?= $edit_data['price'] ?? '' ?>"><br>
<input name="original_price" placeholder="Original Price" value="<?= $edit_data['original_price'] ?? '' ?>"><br>
<input name="weight" required placeholder="weight" value="<?= $edit_data['weight'] ?? '' ?>"><br>
<input name="tax_rate" required placeholder="tax_rate" value="<?= $edit_data['tax_rate'] ?? '' ?>"><br>
<input name="dimensions" required placeholder="dimensions" value="<?= $edit_data['dimensions'] ?? '' ?>"><br>
<input name="package_dimensions" required placeholder="package_dimensions" value="<?= $edit_data['package_dimensions'] ?? '' ?>"><br>
<input name="warranty" required placeholder="warranty" value="<?= $edit_data['warranty'] ?? '' ?>"><br>
<input name="contents" required placeholder="contents" value="<?= $edit_data['contents'] ?? '' ?>"><br>
<input name="stock_qty" placeholder="Stock" value="<?= $edit_data['stock_qty'] ?? '' ?>"><br>
<input name="material" placeholder="Material" value="<?= $edit_data['material'] ?? '' ?>"><br>
<input name="lid_type" placeholder="Lid Type" value="<?= $edit_data['lid_type'] ?? '' ?>"><br>
<input name="size" placeholder="Size" value="<?= $edit_data['size'] ?? '' ?>"><br>
<input name="shape" placeholder="Shape" value="<?= $edit_data['shape'] ?? '' ?>"><br>
<input name="bottom_type" placeholder="Bottom Type" value="<?= $edit_data['bottom_type'] ?? '' ?>"><br>

 <!-- Upload -->
    <label>Upload Variant Images:</label><br>
   <input type="file" name="variant_images[]" multiple><br><br>


    <!-- Attributes -->
    <h3>Attributes <button type="button" onclick="addRow('attribute_section','attribute_key','attribute_value')">➕</button></h3>
    <div id="attribute_section">
        <input type="text" name="attribute_key[]" placeholder="Key">
        <input type="text" name="attribute_value[]" placeholder="Value"><br>
    </div>

    <!-- Highlights -->
    <h3>Highlights <button type="button" onclick="addHighlight()">➕</button></h3>
    <div id="highlight_section">
        <input type="text" name="highlight_text[]" placeholder="Highlight">
        <input type="text" name="highlight_icon[]" placeholder="Icon"><br>
    </div>

    <!-- Tabs -->
    <h3>Tabs <button type="button" onclick="addTab()">➕</button></h3>
    <div id="tab_section">
        <input type="text" name="tab_title[]" placeholder="Tab Title"><br>
        <textarea name="tab_content[]" placeholder="Tab Content"></textarea><br>
    </div>

    <input type="submit" value="<?= $edit_variant_id ? 'Update Variant' : 'Add Variant' ?>">
</form>
</body>
    <script>
        function addRow(containerId, keyName, valueName) {
            const div = document.createElement("div");
            div.innerHTML = `<input type='text' name='${keyName}[]' placeholder='Key'> <input type='text' name='${valueName}[]' placeholder='Value'><br>`;
            document.getElementById(containerId).appendChild(div);
        }

        function addHighlight() {
            const div = document.createElement("div");
            div.innerHTML = `<input type='text' name='highlight_text[]' placeholder='Highlight'> <input type='text' name='highlight_icon[]' placeholder='Icon'><br>`;
            document.getElementById("highlight_section").appendChild(div);
        }

        function addTab() {
            const div = document.createElement("div");
            div.innerHTML = `<input type='text' name='tab_title[]' placeholder='Tab Title'><br><textarea name='tab_content[]' placeholder='Tab Content'></textarea><br>`;
            document.getElementById("tab_section").appendChild(div);
        }
    </script>
</html>
