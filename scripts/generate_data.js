// scripts/generate_data.js

// Grab references
const ctx = document.getElementById('myChart').getContext('2d');
const prevBtn = document.getElementById('prevDay');
const nextBtn = document.getElementById('nextDay');
const dateLabel = document.getElementById('currentDate');
const simOnlyEl = document.getElementById('totalSimOnly');
const postPayEl = document.getElementById('totalPostPay');
const handsetOnlyEl = document.getElementById('totalHandsetOnly');
const insuranceEl = document.getElementById('totalInsurance');

let chart = null;
let currentDate = new Date();

// 1) Format a Date as "DD/MM/YYYY"
function fmtDisplay(d) {
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
}

// 2) Format a Date as "YYYY-MM-DD" for the query parameter
function fmtQuery(d) {
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return `${yyyy}-${mm}-${dd}`;
}

// 3) Show the current date in DD/MM/YYYY
function updateDateDisplay() {
    dateLabel.textContent = fmtDisplay(currentDate);
    document.getElementById('timelineLink').href =
        `timeline.php?date=${fmtQuery(currentDate)}`;
}

// 4) Load summary widgets for the selected date
function loadSummary() {
    fetch(`get_summary.php?date=${fmtQuery(currentDate)}`)
        .then(r => r.json())
        .then(data => {
            simOnlyEl.textContent = data['Sim-Only'];
            postPayEl.textContent = data['Post-Pay'];
            handsetOnlyEl.textContent = data['Handset-Only'];
            insuranceEl.textContent = data['Insurance'];
        })
        .catch(err => console.error('Summary load error:', err));
}

// 5) Load and render the Chart.js timeline
function loadChart() {
    fetch(`get_sales_data.php?date=${fmtQuery(currentDate)}`)
        .then(r => r.json())
        .then(cfg => {
            const palette = ['purple', 'green', 'orange', 'blue', 'red', 'teal', 'magenta', 'brown'];
            cfg.data.datasets.forEach((ds, i) => {
                ds.borderColor = ds.borderColor || palette[i % palette.length];
                ds.backgroundColor = ds.backgroundColor || ds.borderColor.replace(
                    /(rgba\([^,]+,[^,]+,[^,]+,)([^)]+)\)/, '$10.1)'
                );
                ds.fill = false;
                ds.tension = 0;
                ds.pointRadius = 0;
            });
            if (chart) chart.destroy();
            chart = new Chart(ctx, cfg);
        })
        .catch(err => console.error('Chart load error:', err));
}

// Load leaderboard
function loadLeaderboard() {
    fetch(`get_leaderboard.php?date=${fmtQuery(currentDate)}`)
        .then(r => r.json())
        .then(list => {
            const ol = document.getElementById('leaderboardList');
            ol.innerHTML = '';
            if (!list.length) {
                ol.innerHTML = '<li>No sales logged today.</li>';
                return;
            }
            list.forEach(entry => {
                const li = document.createElement('li');
                li.textContent = `${entry.name} - ${entry.cnt}`;
                ol.appendChild(li);
            });
        })
        .catch(e => console.error('Leaderboard load error:', e));
}

// 6) Shift the date by n days and reload everything
function shiftDay(n) {
    currentDate.setDate(currentDate.getDate() + n);
    updateDateDisplay();
    loadSummary();
    loadChart();
    loadLeaderboard();
}

// 7) Wire up Prev/Next buttons
prevBtn.addEventListener('click', () => shiftDay(-1));
nextBtn.addEventListener('click', () => shiftDay(1));

// 8) Initial load when page opens
updateDateDisplay();
loadSummary();
loadChart();
loadLeaderboard();
