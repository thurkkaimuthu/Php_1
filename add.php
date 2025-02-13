<?php
require 'service.php';

$name = $email = $age = "";
$id = null;

if (isset($_GET['id'])) {
    $user = getUserById($pdo, $_GET['id']);
    if ($user) {
        $id = $user['id'];
        $name = $user['name'];
        $email = $user['email'];
        $age = $user['age'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $age = $_POST['age'];

    if ($id) {
        updateUser($pdo, $id, $name, $email, $age);
    } else {
        saveUser($pdo, $name, $email, $age);
    }
    header("Location: index.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $id ? "Edit User" : "Add User" ?></title>
</head>
<body>
<h1><?= $id ? "Edit User" : "Add User" ?></h1>
<form method="POST">
    <label>Name:</label>
    <input type="text" name="name" value="<?= $name ?>" required><br>
    <label>Email:</label>
    <input type="email" name="email" value="<?= $email ?>" required><br>
    <label>Age:</label>
    <input type="number" name="age" value="<?= $age ?>" required><br>
    <button type="submit"><?= $id ? "Update" : "Save" ?></button>
</form>
<a href="index.php">Back to User List</a>
</body>
</html>
