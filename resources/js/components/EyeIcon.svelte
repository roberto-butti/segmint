<script lang="ts">
    let {
        crossed = false,
        class: className = '',
    }: {
        crossed?: boolean;
        class?: string;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class={className}
    aria-hidden="true"
>
    {#if crossed}
        <path
            d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"
        />
        <path
            d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"
        />
        <line x1="2" x2="22" y1="2" y2="22" />
    {:else}
        <path
            d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"
        />
    {/if}
    <circle cx="12" cy="12" r="3" />
</svg>
