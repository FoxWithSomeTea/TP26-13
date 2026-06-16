<?php
session_start();

$action = $_GET["action"] ?? "";

header("Content-Type: application/json");
require_once "db.php";
require_once "helpers.php";

// Tabulka rout – každá akce má oprávnění a volitelně HTTP metodu
$routes = [
    "getStudents"   => ["auth" => "user"],
    "getMessages"   => ["auth" => "user"],
    "sendMessage"   => ["auth" => "user",  "method" => "POST"],
    "markRead"      => ["auth" => "user"],
    "getUsers"      => ["auth" => "user"],
    "getClasses"    => ["auth" => "user"],
    "getStats"      => ["auth" => "user"],
    "createUser"    => ["auth" => "admin", "method" => "POST"],
    "createClass"   => ["auth" => "admin", "method" => "POST"],
    "importStudents" => ["auth" => "admin", "method" => "POST"],
    "resetPassword" => ["auth" => "admin", "method" => "POST"],
    "deleteUser"    => ["auth" => "admin", "method" => "POST"],
    "deleteClass"   => ["auth" => "admin", "method" => "POST"],
    "getMe"              => ["auth" => "user"],
    "getThesis"          => ["auth" => "user"],
    "getThesisByStudent" => ["auth" => "user"],
    "listThesisFiles"    => ["auth" => "user"],
    "uploadInstruction"  => ["auth" => "user", "method" => "POST"],
    "uploadSubmission"   => ["auth" => "user", "method" => "POST"],
    "deleteThesisFile"   => ["auth" => "user", "method" => "POST"],
    "saveThesisNote"     => ["auth" => "user", "method" => "POST"],
    "toggleSubmit"       => ["auth" => "user", "method" => "POST"],
    "reviewThesis"       => ["auth" => "user", "method" => "POST"],
    "downloadFile"       => ["auth" => "user"],
    "changePassword"    => ["method" => "POST"],
];

// Ověření, že akce existuje a uživatel má oprávnění
if (!isset($routes[$action])) {
    die(json_encode(["error" => "Neznámá akce"]));
}

$route = $routes[$action];

if ($route["auth"] === "user" && !isset($_SESSION["user_id"])) {
    die(json_encode(["error" => "Neoprávněný přístup"]));
}
if ($route["auth"] === "admin" && (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin")) {
    die(json_encode(["error" => "Přístup zamítnut"]));
}
if (isset($route["method"]) && $_SERVER["REQUEST_METHOD"] !== $route["method"]) {
    die(json_encode(["error" => "Nepovolená metoda"]));
}

// Hlavní router – podle parametru action
switch ($action) {

case "getStudents":
    // Seznam studentů (učitel vidí jen své studenty)
    $user_id = $_SESSION["user_id"];
    $user_role = $_SESSION["user_role"];
    $sql = "SELECT u.id, u.first_name, u.last_name, t.id AS thesis_id,
        t.instruction_pdf_path,
        CONCAT(u.first_name, ' ', u.last_name) AS name,
        c.name AS class,
        t.title AS thesis_title,
        t.status AS thesis_status,
        (SELECT COUNT(*) FROM thesis_files WHERE thesis_id = t.id) AS file_count,
        CONCAT(s.first_name, ' ', s.last_name) AS teacher_name,
        u.teacher_id
        FROM user u
        LEFT JOIN class c ON u.class_id = c.id
        LEFT JOIN thesis t ON u.id = t.student_id
        LEFT JOIN user s ON u.teacher_id = s.id
        WHERE u.role = 'student'";
    if ($user_role === "teacher") {
        $sql .= " AND u.teacher_id = $user_id";
    }
    $sql .= " ORDER BY u.last_name, u.first_name";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    break;

case "getMessages":
    // Zprávy – přijaté nebo odeslané
    $user_id = $_SESSION["user_id"];
    $box = $_GET["box"] ?? "inbox";
    if ($box === "sent") {
        $sql = "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) AS recipient_name
            FROM messages m JOIN user u ON m.recipient_id = u.id
            WHERE m.sender_id = ? ORDER BY m.sent_at DESC";
    } else {
        $sql = "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) AS sender_name
            FROM messages m JOIN user u ON m.sender_id = u.id
            WHERE m.recipient_id = ? ORDER BY m.sent_at DESC";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    break;

case "sendMessage":
    // Odeslání nové zprávy
    $sender_id = $_SESSION["user_id"];
    $recipient_id = $_POST["recipient_id"] ?? "";
    $subject = $_POST["subject"] ?? "";
    $body = $_POST["body"] ?? "";
    if (empty($recipient_id) || empty($subject) || empty($body)) {
        die(json_encode(["error" => "Všechna pole jsou povinná"]));
    }
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $sender_id, $recipient_id, $subject, $body);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message_id" => $conn->insert_id]);
    } else {
        die(json_encode(["error" => "Chyba při odesílání zprávy"]));
    }
    break;

case "markRead":
    // Označení zprávy jako přečtené
    $message_id = $_GET["id"] ?? "";
    $user_id = $_SESSION["user_id"];
    if (empty($message_id)) {
        die(json_encode(["error" => "ID zprávy je povinné"]));
    }
    $stmt = $conn->prepare("UPDATE messages SET read_at = NOW() WHERE id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $message_id, $user_id);
    $stmt->execute();
    echo json_encode(["success" => true]);
    break;

case "getUsers":
    // Seznam uživatelů (volitelný filtr podle role)
    $role = $_GET["role"] ?? "";
    $sql = "SELECT u.id, u.first_name, u.last_name, u.role, u.email, u.class_id, c.name AS class_name
        FROM user u LEFT JOIN class c ON u.class_id = c.id";
    if (!empty($role)) {
        $stmt = $conn->prepare("$sql WHERE u.role = ? ORDER BY u.last_name, u.first_name");
        $stmt->bind_param("s", $role);
    } else {
        $stmt = $conn->prepare("$sql ORDER BY u.role, u.last_name, u.first_name");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    break;

case "getClasses":
    // Seznam tříd s počtem studentů
    $result = $conn->query("SELECT c.*, COUNT(u.id) AS student_count
        FROM class c LEFT JOIN user u ON u.class_id = c.id AND u.role = 'student'
        GROUP BY c.id ORDER BY c.final_year DESC, c.name");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    break;

case "getStats":
    // Statistiky pro dashboard (počty uživatelů, tříd, prací, nepřečtené zprávy)
    $user_id = $_SESSION["user_id"];
    $stats = [];

    $result = $conn->query("SELECT COUNT(*) AS c FROM user WHERE role = 'student'");
    $stats["students"] = $result->fetch_assoc()["c"];

    $result = $conn->query("SELECT COUNT(*) AS c FROM user WHERE role = 'teacher'");
    $stats["teachers"] = $result->fetch_assoc()["c"];

    $result = $conn->query("SELECT COUNT(*) AS c FROM class");
    $stats["classes"] = $result->fetch_assoc()["c"];

    $result = $conn->query("SELECT COUNT(*) AS c FROM thesis");
    $stats["theses"] = $result->fetch_assoc()["c"];

    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM messages WHERE recipient_id = ? AND read_at IS NULL");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats["unread_messages"] = $stmt->get_result()->fetch_assoc()["c"];

    $result = $conn->query("SELECT status, COUNT(*) AS c FROM thesis GROUP BY status");
    $thesisStatus = [];
    while ($row = $result->fetch_assoc()) {
        $thesisStatus[$row["status"]] = $row["c"];
    }
    $stats["thesis_status"] = $thesisStatus;

    echo json_encode($stats);
    break;

case "createUser":
    // Vytvoření uživatele (pouze admin)
    $first_name = $_POST["first_name"] ?? "";
    $last_name = $_POST["last_name"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "student";
    $class_id = $_POST["class_id"] ?: null;
    $teacher_id = $_POST["teacher_id"] ?: null;

    if (empty($first_name) || empty($last_name) || empty($email)) {
        die(json_encode(["error" => "Jméno, příjmení a email jsou povinné"]));
    }

    $generated_password = "";
    if (empty($password)) {
        $generated_password = generatePassword();
        $hash = password_hash($generated_password, PASSWORD_DEFAULT);
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }

    $stmt = $conn->prepare("INSERT INTO user (first_name, last_name, email, password, role, class_id, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $first_name, $last_name, $email, $hash, $role, $class_id, $teacher_id);

    if ($stmt->execute()) {
        $response = ["success" => true, "user_id" => $conn->insert_id];
        if ($generated_password) {
            $response["generated_password"] = $generated_password;
        }
        echo json_encode($response);
    } else {
        if ($conn->errno === 1062) {
            die(json_encode(["error" => "Email již existuje"]));
        } else {
            die(json_encode(["error" => "Chyba při vytváření uživatele: " . $conn->error]));
        }
    }
    break;

case "createClass":
    // Vytvoření třídy
    $name = $_POST["name"] ?? "";
    $final_year = $_POST["final_year"] ?? "";
    if (empty($name) || empty($final_year)) {
        die(json_encode(["error" => "Název a ročník jsou povinné"]));
    }
    $stmt = $conn->prepare("INSERT INTO class (name, final_year) VALUES (?, ?)");
    $stmt->bind_param("si", $name, $final_year);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "class_id" => $conn->insert_id]);
    } else {
        die(json_encode(["error" => "Chyba při vytváření třídy"]));
    }
    break;

case "importStudents":
    // Hromadný import studentů z JSON pole
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input["students"]) || !is_array($input["students"])) {
        die(json_encode(["error" => "Neplatný JSON formát. Očekáváno { students: [...] }"]));
    }
    // Získání nebo vytvoření třídy podle názvu
    $class_name = trim($input["class_name"] ?? "");
    $class_year = $input["class_year"] ?? null;
    $default_class_id = null;
    if (!empty($class_name)) {
        $stmt = $conn->prepare("SELECT id FROM class WHERE name = ?");
        $stmt->bind_param("s", $class_name);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            $default_class_id = $existing["id"];
        } else {
            if (empty($class_year)) $class_year = date("Y");
            $stmt = $conn->prepare("INSERT INTO class (name, final_year) VALUES (?, ?)");
            $stmt->bind_param("si", $class_name, $class_year);
            if ($stmt->execute()) {
                $default_class_id = $conn->insert_id;
            }
        }
    }
    $imported = 0;
    $errors = [];
    foreach ($input["students"] as $i => $s) {
        $first_name = $s["first_name"] ?? $s["firstName"] ?? $s["name"] ?? "";
        $last_name = $s["last_name"] ?? $s["lastName"] ?? "";
        $email = $s["email"] ?? "";
        if (empty($first_name) || empty($last_name) || empty($email)) {
            if (isset($s["name"])) {
                $parts = explode(" ", trim($s["name"]), 2);
                $first_name = $parts[0] ?? "";
                $last_name = $parts[1] ?? "";
            }
        }
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $errors[] = "Row $i: missing required fields (first_name, last_name, email)";
            continue;
        }
        $class_id = $s["class_id"] ?? $default_class_id;
        $hash = password_hash("heslo123", PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT IGNORE INTO user (first_name, last_name, email, password, role, class_id) VALUES (?, ?, ?, ?, 'student', ?)");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $hash, $class_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $imported++;
        } else {
            $errors[] = "Row $i: $email already exists or invalid";
        }
    }
    echo json_encode(["success" => true, "imported" => $imported, "errors" => $errors]);
    break;

case "resetPassword":
    // Reset hesla (admin, vygeneruje nové)
    $user_id = $_POST["user_id"] ?? "";
    if (empty($user_id)) {
        die(json_encode(["error" => "ID uživatele je povinné"]));
    }
    $new_password = generatePassword();
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "new_password" => $new_password]);
    } else {
        die(json_encode(["error" => "Uživatel nenalezen"]));
    }
    break;

case "deleteUser":
    // Smazání uživatele
    $user_id = $_POST["user_id"] ?? "";
    if (empty($user_id)) {
        die(json_encode(["error" => "ID uživatele je povinné"]));
    }
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(["success" => true]);
    } else {
        die(json_encode(["error" => "Uživatel nenalezen"]));
    }
    break;

case "deleteClass":
    // Smazání třídy (odstraní vazbu studentům)
    $class_id = $_POST["class_id"] ?? "";
    if (empty($class_id)) {
        die(json_encode(["error" => "ID třídy je povinné"]));
    }
    $stmt = $conn->prepare("UPDATE user SET class_id = NULL WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM class WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(["success" => true]);
    } else {
        die(json_encode(["error" => "Třída nenalezena"]));
    }
    break;

case "getMe":
    // Info o přihlášeném uživateli
    echo json_encode([
        "user_id" => $_SESSION["user_id"],
        "role" => $_SESSION["user_role"],
        "name" => $_SESSION["user_name"],
    ]);
    break;

case "getThesis":
    // Získání práce přihlášeného studenta
    $user_id = $_SESSION["user_id"];
    $stmt = $conn->prepare("SELECT t.*, CONCAT(s.first_name, ' ', s.last_name) AS supervisor_name
        FROM thesis t LEFT JOIN user s ON t.supervisor_id = s.id
        WHERE t.student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $thesis = $result->fetch_assoc();
    echo json_encode($thesis ?: null);
    break;

case "getThesisByStudent":
    // Detail práce studenta (pro učitele/admina, kontroluje příslušnost)
    if (empty($_GET["student_id"])) {
        die(json_encode(["error" => "ID studenta je povinné"]));
    }
    $student_id = intval($_GET["student_id"]);
    if ($_SESSION["user_role"] !== "admin" && $_SESSION["user_role"] !== "teacher") {
        die(json_encode(["error" => "Přístup zamítnut"]));
    }
    if ($_SESSION["user_role"] === "teacher") {
        $check = $conn->prepare("SELECT id FROM user WHERE id = ? AND teacher_id = ?");
        $check->bind_param("ii", $student_id, $_SESSION["user_id"]);
        $check->execute();
        if (!$check->get_result()->fetch_assoc()) {
            die(json_encode(["error" => "Toto není váš student"]));
        }
    }
    $stmt = $conn->prepare("SELECT t.*, CONCAT(s.first_name, ' ', s.last_name) AS supervisor_name,
        CONCAT(u.first_name, ' ', u.last_name) AS student_name
        FROM thesis t LEFT JOIN user s ON t.supervisor_id = s.id
        JOIN user u ON t.student_id = u.id
        WHERE t.student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $thesis = $result->fetch_assoc();
    echo json_encode($thesis ?: null);
    break;

case "listThesisFiles":
    // Seznam nahraných souborů k práci
    $user_id = $_SESSION["user_id"];
    $student_id = $_GET["student_id"] ?? $user_id;
    if ($student_id != $user_id && !in_array($_SESSION["user_role"], ["teacher", "admin"])) {
        die(json_encode(["error" => "Přístup zamítnut"]));
    }
    $stmt = $conn->prepare("SELECT id FROM thesis WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $thesis = $stmt->get_result()->fetch_assoc();
    if (!$thesis) { echo json_encode([]); break; }
    $stmt = $conn->prepare("SELECT id, original_name, stored_name, uploaded_at FROM thesis_files WHERE thesis_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param("i", $thesis["id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $files = [];
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    echo json_encode($files);
    break;

case "uploadInstruction":
    // Nahrání zadání práce (učitel/admin, PDF)
    if (!in_array($_SESSION["user_role"], ["teacher", "admin"])) {
        echo json_encode(["success" => false, "error" => "Přístup zamítnut"]); break;
    }
    $thesis_id = $_POST["thesis_id"] ?? "";
    if (empty($thesis_id)) {
        echo json_encode(["success" => false, "error" => "ID práce je povinné"]); break;
    }
    if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "error" => "Nahrávání souboru selhalo"]); break;
    }
    $stmt = $conn->prepare("SELECT student_id FROM thesis WHERE id = ?");
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $t = $stmt->get_result()->fetch_assoc();
    if (!$t) { echo json_encode(["success" => false, "error" => "Práce nenalezena"]); break; }
    $student_dir = "student_" . $t["student_id"];
    $upload_dir = __DIR__ . "/uploads/" . $student_dir;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $ext = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
    $stored = "instruction_" . uniqid() . "." . $ext;
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $upload_dir . "/" . $stored)) {
        $filename = $student_dir . "/" . $stored;
        $stmt = $conn->prepare("UPDATE thesis SET instruction_pdf_path = ? WHERE id = ?");
        $stmt->bind_param("si", $filename, $thesis_id);
        $stmt->execute();
        echo json_encode(["success" => true, "path" => $filename]);
    } else {
        echo json_encode(["success" => false, "error" => "Chyba při ukládání souboru"]);
    }
    break;

case "uploadSubmission":
    // Nahrání souborů k práci (jen pokud není odesláno)
    $user_id = $_SESSION["user_id"];
    if ($_SESSION["user_role"] !== "student") {
        echo json_encode(["success" => false, "error" => "Přístup zamítnut"]); break;
    }
    $stmt = $conn->prepare("SELECT id, status FROM thesis WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $thesis = $stmt->get_result()->fetch_assoc();
    if (!$thesis) {
        echo json_encode(["success" => false, "error" => "Není přiřazena žádná práce"]); break;
    }
    if ($thesis["status"] === "submitted") {
        echo json_encode(["success" => false, "error" => "Práce je již odeslána, nejdříve vraťte odeslání"]); break;
    }
    $thesis_id = $thesis["id"];
    if (!isset($_FILES["files"])) {
        echo json_encode(["success" => false, "error" => "Nebyly nahrány žádné soubory"]); break;
    }
    $student_dir = "student_" . $user_id;
    $upload_dir = __DIR__ . "/uploads/" . $student_dir;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $uploaded = [];
    $fails = [];
    foreach ($_FILES["files"]["name"] as $i => $name) {
        if ($_FILES["files"]["error"][$i] !== UPLOAD_ERR_OK) {
            $fails[] = $name;
            continue;
        }
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
        $final = $safe;
        $dest = $upload_dir . "/" . $final;
        $counter = 1;
        while (file_exists($dest)) {
            $p = pathinfo($safe);
            $final = $p["filename"] . "_$counter." . ($p["extension"] ?? "");
            $dest = $upload_dir . "/" . $final;
            $counter++;
        }
        $stored = $student_dir . "/" . $final;
        if (move_uploaded_file($_FILES["files"]["tmp_name"][$i], $dest)) {
            $stmt = $conn->prepare("INSERT INTO thesis_files (thesis_id, original_name, stored_name) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $thesis_id, $name, $stored);
            $stmt->execute();
            $uploaded[] = $name;
        } else {
            $fails[] = $name;
        }
    }
    echo json_encode(["success" => true, "uploaded" => $uploaded, "failed" => $fails]);
    break;

case "deleteThesisFile":
    // Smazání odevzdaného souboru (jen pokud není odesláno)
    $user_id = $_SESSION["user_id"];
    $file_id = $_POST["file_id"] ?? "";
    if (empty($file_id)) {
        echo json_encode(["success" => false, "error" => "ID souboru je povinné"]); break;
    }
    $stmt = $conn->prepare("SELECT tf.id, tf.stored_name, t.status FROM thesis_files tf JOIN thesis t ON tf.thesis_id = t.id WHERE tf.id = ? AND t.student_id = ?");
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();
    if (!$file) {
        echo json_encode(["success" => false, "error" => "Soubor nenalezen"]); break;
    }
    if ($file["status"] === "submitted") {
        echo json_encode(["success" => false, "error" => "Práce je odeslána, nelze mazat soubory"]); break;
    }
    $file_path = __DIR__ . "/uploads/" . $file["stored_name"];
    if (file_exists($file_path)) unlink($file_path);
    $stmt = $conn->prepare("DELETE FROM thesis_files WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    echo json_encode(["success" => true]);
    break;

case "saveThesisNote":
    // Uložení poznámky studenta k práci
    $user_id = $_SESSION["user_id"];
    $note = $_POST["note"] ?? "";
    $stmt = $conn->prepare("UPDATE thesis SET student_note = ? WHERE student_id = ?");
    $stmt->bind_param("si", $note, $user_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        die(json_encode(["error" => "Chyba při ukládání poznámky"]));
    }
    break;

case "toggleSubmit":
    // Odeslání / vrácení odeslání práce studentem
    $user_id = $_SESSION["user_id"];
    if ($_SESSION["user_role"] !== "student") {
        echo json_encode(["success" => false, "error" => "Přístup zamítnut"]); break;
    }
    $stmt = $conn->prepare("SELECT id, status, grade FROM thesis WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $thesis = $stmt->get_result()->fetch_assoc();
    if (!$thesis) {
        echo json_encode(["success" => false, "error" => "Není přiřazena žádná práce"]); break;
    }
    $new_status = $thesis["status"] === "submitted" ? "in_progress" : "submitted";
    if ($new_status === "in_progress" && $thesis["grade"] !== null) {
        echo json_encode(["success" => false, "error" => "Práci již nelze vrátit, byla ohodnocena"]); break;
    }
    $stmt = $conn->prepare("UPDATE thesis SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $thesis["id"]);
    $stmt->execute();
    echo json_encode(["success" => true, "status" => $new_status]);
    break;

case "reviewThesis":
    // Ohodnocení práce známkou 1-5 učitelem
    if (!in_array($_SESSION["user_role"], ["teacher", "admin"])) {
        echo json_encode(["success" => false, "error" => "Přístup zamítnut"]); break;
    }
    $thesis_id = $_POST["thesis_id"] ?? "";
    $grade = $_POST["grade"] ?? "";
    $teacher_note = $_POST["teacher_note"] ?? "";
    if (empty($thesis_id) || !in_array($grade, [1, 2, 3, 4, 5])) {
        echo json_encode(["success" => false, "error" => "Neplatný požadavek"]); break;
    }
    $stmt = $conn->prepare("UPDATE thesis SET grade = ?, teacher_note = ? WHERE id = ?");
    $stmt->bind_param("isi", $grade, $teacher_note, $thesis_id);
    echo json_encode(["success" => $stmt->execute()]);
    break;

case "changePassword":
    // Změna hesla (z login stránky, není potřeba být přihlášen)
    $email = $_POST["email"] ?? "";
    $old_password = $_POST["old_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    if (empty($email) || empty($old_password) || empty($new_password)) {
        die(json_encode(["error" => "Vyplňte všechna pole"]));
    }
    if (strlen($new_password) < 6) {
        die(json_encode(["error" => "Nové heslo musí mít alespoň 6 znaků"]));
    }
    $stmt = $conn->prepare("SELECT id, password FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || !password_verify($old_password, $user["password"])) {
        die(json_encode(["error" => "Neplatný email nebo staré heslo"]));
    }
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $user["id"]);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        die(json_encode(["error" => "Chyba při změně hesla"]));
    }
    break;

case "downloadFile":
    // Stažení souboru (zadání nebo odevzdaný soubor)
    $thesis_id = $_GET["thesis_id"] ?? "";
    $type = $_GET["type"] ?? "";
    $file_id = $_GET["file_id"] ?? "";
    if ($type === "instruction") {
        $stmt = $conn->prepare("SELECT instruction_pdf_path FROM thesis WHERE id = ?");
        $stmt->bind_param("i", $thesis_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || empty($row["instruction_pdf_path"])) {
            die(json_encode(["error" => "Soubor nenalezen"]));
        }
        $fname = $row["instruction_pdf_path"];
    } elseif ($type === "submission" && !empty($file_id)) {
        $stmt = $conn->prepare("SELECT stored_name, original_name FROM thesis_files WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            die(json_encode(["error" => "Soubor nenalezen"]));
        }
        $fname = $row["stored_name"];
    } else {
        die(json_encode(["error" => "Neplatné parametry"]));
    }
    $file_path = __DIR__ . "/uploads/" . $fname;
    if (!file_exists($file_path) && !str_contains($fname, "/")) {
        $file_path = __DIR__ . "/uploads/" . basename($fname);
    }
    if (!file_exists($file_path)) {
        die(json_encode(["error" => "Soubor nenalezen na disku"]));
    }
    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
    if ($ext === "pdf") {
        header("Content-Type: application/pdf");
        header("Content-Disposition: inline");
    } else {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . htmlspecialchars($row["original_name"] ?? $fname) . "\"");
    }
    header("Content-Length: " . filesize($file_path));
    readfile($file_path);
    exit;

}
