<x-auth-layout title="{{ __('Verify email') }}">
    <div class="bg-gray-800/80 rounded-lg p-8 shadow-lg border border-gray-700 text-center">
        <h1 class="text-xl font-semibold text-white mb-4">{{ __('Verify your email') }}</h1>
        <p class="text-gray-400 text-sm mb-6">{{ __('Thanks for signing up! Please verify using the link we emailed you, or request another below.') }}</p>
        @if (session('status') == 'verification-link-sent')
            <p class="text-green-400 text-sm mb-4">{{ __('A new verification link has been sent.') }}</p>
        @endif
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                        class="py-2 px-4 bg-purple-600 hover:bg-purple-500 rounded-lg text-white text-sm font-medium transition">
                    {{ __('Resend verification email') }}
                </button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="py-2 px-4 border border-gray-600 rounded-lg text-gray-300 text-sm hover:bg-gray-800 transition">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</x-auth-layout>
