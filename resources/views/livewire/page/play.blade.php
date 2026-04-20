<div class="max-w-xl mx-auto px-4 py-12 text-gray-200" x-data="{ isShowing: true }">
    <p class="text-sm text-gray-400 mb-6">Local playground (set <code class="text-purple-400">PLAYGROUND_ENABLED=true</code> to access).</p>
    <div @click="isShowing = !isShowing" class="cursor-pointer select-none mb-4">
        <h4 class="text-xl text-purple-400">click me</h4>
    </div>
    <div x-show="isShowing" x-transition class="mb-4 p-4 bg-gray-800 rounded-lg cursor-pointer" @click="isShowing = !isShowing">
        <h4 class="text-lg">click me</h4>
    </div>
    <div x-show="!isShowing" x-transition class="p-4 bg-gray-800 rounded-lg cursor-pointer" @click="isShowing = !isShowing">
        <h4 class="text-lg text-green-400">NOICE</h4>
    </div>
</div>
