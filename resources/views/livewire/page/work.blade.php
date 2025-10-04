<div class="w-full p-12">
    <div class="header flex items-end justify-between mb-4">
        <div class="title">
            <p class="text-4xl font-bold text-purple-600 mb-4">
                {{ 'Portfolio' }}
            </p>
            <p class="text-2xl font-light text-gray-400">
                {{ 'Let\'s talk about projects...' }}
            </p>
        </div>
    </div>
    @foreach($this->portfolioItems() as $index => $portfolio)
        <livewire:components.portfolio-entry
            :title="$portfolio['title']"
            :text="$portfolio['text']"
            :imgUrl="$portfolio['imgUrl']"
            :index="$index"
            :link="$portfolio['link'] ?? null"
            :linkText="$portfolio['linkText'] ?? null"
        />
    @endforeach
{{--    <portfolio-entry v-for="(portfolio, index) in portfolioThings"--}}
{{--                     :key="index"--}}
{{--                     :index="index"--}}
{{--                     :img-url="portfolio.imgUrl"--}}
{{--                     :text="portfolio.text"--}}
{{--                     :title="portfolio.title"--}}
{{--                     :link="portfolio.link"--}}
{{--                     :link-text="portfolio.linkText"--}}
{{--    />--}}
</div>
