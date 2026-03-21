<script lang="ts">
    import {
        Chart,
        ArcElement,
        DoughnutController,
        Tooltip,
        Legend,
    } from 'chart.js';
    import { onMount } from 'svelte';

    Chart.register(ArcElement, DoughnutController, Tooltip, Legend);

    let {
        labels,
        data,
    }: {
        labels: string[];
        data: number[];
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
        chart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: 'white',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 16,
                            font: { size: 12 },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const total = context.dataset.data.reduce(
                                    (a: number, b: number) => a + b,
                                    0,
                                );
                                const value = context.parsed;
                                const pct =
                                    total > 0
                                        ? ((value / total) * 100).toFixed(1)
                                        : '0';

                                return `${context.label}: ${value} (${pct}%)`;
                            },
                        },
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
