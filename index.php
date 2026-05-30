<?php
// Start or resume the session (set after successful login)
session_start();

// Dev bypass: append ?dev=1 to the URL to skip login during development
$devMode = isset($_GET["dev"]);

if ($devMode) {
    // Pretend we're logged in as a dev admin user
    $_SESSION["user_id"] = 1;
    $_SESSION["user_name"] = "Dev User";
    $_SESSION["user_role"] = "admin";
}

// If no user is logged in (and not in dev mode), redirect to the login page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
</head>
<body>

    <!-- Main layout: sidebar on the left, content area on the right -->
    <div class="layout">

        <!-- Sidebar navigation -->
        <aside class="sidebar">
            <header class="title">
                <h3>Maturita manager</h3>
                <!-- Show the logged-in user's name -->
                <p class="user-info"><?= htmlspecialchars($_SESSION["user_name"]) ?></p>
            </header>
            <nav class="navigation">
                <!-- target="iframe" loads each page inside the iframe below -->
                <a target="iframe" href="overview.html">Přehled</a>
                <a target="iframe" href="students.html">Studenti</a>
                <a target="iframe" href="messages.html">Zpravy</a>
                <!-- Logout link — destroys the session and goes back to login -->
                <a href="logout.php">Odhlásit</a>
            </nav>
        </aside>

        <!-- Main content area with an iframe that loads the selected page -->
        <main class="content">
            <iframe name="iframe" src="overview.html" title="content"></iframe>
        </main>
    </div>

</body>
</html>
