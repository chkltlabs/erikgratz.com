<template>
    <section class="py-5 md:py-8 hidden lg:block">
        <div class="container max-w-screen-xl mx-auto px-4">
            <h1 class="font-medium text-gray-300 text-3xl md:text-4xl mb-5">Experience</h1>
            <div class="flex flex-col lg:flex-row justify-between">
                <div class="  mb-16 md:mb-0">
                    <h6 class="font-medium text-gray-400 text-base uppercase">Company</h6>
                    <p class="font-semibold text-gray-600 text-base">Sonic Boom Wellness
                        <span class="font-normal text-gray-300">/ San Diego, CA -> Fully Remote</span>
                    </p>
                    <p class="font-semibold text-gray-600 text-base">Internet Things -> The Media Lab
                        <span class="font-normal text-gray-300">/ San Diego, CA</span>
                    </p>
                </div>
                <div class="  mb-16 md:mb-0">
                    <h6 class="font-medium text-gray-400 text-base uppercase">Position</h6>
                    <p class="font-normal text-gray-400 text-base">Backend Developer</p>
                    <p class="font-normal text-gray-400 text-base">PHP Developer -> Lead PHP Developer</p>
                </div>
                <div class="">
                    <h6 class="font-medium text-gray-400 text-base uppercase">Year</h6>
                    <p class="font-normal text-gray-400 text-base">2020-{{ new Date().getFullYear() }}</p>
                    <p class="font-normal text-gray-400 text-base">2019-2020</p>
                </div>
            </div>
        </div>
    </section>
    <section class="py-5 md:py-8 lg:hidden">
        <div class="container max-w-screen-xl mx-auto px-4">
            <h1 class="font-medium text-gray-300 text-3xl md:text-4xl mb-5">Experience</h1>
            <div class="flex flex-col lg:flex-row justify-between">
                <div class="space-y-8 lg:space-y-16 mb-16 md:mb-0">
                    <h6 class="font-medium text-gray-300 text-base">
                        Company / Location <br class="md:hidden">/ Title / Timeframe</h6>
                    <p class="font-semibold text-gray-300 text-base">Sonic Boom Wellness
                        <span class="font-normal text-gray-500">/ San Diego, CA -> Fully Remote </span>
                        <br class="md:hidden"/>
                        <span class="font-normal text-gray-300">/ Backend Developer </span>
                        <span class="font-normal text-gray-500">/ 2020-{{ new Date().getFullYear() }}</span>
                    </p>
                    <p class="font-semibold text-gray-600 text-base">Internet Things -> The Media Lab
                        <span class="font-normal text-gray-300">/ San Diego, CA </span>
                        <br class="md:hidden"/>
                        <span class="font-normal text-gray-500">/ PHP Developer -> Lead PHP Developer </span>
                        <span class="font-normal text-gray-300">/ 2019-2020</span>
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
<script>

export default {}

</script>
