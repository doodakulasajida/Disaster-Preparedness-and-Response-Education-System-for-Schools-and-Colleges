<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

echo "<h2>Quiz Results Debug</h2>";

// Show all quiz results
$result = $conn->query("SELECT * FROM quiz_results ORDER BY id DESC");
echo "<h3>All Quiz Results:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>ID</th><th>User ID</th><th>Disaster</th><th>Score</th><th>Total</th><th>Created At</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['user_id']}</td>";
    echo "<td>{$row['disaster']}</td>";
    echo "<td>{$row['score']}</td>";
    echo "<td>{$row['total']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// Show all users
$users = $conn->query("SELECT id, name, email, role FROM users");
echo "<h3>All Users:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
while ($user = $users->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "</tr>";
}
echo "</table>";

// Show quiz results with user names
echo "<h3>Quiz Results with User Names:</h3>";
$query = "SELECT u.name, u.email, qr.disaster, qr.score, qr.total, qr.created_at 
          FROM quiz_results qr 
          JOIN users u ON qr.user_id = u.id 
          ORDER BY qr.created_at DESC";
$results = $conn->query($query);

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Name</th><th>Email</th><th>Disaster</th><th>Score</th><th>Total</th><th>Date</th></tr>";
while ($row = $results->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['disaster']}</td>";
    echo "<td>{$row['score']}/{$row['total']}</td>";
    echo "<td>" . date('Y-m-d H:i:s', strtotime($row['created_at'])) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>