<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['memberID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$username = "root"; 
$password = ""; 
$database = "Caregivers"; 
$connection = new mysqli("localhost", $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $connection->connect_error]);
    exit();
}

// Fetch logged-in user's information
$logged_in_member_id = intval($_SESSION['memberID']);
$stmt = $connection->prepare('SELECT username, address, averageRating, balance, availability, phoneNumber FROM member WHERE memberID = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . $connection->error]);
    exit();
}
$stmt->bind_param('i', $logged_in_member_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    $stmt->close();
    $connection->close();
    exit();
}
$stmt->close();

// Output the response in JSON format
echo json_encode([
    'success' => true,
    'member' => $row
]);

// Close the database connection
$connection->close();
?>