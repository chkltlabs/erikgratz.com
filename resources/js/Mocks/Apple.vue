<template>
        <nav class="bg-dark-black top-0 absolute z-50 w-full flex flex-wrap items-center justify-between px-2 py-3">
            <div class="container px-4 mx-auto flex flex-wrap items-center justify-between">
                <div class="w-full relative flex justify-between lg:w-auto lg:static lg:block lg:justify-start">
                    <a href="#home"
                       class="text-sm font-bold leading-relaxed inline-block mr-4 py-2 whitespace-nowrap uppercase text-white"
                    >Home</a>
                    <button
                        class="cursor-pointer text-xl leading-none px-3 py-1 border-solid border-transparent rounded bg-transparent block lg:hidden lg:outline-none focus:outline-none"
                        type="button"
                        @click="toggleNavbar('example-collapse-id')"
                    >
                        <i class="text-white fas fa-bars"></i>
                    </button>
                </div>
                    <div class="lg:flex flex-grow items-center bg-white lg:bg-opacity-0 lg;shadow-none hidden"
                         id="example-collapse-id">
                        <ul class="flex flex-col lg:flex-row list-none mr-auto">
                            <li class="flex items-center">
                                <a href="#ipad"
                                   class="lg:text-white lg:hover:text-gray-300 text-gray-800 px-3 py-4 lg:py-2 flex items-center text-xs uppercase font-bold">
                                    <i class="lg:text-gray-300 text-gray-500 fas fa-tablet text-lg leading-lg mr-2" />
                                    Ipad
                                </a>
                            </li>
                            <li class="flex items-center">
                                <a href="#macbook"
                                   class="lg:text-white lg:hover:text-gray-300 text-gray-800 px-3 py-4 lg:py-2 flex items-center text-xs uppercase font-bold">
                                    <i class="lg:text-gray-300 text-gray-500 fas fa-laptop text-lg leading-lg mr-2" />
                                    Macbook
                                </a>
                            </li>
                            <li class="flex items-center">
                                <a href="#team"
                                   class="lg:text-white lg:hover:text-gray-300 text-gray-800 px-3 py-4 lg:py-2 flex items-center text-xs uppercase font-bold">
                                    <i class="lg:text-gray-300 text-gray-500 fas fa-users text-lg leading-lg mr-2" />
                                    Team
                                </a>
                            </li>
                            <li class="flex items-center">
                                <a href="#contact"
                                   class="lg:text-white lg:hover:text-gray-300 text-gray-800 px-3 py-4 lg:py-2 flex items-center text-xs uppercase font-bold">
                                    <i class="lg:text-gray-300 text-gray-500 fas fa-address-card text-lg leading-lg mr-2" />
                                    Contact Us
                                </a>
                            </li>
                        </ul>
                        <ul class="flex flex-col lg:flex-row list-none lg:ml-auto">
                            <li class="flex items-center">
                                <a href=""  class="lg:text-white lg:hover:text-gray-300 text-gray-800 px-3 py-4 lg:py-2 flex items-center text-xs uppercase font-bold">
                                    <i class="lg:text-gray-300 text-gray-500 fab fa-facebook text-lg leading-lg"></i>
                                    <span class="lg:hidden inline-block ml-2"></span>
                                </a>
                            </li>
                            <li class="flex items-center">
                                <a href=""  class="lg:text-white lg:hover:text-gray-300 text-gray-800 px-3 py-4 lg:py-2 flex items-center text-xs uppercase font-bold">
                                    <i class="lg:text-gray-300 text-gray-500 fab fa-twitter text-lg leading-lg"></i>
                                    <span class="lg:hidden inline-block ml-2"></span>
                                </a>
                            </li>
                            <li class="flex items-center">
                                <button
                                    class="bg-white text-gray-800 active:bg-gray-100 text-xs font-bold uppercase px-4 py-2 rounded shadow hover:shadow-md outline-none focus:outline-none lg:mr-1 lg:mb-0 ml-3 mb-3"
                                    type="button">
                                    <i class="fas fa-arrow-circle-down mr-2"></i>
                                    Buy Macbook Pro</button>
                            </li>
                        </ul>
                    </div>
            </div>
        </nav>
        <main>
            <div class="bg-dark-black relative pt-16 lg:pb-3 flex content-center items-center justify-center min-h-home-lg" id="home">
                <div class="absolute w-full h-full object-fill flex content-center items-center justify-center">
                    <video
                        src="https://www.apple.com/105/media/us/macbook-pro-13/2020/f2b14406-42ad-405e-bfa0-71b52a0bfd67/anim/chip/large.mp4"
                        autoplay muted class="top-0 bg-center w-full max-w-screen-2xl">
                    </video>
                </div>
            </div>
            <div class="container relative mx-auto hidden md:block">
                <div class="items-center flex flex-wrap">
                    <div class="w-full lg:w-6/12 px-4 ml-auto mr-auto text-center">
                        <div class="pr-12">
                            <h1 class="text-white font-semibold text-5xl -mt-64">  Welcome to Apple</h1>
                        </div>
                    </div>
                </div>
            </div>
            <section class="pb-20 -mt-40 bg-gray-100" id="ipad">
                <div class="container mx-auto px-4">
                    <div class="flex flex-wrap">
                        <div class="lg:pt-12 pt-6 w-full md:w-4/12 px-4 text-center">
                            <div class="relative flex flex-col min-w-0 break-words bg-white w-full mb-8 shadow-lg rounded-lg">
                                <div class="px-4 py-5 flex-auto">
                                    <div class="text-white p-3 text-center inline-flex items-center justify-center w-12 h-12 mb-5 shadow-lg rounded-full bg-red-400">
                                        <i class="fas fa-tablet"></i>
                                    </div>
                                    <h6 class="text-xl font-semibold">iPad Pro</h6>
                                    <p class="mt-2 mb-4 text-gray-600">
                                        Lorem Ipsum is overrated. What people coding frontend really should do is have a stream of consciousness rant about the first thing that comes to mind, like e-waste. God e-waste makes me so angry.
                                        That's why proper disposal and recycling of anything with transistors or a battery is so very, very important. Recycle your mac products with us today.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-4/12 px-4 text-center">
                            <div class="relative flex flex-col min-w-0 break-words bg-white w-full mb-8 shadow-lg rounded-lg">
                                <div class="px-4 py-5 flex-auto">
                                    <div class="text-white p-3 text-center inline-flex items-center justify-center w-12 h-12 mb-5 shadow-lg rounded-full bg-blue-400 ">
                                        <i class="fas fa-laptop"></i>
                                    </div>
                                    <h6 class="text-xl font-semibold">Macbook Pro</h6>
                                    <p class="mt-2 mb-4 text-gray-600">
                                        Lorem Ipsum is overrated. What people coding frontend really should do is have a stream of consciousness rant about the first thing that comes to mind, like e-waste. God e-waste makes me so angry.
                                        That's why proper disposal and recycling of anything with transistors or a battery is so very, very important. Recycle your mac products with us today.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="lg:pt-12 pt-6 w-full md:w-4/12 px-4 text-center">
                            <div class="relative flex flex-col min-w-0 break-words bg-white w-full mb-8 shadow-lg rounded-lg">
                                <div class="px-4 py-5 flex-auto">
                                    <div class="text-white p-3 text-center inline-flex items-center justify-center w-12 h-12 mb-5 shadow-lg rounded-full bg-red-400">
                                        <i class="fas fa-mobile"></i>
                                    </div>
                                    <h6 class="text-xl font-semibold">iPhone</h6>
                                    <p class="mt-2 mb-4 text-gray-600">
                                        Lorem Ipsum is overrated. What people coding frontend really should do is have a stream of consciousness rant about the first thing that comes to mind, like e-waste. God e-waste makes me so angry.
                                        That's why proper disposal and recycling of anything with transistors or a battery is so very, very important. Recycle your mac products with us today.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap item-center mt-32">
                        <div class="w-full md:w-5/12 px-4 mx-auto">
                            <div class="text-gray-600 p-3 text-center inline-flex items-center justify-center w-16 h-16 mb-6 shadow-lg rounded-full bg-gray-200 2xl:bg-purple-500">
                                <i class="fas fa-tablet text-xl"></i>
                            </div>
                            <h3 class="text-3xl mb-2 font-semibold">New iPad Pro.</h3>
                            <p class="text-large font-light leading-relaxed mt-4 text-gray-700">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                Aliquid architecto at debitis doloremque dolores eaque est id, itaque laudantium libero minus molestiae,
                                molestias nemo officia ratione sed tenetur veritatis. Tempora.
                            </p>
                            <p class="text-large font-light leading-relaxed mt-4 text-gray-700">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                Aliquid architecto at debitis doloremque dolores eaque est id, itaque laudantium libero minus molestiae,
                                molestias nemo officia ratione sed tenetur veritatis. Tempora.
                            </p>
                        </div>
                        <div class="w-full md:w-4/12 px-4 mx-auto">
                            <div class="relative flex flex-col min-w-0 break-words w-full mb-6 shadow-lg rounded-lg bg-dark-black">
                                <img
                                    src="https://cdn.vox-cdn.com/thumbor/nGkNQKEJiiJ-EPufnTAfHABsa9U=/1400x1400/filters:format(jpeg)/cdn.vox-cdn.com/uploads/chorus_asset/file/22457892/ipad_pro_2021.jpg"
                                    class="w-full align-middle rounded-t-lg"
                                    alt="...">
                                <blockquote class="relative p-8 mb-4 bg-dark-black">
                                    <h4 class="text-lg font-bold text-white">iPad Pro 12.9" & 11"</h4>
                                    <p class="text-md font-light mt-2 text-white">
                                        Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                        Adipisci aliquam delectus dignissimos fuga magnam maxime ratione rem temporibus!
                                        Alias cum est fugiat impedit ipsa labore libero molestias perferendis sint suscipit.
                                    </p>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="relative py-20 bg-dark-black" id="macbook">
                <div class="bottom-auto top-0 left-0 right-0 w-full h-1/6 absolute overflow-hidden -mt-7" style="height: 80px">
                    <svg class="absolute bottom-0 w-full overflow-hidden"
                         xmlns="http://www.w3.org/2000/svg"
                         preserveAspectRatio="none"
                         version="1.1"
                         viewbox="0 0 2560 100"
                         x="0"
                         y="0"
                    >
                        <polygon points="2560 0 2560 100 0 100" class="text-dark-black fill-current"></polygon>
                    </svg>
                </div>
            </section>
        </main>
</template>

<script>

import Button from "@/Components/Button.vue";
import BlogPostFull from "@/Components/BlogPostFull.vue";
export default {
    components: {BlogPostFull, Button},
    methods: {
        toggleNavbar(thing){
            document.getElementById(thing).classList.toggle('hidden')
            document.getElementById(thing).classList.toggle('block')
        }
    }
}
</script>
