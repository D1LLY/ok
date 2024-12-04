<?php
session_start();

// Ensure the user is logged in
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

// Fetch contracts where the logged-in user is the client
$clientID = intval($_SESSION['memberID']);
$sql = "
    SELECT c.contractID, c.startDate, c.endDate, c.dailyHoursWorked, c.status,
           m.username AS caretakerName, m.address AS caretakerAddress,
           m.averageRating AS caretakerRating, c.requestID
    FROM contracts c
    JOIN member m ON c.caretakerID = m.memberID
    WHERE c.clientID = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $clientID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $contracts = [];
    while ($row = $result->fetch_assoc()) {
        $contracts[] = $row;
    }
    echo json_encode(['success' => true, 'contracts' => $contracts]);
} else {
    echo json_encode(['success' => false, 'message' => 'No hiring contracts found.']);
}

$stmt->close();
$conn->close();
?>
