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

// Get the logged-in user's ID
$logged_in_member_id = intval($_SESSION['memberID']);

// Query to exclude listings of the logged-in user and include the requestID
$sql = "
    SELECT 
        cr.requestID, 
        cr.startDate, 
        cr.startTime, 
        cr.endDate, 
        cr.endTime, 
        p.parentName, 
        p.age AS parentAge, 
        p.needs AS parentNeeds, 
        m.username AS clientName
    FROM 
        care_requests cr
    JOIN 
        parents p ON cr.parentID = p.parentID AND p.memberID = cr.memberID
    JOIN 
        member m ON cr.memberID = m.memberID
    WHERE 
        cr.memberID != ?
    ORDER BY 
        cr.startDate, cr.startTime
";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . $conn->error]);
    exit();
}

$stmt->bind_param('i', $logged_in_member_id);
$stmt->execute();
$result = $stmt->get_result();

// Handle result
if ($result) {
    if ($result->num_rows > 0) {
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        echo json_encode(['success' => true, 'requests' => $requests]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No care requests found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch care requests: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
