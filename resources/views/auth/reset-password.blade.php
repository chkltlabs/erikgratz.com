<x-auth-layout title="{{ __('Reset password') }}">
    <div class="bg-gray-800/80 rounded-lg p-8 shadow-lg border border-gray-700">
        <h1 class="text-xl font-semibold text-white mb-6">{{ __('Reset password') }}</h1>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="block text-sm text-gray-300 mb-1">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                       class="w-full rounded-lg bg-gray-900 border border-gray-600 px-3 py-2 text-white focus:ring-2 focus:ring-purple-500"/>
                @error('email')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-sm text-gray-300 mb-1">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="w-full rounded-lg bg-gray-900 border border-gray-600 px-3 py-2 text-white focus:ring-2 focus:ring-purple-500"/>
                @error('password')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm text-gray-300 mb-1">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                       class="w-full rounded-lg bg-gray-900 border border-gray-600 px-3 py-2 text-white focus:ring-2 focus:ring-purple-500"/>
            </div>
            <button type="submit"
                    class="w-full py-2 px-4 bg-purple-600 hover:bg-purple-500 rounded-lg text-white font-medium transition">
                {{ __('Reset password') }}
            </button>
        </form>
    </div>
</x-auth-layout>
