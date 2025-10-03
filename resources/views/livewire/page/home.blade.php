<div>
    <section class="py-5 md:py-8">
        <div class="container max-w-screen-xl mx-auto px-4">
            <div class="text-center">
                <div class="flex justify-center mb-16">
                    <img class="w-64 h-64 rounded-full" src="{{ $heroImage }}" alt="Image">
                </div>
                <h6 class="font-medium text-gray-600 text-lg md:text-2xl uppercase mb-8">Erik Virgil Gratz</h6>
                <livewire:components.title-box/>
                <p class="font-normal text-gray-600 text-md md:text-xl mb-16">{{ $heroSub }}</p>
{{--                <div class="container max-w-screen-xl mx-auto px-4 my-10">--}}
{{--                    <div class="text-center">--}}
{{--                        <div class="flex items-center justify-center space-x-8">--}}
{{--                            <a href="https://www.twitter.com/chkltlabs"--}}
{{--                               class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">--}}
{{--                                <i class="text-gray-500 hover:text-gray-800 transition ease-in-out duration-500 fab fa-twitter"></i>--}}
{{--                            </a>--}}
{{--                            <a href="https://laracasts.com/@chkltlabs"--}}
{{--                               class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">--}}
{{--                                <i class="text-gray-500 hover:text-gray-700 transition ease-in-out duration-500 fab fa-laravel"></i>--}}
{{--                            </a>--}}
{{--                            <a href="https://www.facebook.com/Sarcastic.Badger"--}}
{{--                               class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">--}}
{{--                                <i class="text-gray-500 hover:text-gray-700 transition ease-in-out duration-500 fab fa-facebook"></i>--}}
{{--                            </a>--}}
{{--                            <a href="mailto:erikgratz110@gmail.com"--}}
{{--                               class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">--}}
{{--                                <i class="text-gray-500 hover:text-gray-700 transition ease-in-out duration-500 fas fa-at"></i>--}}
{{--                            </a>--}}
{{--                            <a href="http://https://www.instagram.com/grotz110/tagged"--}}
{{--                               class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">--}}
{{--                                <i class="text-gray-500 hover:text-gray-700 transition ease-in-out duration-500 fab fa-instagram"></i>--}}
{{--                            </a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
                <a href="https://calendly.com/erikgratz/30min"
                   class="px-7 py-3 md:px-9 md:py-4
                       font-medium md:font-semibold
                       bg-gray-700 text-gray-50 text-sm rounded-full
                       hover:bg-purple-600 hover:text-white
                       transition ease-linear duration-500">Schedule a Call</a>
            </div>
        </div>
    </section>
    <section class="py-5 md:py-8">
        <div class="container max-w-screen-xl mx-auto px-4">
            <h1 class="font-medium text-gray-300 text-3xl md:text-4xl mb-5">Experience</h1>
            <!-- web version -->
            <div class="flex flex-col lg:flex-row justify-between">
                <div class=" hidden lg:block mb-16 md:mb-0">
                    <h6 class="font-medium text-gray-400 text-base uppercase">Company</h6>
                    @foreach ($this->jerbs as $key => $jerb)
                        <p
                            class="font-semibold text-gray-300 text-base"
                            :key="{{$key}}">
                            {{ $jerb['company'] }}
                            <span class="font-normal text-gray-400"> / {{ $jerb['location'] }}</span>
                        </p>
                    @endforeach
{{--                    <p v-for="jerb, key in jerbs"--}}
{{--                       v-bind:key="key"--}}
{{--                       class="font-semibold text-gray-300 text-base"--}}
{{--                    >{{jerb.company}}--}}
{{--                        <span class="font-normal text-gray-400"> / {{jerb.location}}</span>--}}
{{--                    </p>--}}
                    <!-- <p class="font-semibold text-gray-600 text-base">Pocketnest
                        <span class="font-normal text-gray-300">/ Detroit, MI -> Fully Remote</span>
                    </p>
                    <p class="font-semibold text-gray-600 text-base">Sonic Boom Wellness
                        <span class="font-normal text-gray-300">/ San Diego, CA -> Fully Remote</span>
                    </p>
                    <p class="font-semibold text-gray-600 text-base">Internet Things -> The Media Lab
                        <span class="font-normal text-gray-300">/ San Diego, CA</span>
                    </p> -->
                </div>
                <div class=" hidden lg:block mb-16 md:mb-0">
                    <h6 class="font-medium text-gray-400 text-base uppercase">Position</h6>
                    @foreach ($this->jerbs as $key => $jerb)
                        <p
                            class="font-semibold text-gray-300 text-base"
                            :key="{{$key}}">
                            {{ $jerb['title'] }}
                        </p>
                    @endforeach
{{--                    <p v-for="jerb, key in jerbs"--}}
{{--                       v-bind:key="key"--}}
{{--                       class="font-normal text-gray-400 text-base">{{jerb.title}}</p>--}}
                    <!-- <p class="font-normal text-gray-400 text-base">Senior Software Engineer, Backend</p>
                    <p class="font-normal text-gray-400 text-base">Backend Developer</p>
                    <p class="font-normal text-gray-400 text-base">PHP Developer -> Lead PHP Developer</p> -->
                </div>
                <div class=" hidden lg:block">
                    <h6 class="font-medium text-gray-400 text-base uppercase">Year</h6>
                    @foreach ($this->jerbs as $key => $jerb)
                        <p
                            class="font-semibold text-gray-300 text-base"
                            :key="{{$key}}">
                            {{ $jerb['timeframe'] }}
                        </p>
                    @endforeach
{{--                    <p v-for="jerb, key in jerbs"--}}
{{--                       v-bind:key="key"--}}
{{--                       class="font-normal text-gray-400 text-base">{{jerb.timeframe}}</p>--}}
                </div>
                <!-- mobile version -->
                <div class="space-y-2 mb-16 md:mb-0 lg:hidden">
                    <h6 class="font-medium text-gray-300 text-base">
                        Company / Location <br class="md:hidden">/ Title / Timeframe</h6>
                    @foreach ($this->jerbs as $key => $jerb)
                        <p
                            class="font-semibold text-gray-300 text-base"
                            :key="{{$key}}">
                            {{ $jerb['company'] }}
                            <span class="font-normal text-gray-400"> / {{$jerb['location']}} </span>
                            <br class="md:hidden"/>
                            <span class="font-normal text-gray-400"> / {{$jerb['title']}} </span>
                            <span class="font-normal text-gray-400"> / {{$jerb['timeframe']}}</span>
                        </p>
                    @endforeach
{{--                    <p v-for="jerb, key in jerbs"--}}
{{--                       v-bind:key="key"--}}
{{--                       class="font-semibold text-gray-300 text-base">{{jerb.company}}--}}
{{--                        <span class="font-normal text-gray-400"> / {{jerb.location}} </span>--}}
{{--                        <br class="md:hidden"/>--}}
{{--                        <span class="font-normal text-gray-400"> / {{jerb.title}} </span>--}}
{{--                        <span class="font-normal text-gray-400"> / {{jerb.timeframe}}</span>--}}
{{--                    </p>--}}
                </div>
            </div>
        </div>
    </section>
</div>
