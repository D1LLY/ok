<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['memberID'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "Caregivers");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Validate and capture input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberID = $_SESSION['memberID'];
    $parentID = intval($_POST['parentID']);
    $startDate = $_POST['startDate'];
    $startTime = $_POST['startTime'];
    $endDate = $_POST['endDate'];
    $endTime = $_POST['endTime'];

    if (empty($memberID) || empty($parentID) || empty($startDate) || empty($startTime) || empty($endDate) || empty($endTime)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO care_requests (memberID, parentID, startDate, startTime, endDate, endTime) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $memberID, $parentID, $startDate, $startTime, $endDate, $endTime);

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Care request submitted successfully!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to submit care request: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
