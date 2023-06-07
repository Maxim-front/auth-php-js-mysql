<?php
session_start();

// Database connection
$host = "localhost";
$username = "maxim";
$password = "user123";
$dbname = "test_db";

$db = new mysqli($host, $username, $password, $dbname);
if ($db->connect_error) {
    die("Database connection error: " . $db->connect_error);
}

if (isset($_COOKIE['session_token'])) {
    // Get the session token from the cookie
    $session_token = $db->real_escape_string($_COOKIE['session_token']);
    $query = "SELECT session_token FROM sessions WHERE session_token = ?";

    // Prepare the statement
    $stmt = $db->prepare($query);
    if (!$stmt) {
        die("Error in preparing statement: " . $db->error);
    }

    // Bind the session token parameter
    $stmt->bind_param("s", $session_token);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $session_id = $row['session_token'];

        // Destroy the specific session by setting its session ID
        session_unset();
        session_destroy();

        // Log out the user and remove session data
        setcookie('session_token', '', time() - 3600, '/');
        // Remove the session from the database
        $deleteQuery = "DELETE FROM sessions WHERE session_token = ?";

        // Prepare the delete statement
        $deleteStmt = $db->prepare($deleteQuery);
        if (!$deleteStmt) {
            die("Error in preparing delete statement: " . $db->error);
        }

        // Bind the session token parameter
        $deleteStmt->bind_param("s", $session_token);

        // Execute the delete statement
        $deleteStmt->execute();

        // Send a successful response
        $response = array(
            "success" => true,
            "message" => "You have been successfully logged out"
        );

        // Close the delete statement
        $deleteStmt->close();
    } else {
        // Session is invalid
        $response = array(
            "success" => false,
            "message" => "Invalid session"
        );
    }

    // Close the statement
    $stmt->close();
} else {
    // Session is invalid
    $response = array(
        "success" => false,
        "message" => "Invalid session"
    );
}
echo json_encode($response);

// Close the database connection
$db->close();
?>
