<script lang="ts">
    import {
        Chart,
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        LineController,
        Filler,
        Tooltip,
        Legend,
    } from 'chart.js';
    import { onMount } from 'svelte';

    Chart.register(
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        LineController,
        Filler,
        Tooltip,
        Legend,
    );

    let {
        labels,
        datasets,
    }: {
        labels: string[];
        datasets: Record<string, number[]>;
    } = $props();

    let canvas: HTMLCanvasElement;
    let chart: Chart | null = null;

    const colors = [
        '#6366F1',
        '#F59E0B',
        '#10B981',
        '#EF4444',
        '#3B82F6',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316',
        '#06B6D4',
    ];

    onMount(() => {
        const entries = Object.entries(datasets);

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: entries.map(([name, data], i) => ({
                    label: name,
                    data,
                    fill: false,
                    borderColor: colors[i % colors.length],
                    backgroundColor: colors[i % colors.length],
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    tension: 0.3,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 12,
                            font: { size: 11 },
                        },
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 10, font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { precision: 0, font: { size: 11 } },
                    },
                },
            },
        });

        return () => chart?.destroy();
    });
</script>

<div class="h-72">
    <canvas bind:this={canvas}></canvas>
</div>
