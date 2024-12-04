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

// Retrieve and validate input
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['contractID'], $input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing contractID or status.']);
    exit();
}

$contractID = intval($input['contractID']);
$status = $conn->real_escape_string($input['status']);

// Fetch contract details
$contractQuery = $conn->prepare('SELECT caretakerID, clientID, requestID FROM contracts WHERE contractID = ?');
if (!$contractQuery) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare contract query: ' . $conn->error]);
    exit();
}
$contractQuery->bind_param('i', $contractID);
$contractQuery->execute();
$contract = $contractQuery->get_result()->fetch_assoc();
$contractQuery->close();

if (!$contract) {
    echo json_encode(['success' => false, 'message' => 'Contract not found.']);
    exit();
}

$caretakerID = $contract['caretakerID'];
$clientID = $contract['clientID'];
$requestID = $contract['requestID'];

// Process the contract status update
if ($status === 'accepted') {
    // Fetch caretaker's hourly rate and client's balance
    $query = $conn->prepare(
        'SELECT c.dailyHoursWorked, DATEDIFF(c.endDate, c.startDate) + 1 AS totalDays, 
        m.balance AS clientBalance, mc.hourlyRate AS caretakerRate
        FROM contracts c 
        JOIN member m ON c.clientID = m.memberID 
        JOIN member mc ON c.caretakerID = mc.memberID 
        WHERE c.contractID = ?'
    );

    if (!$query) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare contract balance query: ' . $conn->error]);
        exit();
    }

    $query->bind_param('i', $contractID);
    $query->execute();
    $contractDetails = $query->get_result()->fetch_assoc();
    $query->close();

    if (!$contractDetails) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch contract details.']);
        exit();
    }

    $totalPayment = $contractDetails['dailyHoursWorked'] * $contractDetails['totalDays'] * $contractDetails['caretakerRate'];
    $clientBalance = $contractDetails['clientBalance'];

    if ($clientBalance < $totalPayment) {
        echo json_encode(['success' => false, 'message' => 'Client does not have sufficient balance.']);
        exit();
    }

    // Deduct from client's balance
    $updateClientBalance = $conn->prepare('UPDATE member SET balance = balance - ? WHERE memberID = ?');
    $updateClientBalance->bind_param('di', $totalPayment, $clientID);
    $updateClientBalance->execute();
    $updateClientBalance->close();

    // Add to caretaker's balance
    $updateCaretakerBalance = $conn->prepare('UPDATE member SET balance = balance + ? WHERE memberID = ?');
    $updateCaretakerBalance->bind_param('di', $totalPayment, $caretakerID);
    $updateCaretakerBalance->execute();
    $updateCaretakerBalance->close();

    // Remove listing
    $removeListing = $conn->prepare('DELETE FROM care_requests WHERE requestID = ?');
    if ($removeListing) {
        $removeListing->bind_param('i', $requestID);
        $removeListing->execute();
        $removeListing->close();
    }
}

// Update the contract status
$updateStatus = $conn->prepare('UPDATE contracts SET status = ? WHERE contractID = ?');
if (!$updateStatus) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare status update query: ' . $conn->error]);
    exit();
}

$updateStatus->bind_param('si', $status, $contractID);
if ($updateStatus->execute()) {
    echo json_encode(['success' => true, 'message' => 'Contract status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update contract status: ' . $updateStatus->error]);
}
$updateStatus->close();
$conn->close();
?>
