// Called when the user clicks the Search button on the Students page
function searchStudents() {
    // Fetch student data from the local test file
    // TODO: replace "test.json" with "getStudents.php" when the backend is ready
    fetch("test.json")
        .then(response => response.json())
        .then(data => {
            // Read filter values from the form inputs
            const nameFilter = document.getElementById("input_name").value.toLowerCase().trim();
            const classFilter = document.getElementById("input_class").value;
            const statusFilter = document.getElementById("input_status").value;

            // Filter the student list based on the selected criteria
            // If a filter is set to "All" or left empty, it doesn't filter
            const filtered = data.students.filter(student => {
                const matchName = !nameFilter || student.name.toLowerCase().includes(nameFilter);
                const matchClass = classFilter === "All classes" || student.class === classFilter;
                const matchStatus = statusFilter === "All statuses" || student.status === statusFilter;
                return matchName && matchClass && matchStatus;
            });

            // Clear the table body before inserting new rows
            const tableBody = document.getElementById("students_table_body");
            tableBody.innerHTML = "";

            // Create a table row for each filtered student
            filtered.forEach(student => {
                const tr = document.createElement("tr");

                // Fill the row cells with the student's data
                tr.innerHTML = `
                    <td>
                        <div class="student_card_name">
                            <p>${student.name}</p>
                        </div>
                    </td>
                    <td>
                        <div class="student_card_class">
                            ${student.class}
                        </div>
                    </td>
                    <td>${student.thesis_title || ""}</td>
                    <td>${student.file || ""}</td>
                    <td>${student.status || ""}</td>
                `;

                // Add the row to the table body
                tableBody.appendChild(tr);
            });
        })
        .catch(error => {
            // Log any errors (e.g. network failure, JSON parse error)
            console.error("Error loading students:", error);
        });
}
