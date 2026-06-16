<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$current_user_id = $_SESSION["user_id"];
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
</head>
<body class="messages-page">
    <header class="titlebar">
        <h2>Zprávy</h2>
        <button class="search_button" onclick="showCompose()">+ Nová zpráva</button>
    </header>

    <nav class="admin-tabs" style="margin-bottom:15px;">
        <button class="tab-btn active" onclick="switchTab('inbox', this)" id="inbox-tab">Doručené</button>
        <button class="tab-btn" onclick="switchTab('sent', this)" id="sent-tab">Odeslané</button>
    </nav>

    <div class="messages-layout">
        <div class="messages-list" id="messages-list">
            <p class="empty-msg">Načítání...</p>
        </div>

        <div class="message-preview" id="message-preview">
            <p class="empty-msg">Vyberte zprávu</p>
        </div>
    </div>

    <div class="modal-overlay" id="compose-modal" style="display:none">
        <div class="modal">
            <h3>Nová zpráva</h3>
            <form id="compose-form" onsubmit="sendMessage(event)">
                <label for="compose-recipient">Příjemce</label>
                <select class="filter" id="compose-recipient" name="recipient_id" required></select>

                <label for="compose-subject">Předmět</label>
                <input class="filter" type="text" id="compose-subject" name="subject" required placeholder="Předmět">

                <label for="compose-body">Zpráva</label>
                <textarea class="filter" id="compose-body" name="body" required rows="6" placeholder="Text zprávy..."></textarea>

                <div class="modal-actions">
                    <button type="button" class="search_button cancel-btn" onclick="hideCompose()">Zrušit</button>
                    <button type="submit" class="search_button send-btn">Odeslat</button>
                </div>
            </form>
            <p id="compose-error" class="login-error" style="display:none"></p>
        </div>
    </div>

    <script>const currentUserId = <?= $current_user_id ?>;</script>
    <script src="messages.js"></script>
</body>
</html>
