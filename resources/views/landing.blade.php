<x-guest-layout title="Convert Images to Professional SVG">

    <div class="relative">
        <!-- Hero -->
        <section class="hero-gradient relative overflow-hidden">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.4) 0, transparent 30%), radial-gradient(circle at 80% 70%, rgba(255,255,255,0.3) 0, transparent 25%), radial-gradient(circle at 60% 20%, rgba(255,255,255,0.25) 0, transparent 20%);"></div>
            <div class="relative mx-auto max-w-5xl px-6 py-20 text-center lg:py-28">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-medium text-indigo-100">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    Raster-to-vector in seconds
                </div>
                <h1 class="mx-auto max-w-3xl text-4xl font-bold tracking-tight text-white lg:text-6xl">
                    Convert Images to Professional <span class="text-indigo-200">SVG</span>
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-indigo-100">
                    Convert PNG, JPG and WEBP images into optimized SVG vector files — with optional AI background removal.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 shadow-lg transition hover:bg-indigo-50">
                        <x-icon name="converter" class="h-5 w-5" />
                        Convert Image
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        Sign in
                    </a>
                </div>

                <div class="mx-auto mt-12 grid max-w-3xl grid-cols-2 gap-4 text-left sm:grid-cols-4">
                    <div class="rounded-xl bg-white/10 p-4 text-white">
                        <p class="text-2xl font-bold">SVG</p>
                        <p class="text-xs text-indigo-200">Vector output</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 text-white">
                        <p class="text-2xl font-bold">100%</p>
                        <p class="text-xs text-indigo-200">Scaleable &amp; crisp</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 text-white">
                        <p class="text-2xl font-bold">4</p>
                        <p class="text-xs text-indigo-200">Input formats</p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 text-white">
                        <p class="text-2xl font-bold">AI</p>
                        <p class="text-xs text-indigo-200">Background removal</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto max-w-6xl px-6 py-16">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-slate-900">Everything you need for clean vectors</h2>
                <p class="mt-2 text-slate-500">A complete raster-to-vector pipeline designed for professionals.</p>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['name' => 'file', 'title' => 'Image to SVG', 'desc' => 'Genuine raster-to-vector tracing — not a raster embedded inside an SVG. Real, scaleable paths.'],
                        ['name' => 'sparkles', 'title' => 'AI Background Removal', 'desc' => 'Remove backgrounds before vectorizing. Provider-agnostic architecture ready for any AI service.'],
                        ['name' => 'settings', 'title' => 'SVG Optimization', 'desc' => 'Every SVG is validated and optimized to remove bloat while preserving structure and viewBox.'],
                        ['name' => 'clock', 'title' => 'Fast Processing', 'desc' => 'Heavy conversion work runs on background queues so large files never block your upload.'],
                        ['name' => 'shield', 'title' => 'Secure Files', 'desc' => 'Uploads are validated by real MIME and image structure, stored privately, and served through safe controllers.'],
                        ['name' => 'code', 'title' => 'Developer API', 'desc' => 'A clean REST API with token authentication, rate limiting and consistent JSON responses.'],
                    ];
                @endphp
                @foreach ($features as $f)
                    <div class="card p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="mb-4 inline-flex rounded-lg bg-indigo-100 p-3 text-indigo-600"><x-icon name="{{ $f['name'] }}" class="h-6 w-6" /></div>
                        <h3 class="text-base font-semibold text-slate-900">{{ $f['title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- How it works -->
        <section class="bg-white border-y border-slate-200 py-16">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-slate-900">How it works</h2>
                </div>
                <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-3">
                    @foreach ([
                        ['1', 'Upload your image', 'Drag and drop a PNG, JPG or WEBP. Your file is validated for type, size and dimensions.'],
                        ['2', 'Choose your options', 'Pick color or black & white, quality level, path optimization — and optionally remove the background.'],
                        ['3', 'Download your SVG', 'We trace and optimize the vector in the background, then you download a clean, scaleable SVG.'],
                    ] as [$num, $title, $desc])
                        <div class="text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-600 text-lg font-bold text-white">{{ $num }}</div>
                            <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Pricing (architecture-ready) -->
        <section class="mx-auto max-w-6xl px-6 py-16">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-slate-900">Pricing</h2>
                <p class="mt-2 text-slate-500">Start free, upgrade when your conversions grow.</p>
            </div>
            <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-3">
                @forelse ($plans as $plan)
                    <div class="card p-6 {{ $plan->slug === 'pro' ? 'border-indigo-300 ring-2 ring-indigo-100' : '' }}">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                            @if ($plan->slug === 'pro')
                                <span class="badge badge-info">Most popular</span>
                            @endif
                        </div>
                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            ${{ number_format($plan->price, 2) }}<span class="text-sm font-normal text-slate-400">/month</span>
                        </p>
                        <ul class="mt-4 space-y-2 text-sm text-slate-600">
                            @forelse ($plan->features ?? [] as $feature)
                                <li class="flex items-center gap-2">
                                    <x-icon name="check" class="h-4 w-4 text-emerald-500" />
                                    {{ $feature }}
                                </li>
                            @empty
                                <li class="text-slate-400">Contact us for details.</li>
                            @endforelse
                        </ul>
                        <a href="{{ route('register') }}" class="btn {{ $plan->slug === 'pro' ? 'btn-primary' : 'btn-secondary' }} mt-6 w-full">
                            Choose {{ $plan->name }}
                        </a>
                    </div>
                @empty
                    <p class="col-span-full text-center text-slate-400">No plans available yet.</p>
                @endforelse
            </div>
        </section>

        <!-- FAQ -->
        <section class="bg-white border-t border-slate-200 py-16">
            <div class="mx-auto max-w-3xl px-6">
                <h2 class="text-center text-3xl font-bold text-slate-900">Frequently asked questions</h2>
                <div class="mt-8 space-y-4">
                    @foreach ([
                        ['Is the SVG really a vector?', 'Yes. We run an actual raster-to-vector tracing engine that produces real bezier-free path geometry from your pixels — the SVG scales cleanly without blur.'],
                        ['Does it support transparency?', 'PNG transparency is preserved. After removing a background, the remaining subject is traced onto a transparent canvas.'],
                        ['How long does conversion take?', 'Light images finish in seconds. Larger files are processed on a background queue so your request is never blocked.'],
                        ['Can I use the API?', 'The developer API is available on Pro plans and above, protected with personal access tokens and sensible rate limits.'],
                    ] as [$q, $a])
                        <div class="card overflow-hidden">
                            <details class="group">
                                <summary class="flex cursor-pointer items-center justify-between px-5 py-4 text-sm font-semibold text-slate-800">
                                    {{ $q }}
                                    <span class="text-slate-400 group-open:rotate-45 transition">&plus;</span>
                                </summary>
                                <p class="px-5 pb-4 text-sm text-slate-500">{{ $a }}</p>
                            </details>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="hero-gradient py-16 text-center text-white">
            <div class="mx-auto max-w-3xl px-6">
                <h2 class="text-3xl font-bold">Ready to create vectors?</h2>
                <p class="mt-3 text-indigo-100">Join today and start converting your images to professional SVG files.</p>
                <a href="{{ route('register') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 shadow-lg transition hover:bg-indigo-50">
                    Get started free
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </section>
    </div>
</x-guest-layout>