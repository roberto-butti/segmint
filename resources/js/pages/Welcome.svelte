<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login, register } from '@/routes';

    let {
        canRegister = true,
    }: {
        canRegister: boolean;
    } = $props();

    const auth = $derived($page.props.auth);
</script>

<AppHead title="Segmint — Real-time audience segmentation" />

<div class="min-h-screen bg-white dark:bg-[#0a0a0a]">
    <!-- Nav -->
    <header class="border-b border-gray-100 dark:border-gray-800">
        <div
            class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
        >
            <div class="flex items-center gap-2">
                <AppLogoIcon class="size-8" />
                <span class="text-lg font-bold text-gray-900 dark:text-white"
                    >Segmint</span
                >
            </div>
            <nav class="flex items-center gap-4">
                {#if auth.user}
                    <Link
                        href={toUrl(dashboard())}
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                    >
                        Dashboard
                    </Link>
                {:else}
                    <Link
                        href={toUrl(login())}
                        class="text-sm font-medium text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                    >
                        Log in
                    </Link>
                    {#if canRegister}
                        <Link
                            href={toUrl(register())}
                            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                        >
                            Get started
                        </Link>
                    {/if}
                {/if}
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="mx-auto max-w-6xl px-6 py-24 text-center lg:py-32">
        <div class="mx-auto max-w-3xl">
            <div
                class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400"
            >
                <span class="size-2 rounded-full bg-emerald-500"></span>
                Self-hosted &middot; Open source
            </div>
            <h1
                class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl dark:text-white"
            >
                Know your audience.<br />
                <span class="text-emerald-600 dark:text-emerald-400"
                    >Personalise in real time.</span
                >
            </h1>
            <p
                class="mt-6 text-lg leading-relaxed text-gray-600 dark:text-gray-400"
            >
                Segmint lets you define audience segments based on real-time
                events, track how visitors match those segments, and deliver
                personalised content — all within your own infrastructure.
            </p>
            <div class="mt-10 flex items-center justify-center gap-4">
                {#if auth.user}
                    <Link
                        href={toUrl(dashboard())}
                        class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                    >
                        Go to Dashboard
                    </Link>
                {:else}
                    {#if canRegister}
                        <Link
                            href={toUrl(register())}
                            class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                        >
                            Get started free
                        </Link>
                    {/if}
                    <Link
                        href={toUrl(login())}
                        class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Log in
                    </Link>
                {/if}
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section
        class="border-t border-gray-100 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-900/50"
    >
        <div class="mx-auto max-w-6xl px-6">
            <h2
                class="text-center text-2xl font-bold text-gray-900 dark:text-white"
            >
                How it works
            </h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-center text-gray-600 dark:text-gray-400"
            >
                Three steps from raw events to personalised experiences.
            </p>
            <div class="mt-12 grid gap-8 md:grid-cols-3">
                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div
                        class="mb-4 flex size-10 items-center justify-center rounded-lg bg-emerald-100 text-lg font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400"
                    >
                        1
                    </div>
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Track events
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400"
                    >
                        Drop a lightweight JavaScript snippet on your site. It
                        captures page views, clicks, UTM parameters, and custom
                        events — automatically.
                    </p>
                </div>
                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div
                        class="mb-4 flex size-10 items-center justify-center rounded-lg bg-emerald-100 text-lg font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400"
                    >
                        2
                    </div>
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Define segments
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400"
                    >
                        Create audience segments with flexible rules — match on
                        UTM source, visit count, page views, browser language,
                        or any event property.
                    </p>
                </div>
                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div
                        class="mb-4 flex size-10 items-center justify-center rounded-lg bg-emerald-100 text-lg font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400"
                    >
                        3
                    </div>
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Personalise content
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400"
                    >
                        Show different content to different audiences. The SDK
                        tells you which segments a visitor matches — in real
                        time, on every page load.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <h2
                class="text-center text-2xl font-bold text-gray-900 dark:text-white"
            >
                Built for developers
            </h2>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    class="rounded-xl border border-gray-200 p-5 dark:border-gray-700"
                >
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Self-hosted
                    </h3>
                    <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                        Your data stays on your infrastructure. No third-party
                        tracking. Full control.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 p-5 dark:border-gray-700"
                >
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Lightweight SDK
                    </h3>
                    <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                        Under 5KB minified. One script tag. Auto-detects the API
                        endpoint from its own URL.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 p-5 dark:border-gray-700"
                >
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Real-time matching
                    </h3>
                    <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                        Segments are evaluated on every event. No batch
                        processing, no delays.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 p-5 dark:border-gray-700"
                >
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        CMS integration
                    </h3>
                    <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                        Fetch segments via API to populate CMS dropdowns. Tag
                        content with audience segments.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 p-5 dark:border-gray-700"
                >
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Flexible rules
                    </h3>
                    <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                        Comparison, visit count, page views, browser language —
                        combine rules to define any audience.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 p-5 dark:border-gray-700"
                >
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Built-in analytics
                    </h3>
                    <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                        Per-project dashboards with event trends, segment
                        distribution, and top audiences.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Code example -->
    <section
        class="border-t border-gray-100 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-900/50"
    >
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <h2
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Personalise in 4 lines of code
                    </h2>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        Init the SDK, check segment membership, show the right
                        content. No complex setup, no configuration files.
                    </p>
                    <div
                        class="mt-6 space-y-3 text-sm text-gray-600 dark:text-gray-400"
                    >
                        <p>
                            <strong class="text-gray-900 dark:text-white"
                                >visitor.*</strong
                            > — track events, read matched segments for this visitor
                        </p>
                        <p>
                            <strong class="text-gray-900 dark:text-white"
                                >fetch.*</strong
                            > — query project data (list all segments for CMS integration)
                        </p>
                    </div>
                </div>
                <div
                    class="overflow-hidden rounded-xl bg-gray-900 p-6 text-sm dark:bg-black"
                >
                    <pre class="text-gray-300"><code
                            ><span class="text-gray-500"
                                >// Include the SDK</span
                            >
&lt;<span class="text-emerald-400">script</span> <span class="text-yellow-300"
                                >src</span
                            >=<span class="text-orange-300"
                                >"segmint.min.js"</span
                            >&gt;&lt;/<span class="text-emerald-400"
                                >script</span
                            >&gt;

<span class="text-gray-500">// Init and personalise</span>
<span class="text-blue-300">await</span> Segmint.<span class="text-yellow-300"
                                >init</span
                            >(&#123; <span class="text-orange-300">token</span
                            >: <span class="text-emerald-300">'your-token'</span
                            >, <span class="text-orange-300">autoTrack</span
                            >: <span class="text-blue-300">true</span> &#125;);

<span class="text-blue-300">if</span> (Segmint.visitor.<span
                                class="text-yellow-300">hasSegment</span
                            >(<span class="text-emerald-300">'high_intent'</span
                            >)) &#123;
  showSpecialOffer();
&#125;</code
                        ></pre>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Start segmenting your audience today
            </h2>
            <p class="mt-3 text-gray-600 dark:text-gray-400">
                Self-hosted, open source, and free. Deploy on your own
                infrastructure and take full control of your audience data.
            </p>
            <div class="mt-8 flex items-center justify-center gap-4">
                {#if auth.user}
                    <Link
                        href={toUrl(dashboard())}
                        class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                    >
                        Go to Dashboard
                    </Link>
                {:else}
                    {#if canRegister}
                        <Link
                            href={toUrl(register())}
                            class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                        >
                            Get started free
                        </Link>
                    {/if}
                    <Link
                        href={toUrl(login())}
                        class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Log in
                    </Link>
                {/if}
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-gray-100 py-8 dark:border-gray-800">
        <div
            class="mx-auto max-w-6xl px-6 text-center text-sm text-gray-500 dark:text-gray-500"
        >
            Segmint — Real-time audience segmentation for modern web apps.
        </div>
    </footer>
</div>
