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

        <div class="mx-auto mt-16 max-w-5xl">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                {{-- Monitor Type Cards --}}
                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
                     class="transition-all duration-700 delay-100 ease-out group relative rounded-2xl bg-gradient-to-b from-gray-50 to-white p-8 ring-1 ring-gray-200 hover:ring-red-200 hover:shadow-lg hover:shadow-red-100/50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-500 text-white shadow-lg shadow-red-500/30 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>
                    <h3 class="mt-6 text-lg font-semibold text-gray-900">HTTP Monitoring</h3>
                    <p class="mt-2 text-sm text-gray-600">Monitor websites, APIs, and webhooks. Check status codes, response times, and content validation.</p>
                </div>

                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
                     class="transition-all duration-700 delay-200 ease-out group relative rounded-2xl bg-gradient-to-b from-gray-50 to-white p-8 ring-1 ring-gray-200 hover:ring-red-200 hover:shadow-lg hover:shadow-red-100/50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-500 text-white shadow-lg shadow-red-500/30 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />
                        </svg>
                    </div>
                    <h3 class="mt-6 text-lg font-semibold text-gray-900">TCP Monitoring</h3>
                    <p class="mt-2 text-sm text-gray-600">Monitor databases, mail servers, and any TCP service. Verify port availability and connection health.</p>
                </div>

                <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
                     class="transition-all duration-700 delay-300 ease-out group relative rounded-2xl bg-gradient-to-b from-gray-50 to-white p-8 ring-1 ring-gray-200 hover:ring-red-200 hover:shadow-lg hover:shadow-red-100/50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-500 text-white shadow-lg shadow-red-500/30 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <h3 class="mt-6 text-lg font-semibold text-gray-900">Cron Monitoring</h3>
                    <p class="mt-2 text-sm text-gray-600">Track scheduled tasks and background jobs. Get alerted if your cron jobs don't check in on time.</p>
                </div>
            </div>
        </div>

        {{-- Stats Animation --}}
        <div class="mx-auto mt-20 max-w-4xl"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
             class="transition-all duration-700 delay-500 ease-out">
            <div class="rounded-3xl bg-gray-900 p-8 sm:p-12">
                <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
                    <div class="text-center" x-data="{ count: 0 }" x-init="shown && setTimeout(() => { let interval = setInterval(() => { if(count < 99) count++; else clearInterval(interval); }, 20); }, 600)">
                        <div class="text-4xl font-bold text-white" x-text="count + '.9%'">0%</div>
                        <div class="mt-2 text-sm text-gray-400">Uptime SLA</div>
                    </div>
                    <div class="text-center" x-data="{ count: 0 }" x-init="shown && setTimeout(() => { let interval = setInterval(() => { if(count < 60) count++; else clearInterval(interval); }, 30); }, 700)">
                        <div class="text-4xl font-bold text-white"><span x-text="count">0</span>s</div>
                        <div class="mt-2 text-sm text-gray-400">Check Interval</div>
                    </div>
                    <div class="text-center" x-data="{ count: 0 }" x-init="shown && setTimeout(() => { let interval = setInterval(() => { if(count < 10) count++; else clearInterval(interval); }, 100); }, 800)">
                        <div class="text-4xl font-bold text-white"><span x-text="count">0</span>+</div>
                        <div class="mt-2 text-sm text-gray-400">Alert Channels</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-red-500">Free</div>
                        <div class="mt-2 text-sm text-gray-400">Forever</div>
                    </div>
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
