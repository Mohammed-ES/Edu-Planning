let currentDate = new Date();

        // Initialize calendar on page load
        document.addEventListener('DOMContentLoaded', function() {
            renderCalendar();
        });

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Update month/year display
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('monthYear').textContent = `${monthNames[month]} ${year}`;
            
            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            
            // Clear grid
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';
            
            // Add days from previous month
            for (let i = firstDay - 1; i >= 0; i--) {
                const cell = document.createElement('div');
                cell.className = 'calendar-cell other-month';
                cell.textContent = daysInPrevMonth - i;
                grid.appendChild(cell);
            }
            
            // Add days of current month
            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const cell = document.createElement('div');
                cell.className = 'calendar-cell';
                cell.textContent = day;
                
                // Highlight today
                if (year === today.getFullYear() && 
                    month === today.getMonth() && 
                    day === today.getDate()) {
                    cell.classList.add('today');
                }
                
                grid.appendChild(cell);
            }
            
            // Add days from next month
            const totalCells = grid.children.length;
            const remainingCells = 42 - totalCells; // 6 rows × 7 days
            for (let day = 1; day <= remainingCells; day++) {
                const cell = document.createElement('div');
                cell.className = 'calendar-cell other-month';
                cell.textContent = day;
                grid.appendChild(cell);
            }
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }

        function showToast(msg, type) {
            const alertClass = type === 'error' ? 'danger' : type;
            const alertHtml = `<div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            const container = document.querySelector('.content-padding');
            if (container) {
                container.insertAdjacentHTML('afterbegin', alertHtml);
                setTimeout(() => {
                    const alert = container.querySelector('.alert');
                    if (alert) alert.remove();
                }, 5000);
            }
        }