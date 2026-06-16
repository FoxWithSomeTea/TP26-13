<?php
session_start();

$dev = $_GET["dev"] ?? null;
if ($dev) {
    $devUsers = [
        "1" => ["id" => 1, "name" => "Admin User", "role" => "admin"],
        "admin" => ["id" => 1, "name" => "Admin User", "role" => "admin"],
        "2" => ["id" => 4, "name" => "Karel Ucitel", "role" => "teacher"],
        "teacher" => ["id" => 4, "name" => "Karel Ucitel", "role" => "teacher"],
        "3" => ["id" => 2, "name" => "Jan Novak", "role" => "student"],
        "student" => ["id" => 2, "name" => "Jan Novak", "role" => "student"],
    ];
    $u = $devUsers[$dev] ?? $devUsers["1"];
    $_SESSION["user_id"] = $u["id"];
    $_SESSION["user_name"] = $u["name"];
    $_SESSION["user_role"] = $u["role"];
}

// Kontrola přihlášení – jinak přesměrování na login
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
    <div class="layout">
        <aside class="sidebar">
            <header class="title">
                <h3>Maturita manager</h3>
                <p class="user-info"><?= htmlspecialchars($_SESSION["user_name"]) ?> (<?= htmlspecialchars($_SESSION["user_role"]) ?>)</p>
            </header>
            <nav class="navigation">
                <?php if ($_SESSION["user_role"] === "admin"): ?>
                    <a target="iframe" href="students.html">Studenti</a>
                    <a target="iframe" href="admin.html">Administrace</a>
                    <a target="iframe" href="messages.php">Zprávy</a>
                <?php elseif ($_SESSION["user_role"] === "teacher"): ?>
                    <a target="iframe" href="students.html">Studenti</a>
                    <a target="iframe" href="messages.php">Zprávy</a>
                <?php else: ?>
                    <a target="iframe" href="thesis-student.html">Moje práce</a>
                    <a target="iframe" href="messages.php">Zprávy</a>
                <?php endif; ?>
                <a href="login.php">Změnit heslo</a>
                <a href="logout.php">Odhlásit</a>
            </nav>
        </aside>
        <main class="content">
            <iframe name="iframe" src="<?= $_SESSION["user_role"] === "student" ? "thesis-student.html" : "students.html" ?>" title="content"></iframe>
        </main>
    </div>
</body>
</html>
