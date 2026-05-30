<?php
// Start or resume a session to keep the user logged in across pages
session_start();

// Only handle POST requests (form submissions), ignore GET requests (just show the form)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Load database connection settings
    require_once "db.php";

    // Grab the submitted email and password from the form
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    // Look up the user by email using a prepared statement (safe from SQL injection)
    $stmt = $conn->prepare("SELECT id, first_name, last_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // If a user was found and the password matches the hashed one in the DB
    if ($user && password_verify($password, $user["password"])) {
        // Store user info in the session so they stay logged in
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["first_name"] . " " . $user["last_name"];
        $_SESSION["user_role"] = $user["role"];
        // Redirect to the main app page
        header("Location: index.php");
        exit;
    }

    // Login failed — redirect back with an error message shown in the form
    header("Location: login.php?error=Neplatn%C3%BD%20email%20nebo%20heslo");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>Login - Maturita manager</title>
</head>
<body class="login-page">
    <!-- Centered login card with the app name and form -->
    <div class="login-card">
        <h2>Maturita manager</h2>
        <!-- Form submits back to this same file (login.php) via POST -->
        <form action="login.php" method="POST">
            <label for="email">Email</label>
            <input class="filter" type="email" id="email" name="email" placeholder="email@skola.cz" required>

            <label for="password">Heslo</label>
            <input class="filter" type="password" id="password" name="password" placeholder="********" required>

            <button class="search_button" type="submit">Přihlásit</button>
        </form>
        <!-- Show error message if redirected back with ?error=... -->
        <?php if (isset($_GET["error"])): ?>
            <p class="login-error"><?= htmlspecialchars($_GET["error"]) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
