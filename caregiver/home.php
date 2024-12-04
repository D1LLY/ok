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

// Fetch logged-in user's stats dynamically
$logged_in_member_id = intval($_SESSION['memberID']);
$stmt = $connection->prepare('SELECT balance, averageRating, availability FROM member WHERE memberID = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare user stats query: ' . $connection->error]);
    exit();
}
$stmt->bind_param('i', $logged_in_member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo json_encode(['success' => false, 'message' => 'User stats not found.']);
    $stmt->close();
    $connection->close();
    exit();
}
$stmt->close();

// Fetch other members excluding the logged-in user
$members_stmt = $connection->prepare('SELECT username, address, averageRating, balance, availability FROM member WHERE memberID != ?');
if (!$members_stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare members query: ' . $connection->error]);
    exit();
}
$members_stmt->bind_param('i', $logged_in_member_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();

$members = [];
while ($row = $members_result->fetch_assoc()) {
    $members[] = $row;
}
$members_stmt->close();

// Fetch working notifications (contracts where the logged-in user is a caretaker and status is 'pending')
$working_stmt = $connection->prepare('SELECT COUNT(*) AS working_notifications FROM contracts WHERE caretakerID = ? AND status = "pending"');
if (!$working_stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare working notifications query: ' . $connection->error]);
    exit();
}
$working_stmt->bind_param('i', $logged_in_member_id);
$working_stmt->execute();
$working_result = $working_stmt->get_result();
$working_notifications = $working_result->fetch_assoc()['working_notifications'] ?? 0;
$working_stmt->close();

// Fetch hiring notifications (contracts where the logged-in user is a client and status is 'pending')
$hiring_stmt = $connection->prepare('SELECT COUNT(*) AS hiring_notifications FROM contracts WHERE clientID = ? AND status = "pending"');
if (!$hiring_stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare hiring notifications query: ' . $connection->error]);
    exit();
}
$hiring_stmt->bind_param('i', $logged_in_member_id);
$hiring_stmt->execute();
$hiring_result = $hiring_stmt->get_result();
$hiring_notifications = $hiring_result->fetch_assoc()['hiring_notifications'] ?? 0;
$hiring_stmt->close();

// Output the response in JSON format
echo json_encode([
    'success' => true,
    'user' => [
        'balance' => $user['balance'],
        'averageRating' => $user['averageRating'],
        'availability' => $user['availability']
    ],
    'members' => $members,
    'working_notifications' => $working_notifications,
    'hiring_notifications' => $hiring_notifications
]);

$connection->close();
?>
