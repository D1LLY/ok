<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['memberID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Database connection
$username = "root"; // Replace with your DB username
$password = ""; // Replace with your DB password
$database = "Caregivers"; // Replace with your DB name
$connection = new mysqli("localhost", $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $connection->connect_error]);
    exit();
}

// Get type of data to fetch
$logged_in_member_id = intval($_SESSION['memberID']);
$type = isset($_GET['type']) ? $_GET['type'] : 'basic';

if ($type === 'basic') {
    // Fetch basic member data for dropdowns
    $stmt = $connection->prepare('SELECT memberID, username FROM member WHERE memberID != ?');
} elseif ($type === 'detailed') {
    // Fetch detailed member data for cards
    $stmt = $connection->prepare('SELECT username, address, averageRating, balance, availability FROM member WHERE memberID != ?');
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid type specified.']);
    exit();
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . $connection->error]);
    exit();
}

$stmt->bind_param('i', $logged_in_member_id);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

if (count($members) > 0) {
    echo json_encode(['success' => true, 'members' => $members]);
} else {
    echo json_encode(['success' => false, 'message' => 'No members found.']);
}

$stmt->close();
$connection->close();
?>
