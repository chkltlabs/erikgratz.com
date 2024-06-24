<template>
    <div class="overflow-hidden shadow-lg rounded-lg h-90 w-60 md:w-80 cursor-pointer m-auto">
        <a href="#" class="w-full block h-full">
            <img alt="blog photo" :src="post.imageUrl" class="max-h-40 w-full object-cover"/>
            <div class="bg-white dark:bg-gray-800 w-full p-4">
                <p class="text-indigo-500 text-md font-medium">
                    {{ post.posted }}
                </p>
                <p class="text-gray-800 dark:text-white text-xl font-medium mb-2">
                    {{ post.title }}
                </p>
                <p class="text-gray-400 dark:text-gray-300 font-light text-md">
                    {{ post.subtitle }}
                </p>
                <div class="flex items-center mt-4">
                    <a href="#" class="block relative">
<!--                        <img alt="" :src="post.author.imageUrl" class="mx-auto object-cover rounded-full h-10 w-10 "/>-->
                    </a>
                    <div class="flex flex-col justify-between ml-4 text-sm">
                        <p class="text-gray-800 dark:text-white">
                            {{ post.author.name }}
                        </p>
                        <p class="text-gray-400 dark:text-gray-300">
                            Tags holding Area
                        </p>
                    </div>
                </div>
            </div>
        </a>
    </div>
</template>
<script>

export default {
    props: ['post']
}
</script>
