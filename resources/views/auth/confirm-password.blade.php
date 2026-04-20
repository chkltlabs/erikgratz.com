<x-auth-layout title="{{ __('Confirm password') }}">
    <div class="bg-gray-800/80 rounded-lg p-8 shadow-lg border border-gray-700">
        <h1 class="text-xl font-semibold text-white mb-2">{{ __('Confirm password') }}</h1>
        <p class="text-gray-400 text-sm mb-6">{{ __('Please enter your password to continue.') }}</p>
        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf
            <div>
                <label for="password" class="block text-sm text-gray-300 mb-1">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-lg bg-gray-900 border border-gray-600 px-3 py-2 text-white focus:ring-2 focus:ring-purple-500"/>
                @error('password')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full py-2 px-4 bg-purple-600 hover:bg-purple-500 rounded-lg text-white font-medium transition">
                {{ __('Confirm') }}
            </button>
        </form>
    </div>
</x-auth-layout>
