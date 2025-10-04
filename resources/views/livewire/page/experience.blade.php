<div class="w-full p-12">
    <div class="header flex items-end justify-between mb-4">
        <div class="title">
            <p class="text-4xl font-bold text-purple-600 mb-4">
                {{ 'Experience' }}
            </p>
{{--            <p class="text-2xl font-light text-gray-400">--}}
{{--                {{ 'So, what have we d' }}--}}
{{--            </p>--}}
        </div>
    </div>
    @foreach($this->experience() as $index => $jerb)
        <livewire:components.experience-entry
            :company="$jerb['company']"
            :location="$jerb['location']"
            :title="$jerb['title']"
            :timeframe="$jerb['timeframe']"
            :bullets="$jerb['bullets']"
            :technologies="$jerb['technologies']"
        />
    @endforeach
</div>
