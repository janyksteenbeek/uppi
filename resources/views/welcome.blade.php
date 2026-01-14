@php
    $dashboardUrl = \App\Filament\Pages\Dashboard::getUrl();
@endphp
    <!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Uppi</title>

    <meta name="description"
          content="Open-source uptime monitoring for websites and APIs. Monitor your website every minute and get notified when it goes down.">
    <meta name="keywords" content="uptime monitoring, website monitoring, api monitoring, open-source">
    <meta name="author" content="Janyk Steenbeek">

    <meta property="og:title" content="Uppi">
    <meta property="og:description"
          content="Open-source uptime monitoring for websites and APIs. Monitor your website every minute and get notified when it goes down.">
    <meta property="og:image" content="{{ asset('static/iPad.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@janyksteenbeek">
    <meta name="twitter:creator" content="@janyksteenbeek">
    <meta name="twitter:title" content="Uppi">
    <meta name="twitter:description"
          content="Open-source uptime monitoring for websites and services. Monitor your website every minute and get notified when it goes down.">
    <meta name="twitter:image" content="{{ asset('static/iPad.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}"/>

    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet"/>
    <script defer src="https://statisfyer.nl/script.js" data-website-id="5e2d6b2a-67a0-4965-ace2-8677b879fbdf"></script>
    <script defer src="https://unpkg.com/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
</head>
<body>
<div class="bg-white" x-data="{ open: false }">
    <header class="absolute inset-x-0 top-0 z-50">
        <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
            <div class="flex lg:flex-1">
                <a class="-m-1.5 p-1.5">
                    <span class="sr-only">Uppi</span>
                    <img class="h-8 w-auto" src="{{ asset('logo.svg') }}"
                         alt="Uppi">
                </a>
            </div>
            <div class="flex lg:hidden">
                <button type="button"
                        x-on:click="open = !open"
                        class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700">
                    <span class="sr-only">Open main menu</span>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-12">
                <a href="#features"
                   class="text-sm/6 font-semibold text-gray-900">Features</a>
                <a href="https://github.com/janyksteenbeek/uppi/blob/main/README.md"
                   class="text-sm/6 font-semibold text-gray-900">Docs</a>
                <a href="https://github.com/sponsors/janyksteenbeek" class="text-sm/6 font-semibold text-gray-900">Sponsor</a>
                <a href="https://github.com/janyksteenbeek/uppi" class="text-sm/6 font-semibold text-gray-900">Contribute</a>
                <a href="{{ $dashboardUrl }}" class="text-sm/6 font-semibold text-gray-900">Sign
                    in</a>
                <a href="https://apps.apple.com/app/uppi/id6739699410"
                   class="text-sm/6 font-semibold text-gray-900 inline-flex items-center gap-0.5">
                    <svg class="w-4 h-4 " viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                    </svg>
                </a>
                <a href="https://play.google.com/store/apps/details?id=dev.uppi.app"
                   class="text-sm/6 font-semibold text-gray-900 inline-flex items-center gap-0.5 -ml-8">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
                    </svg>
                </a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="{{ $dashboardUrl }}"
                   class="rounded-md bg-red-600 px-3 py-2.5 font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    Create a free account
                </a>
            </div>
        </nav>
        <!-- Mobile menu, show/hide based on menu open state. -->
        <div class="lg:hidden" role="dialog" aria-modal="true" x-show="open" x-cloak>
            <!-- Background backdrop, show/hide based on slide-over state. -->
            <div class="fixed inset-0 z-50"></div>
            <div
                class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
                <div class="flex items-center justify-between">
                    <a href="#" class="-m-1.5 p-1.5">
                        <span class="sr-only">Uppi</span>
                        <img class="h-8 w-auto"
                             src="{{ asset('logo.svg') }}" alt="Uppi">
                    </a>
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700" x-on:click="open = false">
                        <span class="sr-only">Close menu</span>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                             aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-6 divide-y divide-gray-500/10">
                        <div class="space-y-2 py-6">
                            <a href="https://github.com/janyksteenbeek/uppi"
                               class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Source</a>
                            <a href="https://github.com/sponsors/janyksteenbeek"
                               class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Sponsor</a>
                        </div>
                        <div class="py-6">
                            <a href="{{ $dashboardUrl }}"
                               class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Log
                                in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="relative isolate pt-14">
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
             aria-hidden="true">
            <div
                class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-red-500 to-red-600 opacity-20 sm:left-[calc(50%-30rem)] sm:w-[79.1875rem]"
                style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
        </div>
        <div class="py-24 sm:py-32 lg:pb-40">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <h1 class="text-balance text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl">
                        Be the <strong class="text-red-500">first</strong> to know when your website goes <strong
                            class="glitch" data-text="down">down</strong>
                    </h1>
                    <p class="mt-8 text-pretty text-lg font-medium text-gray-600 sm:text-xl/8">
                        Open-source uptime monitoring for websites and APIs. Monitor your website every minute and get
                        notified when it goes down.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a href="{{ url(\App\Filament\Pages\Dashboard::getUrl()) }}"
                           class="rounded-md bg-red-600 px-3.5 py-2.5 text-lg font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            Start monitoring for free <span aria-hidden="true">→</span>
                        </a>
                        <a href="https://github.com/janyksteenbeek/uppi"
                           class="text-sm/6 font-semibold text-gray-900 inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" class="size-5" viewBox="0 0 50 50">
                                <path
                                    d="M17.791,46.836C18.502,46.53,19,45.823,19,45v-5.4c0-0.197,0.016-0.402,0.041-0.61C19.027,38.994,19.014,38.997,19,39 c0,0-3,0-3.6,0c-1.5,0-2.8-0.6-3.4-1.8c-0.7-1.3-1-3.5-2.8-4.7C8.9,32.3,9.1,32,9.7,32c0.6,0.1,1.9,0.9,2.7,2c0.9,1.1,1.8,2,3.4,2 c2.487,0,3.82-0.125,4.622-0.555C21.356,34.056,22.649,33,24,33v-0.025c-5.668-0.182-9.289-2.066-10.975-4.975 c-3.665,0.042-6.856,0.405-8.677,0.707c-0.058-0.327-0.108-0.656-0.151-0.987c1.797-0.296,4.843-0.647,8.345-0.714 c-0.112-0.276-0.209-0.559-0.291-0.849c-3.511-0.178-6.541-0.039-8.187,0.097c-0.02-0.332-0.047-0.663-0.051-0.999 c1.649-0.135,4.597-0.27,8.018-0.111c-0.079-0.5-0.13-1.011-0.13-1.543c0-1.7,0.6-3.5,1.7-5c-0.5-1.7-1.2-5.3,0.2-6.6 c2.7,0,4.6,1.3,5.5,2.1C21,13.4,22.9,13,25,13s4,0.4,5.6,1.1c0.9-0.8,2.8-2.1,5.5-2.1c1.5,1.4,0.7,5,0.2,6.6c1.1,1.5,1.7,3.2,1.6,5 c0,0.484-0.045,0.951-0.11,1.409c3.499-0.172,6.527-0.034,8.204,0.102c-0.002,0.337-0.033,0.666-0.051,0.999 c-1.671-0.138-4.775-0.28-8.359-0.089c-0.089,0.336-0.197,0.663-0.325,0.98c3.546,0.046,6.665,0.389,8.548,0.689 c-0.043,0.332-0.093,0.661-0.151,0.987c-1.912-0.306-5.171-0.664-8.879-0.682C35.112,30.873,31.557,32.75,26,32.969V33 c2.6,0,5,3.9,5,6.6V45c0,0.823,0.498,1.53,1.209,1.836C41.37,43.804,48,35.164,48,25C48,12.318,37.683,2,25,2S2,12.318,2,25 C2,35.164,8.63,43.804,17.791,46.836z"></path>
                            </svg>

                            janyksteenbeek/uppi
                        </a>
                    </div>


                </div>
                <div class="mt-16 flow-root sm:mt-24">
                    <div
                        class="-m-2 rounded-xl bg-gray-900/5 p-2 ring-1 ring-inset ring-gray-900/10 lg:-m-4 lg:rounded-2xl lg:p-4">
                        <img src="{{ asset('static/screenshot-dashboard.png') }}"
                             alt="App screenshot" width="2432" height="1442"
                             class="rounded-md shadow-2xl ring-1 ring-gray-900/10">
                    </div>
                </div>
            </div>
        </div>
        <div
            class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]"
            aria-hidden="true">
            <div
                class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-red-300 to-red-700 opacity-30 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"
                style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
        </div>
    </div>
</div>

{{-- Monitors Section --}}
<div class="relative overflow-hidden bg-white py-24 sm:py-32" id="features"
     x-data="{ shown: false }"
     x-intersect.once.half="shown = true">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <p class="text-base font-semibold text-red-600">Real-time monitoring</p>
            <h2 class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                Monitor everything.<br>Miss nothing.
            </h2>
            <p class="mt-6 text-lg/8 text-gray-600">
                HTTP, TCP, and cron-job monitoring with minute-by-minute precision. Get instant alerts when things go wrong.
            </p>
        </div>

        {{-- Monitor Type Details --}}
        <div class="mx-auto mt-16 max-w-5xl"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
             class="transition-all duration-700 delay-200 ease-out">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- HTTP Details --}}
                <div class="rounded-2xl bg-white border border-gray-200 p-6 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    {{-- HTTP Animation - Request/Response --}}
                    <div class="relative h-24 mb-4 flex items-center justify-center bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-6">
                            {{-- Browser/Client --}}
                            <div class="relative">
                                <div class="w-10 h-8 rounded bg-gray-200 flex items-center justify-center">
                                    {{-- Lucide: monitor --}}
                                    <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="3" rx="2"/>
                                        <line x1="8" x2="16" y1="21" y2="21"/>
                                        <line x1="12" x2="12" y1="17" y2="21"/>
                                    </svg>
                                </div>
                            </div>
                            {{-- Request arrow --}}
                            <div class="relative w-16">
                                <div class="absolute top-1/2 -translate-y-1/2 w-full h-0.5 bg-gray-300"></div>
                                <div class="absolute top-1/2 -translate-y-1/2 h-0.5 bg-green-500 animate-[httpRequest_2s_ease-in-out_infinite]" style="width: 0;"></div>
                                <svg class="absolute right-0 top-1/2 -translate-y-1/2 w-2 h-2 text-gray-400" fill="currentColor" viewBox="0 0 8 8"><path d="M0 0 L8 4 L0 8 Z"/></svg>
                            </div>
                            {{-- Server --}}
                            <div class="relative">
                                <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center">
                                    {{-- Lucide: server --}}
                                    <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="8" x="2" y="2" rx="2" ry="2"/>
                                        <rect width="20" height="8" x="2" y="14" rx="2" ry="2"/>
                                        <line x1="6" x2="6.01" y1="6" y2="6"/>
                                        <line x1="6" x2="6.01" y1="18" y2="18"/>
                                    </svg>
                                </div>
                                {{-- Status indicator --}}
                                <div class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                            </div>
                        </div>
                        {{-- Status code badge --}}
                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center rounded bg-green-100 px-2 py-0.5 text-xs font-mono text-green-600 animate-[fadeInOut_2s_ease-in-out_infinite]">200 OK</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-gray-900">
                        {{-- Lucide: globe --}}
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                            <path d="M2 12h20"/>
                        </svg>
                        <span class="font-semibold">HTTP Monitor</span>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            {{-- Lucide: check --}}
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Status code validation
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Response body matching
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Custom headers & body
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Response time tracking
                        </li>
                    </ul>
                </div>

                {{-- TCP Details --}}
                <div class="rounded-2xl bg-white border border-gray-200 p-6 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    {{-- TCP Animation - Port connections --}}
                    <div class="relative h-24 mb-4 flex items-center justify-center bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-2">
                            {{-- Server with ports --}}
                            <div class="relative">
                                <div class="w-16 h-16 rounded-lg bg-gray-100 border border-gray-200 flex flex-col items-center justify-center gap-1 p-2">
                                    {{-- Lucide: database --}}
                                    <svg class="w-6 h-6 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <ellipse cx="12" cy="5" rx="9" ry="3"/>
                                        <path d="M3 5V19A9 3 0 0 0 21 19V5"/>
                                        <path d="M3 12A9 3 0 0 0 21 12"/>
                                    </svg>
                                </div>
                            </div>
                            {{-- Port indicators --}}
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-1">
                                    <div class="w-8 h-0.5 bg-gray-300"></div>
                                    <div class="relative">
                                        <div class="w-2 h-2 rounded-full bg-green-500 animate-[tcpPulse_1.5s_ease-in-out_infinite]"></div>
                                        <div class="absolute inset-0 w-2 h-2 rounded-full bg-green-500 animate-ping opacity-75"></div>
                                    </div>
                                    <span class="text-[10px] text-gray-500 font-mono ml-1">:443</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-8 h-0.5 bg-gray-300"></div>
                                    <div class="relative">
                                        <div class="w-2 h-2 rounded-full bg-green-500 animate-[tcpPulse_1.5s_ease-in-out_infinite_0.3s]"></div>
                                        <div class="absolute inset-0 w-2 h-2 rounded-full bg-green-500 animate-ping opacity-75" style="animation-delay: 0.3s;"></div>
                                    </div>
                                    <span class="text-[10px] text-gray-500 font-mono ml-1">:3306</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-8 h-0.5 bg-gray-300"></div>
                                    <div class="relative">
                                        <div class="w-2 h-2 rounded-full bg-green-500 animate-[tcpPulse_1.5s_ease-in-out_infinite_0.6s]"></div>
                                        <div class="absolute inset-0 w-2 h-2 rounded-full bg-green-500 animate-ping opacity-75" style="animation-delay: 0.6s;"></div>
                                    </div>
                                    <span class="text-[10px] text-gray-500 font-mono ml-1">:6379</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-gray-900">
                        {{-- Lucide: network --}}
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="16" y="16" width="6" height="6" rx="1"/>
                            <rect x="2" y="16" width="6" height="6" rx="1"/>
                            <rect x="9" y="2" width="6" height="6" rx="1"/>
                            <path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/>
                            <path d="M12 12V8"/>
                        </svg>
                        <span class="font-semibold">TCP Monitor</span>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            {{-- Lucide: check --}}
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Port availability check
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Database connections
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Mail server monitoring
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Any TCP service
                        </li>
                    </ul>
                </div>

                {{-- Cron Details --}}
                <div class="rounded-2xl bg-white border border-gray-200 p-6 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    {{-- Cron Animation - Clock with heartbeat --}}
                    <div class="relative h-24 mb-4 flex items-center justify-center bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-4">
                            {{-- Animated clock --}}
                            <div class="relative w-14 h-14">
                                <div class="absolute inset-0 rounded-full border-2 border-gray-300"></div>
                                {{-- Clock face dots --}}
                                <div class="absolute top-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-gray-400"></div>
                                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-gray-400"></div>
                                <div class="absolute left-1 top-1/2 -translate-y-1/2 w-1 h-1 rounded-full bg-gray-400"></div>
                                <div class="absolute right-1 top-1/2 -translate-y-1/2 w-1 h-1 rounded-full bg-gray-400"></div>
                                {{-- Clock hands --}}
                                <div class="absolute top-1/2 left-1/2 w-0.5 h-4 bg-gray-500 origin-bottom -translate-x-1/2 -translate-y-full animate-[clockMinute_4s_linear_infinite]"></div>
                                <div class="absolute top-1/2 left-1/2 w-0.5 h-3 bg-red-500 origin-bottom -translate-x-1/2 -translate-y-full animate-[clockSecond_2s_steps(60)_infinite]"></div>
                                <div class="absolute top-1/2 left-1/2 w-1.5 h-1.5 rounded-full bg-gray-500 -translate-x-1/2 -translate-y-1/2"></div>
                            </div>
                            {{-- Heartbeat line --}}
                            <div class="flex-1">
                                <svg class="w-24 h-8" viewBox="0 0 100 32">
                                    <path d="M0,16 L20,16 L25,16 L30,4 L35,28 L40,16 L60,16 L65,16 L70,4 L75,28 L80,16 L100,16" 
                                          fill="none" 
                                          stroke="#22c55e" 
                                          stroke-width="2"
                                          class="animate-[heartbeat_2s_ease-in-out_infinite]"
                                          stroke-dasharray="200"
                                          stroke-dashoffset="200"/>
                                </svg>
                                <div class="text-center mt-1">
                                    <span class="text-[10px] text-green-600 font-mono animate-pulse">CHECK-IN ✓</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-gray-900">
                        {{-- Lucide: clock --}}
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span class="font-semibold">Cron Monitor</span>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            {{-- Lucide: check --}}
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Heartbeat check-ins
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Configurable grace period
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Background job tracking
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Unique check-in URLs
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes httpRequest {
        0%, 100% { width: 0; opacity: 0; }
        10% { opacity: 1; }
        50% { width: 100%; opacity: 1; }
        60%, 100% { width: 100%; opacity: 0; }
    }
    @keyframes fadeInOut {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 1; }
    }
    @keyframes tcpPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }
    @keyframes clockMinute {
        from { transform: translate(-50%, -100%) rotate(0deg); }
        to { transform: translate(-50%, -100%) rotate(360deg); }
    }
    @keyframes clockSecond {
        from { transform: translate(-50%, -100%) rotate(0deg); }
        to { transform: translate(-50%, -100%) rotate(360deg); }
    }
    @keyframes heartbeat {
        0% { stroke-dashoffset: 200; }
        100% { stroke-dashoffset: 0; }
    }
    @keyframes cpuPulse {
        0%, 100% { height: 60%; }
        25% { height: 75%; }
        50% { height: 45%; }
        75% { height: 80%; }
    }
    @keyframes memoryWave {
        0%, 100% { width: 65%; }
        50% { width: 72%; }
    }
    @keyframes diskFill {
        0%, 100% { width: 78%; }
        50% { width: 82%; }
    }
    @keyframes networkPulse {
        0% { transform: translateX(-100%); opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { transform: translateX(100%); opacity: 0; }
    }
    @keyframes serverGlow {
        0%, 100% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.3); }
        50% { box-shadow: 0 0 40px rgba(34, 197, 94, 0.5); }
    }
    @keyframes metricFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }
</style>

{{-- Server Monitoring Section --}}
<div class="relative overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 py-24 sm:py-32"
     x-data="{ shown: false }"
     x-intersect.once.half="shown = true">
    {{-- Background grid pattern --}}
    <div class="absolute inset-0 opacity-10">
        <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="server-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="0.5" class="text-gray-400"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#server-grid)"/>
        </svg>
    </div>
    {{-- Glowing orbs --}}
    <div class="absolute top-1/4 left-1/4 h-64 w-64 rounded-full bg-green-500/10 blur-3xl"></div>
    <div class="absolute bottom-1/4 right-1/4 h-96 w-96 rounded-full bg-red-500/10 blur-3xl"></div>

    <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-16 gap-y-16 lg:mx-0 lg:max-w-none lg:grid-cols-2 lg:items-center">
            {{-- Animated Server Visualization --}}
            <div :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-12'"
                 class="transition-all duration-700 delay-300 ease-out order-2 lg:order-1">
                <div class="relative rounded-2xl bg-gray-800/50 backdrop-blur-sm p-8 ring-1 ring-white/10" style="animation: serverGlow 3s ease-in-out infinite;">
                    {{-- Server Header --}}
                    <div class="flex items-center justify-between border-b border-gray-700 pb-4 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                <div class="absolute inset-0 h-3 w-3 rounded-full bg-green-500 animate-ping"></div>
                            </div>
                            <span class="font-mono text-sm text-gray-300">production-server-01</span>
                        </div>
                        <span class="text-xs text-gray-500">Ubuntu 24.04 LTS</span>
                    </div>

                    {{-- Metrics Grid --}}
                    <div class="grid grid-cols-2 gap-6">
                        {{-- CPU --}}
                        <div class="rounded-xl bg-gray-900/50 p-4" style="animation: metricFloat 4s ease-in-out infinite;">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">CPU</span>
                                <span class="font-mono text-lg text-green-400">27%</span>
                            </div>
                            <div class="flex items-end gap-1 h-12">
                                @for($i = 0; $i < 8; $i++)
                                    <div class="flex-1 bg-gray-700 rounded-t relative overflow-hidden">
                                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-green-500 to-green-400 rounded-t"
                                             style="animation: cpuPulse 2s ease-in-out infinite; animation-delay: {{ $i * 0.1 }}s; height: {{ 30 + rand(20, 50) }}%;"></div>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        {{-- Memory --}}
                        <div class="rounded-xl bg-gray-900/50 p-4" style="animation: metricFloat 4s ease-in-out infinite 0.5s;">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Memory</span>
                                <span class="font-mono text-lg text-blue-400">6.2 / 8 GB</span>
                            </div>
                            <div class="h-12 flex flex-col justify-center">
                                <div class="h-4 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-500 to-blue-400 rounded-full" style="animation: memoryWave 3s ease-in-out infinite; width: 77%;"></div>
                                </div>
                                <div class="flex justify-between mt-2 text-[10px] text-gray-500">
                                    <span>Used: 6.2 GB</span>
                                    <span>Free: 1.8 GB</span>
                                </div>
                            </div>
                        </div>

                        {{-- Disk --}}
                        <div class="rounded-xl bg-gray-900/50 p-4" style="animation: metricFloat 4s ease-in-out infinite 1s;">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Disk /</span>
                                <span class="font-mono text-lg text-amber-400">78%</span>
                            </div>
                            <div class="h-12 flex flex-col justify-center">
                                <div class="h-4 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-amber-500 to-amber-400 rounded-full" style="animation: diskFill 4s ease-in-out infinite; width: 78%;"></div>
                                </div>
                                <div class="flex justify-between mt-2 text-[10px] text-gray-500">
                                    <span>156 GB / 200 GB</span>
                                    <span>44 GB free</span>
                                </div>
                            </div>
                        </div>

                        {{-- Network --}}
                        <div class="rounded-xl bg-gray-900/50 p-4" style="animation: metricFloat 4s ease-in-out infinite 1.5s;">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Network</span>
                                <span class="font-mono text-lg text-purple-400">eth0</span>
                            </div>
                            <div class="h-12 flex flex-col justify-center gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-gray-500 w-6">↓ RX</span>
                                    <div class="flex-1 h-2 bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full w-1/3 bg-purple-500 rounded-full" style="animation: networkPulse 2s ease-in-out infinite;"></div>
                                    </div>
                                    <span class="text-[10px] text-purple-400 w-16 text-right">12.4 MB/s</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-gray-500 w-6">↑ TX</span>
                                    <div class="flex-1 h-2 bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full w-1/4 bg-green-500 rounded-full" style="animation: networkPulse 2.5s ease-in-out infinite 0.5s;"></div>
                                    </div>
                                    <span class="text-[10px] text-green-400 w-16 text-right">3.2 MB/s</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Load Average --}}
                    <div class="mt-6 pt-4 border-t border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Load Average</span>
                            <div class="flex items-center gap-4 font-mono text-sm">
                                <span class="text-green-400">0.45 <span class="text-[10px] text-gray-500">1m</span></span>
                                <span class="text-green-400">0.62 <span class="text-[10px] text-gray-500">5m</span></span>
                                <span class="text-yellow-400">1.24 <span class="text-[10px] text-gray-500">15m</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Text Content --}}
            <div :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-12'"
                 class="transition-all duration-700 ease-out order-1 lg:order-2">
                <div class="inline-flex items-center gap-2 rounded-full bg-green-500/10 px-4 py-1.5 text-sm font-medium text-green-400 ring-1 ring-green-500/20">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    New Feature
                </div>
                <h2 class="mt-6 text-pretty text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                    Server monitoring.<br>Full visibility.
                </h2>
                <p class="mt-6 text-lg text-gray-400">
                    Monitor your servers with a lightweight agent. Track CPU, memory, disk, network, and load averages in real-time. Get alerted before your servers hit critical thresholds.
                </p>

                <div class="mt-10 space-y-4">
                    <div class="flex gap-4"
                         :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'"
                         style="transition: all 0.5s ease-out 0.2s;">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-500/20 text-green-400 ring-1 ring-green-500/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-white">One-line install</h3>
                            <p class="mt-1 text-sm text-gray-400">Install the agent with a single curl command. No configuration needed.</p>
                        </div>
                    </div>

                    <div class="flex gap-4"
                         :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'"
                         style="transition: all 0.5s ease-out 0.3s;">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-500/20 text-green-400 ring-1 ring-green-500/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-white">Threshold alerts</h3>
                            <p class="mt-1 text-sm text-gray-400">Set custom thresholds for any metric. Get notified when things cross the line.</p>
                        </div>
                    </div>

                    <div class="flex gap-4"
                         :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'"
                         style="transition: all 0.5s ease-out 0.4s;">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-500/20 text-green-400 ring-1 ring-green-500/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-white">Open source agent</h3>
                            <p class="mt-1 text-sm text-gray-400">
                                Audit the code yourself. Lightweight Go binary with minimal footprint.
                                <a href="https://github.com/janyksteenbeek/uppi-server-agent" target="_blank" class="text-green-400 hover:text-green-300 inline-flex items-center gap-1 ml-1">
                                    View on GitHub
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alert Channels Section --}}
<div class="bg-gray-50 py-24 sm:py-32"
     x-data="{ shown: false }"
     x-intersect.once.half="shown = true">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <p class="text-base font-semibold text-red-600">Instant notifications</p>
            <h2 class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                Get alerted your way
            </h2>
            <p class="mt-6 text-lg/8 text-gray-600">
                Choose from multiple notification channels. Mix and match per monitor for the perfect alert setup.
            </p>
        </div>

        <div class="mx-auto mt-16 max-w-4xl">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {{-- Email --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     class="transition-all duration-500 delay-100 ease-out flex flex-col items-center rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md hover:ring-red-200">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-7 w-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">Email</h3>
                    <p class="mt-1 text-center text-xs text-gray-500">Detailed alert emails</p>
                </div>

                {{-- Slack --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     class="transition-all duration-500 delay-150 ease-out flex flex-col items-center rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md hover:ring-red-200">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-purple-100">
                        <svg class="h-7 w-7 text-purple-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">Slack</h3>
                    <p class="mt-1 text-center text-xs text-gray-500">Channel notifications</p>
                </div>

                {{-- Telegram --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     class="transition-all duration-500 delay-200 ease-out flex flex-col items-center rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md hover:ring-red-200">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-7 w-7 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">Telegram</h3>
                    <p class="mt-1 text-center text-xs text-gray-500">Bot messages</p>
                </div>

                {{-- Pushover --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     class="transition-all duration-500 delay-250 ease-out flex flex-col items-center rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md hover:ring-red-200">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-cyan-100">
                        <svg class="h-7 w-7 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">Pushover</h3>
                    <p class="mt-1 text-center text-xs text-gray-500">Push notifications</p>
                </div>

                {{-- SMS (Bird) --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     class="transition-all duration-500 delay-300 ease-out flex flex-col items-center rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md hover:ring-red-200">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">SMS</h3>
                    <p class="mt-1 text-center text-xs text-gray-500">Via Bird</p>
                </div>

                {{-- Webhook --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     class="transition-all duration-500 delay-350 ease-out flex flex-col items-center rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md hover:ring-red-200">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-orange-100">
                        <svg class="h-7 w-7 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">Webhooks</h3>
                    <p class="mt-1 text-center text-xs text-gray-500">Custom integrations</p>
                </div>

                {{-- Mobile App --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     class="transition-all duration-500 delay-400 ease-out flex flex-col items-center rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md hover:ring-red-200 sm:col-span-2 lg:col-span-1">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-900">
                        <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">Mobile App</h3>
                    <p class="mt-1 text-center text-xs text-gray-500">iOS & Android</p>
                </div>

                {{-- More coming --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     class="transition-all duration-500 delay-450 ease-out flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-6">
                    <p class="text-sm font-medium text-gray-500">More coming soon...</p>
                    <a href="https://github.com/janyksteenbeek/uppi" class="mt-2 text-xs text-red-600 hover:text-red-500">Contribute →</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Browser Tests Section --}}
<div class="relative overflow-hidden bg-gradient-to-b from-gray-50 to-white py-24 sm:py-32"
     x-data="{ shown: false }"
     x-intersect.once.half="shown = true">
    {{-- Background decoration --}}
    <div class="absolute inset-0 -z-10">
        <div class="absolute top-1/4 left-0 h-72 w-72 rounded-full bg-red-100 opacity-50 blur-3xl"></div>
        <div class="absolute bottom-1/4 right-0 h-96 w-96 rounded-full bg-red-50 opacity-60 blur-3xl"></div>
    </div>

    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-16 gap-y-16 lg:mx-0 lg:max-w-none lg:grid-cols-2 lg:items-center">
            {{-- Text Content --}}
            <div :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-12'"
                 class="transition-all duration-700 ease-out">
                <div class="inline-flex items-center gap-2 rounded-full bg-red-100 px-4 py-1.5 text-sm font-medium text-red-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                    </svg>
                    Browser Tests
                </div>
                <h2 class="mt-6 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                    Test user flows.<br>Catch issues before users do.
                </h2>
                <p class="mt-6 text-lg text-gray-600">
                    Go beyond simple uptime checks. Create automated browser tests that simulate real user interactions—clicking, typing, navigating—and verify your application works end-to-end.
                </p>

                <div class="mt-10 space-y-6">
                    <div class="flex gap-4"
                         :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-8'"
                         style="transition: all 0.5s ease-out 0.2s;">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Visual Test Builder</h3>
                            <p class="mt-1 text-sm text-gray-600">Build tests step-by-step with an intuitive interface. No coding required.</p>
                        </div>
                    </div>

                    <div class="flex gap-4"
                         :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-8'"
                         style="transition: all 0.5s ease-out 0.3s;">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Screenshots & Snapshots</h3>
                            <p class="mt-1 text-sm text-gray-600">Capture screenshots and HTML snapshots on failure for easy debugging.</p>
                        </div>
                        </div>

                    <div class="flex gap-4"
                         :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-8'"
                         style="transition: all 0.5s ease-out 0.4s;">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Integrated with Monitors</h3>
                            <p class="mt-1 text-sm text-gray-600">Run tests on a schedule as part of your monitoring. Same alerts, same dashboard.</p>
                        </div>
                    </div>
                </div>
                        </div>

            {{-- Test Flow Animation --}}
            <div :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-12'"
                 class="transition-all duration-700 delay-300 ease-out">
                <div class="relative rounded-2xl bg-gray-900 p-6 shadow-2xl ring-1 ring-white/10">
                    {{-- Browser Chrome --}}
                    <div class="flex items-center gap-2 pb-4 border-b border-gray-700">
                        <div class="flex gap-1.5">
                            <div class="h-3 w-3 rounded-full bg-red-500"></div>
                            <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                            <div class="h-3 w-3 rounded-full bg-green-500"></div>
                        </div>
                        <div class="flex-1 ml-4">
                            <div class="flex items-center gap-2 rounded-md bg-gray-800 px-3 py-1.5 text-xs text-gray-400">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                your-app.com/checkout
                            </div>
                        </div>
                        </div>

                    {{-- Test Steps --}}
                    <div class="mt-6 space-y-3">
                        <div class="flex items-center gap-3 rounded-lg bg-green-500/10 p-3 ring-1 ring-green-500/20"
                             x-data="{ animate: false }"
                             x-init="setTimeout(() => animate = true, 800)"
                             :class="animate && shown ? 'opacity-100' : 'opacity-0'"
                             style="transition: opacity 0.3s ease-out;">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500">
                                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-green-400">Visit</div>
                                <div class="text-xs text-gray-500">https://your-app.com/login</div>
                            </div>
                            <div class="text-xs text-gray-500">124ms</div>
                        </div>

                        <div class="flex items-center gap-3 rounded-lg bg-green-500/10 p-3 ring-1 ring-green-500/20"
                             x-data="{ animate: false }"
                             x-init="setTimeout(() => animate = true, 1100)"
                             :class="animate && shown ? 'opacity-100' : 'opacity-0'"
                             style="transition: opacity 0.3s ease-out;">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500">
                                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-green-400">Type</div>
                                <div class="text-xs text-gray-500">#email → user@example.com</div>
                            </div>
                            <div class="text-xs text-gray-500">89ms</div>
                        </div>

                        <div class="flex items-center gap-3 rounded-lg bg-green-500/10 p-3 ring-1 ring-green-500/20"
                             x-data="{ animate: false }"
                             x-init="setTimeout(() => animate = true, 1400)"
                             :class="animate && shown ? 'opacity-100' : 'opacity-0'"
                             style="transition: opacity 0.3s ease-out;">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500">
                                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-green-400">Press</div>
                                <div class="text-xs text-gray-500">Sign In</div>
                            </div>
                            <div class="text-xs text-gray-500">312ms</div>
                        </div>

                        <div class="flex items-center gap-3 rounded-lg bg-green-500/10 p-3 ring-1 ring-green-500/20"
                             x-data="{ animate: false }"
                             x-init="setTimeout(() => animate = true, 1700)"
                             :class="animate && shown ? 'opacity-100' : 'opacity-0'"
                             style="transition: opacity 0.3s ease-out;">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500">
                                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-green-400">Wait for text</div>
                                <div class="text-xs text-gray-500">Welcome back!</div>
                            </div>
                            <div class="text-xs text-gray-500">1.2s</div>
                        </div>

                        <div class="flex items-center gap-3 rounded-lg bg-gray-800 p-3 ring-1 ring-gray-700"
                             x-data="{ animate: false }"
                             x-init="setTimeout(() => animate = true, 2000)"
                             :class="animate && shown ? 'opacity-100' : 'opacity-0'"
                             style="transition: opacity 0.3s ease-out;">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-red-500">
                                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l7.5-7.5 7.5 7.5m-15 6l7.5-7.5 7.5 7.5" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-white">Success</div>
                                <div class="text-xs text-gray-500">Test completed</div>
                            </div>
                            <div class="text-xs text-green-400 font-medium">1.7s total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Features Grid Section --}}
<div class="overflow-hidden bg-white py-24 sm:py-32"
     x-data="{ shown: false }"
     x-intersect.once.half="shown = true">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <h2 class="text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                Everything you need.<br>Nothing you don't.
            </h2>
            <p class="mt-6 text-lg/8 text-gray-600">
                Features you expect from a world-class monitoring service, completely free and open-source.
            </p>
        </div>

        <div class="mx-auto mt-16 max-w-5xl">
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['title' => 'Alert Routing', 'desc' => 'Get notified via mobile app, email, SMS, Slack, Pushover, or Bird.'],
                        ['title' => 'Response Time Tracking', 'desc' => 'Monitor performance trends and catch slowdowns early.'],
                        ['title' => 'Mobile App', 'desc' => 'Native iOS and Android apps for alerts on the go.'],
                        ['title' => 'Status Pages', 'desc' => 'Beautiful public status pages for your users.'],
                        ['title' => 'Custom Intervals', 'desc' => 'Check every minute or set your own schedule.'],
                        ['title' => 'Open Source', 'desc' => 'Self-host or use our hosted version. Your choice.'],
                    ];
                @endphp

                @foreach($features as $index => $feature)
                    <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                         class="transition-all duration-500 ease-out rounded-xl bg-gray-50 p-6 hover:bg-gray-100"
                         style="transition-delay: {{ ($index + 1) * 100 }}ms;">
                        <dt class="text-base font-semibold text-gray-900">{{ $feature['title'] }}</dt>
                        <dd class="mt-2 text-sm text-gray-600">{{ $feature['desc'] }}</dd>
            </div>
                @endforeach
            </dl>
        </div>
    </div>
</div>


<footer class="bg-white">
    <div class="mx-auto max-w-7xl overflow-hidden py-20 px-6 sm:py-24 lg:px-8">
        <nav class="-mb-6 justify-center sm:columns-2 sm:flex sm:justify-center sm:space-x-12"
             aria-label="Footer">
            <div class="pb-6"><a href="https://www.webmethod.nl/juridisch/algemene-voorwaarden"
                                 class="text-sm leading-6 text-gray-600 hover:text-gray-900">Terms</a>
            </div>
            <div class="pb-6"><a href="/privacy"
                                 class="text-sm leading-6 text-gray-600 hover:text-gray-900">Privacy</a></div>
            <div class="pb-6"><a href="https://www.webmethod.nl/juridisch/coordinated-vulnerability-disclosure"
                                 class="text-sm leading-6 text-gray-600 hover:text-gray-900">Coordinated Vulnerability
                    Disclosure</a></div>
            <div class="pb-6"><a href="https://github.com/sponsors/janyksteenbeek"
                                 class="text-sm leading-6 text-gray-600 hover:text-gray-900">Sponsor</a></div>
            <div class=" pb-6"><a href="https://github.com/janyksteenbeek/uppi"
                                  class="text-sm leading-6 text-gray-600 hover:text-gray-900">GitHub</a></div>
            <div class=" pb-6"><a href="https://x.com/janyksteenbeek">𝕏</a></div>
            <a class="pb-6 lg:-mt-4"
               href="https://www.producthunt.com/posts/uppi?embed=true&utm_source=badge-featured&utm_medium=badge&utm_souce=badge-uppi"
               target="_blank"><img
                    src="https://api.producthunt.com/widgets/embed-image/v1/featured.svg?post_id=750291&theme=light"
                    alt="Uppi - Uptime&#0032;monitoring&#0032;and&#0032;alerting&#0044;&#0032;open&#0045;source&#0032;&#0038;&#0032;free | Product Hunt"
                    style="width: 250px; height: 54px;" width="250" height="54"/></a>
        </nav>
        <div class="mt-10 flex justify-center"><a href="https://www.webmethod.nl?utm_source=uppi&utm_medium=footer"
                                                  class="text-gray-400 hover:text-gray-500"><img
                    src="https://www.webmethod.nl/assets/images/logo/logo.png"
                    alt="Webmethod"
                    class="h-5"></a></div>
        <p class="mt-10 text-center text-xs leading-5 text-gray-500">© {{ date('Y') }} Webmethod ·
            KVK 63314061 · BTW-ID NL002401656B67</p></div>
</footer>
</body>
</html>
