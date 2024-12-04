<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli('localhost', 'root', '', 'Caregivers');
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT * FROM member WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['passwd'])) {
            $_SESSION['memberID'] = $user['memberID'];
            header("Location: home.html?id=" . $user['memberID']);
            exit();
        } else {
            echo "Invalid username or password.";
        }
    } else {
        echo "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}
?>
