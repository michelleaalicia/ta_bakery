import Chart from 'chart.js/auto';

function initializeForecastChart() {
    const canvas = document.getElementById('forecastChart');
    const payload = document.getElementById('forecastChartPayload');
    if (!canvas || !payload) return;

    let parsed;
    try { parsed = JSON.parse(payload.textContent); }
    catch (error) { console.error('Data grafik Forecast tidak valid.', error); return; }

    const history = Array.isArray(parsed.history) ? parsed.history : [];
    const future = Array.isArray(parsed.future) ? parsed.future : [];
    if (!history.length) return;

    const allRows = [...history, ...future];
    const labels = allRows.map(row => row.period_short ?? row.period);
    const fullLabels = allRows.map(row => row.period_label ?? row.period);
    const historyLength = history.length;
    const convert = value => (value === null || value === undefined ? null : Number(value));

    const actual = [...history.map(row => convert(row.actual)), ...future.map(() => null)];
    const historicalForecast = [...history.map(row => convert(row.forecast)), ...future.map(() => null)];

    // Sambungkan forecast masa depan ke titik aktual terakhir tanpa mengubah nilai DES.
    const futureForecast = Array(labels.length).fill(null);
    futureForecast[historyLength - 1] = convert(history[historyLength - 1].actual);
    future.forEach((row, index) => {
        futureForecast[historyLength + index] = convert(row.units);
    });

    const textColor = '#65564c';
    const actualColor = '#794330';     // Cokelat: aktual
    const historicalColor = '#16847d'; // Hijau teal: prediksi historis
    const futureColor = '#e18928';     // Oranye: forecast mendatang

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Penjualan Aktual', data: actual,
                    borderColor: actualColor, backgroundColor: actualColor,
                    tension: 0.15, borderWidth: 2.5, pointRadius: 2,
                    pointHoverRadius: 6, pointHitRadius: 12, spanGaps: false,
                },
                {
                    label: 'Prediksi DES (historis)', data: historicalForecast,
                    borderColor: historicalColor, backgroundColor: historicalColor,
                    tension: 0.15, borderWidth: 2.2, pointRadius: 2,
                    pointHoverRadius: 6, pointHitRadius: 12, spanGaps: false,
                },
                {
                    label: 'Forecast Mendatang', data: futureForecast,
                    borderColor: futureColor, backgroundColor: futureColor,
                    borderDash: [7, 5], tension: 0.15, borderWidth: 2.8,
                    pointRadius: 3.5, pointHoverRadius: 7, pointHitRadius: 12,
                    spanGaps: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 450 },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, usePointStyle: true, boxWidth: 8, padding: 18 },
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#332820',
                    titleColor: '#fff', bodyColor: '#fff',
                    padding: 12, displayColors: true,
                    callbacks: {
                        title(items) { return fullLabels[items[0]?.dataIndex] ?? ''; },
                        label(context) {
                            const number = Number(context.parsed.y);
                            return ` ${context.dataset.label}: ${number.toLocaleString('id-ID', { maximumFractionDigits: 2 })} pcs`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, maxTicksLimit: 9, maxRotation: 0, autoSkip: true },
                    border: { color: '#e9dfd6' },
                },
                y: {
                    grace: '10%',
                    title: { display: true, text: 'Jumlah (pcs)', color: textColor },
                    ticks: { color: textColor, precision: 0 },
                    grid: { color: '#f0ebe6' },
                    border: { display: false },
                },
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeForecastChart);
} else {
    initializeForecastChart();
}
