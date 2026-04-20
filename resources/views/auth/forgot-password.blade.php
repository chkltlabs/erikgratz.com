<x-auth-layout title="{{ __('Forgot password') }}">
    <div class="bg-gray-800/80 rounded-lg p-8 shadow-lg border border-gray-700">
        <h1 class="text-xl font-semibold text-white mb-2">{{ __('Forgot password') }}</h1>
        <p class="text-gray-400 text-sm mb-6">{{ __('Enter your account email. We will send a reset link.') }}</p>
        @if (session('status'))
            <p class="text-green-400 text-sm mb-4">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm text-gray-300 mb-1">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg bg-gray-900 border border-gray-600 px-3 py-2 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"/>
                @error('email')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full py-2 px-4 bg-purple-600 hover:bg-purple-500 rounded-lg text-white font-medium transition">
                {{ __('Email password reset link') }}
            </button>
        </form>
    </div>
</x-auth-layout>
