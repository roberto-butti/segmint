<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 100 100"
    class={className}
    {...rest}
>
    <g transform="translate(50, 50)">
        <path d="M 4 -38 A 38 38 0 0 1 34 18" fill="none" stroke="#2DD4A8" stroke-width="14" stroke-linecap="round"/>
        <path d="M 28 24 A 38 38 0 0 1 -28 24" fill="none" stroke="#10B981" stroke-width="14" stroke-linecap="round"/>
        <path d="M -34 18 A 38 38 0 0 1 -4 -38" fill="none" stroke="#059669" stroke-width="14" stroke-linecap="round"/>
    </g>
</svg>
