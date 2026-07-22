<?php
$conn = new mysqli("localhost", "root", "", "disaster_system");

if ($conn->connect_error) {
    die("DB Connection Failed");
}
?>