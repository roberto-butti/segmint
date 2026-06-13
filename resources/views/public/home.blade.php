@extends('layouts.public')

@section('title', 'Segmint - Real-time audience segmentation')
@section('description', 'Track events, define audience segments with flexible rules, and understand your audience in real time.')

@section('content')
    <div class="min-h-screen bg-white">
        <header class="border-b border-gray-100">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="Segmint home">
                    <img src="{{ asset('logo-segmint.svg') }}" alt="" class="size-8">
                    <span class="text-lg font-bold text-gray-900">Segmint</span>
                </a>

                <nav class="flex items-center gap-4" aria-label="Account">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">
                            Log in
                        </a>
                        @if ($canRegister)
                            <a href="{{ route('register') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                                Get started
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-6 py-24 text-center lg:py-32">
                <div class="mx-auto max-w-3xl">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-sm text-emerald-700">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        Self-hosted &middot; Open source
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                        Know your audience.<br>
                        <span class="text-emerald-600">Personalise in real time.</span>
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-gray-600">
                        Segmint lets you define audience segments based on real-time events, track how visitors match those segments,
                        and deliver personalised content within your own infrastructure.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800">
                                Go to Dashboard
                            </a>
                        @else
                            @if ($canRegister)
                                <a href="{{ route('register') }}" class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800">
                                    Get started free
                                </a>
                            @endif
                            <a href="{{ route('login') }}" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                Log in
                            </a>
                        @endauth
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-100 bg-gray-50 py-20">
                <div class="mx-auto max-w-6xl px-6">
                    <h2 class="text-center text-2xl font-bold text-gray-900">How it works</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-center text-gray-600">
                        Three steps from raw events to personalised experiences.
                    </p>
                    <div class="mt-12 grid gap-8 md:grid-cols-3">
                        @foreach ([
                            ['Track events', 'Add the lightweight JavaScript SDK to capture page views, UTM parameters, referrers, and custom events.'],
                            ['Define segments', 'Create audience segments with flexible rules based on tracked activity and visitor properties.'],
                            ['Use segment results', 'Read matched segments through the SDK and use them in your application or content-selection logic.'],
                        ] as [$title, $description])
                            <article class="rounded-xl bg-white p-6 shadow-sm">
                                <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-emerald-100 text-lg font-bold text-emerald-700">
                                    {{ $loop->iteration }}
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-20">
                <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2">
                    <div>
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-1.5 text-sm text-amber-700">
                            <span class="size-2 rounded-full bg-amber-500"></span>
                            Data-based suggestions
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Your data suggests the segments</h2>
                        <p class="mt-3 text-gray-600">
                            Segmint analyses tracked event data and suggests useful segments with pre-built rules, confidence levels,
                            and duplicate detection.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-gray-600">
                            <li>Detects top UTM sources, campaigns, referrers, and high-traffic pages.</li>
                            <li>Identifies returning visitors and frequent page visitors from behaviour patterns.</li>
                            <li>Shows whether a suggestion already exists or resembles an existing segment.</li>
                        </ul>
                    </div>

                    <div class="space-y-3">
                        @foreach ([
                            ['Google Visitors', 'utm_source = google · 42% of traffic', 'high', 'green'],
                            ['Returning Visitors', '5+ page views · 18 visitors match', 'high', 'green'],
                            ['Facebook Visitors', 'Similar to an existing source-based segment', 'similar', 'amber'],
                        ] as [$name, $description, $status, $color])
                            <div @class([
                                'rounded-xl border bg-white p-4 shadow-sm',
                                'border-gray-200' => $color === 'green',
                                'border-amber-200 bg-amber-50/50' => $color === 'amber',
                            ])>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">{{ $name }}</span>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-green-100 text-green-700' => $color === 'green',
                                        'bg-amber-100 text-amber-700' => $color === 'amber',
                                    ])>{{ $status }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">{{ $description }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-100 bg-gray-50 py-20">
                <div class="mx-auto max-w-6xl px-6">
                    <h2 class="text-center text-2xl font-bold text-gray-900">Built for developers</h2>
                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['Self-hosted', 'Run Segmint on infrastructure you control and keep event data in your chosen environment.'],
                            ['Lightweight SDK', 'Track events and read visitor segment matches through a small JavaScript SDK.'],
                            ['Real-time matching', 'Re-evaluate visitor segments whenever an event is received.'],
                            ['CMS integration', 'Fetch segments through the API and associate them with content in a CMS.'],
                            ['Flexible rules', 'Combine comparisons, visit counts, page views, and browser-language rules.'],
                            ['Built-in analytics', 'Inspect per-project event trends, segment distribution, and recent activity.'],
                        ] as [$title, $description])
                            <article class="rounded-xl border border-gray-200 bg-white p-5">
                                <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                                <p class="mt-1.5 text-sm text-gray-600">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-100 py-20">
                <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Use segments in a few lines of code</h2>
                        <p class="mt-3 text-gray-600">
                            Initialise the SDK, check segment membership, and select the appropriate content.
                        </p>
                    </div>
                    <div class="overflow-x-auto rounded-xl bg-gray-900 p-6 text-sm">
                        <pre class="text-gray-300"><code><span class="text-blue-300">await</span> Segmint.<span class="text-yellow-300">init</span>({ token: <span class="text-emerald-300">'your-token'</span>, autoTrack: <span class="text-blue-300">true</span> });

<span class="text-blue-300">if</span> (Segmint.visitor.<span class="text-yellow-300">hasSegment</span>(<span class="text-emerald-300">'high_intent'</span>)) {
  showSpecialOffer();
}</code></pre>
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-100 bg-gray-50 py-20">
                <div class="mx-auto max-w-3xl px-6 text-center">
                    <h2 class="text-2xl font-bold text-gray-900">Start segmenting your audience</h2>
                    <p class="mt-3 text-gray-600">
                        Deploy Segmint on your own infrastructure and use tracked data to understand and serve your audience.
                    </p>
                    <div class="mt-8 flex items-center justify-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800">
                                Go to Dashboard
                            </a>
                        @else
                            @if ($canRegister)
                                <a href="{{ route('register') }}" class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800">
                                    Get started free
                                </a>
                            @endif
                            <a href="{{ route('login') }}" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                                Log in
                            </a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-100 py-8">
            <div class="mx-auto max-w-6xl px-6 text-center text-sm text-gray-500">
                Segmint - Open-source event tracking and real-time audience segmentation.
            </div>
        </footer>
    </div>
@endsection
