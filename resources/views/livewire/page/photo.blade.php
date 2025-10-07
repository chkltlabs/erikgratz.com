<div>
    <div class="w-full p-12">
        <div class="header flex items-end justify-between mb-8">
            <div class="title">
                <p class="text-4xl font-bold text-purple-600 mb-4">
                    Photography
                </p>
                <p class="text-2xl font-light text-gray-400">
                    Light & magic...
                </p>
            </div>
        </div>

        <!-- Tag Filter -->
        <div class="mb-8">
            <div class="flex flex-wrap gap-2">
                @foreach($this->tags as $tag)
                    <button
                        wire:click="filterByTag('{{ $tag }}')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $selectedTag === $tag ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                        {{ ucfirst($tag) }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Photo Gallery Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->filteredPhotos() as $photo)
                <div class="bg-gray-800 rounded-lg overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                    <div class="aspect-w-16 aspect-h-9 bg-gray-700">
                        <img
                            src="{{ $photo['url'] }}"
                            alt="{{ $photo['title'] }}"
                            class="w-full h-64 object-cover cursor-pointer"
                            onclick="openLightbox('{{ $photo['url'] }}', '{{ $photo['title'] }}', '{{ $photo['description'] }}')"
                        />
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-100 mb-2">{{ $photo['title'] }}</h3>
                        <p class="text-gray-400 text-sm mb-3">{{ $photo['description'] }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($photo['tags'] as $tag)
                                <span class="inline-block px-2 py-1 bg-purple-900 bg-opacity-30 text-purple-300 text-xs rounded-md border border-purple-700">
                                    {{ ucfirst($tag) }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        @if(empty($this->filteredPhotos))
            <div class="text-center py-12">
                <p class="text-gray-400 text-lg">No photos found in this tag.</p>
            </div>
        @endif
    </div>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="fixed inset-0 bg-black/70 hidden z-50 flex items-center justify-center p-4">
        <div class="relative max-w-4xl max-h-full">
            <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
                ×
            </button>
            <img id="lightbox-image" src="" alt="" class="max-w-full max-h-screen object-contain">
            <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white p-4">
                <h3 id="lightbox-title" class="text-lg font-semibold mb-1"></h3>
                <p id="lightbox-description" class="text-gray-300 text-sm"></p>
            </div>
        </div>
    </div>

    <script>
        function openLightbox(url, title, description) {
            document.getElementById('lightbox').classList.remove('hidden');
            document.getElementById('lightbox-image').src = url;
            document.getElementById('lightbox-image').alt = title;
            document.getElementById('lightbox-title').textContent = title;
            document.getElementById('lightbox-description').textContent = description;
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close lightbox when clicking outside the image
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });

        // Close lightbox with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
    </script>
</div>
