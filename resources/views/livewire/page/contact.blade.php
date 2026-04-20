<div class="w-full min-h-screen font-sans bg-cover bg-landscape">
    <div class="w-full flex flex-row flex-wrap gap-4 justify-center my-8 px-4">
        <a href="{{ $resumeUrl }}" target="_blank" rel="noopener noreferrer"
           class="px-4 py-2 rounded-lg bg-purple-600/80 text-white hover:bg-purple-500 transition text-center text-sm md:text-base">
            My Resume
        </a>
        <a href="mailto:{{ $email }}"
           class="px-4 py-2 rounded-lg bg-purple-600/80 text-white hover:bg-purple-500 transition text-center text-sm md:text-base">
            Direct Email
        </a>
        <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}"
           class="px-4 py-2 rounded-lg bg-purple-600/80 text-white hover:bg-purple-500 transition text-center text-sm md:text-base">
            Call me!
        </a>
    </div>
    <div class="container flex items-center justify-center flex-1 h-auto mx-auto pb-16 px-4">
        <div class="w-full max-w-lg">
            <div class="leading-loose">
                <form wire:submit="submit" class="max-w-sm p-10 m-auto bg-white/10 rounded shadow-xl backdrop-blur-sm">
                    <p class="mb-8 text-2xl font-light text-center text-white">
                        Say Something Kind?
                    </p>
                    @if (session('success'))
                        <div class="rounded-lg border border-transparent mb-2 text-base text-green-200 bg-green-900/40 font-light text-center p-2">
                            <strong>{{ session('success') }}</strong>
                        </div>
                    @endif
                    <div class="mb-2">
                        <input type="text" wire:model="contact" id="contact"
                               class="rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="email or phone" autocomplete="contact"/>
                        @error('contact')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <input type="text" wire:model="name" id="name"
                               class="rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="name" autocomplete="name"/>
                        @error('name')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <textarea wire:model="message" id="message" rows="4"
                                  class="rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                                  placeholder="..."></textarea>
                        @error('message')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center justify-between mt-4">
                        <button type="submit"
                                class="py-2 px-4 bg-purple-600 hover:bg-purple-800 focus:ring-purple-500 focus:ring-offset-purple-200 text-white w-full transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-lg">
                            {{ $submitButtonText }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
