// Student dashboard JavaScript for dynamic data
document.addEventListener('DOMContentLoaded', () => {
    const attendanceTable = document.getElementById('student-attendance');

    // Fetch attendance data (dummy fetch for now)
    fetch('get_student_attendance.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(record => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${record.date}</td>
                    <td>${record.status}</td>
                `;
                attendanceTable.appendChild(row);
            });
        })
        .catch(error => console.error('Error fetching student attendance:', error));
});
