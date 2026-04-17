@props(['fixed' => false])
<div class="{{ $fixed ? 'fixed -top-12 -right-10 opacity-40' : 'relative' }} mx-auto ">
    <div
        class="absolute lg:right-4 -top-44 lg:-top-32 {{ $fixed ? 'h-96' : 'h-24' }} w-[46rem] transform-gpu md:right-0 bg-linear-[115deg] from-primary-200 from-28% via-primary-600 via-70% to-primary-700 rotate-[-10deg] rounded-full blur-3xl"></div>
</div>
