<?php
require 'service.php';

if (isset($_GET['delete'])) {
    deleteUser($pdo, $_GET['delete']);
    header("Location: index.php");
}

$users = getAllUsers($pdo);
?>
<!DOCTYPE html>
<html>
<head>
    <title>User List</title>
</head>
<body>
<h1>User List</h1>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Age</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= $user['name'] ?></td>
            <td><?= $user['email'] ?></td>
            <td><?= $user['age'] ?></td>
            <td>
                <a href="add.php?id=<?= $user['id'] ?>">Update</a>
                <a href="index.php?delete=<?= $user['id'] ?>">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<a href="add.php">Add New User</a>
</body>
</html>
