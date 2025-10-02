<div class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <header class="max-w-5xl mx-auto p-6 flex items-center justify-between">
        <a href="/" class="text-xl font-semibold">{{ $name }}</a>
        <nav class="space-x-4 text-sm">
            <a href="#about" class="hover:underline">About</a>
            <a href="#projects" class="hover:underline">Projects</a>
            <a href="#contact" class="hover:underline">Contact</a>
            <a href="https://github.com/chkltlabs/erikgratz.com" target="_blank" rel="noopener" class="ml-4 inline-block px-3 py-2 bg-black text-white rounded">Repo</a>
        </nav>
    </header>

    <main class="max-w-5xl mx-auto px-6 pb-16">
        <!-- Hero -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center py-12">
            <div>
                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">{{ $name }}</h1>
                <p class="mt-4 text-lg">{{ $tagline }}</p>
                <p class="mt-3 text-slate-600">{{ $intro }}</p>

                <div class="mt-6 flex space-x-3">
                    <a href="#contact" class="px-5 py-3 rounded-md bg-indigo-600 text-white shadow hover:bg-indigo-700">{{ $cta }}</a>
                    <a href="https://github.com/chkltlabs/erikgratz.com" target="_blank" class="px-5 py-3 rounded-md border">Source</a>
                </div>
            </div>

            <div class="p-6 rounded-lg bg-white shadow">
                <h3 class="font-semibold">Quick links</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="https://github.com/chkltlabs" target="_blank" class="hover:underline">GitHub (chkltlabs)</a></li>
                    <li><a href="https://www.linkedin.com/in/erik-gratz-126ba410b" target="_blank" class="hover:underline">LinkedIn</a></li>
                    <li><a href="mailto:erikgratz110@example.com" class="hover:underline">Email</a></li>
                </ul>
            </div>
        </section>

        <!-- About -->
        <section id="about" class="mt-10 bg-white p-6 rounded shadow">
            <h2 class="text-2xl font-semibold">About</h2>
            <p class="mt-3 text-slate-600">
                I build modern web solutions — APIs, websockets experiments, and small games. This site is a playground where ideas get tested.
            </p>
            <p class="mt-2 text-sm text-slate-500">
                (Converted from the public repo as a reference.) — see the repo link above for more.
            </p>
        </section>

        <!-- Projects / Highlights -->
        <section id="projects" class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-4 rounded shadow">
                <h4 class="font-semibold">API experiments</h4>
                <p class="mt-2 text-sm text-slate-600">Tiny APIs and endpoints used for demos.</p>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <h4 class="font-semibold">Websockets</h4>
                <p class="mt-2 text-sm text-slate-600">Realtime experiments and game prototypes.</p>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <h4 class="font-semibold">Misc tools</h4>
                <p class="mt-2 text-sm text-slate-600">Utilities I use for dev and testing.</p>
            </div>
        </section>

        <!-- Contact -->
        <section id="contact" class="mt-10 bg-white p-6 rounded shadow">
            <h2 class="text-2xl font-semibold">Contact</h2>

            @if (session()->has('success'))
                <div class="mt-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit.prevent="submitContact" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-700">Name</label>
                    <input wire:model.defer="contact_name" type="text" class="mt-1 block w-full rounded border px-3 py-2" />
                    @error('contact_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm text-slate-700">Email</label>
                    <input wire:model.defer="contact_email" type="email" class="mt-1 block w-full rounded border px-3 py-2" />
                    @error('contact_email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm text-slate-700">Message</label>
                    <textarea wire:model.defer="contact_message" rows="5" class="mt-1 block w-full rounded border px-3 py-2"></textarea>
                    @error('contact_message') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2 flex items-center space-x-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Send</button>
                    <span class="text-sm text-slate-500">Or email: <a href="mailto:erikgratz110@example.com" class="underline">erikgratz110@example.com</a></span>
                </div>
            </form>
        </section>
    </main>

    <footer class="border-t bg-white">
        <div class="max-w-5xl mx-auto p-6 text-sm text-slate-500">
            © {{ date('Y') }} {{ $name }} — Converted to Livewire. Source: repo.
        </div>
    </footer>
</div>
