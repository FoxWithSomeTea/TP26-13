// Načtení tříd a učitelů do filtrů
function loadFilters() {
    fetch("api.php?action=getClasses")
        .then(r => r.json())
        .then(classes => {
            const sel = document.getElementById("input_class");
            classes.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.name;
                opt.textContent = c.name;
                sel.appendChild(opt);
            });
        })
        .catch(() => {});

    fetch("api.php?action=getUsers&role=teacher")
        .then(r => r.json())
        .then(teachers => {
            const sel = document.getElementById("input_teacher");
            teachers.forEach(t => {
                const opt = document.createElement("option");
                opt.value = t.id;
                opt.textContent = t.first_name + " " + t.last_name;
                sel.appendChild(opt);
            });
        })
        .catch(() => {});
}

// Filtrování a vykreslení seznamu studentů
function searchStudents() {
    const tableBody = document.getElementById("students_table_body");
    tableBody.innerHTML = '<tr><td colspan="6" class="empty-msg">Načítání...</td></tr>';

    fetch("api.php?action=getStudents")
        .then(response => response.json())
        .then(data => {
            const nameFilter = document.getElementById("input_name").value.toLowerCase().trim();
            const classFilter = document.getElementById("input_class").value;
            const statusFilter = document.getElementById("input_status").value;
            const teacherFilter = document.getElementById("input_teacher").value;

            const filtered = data.filter(student => {
                const matchName = !nameFilter || student.name.toLowerCase().includes(nameFilter);
                const matchClass = classFilter === "All classes" || student.class === classFilter;
                const matchStatus = statusFilter === "All statuses" || student.thesis_status === statusFilter;
                const matchTeacher = teacherFilter === "All teachers" || String(student.teacher_id) === teacherFilter;
                return matchName && matchClass && matchStatus && matchTeacher;
            });

            tableBody.innerHTML = "";

            if (filtered.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="empty-msg">Žádní studenti nenalezeni</td></tr>';
                return;
            }

            const statusLabels = {
                "in_progress": "řeší se",
                "submitted": "odevzdáno"
            };

            filtered.forEach(student => {
                const tr = document.createElement("tr");
                const statusText = statusLabels[student.thesis_status] || student.thesis_status || "";

                let titleHtml = '<a target="students-frame" href="student-detail.html?student_id=' + student.id + '">' + (student.thesis_title || "") + '</a>';

                let filesHtml = student.file_count > 0 ? student.file_count + " souborů" : "";

                tr.innerHTML = `
                    <td>
                        <div class="student_card_name">
                            <a target="students-frame" href="student-detail.html?student_id=${student.id}" style="text-decoration:none; color:var(--text);">
                                <p>${student.name}</p>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div class="student_card_class">${student.class || ""}</div>
                    </td>
                    <td>${titleHtml}</td>
                    <td>${filesHtml}</td>
                    <td><span class="status-chip status-${student.thesis_status || 'none'}">${statusText}</span></td>
                    <td>${student.teacher_name || ""}</td>
                `;
                tableBody.appendChild(tr);
            });
        })
        .catch(() => {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--redwrong);">Chyba při načítání</td></tr>';
        });
}

loadFilters();
searchStudents();
