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
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Fetch parents for the logged-in user
$memberID = $_SESSION['memberID'];
$stmt = $conn->prepare("SELECT parentID, parentName FROM parents WHERE memberID = ?");
$stmt->bind_param("i", $memberID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $parents = [];
    while ($row = $result->fetch_assoc()) {
        $parents[] = $row;
    }
    echo json_encode(['success' => true, 'parents' => $parents]);
} else {
    echo json_encode(['success' => false, 'message' => 'No parents found.']);
}

$stmt->close();
$conn->close();
?>
