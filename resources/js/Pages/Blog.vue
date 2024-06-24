<template>
        <div class="w-full p-12">
            <div class="header flex items-end justify-between mb-12">
                <div class="title">
                    <FadeTransition>
                        <p v-if="this.whoIsOpen == null" class="text-4xl font-bold text-mint mb-4">
                            {{'Latest Posts' }}
                        </p>
                        <div v-else>
                            <p class="text-4xl font-bold text-mint mb-4">
                                {{ this.whoIsOpen.title }}
                            </p>
                        </div>
                    </FadeTransition>
                    <FadeTransition>
                        <p v-if="this.whoIsOpen == null" class="text-2xl font-light text-gray-400">
                            {{ inspire }}
                        </p>
                        <p v-else class="text-2xl font-light text-gray-400">
                            {{ this.whoIsOpen.subtitle }}
                        </p>
                    </FadeTransition>
                </div>
                <FadeTransition>
                    <div v-if="!this.whoIsOpen" class="text-end">
                        <form class="flex w-full max-w-sm space-x-3">
                            <div class=" relative ">
                                <input type="text" v-model="filters.searchBoxValue" id="search-box"
                                       class=" rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-gray-300 text-purple-700 placeholder-gray-500 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                                       placeholder="Search my drivel..."/>
                            </div>
                            <!--                    <button class="flex-shrink-0 px-4 py-2 text-base font-semibold text-white bg-purple-600 rounded-lg shadow-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-purple-200" type="submit">-->
                            <!--                        Search-->
                            <!--                    </button>-->
                        </form>
                    </div>
<!--                this else block helps synchronize the transition of the search box,
                    without it the search box begins entering while the other elements
                    are still leaving-->
                    <div v-else ><p> </p></div>
                </FadeTransition>
            </div>
            <fade-transition>
                <div v-if="!this.whoIsOpen" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-12">
                    <blog-post-preview
                        v-for="post in filtered_posts"
                        :post="post"
                        @click="this.whoIsOpen = post"
                    />
                </div>
                <div v-else>
                    <div class="prose prose-dark m-5 focus:outline-none" >
                        <div v-html="this.whoIsOpen.body"></div>
                    </div>
                <br>
                    <button class="text-gray-400 mt-4" @click="whoIsOpen = null">-- Back to Articles --</button>
                </div>
            </fade-transition>
        </div>
</template>
<script>

import BlogPostPreview from "@/Components/BlogPostPreview.vue";
import BlogPostFull from "@/Components/BlogPostFull.vue";
import FadeTransition from "@/Components/FadeTransition.vue";
import { ArrowLeftIcon } from "@vue-hero-icons/solid"
import Button from "@/Components/Button.vue";
import Headers from "@/Layouts/Headers.vue"

export default {
    layout: Headers,
    components: {Button, ArrowLeftIcon, FadeTransition, BlogPostFull, BlogPostPreview},
    props: ['posts', 'inspire'],
    data() {
        return {
            whoIsOpen: null,
            filters: {
                searchBoxValue: '',
            }
        }
    },
    computed: {
        filtered_posts() {
            if (!this.filters.searchBoxValue) {
                console.log(this.posts)
                return this.posts
            }
            return this.posts.filter(post => {
                let s = this.filters.searchBoxValue.toLowerCase();
                return (post.title.toLowerCase().indexOf(s) !== -1
                    || post.subtitle.toLowerCase().indexOf(s) !== -1
                    || post.body.toLowerCase().indexOf(s) !== -1
                    || post.author.name.toLowerCase().indexOf(s) !== -1)
                    || post.tags.includes(s)
            })
        }
    },
}
</script>
