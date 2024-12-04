<?php
session_start();

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

// Fetch care requests with parent and client details
$result = $connection->query("
    SELECT 
        cr.startDate, cr.startTime, cr.endTime,
        m.username AS clientName,
        p.parentName, p.age AS parentAge, p.needs AS parentNeeds
    FROM 
        caregiver_requests cr
    JOIN 
        parents p ON cr.parentID = p.parentID
    JOIN 
        member m ON cr.memberID = m.memberID
    ORDER BY 
        cr.startDate ASC, cr.startTime ASC
");

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $connection->error]);
    exit();
}

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

if (count($requests) > 0) {
    echo json_encode(['success' => true, 'requests' => $requests]);
} else {
    echo json_encode(['success' => false, 'message' => 'No care requests found.']);
}

$connection->close();
?>
