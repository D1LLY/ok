<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['memberID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your DB username
$password = "";     // Replace with your DB password
$database = "Caregivers"; // Replace with your DB name
$connection = new mysqli($servername, $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $connection->connect_error]);
    exit();
}

// Get logged-in user's memberID
$logged_in_member_id = intval($_SESSION['memberID']);

// Fetch all contracts submitted by the caregiver
$sql = "
    SELECT c.contractID, c.startDate, c.endDate, c.dailyHoursWorked, c.status,
           m.username AS clientName, m.address AS clientAddress, m.averageRating AS clientRating
    FROM contracts c
    JOIN member m ON c.clientID = m.memberID
    WHERE c.caretakerID = ?
    ORDER BY c.startDate ASC
";

$stmt = $connection->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . $connection->error]);
    exit();
}

$stmt->bind_param('i', $logged_in_member_id);
$stmt->execute();
$result = $stmt->get_result();

$contracts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $contracts[] = $row;
    }
}

echo json_encode(['success' => true, 'contracts' => $contracts]);
$stmt->close();
$connection->close();
?>
