<template>
    <transition
        enter-active-class="transition ease-out duration-100"
        enter-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
    >
        <div
            class="origin-top-right absolute right-0 mt-2 mx-2 w-56 rounded-md shadow-lg text-sm overflow-hidden border border-purple-600 text-white z-20"
        >
            <div
                class="rounded-md bg-black shadow-xs"
                role="menu"
                aria-orientation="vertical"
                aria-labelledby="options-menu"
            >
                <div>
                    <div class="bg-purple-600 p-4 flex items-center text-white">
                        <div class="w-full">
                            <p class="font-semibold text-base">Erik V Gratz</p>
                            <button
                                class="flex items-center justify-between w-full focus:outline-none"
                            >
                                <p class="text-gray-300">erikgratz110@gmail.com</p>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="border-t border-purple-600"></div>
                <div class="py-1">
                    <inertia-link
                        href="/donate"
                        class="p-4 flex items-center space-x-2"
                        @click.native="closeBox"
                    >
                        <svg width="32px" height="32px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" fill="currentColor" color="#000000" class="text-purple-600">
                            <g fill-rule="evenodd">
                                <path d="M16 32C7.163 32 0 24.837 0 16S7.163 0 16 0s16 7.163 16 16-7.163 16-16 16zm7.994-15.781L16.498 4 9 16.22l7.498 4.353 7.496-4.354zM24 17.616l-7.502 4.351L9 17.617l7.498 10.378L24 17.616z"></path>
                                <g fill-rule="nonzero">
                                    <path fill-opacity=".298" d="M16.498 4v8.87l7.497 3.35zm0 17.968v6.027L24 17.616z"></path>
                                    <path fill-opacity=".801" d="M16.498 20.573l7.497-4.353-7.497-3.348z"></path>
                                    <path fill-opacity=".298" d="M9 16.22l7.498 4.353v-7.701z"></path>
                                </g>
                            </g>
                        </svg>
                        <span> Buy me a coffee? </span>
                    </inertia-link>
                    <inertia-link
                        href="/contact"
                        @click.native="closeBox"
                        class="p-4 flex items-center space-x-2"
                    >
                        <svg class="text-purple-600" width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" color="#000">
                            <path fill-rule="evenodd" d="M0 3.75C0 2.784.784 2 1.75 2h12.5c.966 0 1.75.784 1.75 1.75v8.5A1.75 1.75 0 0114.25 14H1.75A1.75 1.75 0 010 12.25v-8.5zm1.75-.25a.25.25 0 00-.25.25v8.5c0 .138.112.25.25.25h12.5a.25.25 0 00.25-.25v-8.5a.25.25 0 00-.25-.25H1.75zM3.5 6.25a.75.75 0 01.75-.75h7a.75.75 0 010 1.5h-7a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h4a.75.75 0 000-1.5h-4z"></path>
                        </svg>
                        <span> Leave a note </span>
                    </inertia-link>
                </div>
                <div class="border-t border-purple-600"></div>
                <div class="py-1">
                    <a
                        href="http://www.github.com"
                        class="p-4 flex items-center space-x-2"
                        @mouseover="gitMouseover = true"
                        @mouseleave="gitMouseover = false"
                    >
                        <svg width="32px" height="32px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" color="#000000" class="text-purple-600">
                            <path fill-rule="evenodd" d="M11.75 2.5a.75.75 0 100 1.5.75.75 0 000-1.5zm-2.25.75a2.25 2.25 0 113 2.122V6A2.5 2.5 0 0110 8.5H6a1 1 0 00-1 1v1.128a2.251 2.251 0 11-1.5 0V5.372a2.25 2.25 0 111.5 0v1.836A2.492 2.492 0 016 7h4a1 1 0 001-1v-.628A2.25 2.25 0 019.5 3.25zM4.25 12a.75.75 0 100 1.5.75.75 0 000-1.5zM3.5 3.25a.75.75 0 111.5 0 .75.75 0 01-1.5 0z"></path>
                        </svg>
                        <span v-if="gitMouseover"> [Leave for GitHub.com] </span>
                        <span v-else> Read my code </span>
                    </a>
                </div>
            </div>
        </div>
    </transition>
</template>
<script>

export default {
    props: [
        // 'isMenuOpen'
    ],
    data(){
        return{
            gitMouseover: false
        }
    },
    methods:{
        closeBox(){
            //console.log('closeBox fired')
            this.$emit('close-box-from-inside')
        },
    },
    mounted() {
    }
}
</script>
