{{--
    Pulse — auth banner.
    Calm editorial paper panel: logo top, big tagline middle.
--}}
<div class="hidden lg:flex flex-col justify-between w-full relative overflow-hidden"
     style="background: var(--pulse-bg); color: var(--pulse-ink); padding: 56px 64px;">

    {{-- Faint ruler running the height of the column --}}
    <div aria-hidden="true"
         style="position: absolute; top: 0; bottom: 0; left: 32px; width: 1px;
                background: linear-gradient(to bottom,
                    transparent 0,
                    var(--pulse-line) 8%,
                    var(--pulse-line) 92%,
                    transparent 100%);"></div>

    {{-- Top: logo --}}
    <div class="flex items-start">
        <img src="{{ asset('logo.svg') }}" alt="Uppi" style="height: 28px;">
    </div>

    {{-- Middle: tagline --}}
    <div style="max-width: 600px;">
        <h1 style="font-family: var(--pulse-display); font-weight: 400;
                   font-size: clamp(48px, 6vw, 88px); line-height: 0.98;
                   letter-spacing: -0.035em; margin: 0; color: var(--pulse-ink);">
            It will break.<br>
            You&rsquo;ll know <em style="font-style: italic; color: var(--pulse-red); font-weight: 300;">first.</em>
        </h1>
    </div>

    {{-- Bottom: spacer so flex-justify-between distributes evenly --}}
    <div></div>
</div>
