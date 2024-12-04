<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $address = trim($_POST['address']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $availability = trim($_POST['availability']);
    $parentNames = $_POST['parentName'];
    $parentAges = $_POST['parentAge'];
    $parentNeeds = $_POST['parentNeeds'];

    // Validate inputs
    if (empty($username) || empty($password) || empty($address) || empty($phoneNumber) || empty($availability) || empty($parentNames)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: register.html");
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'Caregivers');
    if ($conn->connect_error) {
        $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
        header("Location: register.html");
        exit();
    }

    // Insert new member
    $stmt = $conn->prepare("INSERT INTO member (username, passwd, address, phoneNumber, availability) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $username, $hashedPassword, $address, $phoneNumber, $availability);

    if ($stmt->execute()) {
        $memberID = $stmt->insert_id; // Get the newly created member ID

        // Insert parent information
        $parentStmt = $conn->prepare("INSERT INTO parents (memberID, parentName, age, needs) VALUES (?, ?, ?, ?)");
        for ($i = 0; $i < count($parentNames); $i++) {
            $parentName = $parentNames[$i];
            $parentAge = $parentAges[$i];
            $parentNeed = $parentNeeds[$i];

            $parentStmt->bind_param("isis", $memberID, $parentName, $parentAge, $parentNeed);
            $parentStmt->execute();
        }

        // Update numParents field in the member table
        $numParents = count($parentNames);
        $updateStmt = $conn->prepare("UPDATE member SET numParents = ? WHERE memberID = ?");
        $updateStmt->bind_param("ii", $numParents, $memberID);
        $updateStmt->execute();

        // Redirect to index.html with a success message
        $_SESSION['success'] = "Registration successful! Please log in.";
        $stmt->close();
        $parentStmt->close();
        $updateStmt->close();
        $conn->close();
        header("Location: index.html");
        exit();
    } else {
        $_SESSION['error'] = "Failed to register: " . $stmt->error;
        $stmt->close();
        $conn->close();
        header("Location: register.html");
        exit();
    }
}
?>
