<?php
require 'db.php';

// Get all users
function getAllUsers($pdo) {
    $stmt = $pdo->query("SELECT * FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Save user
function saveUser($pdo, $name, $email, $age) {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, age) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $age]);
}

// Get single user by ID
function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update user
function updateUser($pdo, $id, $name, $email, $age) {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, age = ? WHERE id = ?");
    $stmt->execute([$name, $email, $age, $id]);
}

// Delete user
function deleteUser($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
}
?>
