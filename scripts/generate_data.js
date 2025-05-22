// scripts/generate_data.js
const ctx = document.getElementById('myChart').getContext('2d');

fetch('get_sales_data.php')
    .then(res => {
        if (!res.ok) throw new Error('Network response was not OK');
        return res.json();
    })
    .then(cfg => {
        // optional: pick a palette so each staff has a distinct color
        const palette = [
            'purple', 'green', 'orange', 'blue', 'red', 'teal', 'magenta', 'brown'
        ];

        cfg.data.datasets.forEach((ds, i) => {
            // assign a color if not already set in the JSON
            ds.borderColor = ds.borderColor || palette[i % palette.length];
            ds.backgroundColor = ds.backgroundColor || ds.borderColor.replace(/(rgba\([^,]+,[^,]+,[^,]+,)([^)]+)\)/, '$10.1)');
            ds.fill = false;
            ds.tension = 0;
            ds.pointRadius = 0;
            ds.pointHoverRadius = 0;
        });

        // actually build the chart
        new Chart(ctx, cfg);
    })
    .catch(err => {
        console.error('Failed to load chart data:', err);
        const container = document.getElementById('myChart').parentNode;
        const msg = document.createElement('div');
        msg.textContent = 'Could not load chart data.';
        msg.style.color = 'red';
        container.appendChild(msg);
    });
