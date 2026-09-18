import { Chart, registerables } from 'chart.js';

const pieSliceLabelsPlugin = {
    id: 'pieSliceLabels',
    afterDatasetsDraw(chart) {
        const items = chart.options.plugins?.pieSliceLabels?.items || [];
        const meta = chart.getDatasetMeta(0);

        if (! meta?.data?.length || ! items.length) {
            return;
        }

        const { ctx } = chart;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        meta.data.forEach((arc, index) => {
            const item = items[index];

            if (! item || Number(item.percent) <= 0) {
                return;
            }

            const angle = (arc.startAngle + arc.endAngle) / 2;
            const radius = arc.outerRadius * 0.64;
            const x = arc.x + (Math.cos(angle) * radius);
            const y = arc.y + (Math.sin(angle) * radius);

            ctx.fillStyle = item.color || '#ffffff';
            ctx.font = 'bold 15px Figtree, ui-sans-serif, system-ui, sans-serif';
            ctx.fillText(`${item.percent}%`, x, y - 8);
            ctx.font = '600 9px Figtree, ui-sans-serif, system-ui, sans-serif';
            ctx.fillText(String(item.title || '').toUpperCase(), x, y + 8);
        });

        ctx.restore();
    },
};

Chart.register(...registerables, pieSliceLabelsPlugin);

const COLORS = {
    received: '#6366f1',
    pending: '#c7d2fe',
    feeIncome: '#6366f1',
    expenses: '#f87171',
    income: '#818cf8',
    expenseBar: '#fca5a5',
    profit: '#34d399',
};

export function formatInr(value) {
    const num = Number(value) || 0;
    const hasDecimals = Math.abs(num % 1) > 0.009;

    return `₹${num.toLocaleString('en-IN', {
        minimumFractionDigits: hasDecimals ? 2 : 0,
        maximumFractionDigits: hasDecimals ? 2 : 0,
    })}`;
}

function destroyChart(instance) {
    if (instance) {
        instance.destroy();
    }
}

function destroyCanvasChart(canvas) {
    if (! canvas) {
        return;
    }

    destroyChart(Chart.getChart(canvas));
}

function axisTickLabel(value) {
    if (typeof value !== 'number' || Number.isNaN(value)) {
        return '';
    }

    return formatInr(value);
}

function createChart(canvas, config) {
    destroyCanvasChart(canvas);

    return new Chart(canvas, config);
}

export async function waitForChartCanvases(canvases, attempts = 30) {
    for (let attempt = 0; attempt < attempts; attempt += 1) {
        const ready = canvases.every((canvas) => canvas
            && canvas.isConnected
            && canvas.offsetWidth > 0
            && canvas.offsetHeight > 0);

        if (ready) {
            return true;
        }

        await new Promise((resolve) => requestAnimationFrame(resolve));
    }

    return canvases.every((canvas) => canvas && canvas.isConnected);
}

export function renderFinanceCharts(containers, data, options = {}) {
    const instances = {
        feeCollection: null,
        line: null,
        bar: null,
    };

    destroyChart(containers._feeCollection);
    destroyChart(containers._line);
    destroyChart(containers._bar);

    const feeCollection = data?.fee_collection || {};
    const lineData = data?.fee_income_vs_expenses || {};
    const barData = data?.income_expenses_profit || {};

    if (containers.feeCollectionCanvas) {
        const pieReceived = Number(feeCollection.pie_received ?? feeCollection.received) || 0;
        const piePending = Number(feeCollection.pie_pending ?? feeCollection.pending) || 0;
        const hasData = Boolean(feeCollection.has_data) && (pieReceived > 0 || piePending > 0);

        const receivedPercent = Number(feeCollection.received_percentage) || 0;
        const pendingPercent = Number(feeCollection.pending_percentage) || 0;

        instances.feeCollection = createChart(containers.feeCollectionCanvas, {
            type: 'pie',
            data: {
                labels: ['Received', 'Pending'],
                datasets: [{
                    data: hasData ? [pieReceived, piePending] : [1],
                    backgroundColor: hasData ? [COLORS.received, COLORS.pending] : ['#e5e7eb'],
                    borderWidth: hasData ? 2 : 0,
                    borderColor: '#ffffff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: 2 },
                plugins: {
                    legend: { display: false },
                    pieSliceLabels: {
                        items: hasData ? [
                            { percent: receivedPercent, title: 'Received', color: '#ffffff' },
                            { percent: pendingPercent, title: 'Pending', color: '#4338ca' },
                        ] : [],
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                if (! hasData) {
                                    return 'No student fee data available.';
                                }

                                const label = context.label || '';
                                const value = formatInr(context.parsed);

                                return `${label}\n${value}`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (containers.lineCanvas) {
        const labels = lineData.labels || [];
        const hasData = Boolean(lineData.has_data);

        instances.line = createChart(containers.lineCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Student Fee Income',
                        data: hasData ? (lineData.student_fee_income || []) : [],
                        borderColor: COLORS.feeIncome,
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        tension: 0.35,
                        fill: false,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Total Expenses',
                        data: hasData ? (lineData.total_expenses || []) : [],
                        borderColor: COLORS.expenses,
                        backgroundColor: 'rgba(248, 113, 113, 0.08)',
                        tension: 0.35,
                        fill: false,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: axisTickLabel,
                        },
                        grid: { color: '#f3f4f6' },
                    },
                    x: {
                        grid: { display: false },
                    },
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            title(items) {
                                return items[0]?.label || '';
                            },
                            label(context) {
                                const value = context.parsed?.y ?? context.parsed ?? 0;

                                return `${context.dataset.label}\n${formatInr(value)}`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (containers.barCanvas) {
        const labels = barData.labels || [];
        const hasData = Boolean(barData.has_data);

        instances.bar = createChart(containers.barCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Total Income',
                        data: hasData ? (barData.total_income || []) : [],
                        backgroundColor: COLORS.income,
                        borderRadius: 4,
                    },
                    {
                        label: 'Total Expenses',
                        data: hasData ? (barData.total_expenses || []) : [],
                        backgroundColor: COLORS.expenseBar,
                        borderRadius: 4,
                    },
                    {
                        label: 'Total Profit',
                        data: hasData ? (barData.total_profit || []) : [],
                        backgroundColor: COLORS.profit,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: axisTickLabel,
                        },
                        grid: { color: '#f3f4f6' },
                    },
                    x: {
                        grid: { display: false },
                    },
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            title(items) {
                                return items[0]?.label || '';
                            },
                            label(context) {
                                const value = context.parsed?.y ?? context.parsed ?? 0;

                                return `${context.dataset.label}\n${formatInr(value)}`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (typeof options.onSummaryUpdate === 'function') {
        options.onSummaryUpdate(feeCollection);
    }

    return instances;
}

export function destroyFinanceCharts(instances = {}) {
    destroyChart(instances.feeCollection);
    destroyChart(instances.line);
    destroyChart(instances.bar);
}

export function resizeFinanceCharts(instances = {}) {
    Object.values(instances).forEach((chart) => {
        if (chart) {
            chart.resize();
        }
    });
}
