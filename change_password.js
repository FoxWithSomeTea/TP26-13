function switchToChangePassword() {
    document.getElementById("login-form").style.display = "none";
    document.getElementById("change-password-form-wrap").style.display = "block";
    document.getElementById("cp-error").style.display = "none";
    document.getElementById("cp-success").style.display = "none";
    document.getElementById("change-password-form").reset();
}

function switchToLogin() {
    document.getElementById("change-password-form-wrap").style.display = "none";
    document.getElementById("login-form").style.display = "block";
}

function changePassword(e) {
    e.preventDefault();
    const email = document.getElementById("cp-email").value;
    const oldPw = document.getElementById("cp-old").value;
    const newPw = document.getElementById("cp-new").value;
    const confirmPw = document.getElementById("cp-confirm").value;

    if (newPw !== confirmPw) {
        const err = document.getElementById("cp-error");
        err.textContent = "Nové heslo a potvrzení se neshodují";
        err.style.display = "block";
        return;
    }

    const data = new URLSearchParams();
    data.append("email", email);
    data.append("old_password", oldPw);
    data.append("new_password", newPw);

    fetch("api.php?action=changePassword", { method: "POST", body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const success = document.getElementById("cp-success");
                success.textContent = "Heslo bylo změněno!";
                success.className = "import-result success";
                success.style.display = "block";
                document.getElementById("cp-error").style.display = "none";
                document.getElementById("change-password-form").reset();
            } else {
                const err = document.getElementById("cp-error");
                err.textContent = res.error || "Chyba";
                err.style.display = "block";
            }
        })
        .catch(() => {
            const err = document.getElementById("cp-error");
            err.textContent = "Chyba při změně hesla";
            err.style.display = "block";
        });
}
