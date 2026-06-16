let currentTab = "inbox";

// Přepnutí záložky (doručené / odeslané)
function switchTab(tab, btn) {
    currentTab = tab;
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
    loadMessages();
}

// Načtení seznamu zpráv podle aktuální záložky
function loadMessages() {
    const list = document.getElementById("messages-list");
    list.innerHTML = '<p class="empty-msg">Načítání...</p>';
    document.getElementById("message-preview").innerHTML = '<p class="empty-msg">Vyberte zprávu</p>';

    fetch("api.php?action=getMessages&box=" + currentTab)
        .then(r => r.json())
        .then(messages => {
            list.innerHTML = "";
            if (messages.length === 0) {
                list.innerHTML = '<p class="empty-msg">Žádné zprávy</p>';
                return;
            }
            messages.forEach(m => {
                const div = document.createElement("div");
                div.className = "message-item" + (m.read_at ? "" : " unread");
                div.dataset.id = m.id;
                const otherName = currentTab === "inbox" ? m.sender_name : m.recipient_name;
                div.innerHTML = `
                    <div class="msg-header">
                        <strong>${otherName}</strong>
                        <span class="msg-date">${m.sent_at}</span>
                    </div>
                    <div class="msg-subject">${m.subject}</div>
                `;
                div.onclick = () => openMessage(m.id, m.subject, m.body, otherName, m.sent_at);
                list.appendChild(div);
            });
        })
        .catch(() => {
            list.innerHTML = '<p class="empty-msg">Chyba při načítání</p>';
        });
}

// Zobrazení detailu zprávy a označení jako přečtené
function openMessage(id, subject, body, from, date) {
    const preview = document.getElementById("message-preview");
    preview.innerHTML = `
        <div class="msg-full">
            <div class="msg-full-header">
                <p><strong>Od:</strong> ${from}</p>
                <p><strong>Datum:</strong> ${date}</p>
                <h3>${subject}</h3>
            </div>
            <div class="msg-full-body">${body.replace(/\n/g, "<br>")}</div>
        </div>
    `;
    fetch("api.php?action=markRead&id=" + id).catch(() => {});
    document.querySelector(`.message-item[data-id="${id}"]`)?.classList.remove("unread");
}

// Zobrazení modálu pro novou zprávu
function showCompose() {
    document.getElementById("compose-modal").style.display = "flex";
    document.getElementById("compose-error").style.display = "none";
    const sel = document.getElementById("compose-recipient");
    if (sel.options.length === 0) {
        fetch("api.php?action=getUsers")
            .then(r => r.json())
            .then(users => {
                sel.innerHTML = "";
                users.forEach(u => {
                    if (u.id == currentUserId) return;
                    const opt = document.createElement("option");
                    opt.value = u.id;
                    opt.textContent = u.first_name + " " + u.last_name + " (" + u.role + ")";
                    sel.appendChild(opt);
                });
            });
    }
}

function hideCompose() {
    document.getElementById("compose-modal").style.display = "none";
    document.getElementById("compose-form").reset();
}

// Odeslání zprávy – formulář pošle na API
function sendMessage(e) {
    e.preventDefault();
    const form = document.getElementById("compose-form");
    const data = new URLSearchParams(new FormData(form));
    fetch("api.php?action=sendMessage", { method: "POST", body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                hideCompose();
                loadMessages();
            } else {
                const err = document.getElementById("compose-error");
                err.textContent = res.error || "Chyba při odesílání";
                err.style.display = "block";
            }
        })
        .catch(() => {
            const err = document.getElementById("compose-error");
            err.textContent = "Chyba při odesílání";
            err.style.display = "block";
        });
}

loadMessages();
