let resetUserId = null;
let deleteTarget = null;
let deleteType = null;

// Načtení a filtrování seznamu uživatelů
function loadUsers() {
    const tbody = document.getElementById("users-table-body");
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:15px;">Načítání...</td></tr>';
    const nameFilter = document.getElementById("au-name").value.toLowerCase().trim();
    const emailFilter = document.getElementById("au-email").value.toLowerCase().trim();
    const roleFilter = document.getElementById("au-role").value;
    const classFilter = document.getElementById("au-class").value;
    fetch("api.php?action=getUsers")
        .then(r => r.json())
        .then(users => {
            const filtered = users.filter(u => {
                return (!nameFilter || (u.first_name + " " + u.last_name).toLowerCase().includes(nameFilter))
                    && (!emailFilter || (u.email || "").toLowerCase().includes(emailFilter))
                    && (!roleFilter || u.role === roleFilter)
                    && (!classFilter || String(u.class_id) === classFilter);
            });
            tbody.innerHTML = "";
            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:15px; color:var(--muted);">Žádní uživatelé</td></tr>';
                return;
            }
            filtered.forEach(u => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${u.first_name} ${u.last_name}</td>
                    <td>${u.email}</td>
                    <td>${u.role}</td>
                    <td>${u.class_name || "-"}</td>
                    <td style="white-space:nowrap;">
                        <button class="search_button" style="padding:4px 8px;font-size:12px;" onclick="showResetPassword(${u.id}, '${u.first_name} ${u.last_name}')">Reset</button>
                        <button class="search_button" style="padding:4px 8px;font-size:12px;background:var(--redwrong);" onclick="showDeleteUser(${u.id}, '${u.first_name} ${u.last_name}')">Smazat</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:15px; color:var(--redwrong);">Chyba</td></tr>';
        });
}

// Načtení seznamu tříd a naplnění selectů
function loadClasses() {
    const tbody = document.getElementById("classes-table-body");
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:15px;">Načítání...</td></tr>';
    }
    fetch("api.php?action=getClasses")
        .then(r => r.json())
        .then(classes => {
            if (tbody) {
                tbody.innerHTML = "";
                if (classes.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:15px; color:var(--muted);">Žádné třídy</td></tr>';
                } else {
                    classes.forEach(c => {
                        const tr = document.createElement("tr");
                        tr.innerHTML = `
                            <td>${c.name}</td>
                            <td>${c.final_year}</td>
                            <td>${c.student_count}</td>
                            <td><button class="search_button" style="padding:4px 8px;font-size:12px;background:var(--redwrong);" onclick="showDeleteClass(${c.id}, '${c.name}')">Smazat</button></td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }
            document.querySelectorAll("#cu-class, #import-class, #au-class").forEach(sel => {
                const val = sel.value;
                sel.innerHTML = '<option value="">-- Vyberte --</option>';
                classes.forEach(c => {
                    const opt = document.createElement("option");
                    opt.value = c.id;
                    opt.textContent = c.name;
                    sel.appendChild(opt);
                });
                if (val) sel.value = val;
            });
        })
        .catch(() => {
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:15px; color:var(--redwrong);">Chyba</td></tr>';
            }
        });
}

// Načtení seznamu učitelů do selectu
function loadTeachers() {
    const sel = document.getElementById("cu-teacher");
    if (!sel) return;
    fetch("api.php?action=getUsers&role=teacher")
        .then(r => r.json())
        .then(teachers => {
            sel.innerHTML = '<option value="">-- Žádný --</option>';
            teachers.forEach(t => {
                const opt = document.createElement("option");
                opt.value = t.id;
                opt.textContent = t.first_name + " " + t.last_name;
                sel.appendChild(opt);
            });
        });
}

// Skryje/zobrazí pole třída a učitel podle vybrané role
function toggleUserFields() {
    const role = document.getElementById("cu-role").value;
    ["cu-class", "cu-teacher"].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const lbl = el.closest("label");
        if (lbl) lbl.style.display = role === "student" ? "block" : "none";
        el.style.display = role === "student" ? "block" : "none";
    });
}

// Zobrazení modálu pro vytvoření uživatele
function showCreateUser() {
    document.getElementById("create-user-modal").style.display = "flex";
    document.getElementById("cu-error").style.display = "none";
    document.getElementById("cu-success").style.display = "none";
    loadClasses();
    loadTeachers();
    toggleUserFields();
    const pw = document.getElementById("cu-password");
    if (pw) pw.value = "";
}

function hideCreateUser() {
    document.getElementById("create-user-modal").style.display = "none";
    const form = document.getElementById("create-user-form");
    if (form) form.reset();
}

// Odeslání formuláře – vytvoření uživatele
function createUser(e) {
    e.preventDefault();
    const data = new URLSearchParams();
    ["first_name","last_name","email","role","password"].forEach(f => {
        const v = document.getElementById("cu-" + f);
        if (v && v.value) data.append(f, v.value);
    });
    data.append("class_id", document.getElementById("cu-class")?.value || "");
    data.append("teacher_id", document.getElementById("cu-teacher")?.value || "");
    fetch("api.php?action=createUser", { method: "POST", body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const success = document.getElementById("cu-success");
                if (!success) return;
                let msg = "Uživatel vytvořen!";
                if (res.generated_password) {
                    msg += "<br>Vygenerované heslo: <strong>" + res.generated_password + "</strong><br><small>Uložte si ho, po zavření se už nezobrazí.</small>";
                }
                success.className = "import-result success";
                success.innerHTML = msg;
                success.style.display = "block";
                const err = document.getElementById("cu-error");
                if (err) err.style.display = "none";
                const form = document.getElementById("create-user-form");
                if (form) form.reset();
                loadUsers();
            } else {
                const err = document.getElementById("cu-error");
                if (err) {
                    err.textContent = res.error || "Chyba";
                    err.style.display = "block";
                }
            }
        })
        .catch(() => {
            const err = document.getElementById("cu-error");
            if (err) { err.textContent = "Chyba při vytváření"; err.style.display = "block"; }
        });
}

// Modál pro reset hesla
function showResetPassword(userId, userName) {
    resetUserId = userId;
    document.getElementById("reset-password-modal").style.display = "flex";
    document.getElementById("reset-password-info").textContent = "Resetovat heslo pro: " + userName;
    document.getElementById("reset-password-result").style.display = "none";
    document.getElementById("reset-password-btn").style.display = "inline-block";
}

function hideResetPassword() {
    document.getElementById("reset-password-modal").style.display = "none";
    resetUserId = null;
}

// Potvrzení resetu – zavolá API a zobrazí nové heslo
function confirmResetPassword() {
    const data = new URLSearchParams();
    data.append("user_id", resetUserId);
    fetch("api.php?action=resetPassword", { method: "POST", body: data })
        .then(r => r.json())
        .then(res => {
            const result = document.getElementById("reset-password-result");
            if (!result) return;
            if (res.success) {
                result.className = "import-result success";
                result.innerHTML = "Nové heslo: <strong>" + res.new_password + "</strong><br><small>Uložte si ho, po zavření se už nezobrazí.</small>";
                document.getElementById("reset-password-btn").style.display = "none";
            } else {
                result.className = "import-result error";
                result.textContent = res.error || "Chyba";
            }
            result.style.display = "block";
        })
        .catch(() => {
            const result = document.getElementById("reset-password-result");
            if (result) { result.className = "import-result error"; result.textContent = "Chyba"; result.style.display = "block"; }
        });
}

// Modál pro smazání uživatele / třídy
function showDeleteUser(userId, userName) {
    deleteTarget = userId;
    deleteType = "user";
    document.getElementById("delete-modal-title").textContent = "Smazat uživatele";
    document.getElementById("delete-modal-info").innerHTML = "Opravdu chcete smazat uživatele <strong>" + userName + "</strong>?<br>Tato akce je nevratná.";
    document.getElementById("delete-modal-result").style.display = "none";
    document.getElementById("delete-modal-btn").style.display = "inline-block";
    document.getElementById("delete-modal").style.display = "flex";
}

function showDeleteClass(classId, className) {
    deleteTarget = classId;
    deleteType = "class";
    document.getElementById("delete-modal-title").textContent = "Smazat třídu";
    document.getElementById("delete-modal-info").innerHTML = "Opravdu chcete smazat třídu <strong>" + className + "</strong>?<br>Studentům ve třídě bude odebrána třída, ale uživatelé zůstanou.";
    document.getElementById("delete-modal-result").style.display = "none";
    document.getElementById("delete-modal-btn").style.display = "inline-block";
    document.getElementById("delete-modal").style.display = "flex";
}

function hideDelete() {
    document.getElementById("delete-modal").style.display = "none";
    deleteTarget = null;
    deleteType = null;
}

// Potvrzení smazání – zavolá API a obnoví seznam
function confirmDelete() {
    const data = new URLSearchParams();
    const action = deleteType === "user" ? "deleteUser" : "deleteClass";
    data.append(deleteType === "user" ? "user_id" : "class_id", deleteTarget);
    fetch("api.php?action=" + action, { method: "POST", body: data })
        .then(r => r.json())
        .then(res => {
            const result = document.getElementById("delete-modal-result");
            if (!result) return;
            if (res.success) {
                result.className = "import-result success";
                result.textContent = "Smazáno!";
                document.getElementById("delete-modal-btn").style.display = "none";
                setTimeout(() => { hideDelete(); loadUsers(); loadClasses(); }, 1000);
            } else {
                result.className = "import-result error";
                result.textContent = res.error || "Chyba";
            }
            result.style.display = "block";
        })
        .catch(() => {
            const result = document.getElementById("delete-modal-result");
            if (result) { result.className = "import-result error"; result.textContent = "Chyba"; result.style.display = "block"; }
        });
}

// Modál pro vytvoření třídy
function showCreateClass() {
    document.getElementById("create-class-modal").style.display = "flex";
    document.getElementById("cc-error").style.display = "none";
    document.getElementById("cc-success").style.display = "none";
}

function hideCreateClass() {
    document.getElementById("create-class-modal").style.display = "none";
    const form = document.getElementById("create-class-form");
    if (form) form.reset();
}

// Odeslání formuláře – vytvoření třídy
function createClass(e) {
    e.preventDefault();
    const data = new URLSearchParams();
    data.append("name", document.getElementById("cc-name")?.value || "");
    data.append("final_year", document.getElementById("cc-year")?.value || "");
    fetch("api.php?action=createClass", { method: "POST", body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const success = document.getElementById("cc-success");
                if (!success) return;
                success.textContent = "Třída vytvořena!";
                success.className = "import-result success";
                success.style.display = "block";
                const err = document.getElementById("cc-error");
                if (err) err.style.display = "none";
                const form = document.getElementById("create-class-form");
                if (form) form.reset();
                loadClasses();
            } else {
                const err = document.getElementById("cc-error");
                if (err) {
                    err.textContent = res.error || "Chyba";
                    err.style.display = "block";
                }
            }
        })
        .catch(() => {
            const err = document.getElementById("cc-error");
            if (err) { err.textContent = "Chyba při vytváření třídy"; err.style.display = "block"; }
        });
}

// Import studentů z JSON – parsování a odeslání na API
function importStudents(e) {
    e.preventDefault();
    const result = document.getElementById("import-result");
    if (!result) return;
    result.textContent = "Importuji...";
    result.className = "import-result";

    let students;
    try {
        const raw = JSON.parse(document.getElementById("import-json")?.value || "{}");
        students = Array.isArray(raw) ? raw : raw.students || raw;
    } catch {
        result.textContent = "Neplatný JSON";
        result.className = "import-result error";
        return;
    }

    const class_id = document.getElementById("import-class")?.value || "";

    fetch("api.php?action=importStudents", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ students, class_id: class_id || null })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            let msg = "Importováno: " + res.imported + " studentů";
            if (res.errors && res.errors.length > 0) {
                msg += "<br>Chyby: " + res.errors.join("<br>");
                result.className = "import-result warning";
            } else {
                result.className = "import-result success";
            }
            result.innerHTML = msg;
        } else {
            result.textContent = res.error || "Chyba";
            result.className = "import-result error";
        }
    })
    .catch(() => {
        result.textContent = "Chyba při importu";
        result.className = "import-result error";
    });
}
