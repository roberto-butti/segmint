<script lang="ts">
    import {
        Chart,
        CategoryScale,
        LinearScale,
        BarElement,
        BarController,
        Tooltip,
    } from 'chart.js';
    import { onMount } from 'svelte';

    Chart.register(
        CategoryScale,
        LinearScale,
        BarElement,
        BarController,
        Tooltip,
    );

    let {
        labels,
        data,
        label = 'Count',
        horizontal = false,
    }: {
        labels: string[];
        data: number[];
        label?: string;
        horizontal?: boolean;
    } = $props();

    let canvas: HTMLCanvasElement;
    let chart: Chart | null = null;

    onMount(() => {
        chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label,
                        data,
                        backgroundColor: data.map(
                            (_, i) =>
                                [
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
                                ][i % 10],
                        ),
                        borderRadius: 4,
                        barThickness: horizontal ? 20 : undefined,
                    },
                ],
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
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
                        grid: { display: horizontal },
                        ticks: { precision: 0, font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: !horizontal,
                            color: 'rgba(0,0,0,0.05)',
                        },
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
