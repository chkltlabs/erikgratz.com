<template>
    <div>
        <section class="py-5 md:py-8">
            <div class="container max-w-screen-xl mx-auto px-4">
                <div class="text-center">
                    <div class="flex justify-center mb-16">
                        <img class="w-64 h-64 rounded-full" :src="heroImage" alt="Image">
                    </div>
                    <h6 class="font-medium text-gray-600 text-lg md:text-2xl uppercase mb-8">Erik Virgil Gratz</h6>
                    <title-box/>
                    <p class="font-normal text-gray-600 text-md md:text-xl mb-16">I have a passion for computers. I create solutions to modern web problems.</p>
                    <div class="container max-w-screen-xl mx-auto px-4 my-10">
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-8">
                                <a href="https://www.twitter.com/chkltlabs"
                                   class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">
                                    <i class="text-gray-500 hover:text-gray-800 transition ease-in-out duration-500 fab fa-twitter"></i>
                                </a>
                                <a href="https://laracasts.com/@chkltlabs"
                                   class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">
                                    <i class="text-gray-500 hover:text-gray-700 transition ease-in-out duration-500 fab fa-laravel"></i>
                                </a>
                                <a href="https://www.facebook.com/Sarcastic.Badger"
                                   class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">
                                    <i class="text-gray-500 hover:text-gray-700 transition ease-in-out duration-500 fab fa-facebook"></i>
                                </a>
                                <a href="mailto:erikgratz110@gmail.com"
                                   class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">
                                    <i class="text-gray-500 hover:text-gray-700 transition ease-in-out duration-500 fas fa-at"></i>
                                </a>
                                <a href="http://https://www.instagram.com/grotz110/tagged"
                                   class="w-16 h-16 flex items-center justify-center rounded-full hover:bg-gray-200 transition ease-in-out duration-500">
                                    <i class="text-gray-500 hover:text-gray-700 transition ease-in-out duration-500 fab fa-instagram"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <a href="https://calendly.com/erikgratz/30min"
                       class="px-7 py-3 md:px-9 md:py-4
                       font-medium md:font-semibold
                       bg-gray-700 text-gray-50 text-sm rounded-md
                       hover:bg-purple-600 hover:text-white
                       transition ease-linear duration-500">Schedule a Call</a>
                </div>
            </div>
        </section>
        <HomeAboutSection />
        <HomeExperienceSection />
    </div>
</template>
<script>

import TitleBox from "@/Components/TitleBox.vue";
import HomeExperienceSection from "@/Components/HomeExperienceSection.vue";
import HomeAboutSection from "@/Components/HomeAboutSection.vue";
import Headers from "@/Layouts/Headers.vue"



export default {
    components: {HomeAboutSection, HomeExperienceSection, TitleBox},
    layout: Headers,
    props: ['useRealHomepage', 'errors', 'auth', 'canLogin', 'canRegister', 'laravelVersion', 'phpVersion', 'messages'],
    data(){
        return {
            // heroImage: import('../../../storage/app/public/images/webp/suit-up.webp'),
            heroImage: new URL('../../../storage/app/public/images/webp/suit-up.webp', import.meta.url).href
        }
    },
    mounted() {
    }
}
</script>

