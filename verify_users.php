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

if (isset($_POST["session_token"])) {
    // Get the session token from the request
    $session_token = $db->real_escape_string($_POST["session_token"]);
    $query = "SELECT * FROM sessions WHERE session_token = ?";

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
        // Session token is valid, retrieve user ID and expiration time
        $row = $result->fetch_assoc();
        $expires_at = $row['expires_at'];
        $user_id = $row['user_id'];

        // Check if the session has expired
        if (strtotime($expires_at) < time()) {
            // Session expired, delete the session record
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

            // Close the delete statement
            $deleteStmt->close();

            $response = array(
                "success" => false,
                "message" => "Session has expired"
            );
            echo json_encode($response);
            exit;
        }

        // Find user in the database
        $query = "SELECT * FROM users WHERE user_id = ?";

        // Prepare the user query statement
        $userStmt = $db->prepare($query);
        if (!$userStmt) {
            die("Error in preparing user query statement: " . $db->error);
        }

        // Bind the user ID parameter
        $userStmt->bind_param("s", $user_id);

        // Execute the user query statement
        $userStmt->execute();

        // Get the user result
        $userResult = $userStmt->get_result();

        if ($userResult && $userResult->num_rows > 0) {
            // User found, retrieve user data
            $user = $userResult->fetch_assoc();

            $response = array(
                "success" => true,
                "user" => array(
                    "name" => $user["user_name"],
                    "photo" => $user["photo"],
                    "birthday" => $user["birthday"]
                )
            );
        } else {
            // User not found in the database
            $response = array(
                "success" => false,
                "message" => "User not found"
            );
        }

        // Close the user query statement
        $userStmt->close();

        echo json_encode($response);
    } else {
        // Session token is invalid or expired
        $response = array(
            "success" => false,
            "message" => "Session is not valid"
        );
        echo json_encode($response);
    }

    // Close the statement
    $stmt->close();
} else {
    // Session token is not provided in the request
    $response = array(
        "success" => false,
        "message" => "Session is not valid"
    );
    echo json_encode($response);
}

// Close the database connection
$db->close();
?>
