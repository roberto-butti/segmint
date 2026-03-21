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
    );

    let {
        labels,
        data,
        label = 'Events',
    }: {
        labels: string[];
        data: number[];
        label?: string;
    } = $props();

    let canvas: HTMLCanvasElement;
    let chart: Chart | null = null;

    onMount(() => {
        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label,
                        data,
                        fill: true,
                        backgroundColor: 'rgba(99, 102, 241, 0.15)',
                        borderColor: '#6366F1',
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        tension: 0.3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 7, font: { size: 11 } },
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

<div class="h-64">
    <canvas bind:this={canvas}></canvas>
</div>
