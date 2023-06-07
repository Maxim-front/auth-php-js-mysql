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

// Get username and password from the request
$username = $db->real_escape_string($_POST["username"]);
$password = $_POST["password"];

// Check if the user exists
$query = "SELECT * FROM users WHERE email = ?";

// Prepare the statement
$stmt = $db->prepare($query);
if (!$stmt) {
    die("Error in preparing statement: " . $db->error);
}

// Bind the username parameter
$stmt->bind_param("s", $username);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Verify the password
    if (!password_verify($password, $user['password'])) {
        // Incorrect login or password
        $response = array(
            "success" => false,
            "message" => "Incorrect login or password"
        );
        echo json_encode($response);
        exit;
    }

    $user_id = $user["user_id"];
    $salt = bin2hex(random_bytes(16));
    $raw_token = $user_id . $salt;
    $session_token = hash('sha256', $raw_token);

    // Store session token in session and set a cookie
    $_SESSION['session_token'] = $session_token;
    setcookie('session_token', $session_token, time() + 3600, '/');
    saveSessionTokenToDatabase($user_id, $session_token);

    // Successful authentication
    $response = array(
        "success" => true,
        "message" => "Authentication successful",
        "user" => array(
            "name" => $user["user_name"],
            "photo" => $user["photo"],
            "birthday" => $user["birthday"]
        ),
    );
} else {
    // Incorrect login or password
    $response = array(
        "success" => false,
        "message" => "Incorrect login or password"
    );
}
echo json_encode($response);

function saveSessionTokenToDatabase($user_id, $session_token): bool
{
    global $db;
    $user_id = $db->real_escape_string($user_id);
    $session_token = $db->real_escape_string($session_token);

    $expires_at = date('Y-m-d H:i:s', time() + 3600);

    $query = "INSERT INTO sessions (user_id, session_token, expires_at) VALUES ('$user_id', '$session_token', '$expires_at')";
    $result = $db->query($query);

    return (bool)$result;
}

$db->close();
?>
