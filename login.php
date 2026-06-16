<?php
session_start();

// Dev bypass – přihlášení bez hesla (pro vývoj)
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
    header("Location: index.php");
    exit;
}

// Přihlášení přes databázi (email + heslo)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once "db.php";

    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT id, first_name, last_name, password, role FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["first_name"] . " " . $user["last_name"];
        $_SESSION["user_role"] = $user["role"];
        header("Location: index.php");
        exit;
    }

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
    <div class="login-card">
        <div id="login-form">
            <h2>Maturita manager</h2>
            <form action="login.php" method="POST">
                <label for="email">Email</label>
                <input class="filter" type="email" id="email" name="email" placeholder="email@skola.cz" required>

                <label for="password">Heslo</label>
                <input class="filter" type="password" id="password" name="password" placeholder="********" required>

                <button class="search_button" type="submit">Přihlásit</button>
            </form>
            <p style="text-align:center; margin-top:15px;">
                <a href="#" onclick="switchToChangePassword(); return false;" style="color:var(--importantblue);">Změnit heslo</a>
            </p>
            <?php if (isset($_GET["error"])): ?>
                <p class="login-error"><?= htmlspecialchars($_GET["error"]) ?></p>
            <?php endif; ?>
        </div>

        <div id="change-password-form-wrap" style="display:none;">
            <h2>Změnit heslo</h2>
            <form id="change-password-form" onsubmit="changePassword(event)">
                <label for="cp-email">Email</label>
                <input class="filter" type="email" id="cp-email" required placeholder="email@skola.cz">

                <label for="cp-old">Staré heslo</label>
                <input class="filter" type="password" id="cp-old" required placeholder="Staré heslo">

                <label for="cp-new">Nové heslo</label>
                <input class="filter" type="password" id="cp-new" required placeholder="Nové heslo (min. 6 znaků)" minlength="6">

                <label for="cp-confirm">Potvrdit nové heslo</label>
                <input class="filter" type="password" id="cp-confirm" required placeholder="Potvrdit nové heslo">

                <button class="search_button" type="submit">Uložit heslo</button>
            </form>
            <p id="cp-error" class="login-error" style="display:none"></p>
            <p id="cp-success" class="import-result" style="display:none"></p>
            <p style="text-align:center; margin-top:15px;">
                <a href="#" onclick="switchToLogin(); return false;" style="color:var(--importantblue);">Zpět na přihlášení</a>
            </p>
        </div>
    </div>
    <script src="change_password.js"></script>
</body>
</html>
