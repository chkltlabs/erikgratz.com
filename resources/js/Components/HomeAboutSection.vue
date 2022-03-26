<template>
    <section class="py-5 md:py-8">
        <div class="container max-w-screen-xl mx-auto px-4">
            <h1 class="font-medium text-gray-300 text-3xl md:text-4xl mb-5">{{ title }}</h1>
            <p class="font-normal text-gray-500 text-s md:text-base md:mx-10">{{ text }}</p>
        </div>
    </section>
</template>
<script>

export default {
    data() {
        return {
            title: "Welcome",
            text: "I'm Erik, thanks for visiting. I have been writing code professionally since 2019. " +
                "I have a passion, a need to solve complex puzzles. I get a real burst of joy every time a test comes back all green. " +
                "My preferred stack is Apache -> MongoDB/MySQL -> Laravel -> Vue.js, " +
                "but I have been interested in Ruby and Python Machine Learning Projects as well. " +
                "Schedule a call with me to discuss how I can help you see sustained success in your modern web project! "
        }
    }
}

</script>
