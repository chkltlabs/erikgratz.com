<template>
    <main>
        <nav
            class="bg-black shadow animated fixed w-full"
            :class="{ 'scrolled': !view.atTopOfPage}"
            role="navigation"
        >
            <div class="container p-4 flex flex-row items-center justify-center">
                <div class="mr-4 ">
                    <inertia-link href="/login" rel="login">
                        <svg class="w-10 h-10 text-purple-600 transition duration-1000 transform hover:scale-150"
                             width="54" height="54" viewBox="0 0 305.033 305.033" xmlns="http://www.w3.org/2000/svg">
                            <title>Chocolate Labs</title>
                            <path fill="currentColor"
                                  d="M303.273,193.23l-67.419-67.419l3.21-0.994c2.009-0.621,3.545-2.249,4.05-4.29c0.505-2.041-0.095-4.197-1.582-5.684  l-31.166-31.166c-1.126-1.126-2.651-1.758-4.243-1.758c-1.591,0-3.118,0.632-4.243,1.758l-4.081,4.081L111.803,1.758  C110.678,0.632,109.152,0,107.561,0c-1.591,0-3.118,0.632-4.243,1.758L52.562,52.516c-0.007,0.007-0.017,0.012-0.024,0.02  c-0.007,0.008-0.012,0.017-0.02,0.024L1.76,103.318c-2.343,2.343-2.343,6.142,0,8.484l45.103,45.103  c0.007,0.007,0.011,0.015,0.018,0.021c0.006,0.007,0.015,0.011,0.021,0.018l40.859,40.859l-4.081,4.081  c-2.343,2.343-2.343,6.142,0,8.484l31.163,31.166c1.139,1.14,2.671,1.758,4.243,1.758c0.48,0,0.964-0.058,1.441-0.176  c2.041-0.505,3.669-2.042,4.291-4.05l0.994-3.211l67.418,67.419c1.126,1.126,2.651,1.758,4.243,1.758  c1.591,0,3.118-0.632,4.243-1.758l101.558-101.561C305.616,199.372,305.616,195.573,303.273,193.23z M147.025,138.537  l-36.636-36.636l42.292-42.294l36.635,36.636L147.025,138.537z M107.561,14.485l36.636,36.637l-42.293,42.294L65.268,56.78  L107.561,14.485z M56.783,65.265l36.637,36.637l-42.295,42.296l-36.636-36.637L56.783,65.265z M59.609,152.682l42.295-42.296  l36.636,36.637l-42.294,42.296L59.609,152.682z M96.408,206.127l4.08-4.08c0.001-0.001,0.001-0.001,0.001-0.001  c0.001,0,0.001-0.001,0.001-0.001l50.765-50.768c0.004-0.004,0.01-0.007,0.015-0.012c0.004-0.005,0.007-0.01,0.012-0.015  l54.843-54.846l19.89,19.89l-35.63,11.028c-1.892,0.585-3.373,2.066-3.958,3.957l-13.033,42.113l-42.112,13.033  c-1.892,0.585-3.373,2.066-3.958,3.958l-11.027,35.632L96.408,206.127z M197.473,290.548l-67.65-67.65l8.029-25.943l42.112-13.033  c1.892-0.585,3.372-2.066,3.958-3.958l13.033-42.112l25.94-8.028l67.65,67.65L197.473,290.548z"/>
                        </svg>
                    </inertia-link>
                </div>
                <div class="mx-auto">
                    <button @click="isNavOpen = !isNavOpen"
                            class="flex items-center px-3 py-2 border rounded border-purple-600" type="button">
                        <svg class="h-3 w-3 text-purple-600" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <title>Menu</title>
                            <path fill="currentColor" d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/>
                        </svg>
                    </button>
                </div>
                <div class="flex-col">
                    <img :src="this.imgUrl"
                         alt="..."
                         class="ring-mint shadow rounded-full h-12 align-middle border-none transition duration-1000 transform hover:scale-150"
                         v-on:click="toggleContactMenu"
                         ref="faceButton"
                    />
                    <contact-box
                        v-if="this.isMenuOpen"
                        v-click-away="closeContactMenu"
                        v-on:close-box-from-inside="this.isMenuOpen = false"
                        :class="{ 'scrolled': !view.atTopOfPage}"
                    />
                </div>

            </div>
            <slide-transition>
                <div v-if="isNavOpen" class="w-full flex border-t border-mint" style="">
                    <ul
                        class="flex flex-row">
                        <li>
                            <inertia-link class="block px-4 py-1 md:p-2 lg:px-4" @click="isNavOpen = false"
                                          :class="($page.component == 'Home') ? selectedColor : unselectedColor"
                                          href="/"
                                          title="Link">Home
                            </inertia-link>
                        </li>
                        <li>
                            <inertia-link class="block px-4 py-1 md:p-2 lg:px-4" @click="isNavOpen = false"
                                          :class="($page.component == 'Blog') ? selectedColor : unselectedColor"
                                          href="/blog"
                                          title="Active Link">Blog
                            </inertia-link>
                        </li>
                        <li>
                            <inertia-link class="block px-4 py-1 md:p-2 lg:px-4" @click="isNavOpen = false"
                                          :class="($page.component == 'Portfolio') ? selectedColor : unselectedColor"
                                          href="/portfolio" title="Link">Portfolio
                            </inertia-link>
                        </li>
                        <li v-if="!['Home','Blog','Portfolio'].includes( $page.component)">
                            <inertia-link class="block px-4 py-1 md:p-2 lg:px-4" @click="isNavOpen = false"
                                          :class="selectedColor"
                                          :href="$page.url" title="Link">{{ $page.component }}
                            </inertia-link>
                        </li>
                    </ul>
                </div>
            </slide-transition>
        </nav>
<!--        <article>-->
<!--            <div class="bg-black h-full flex flex-col min-h-screen text-purple-600 font-sans">-->
<!--                <div class="pt-20"/>-->
<!--                <slot/>-->
<!--                <p class="text-center text-sm">Copyright&copy; 2021 Erik V Gratz</p>-->
<!--            </div>-->
<!--        </article>-->
    </main>
</template>
<script>

import ContactBox from "@/Layouts/ContactBox.vue"
import Dropdown from "@/Components/Dropdown.vue";
import {mixin as VueClickAway} from 'vue3-click-away'
import FadeTransition from "@/Components/FadeTransition.vue";
import SlideTransition from "../Components/SlideTransition.vue";
import { Link as InertiaLink} from "@inertiajs/vue3";

export default {
    mixins: [VueClickAway],
    components: {InertiaLink, SlideTransition, FadeTransition, Dropdown, ContactBox},
    props: {
        noteCount: Number,
    },
    data() {
        return {
            selectedColor: 'text-mint',
            unselectedColor: 'text-purple-600',
            isMenuOpen: false,
            isNavOpen: false,
            menuYPos: false,
            view: {
                atTopOfPage: true,
                width: 0,
            },
            imgUrl: new URL('../../../storage/app/public/images/webp/face.webp', import.meta.url).href
        }
    },
    beforeMount() {
        window.addEventListener('scroll', this.handleScroll);
    },
    methods: {
        toggleContactMenu() {
            this.isMenuOpen = !this.isMenuOpen
        },
        closeContactMenu() {
            this.isMenuOpen = false

        },
        print(thing) {
            console.log(thing)
        },
        handleScroll() {
            // when the user scrolls, check the pageYOffset
            if (window.pageYOffset > 0) {
                if (this.isMenuOpen) {
                    if (this.menuYPos === false) {
                        this.menuYPos = window.pageYOffset
                    } else if (Math.abs(this.menuYPos - window.pageYOffset) > (window.screenY / 4)) {
                        this.isMenuOpen = false
                        this.menuYPos = false
                    }
                }
                // user is scrolled
                if (this.view.atTopOfPage) this.view.atTopOfPage = false
            } else {
                // user is at top of page
                if (!this.view.atTopOfPage) this.view.atTopOfPage = true
            }
        },
        myEventHandler(e) {
            //console.log(e.target.innerWidth)
            this.view.width = e.target.innerWidth;
            if (e.target.innerWidth > 767 && this.isNavOpen === false) {
                this.isNavOpen = true;
            }
        }
    },
    created() {
        window.addEventListener("resize", this.myEventHandler);
    },
    unmounted() {
        window.removeEventListener("resize", this.myEventHandler);
    },
    mounted() {
    }
}
</script>
