// Teacher dashboard JavaScript for dynamic data
document.addEventListener('DOMContentLoaded', () => {
    const attendanceTable = document.getElementById('attendance-overview');

    // Fetch attendance data (dummy fetch for now)
    fetch('get_attendance.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(record => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${record.date}</td>
                    <td>${record.class}</td>
                    <td>${record.percentage}%</td>
                `;
                attendanceTable.appendChild(row);
            });
        })
        .catch(error => console.error('Error fetching attendance:', error));
});
