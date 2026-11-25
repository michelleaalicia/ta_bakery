import Chart from 'chart.js/auto';

// Data dan rumus forecast tetap dari DashboardController / BrownDesService.
// File ini HANYA mengubah cara visualisasinya.
function renderDashboardForecast() {
    const canvas = document.getElementById('dashboardForecastChart');
    const jsonElement = document.getElementById('dashboardForecastData');
    if (!canvas || !jsonElement) return;

    let forecast;
    try {
        forecast = JSON.parse(jsonElement.textContent);
    } catch (error) {
        console.error('Data grafik DES tidak dapat dibaca.', error);
        return;
    }

    const labels = forecast.labels || [];
    const actual = forecast.actual || [];
    const prediction = forecast.prediction || [];
    if (labels.length === 0) return;

    // Hilangkan instance chart lama jika Vite hot reload menginisialisasi ulang.
    const previous = Chart.getChart(canvas);
    if (previous) previous.destroy();

    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const fade = ctx.createLinearGradient(0, 0, 0, 365);
    fade.addColorStop(0, 'rgba(121, 67, 48, 0.18)');
    fade.addColorStop(1, 'rgba(121, 67, 48, 0.005)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Penjualan Aktual',
                    data: actual,
                    borderColor: '#794330',
                    backgroundColor: fade,
                    fill: 'origin',
                    borderWidth: 2.5,
                    pointRadius: 2,
                    pointHoverRadius: 6,
                    pointHitRadius: 14,
                    pointBackgroundColor: '#794330',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    tension: 0.28,
                    spanGaps: false,
                },
                {
                    label: 'Prediksi DES',
                    data: prediction,
                    borderColor: '#E09B32',
                    backgroundColor: '#E09B32',
                    borderDash: [7, 5],
                    borderWidth: 3,
                    pointRadius: (context) => context.dataIndex >= labels.length - 2 ? 5 : 0,
                    pointHoverRadius: 7,
                    pointHitRadius: 14,
                    pointBackgroundColor: '#E09B32',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    tension: 0.25,
                    spanGaps: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550 },
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 8, right: 8 } },
            plugins: {
                legend: { display: false }, // Legenda HTML bawaan dashboard dipertahankan
                tooltip: {
                    enabled: true,
                    backgroundColor: '#332820',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    padding: 12,
                    displayColors: true,
                    cornerRadius: 8,
                    callbacks: {
                        title: (items) => items.length ? `Tanggal ${items[0].label}` : '',
                        label: (item) => {
                            const value = item.parsed.y;
                            if (value === null || !Number.isFinite(value)) return '';
                            const display = Number.isInteger(value) ? value.toString() : value.toLocaleString('id-ID', { maximumFractionDigits: 2 });
                            return `${item.dataset.label}: ${display} pcs`;
                        },
                    },
                    // Titik penghubung dari aktual terakhir tidak dianggap hasil prediksi.
                    filter: (item) => !(item.datasetIndex === 1 && item.dataIndex === labels.length - 3),
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#776C64',
                        maxTicksLimit: 9,
                        maxRotation: 0,
                        autoSkip: true,
                        font: { size: 11 },
                    },
                    border: { color: '#e8e0d8' },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#eee8e1' },
                    border: { display: false },
                    ticks: {
                        precision: 0,
                        color: '#776C64',
                        font: { size: 11 },
                        callback: (value) => `${value} pcs`,
                    },
                },
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderDashboardForecast, { once: true });
} else {
    renderDashboardForecast();
}
