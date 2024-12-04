<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['memberID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Database connection
$username = "root"; 
$password = ""; 
$database = "Caregivers"; 
$connection = new mysqli("localhost", $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $connection->connect_error]);
    exit();
}

// Fetch logged-in user's ID
$logged_in_member_id = intval($_SESSION['memberID']);

// Handle the form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'updateAvailability' && isset($_POST['availability'])) {
            $newAvailability = intval($_POST['availability']);
            
            // Update availability in the database
            $stmt = $connection->prepare('UPDATE member SET availability = ? WHERE memberID = ?');
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare availability update query: ' . $connection->error]);
                exit();
            }
            $stmt->bind_param('ii', $newAvailability, $logged_in_member_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Availability updated successfully.']);
            exit();
        }

        if ($_POST['action'] === 'updateAddress' && isset($_POST['address'])) {
            $newAddress = $connection->real_escape_string($_POST['address']);
            
            // Update address in the database
            $stmt = $connection->prepare('UPDATE member SET address = ? WHERE memberID = ?');
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare address update query: ' . $connection->error]);
                exit();
            }
            $stmt->bind_param('si', $newAddress, $logged_in_member_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Address updated successfully.']);
            exit();
        }

        if ($_POST['action'] === 'updatePhone' && isset($_POST['phone'])) {
            $newPhone = $connection->real_escape_string($_POST['phone']);
            
            // Update phone number in the database
            $stmt = $connection->prepare('UPDATE member SET phone = ? WHERE memberID = ?');
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare phone update query: ' . $connection->error]);
                exit();
            }
            $stmt->bind_param('si', $newPhone, $logged_in_member_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Phone number updated successfully.']);
            exit();
        }
    }
}

// If no action matched or POST data is missing
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit();
?>
