<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$level = $_GET['level'] ?? '';
$main_category_id = $_GET['main_category_id'] ?? '';
$sub_category_id = $_GET['sub_category_id'] ?? '';
$series_id = $_GET['series_id'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Category Structure</title>
    <style>
        select, input[type="text"] {
            width: 300px;
            padding: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<h2>Add New Category / Series / Option</h2>

<form method="GET" action="">
    <label>Select Type:</label><br>
    <select name="level" onchange="this.form.submit()">
        <option value="">-- Select Level --</option>
        <option value="main" <?= $level === 'main' ? 'selected' : '' ?>>Main Category</option>
        <option value="sub" <?= $level === 'sub' ? 'selected' : '' ?>>Sub Category</option>
        <option value="series" <?= $level === 'series' ? 'selected' : '' ?>>Series</option>
        <option value="series_option" <?= $level === 'series_option' ? 'selected' : '' ?>>Series Option</option>
    </select><br><br>

    <?php if ($level === 'sub' || $level === 'series' || $level === 'series_option'): ?>
        <label>Main Category:</label><br>
        <select name="main_category_id" onchange="this.form.submit()">
            <option value="">-- Select Main Category --</option>
            <?php
            $result = $conn->query("SELECT id, name FROM main_categories ORDER BY name");
            while ($row = $result->fetch_assoc()) {
                $selected = ($main_category_id == $row['id']) ? "selected" : "";
                echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
            }
            ?>
        </select><br><br>
    <?php endif; ?>

    <?php if ($level === 'series' || $level === 'series_option'): ?>
        <label>Sub Category:</label><br>
        <select name="sub_category_id" onchange="this.form.submit()">
            <option value="">-- Select Sub Category --</option>
            <?php
            if ($main_category_id) {
                $stmt = $conn->prepare("SELECT id, name FROM sub_categories WHERE main_category_id = ? ORDER BY name");
                $stmt->bind_param("i", $main_category_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $selected = ($sub_category_id == $row['id']) ? "selected" : "";
                    echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
                }
                $stmt->close();
            }
            ?>
        </select><br><br>
    <?php endif; ?>

    <?php if ($level === 'series_option'): ?>
        <label>Series:</label><br>
        <select name="series_id" onchange="this.form.submit()">
            <option value="">-- Select Series --</option>
            <?php
            if ($sub_category_id) {
                $stmt = $conn->prepare("SELECT id, name FROM series WHERE sub_category_id = ? ORDER BY name");
                $stmt->bind_param("i", $sub_category_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $selected = ($series_id == $row['id']) ? "selected" : "";
                    echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
                }
                $stmt->close();
            }
            ?>
        </select><br><br>
    <?php endif; ?>
</form>

<!-- Form to add the new item -->
<form method="POST">
    <input type="hidden" name="level" value="<?= htmlspecialchars($level) ?>">
    <input type="hidden" name="main_category_id" value="<?= htmlspecialchars($main_category_id) ?>">
    <input type="hidden" name="sub_category_id" value="<?= htmlspecialchars($sub_category_id) ?>">
    <input type="hidden" name="series_id" value="<?= htmlspecialchars($series_id) ?>">

    <label>Enter Name:</label><br>
    <input type="text" name="name" required><br><br>

    <input type="submit" name="submit" value="Add">
</form>

<?php
if (isset($_POST['submit'])) {
    $level = $_POST['level'];
    $name = $_POST['name'];

    if ($level === 'main') {
        $stmt = $conn->prepare("INSERT INTO main_categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
    } elseif ($level === 'sub') {
        $main_id = $_POST['main_category_id'];
        if (!$main_id) {
            echo "<p style='color:red;'>❌ Please select a valid Main Category</p>";
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO sub_categories (name, main_category_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $main_id);
    } elseif ($level === 'series') {
        $sub_id = $_POST['sub_category_id'];
        if (!$sub_id) {
            echo "<p style='color:red;'>❌ Please select a valid Sub Category</p>";
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO series (name, sub_category_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $sub_id);
    } elseif ($level === 'series_option') {
        $series_id = $_POST['series_id'];
        if (!$series_id) {
            echo "<p style='color:red;'>❌ Please select a valid Series</p>";
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO series_options (name, series_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $series_id);
    } else {
        echo "<p style='color:red;'>❌ Invalid type selected.</p>";
        exit;
    }

    if ($stmt->execute()) {
        echo "<p style='color:green;'>✅ Successfully added!</p>";
    } else {
        echo "<p style='color:red;'>❌ Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
}
?>
</body>
</html>
