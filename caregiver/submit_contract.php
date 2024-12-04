<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['memberID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "Caregivers");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Validate inputs
if (empty($_POST['clientName']) || empty($_POST['startDate']) || empty($_POST['endDate']) || empty($_POST['dailyHoursWorked']) || empty($_POST['requestID'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

// Retrieve form data
$caretakerID = intval($_SESSION['memberID']); // Logged-in caretaker
$clientName = $conn->real_escape_string($_POST['clientName']);
$startDate = $_POST['startDate'];
$endDate = $_POST['endDate'];
$dailyHoursWorked = intval($_POST['dailyHoursWorked']);
$requestID = intval($_POST['requestID']);

// Get the client ID based on the client name
$clientQuery = $conn->prepare('SELECT memberID FROM member WHERE username = ?');
if (!$clientQuery) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare client query: ' . $conn->error]);
    exit();
}
$clientQuery->bind_param('s', $clientName);
$clientQuery->execute();
$clientResult = $clientQuery->get_result();

if ($clientResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Client not found.']);
    $clientQuery->close();
    $conn->close();
    exit();
}

$clientData = $clientResult->fetch_assoc();
$clientID = intval($clientData['memberID']);
$clientQuery->close();

// Insert the contract into the database
$insertContract = $conn->prepare('INSERT INTO contracts (caretakerID, clientID, startDate, endDate, dailyHoursWorked, requestID) VALUES (?, ?, ?, ?, ?, ?)');
if (!$insertContract) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare contract insertion: ' . $conn->error]);
    exit();
}
$insertContract->bind_param('iissii', $caretakerID, $clientID, $startDate, $endDate, $dailyHoursWorked, $requestID);

if ($insertContract->execute()) {
    echo json_encode(['success' => true, 'message' => 'Contract successfully submitted.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit contract: ' . $insertContract->error]);
}

$insertContract->close();
$conn->close();
?>
