<template>
    <section class="py-5 md:py-8">
        <div class="container max-w-screen-xl mx-auto px-4">
            <h1 class="font-medium text-gray-300 text-3xl md:text-4xl mb-5">Experience</h1>
            <!-- web version -->
            <div class="flex flex-col lg:flex-row justify-between">
                <div class=" hidden lg:block mb-16 md:mb-0">
                    <h6 class="font-medium text-gray-400 text-base uppercase">Company</h6>
                    <p v-for="jerb, key in jerbs" 
                        v-bind:key="key" 
                        class="font-semibold text-gray-600 text-base"
                        >{{jerb.company}}
                        <span class="font-normal text-gray-300">/ {{jerb.location}}</span>
                    </p>
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
                    <p v-for="jerb, key in jerbs" 
                        v-bind:key="key"
                        class="font-normal text-gray-400 text-base">{{jerb.title}}</p>
                    <!-- <p class="font-normal text-gray-400 text-base">Senior Software Engineer, Backend</p>
                    <p class="font-normal text-gray-400 text-base">Backend Developer</p>
                    <p class="font-normal text-gray-400 text-base">PHP Developer -> Lead PHP Developer</p> -->
                </div>
                <div class=" hidden lg:block">
                    <h6 class="font-medium text-gray-400 text-base uppercase">Year</h6>
                    <p v-for="jerb, key in jerbs" 
                        v-bind:key="key"
                        class="font-normal text-gray-400 text-base">{{jerb.timeframe}}</p>
                </div>
            <!-- mobile version -->
                <div class="space-y-2 mb-16 md:mb-0 lg:hidden">
                    <h6 class="font-medium text-gray-300 text-base">
                        Company / Location <br class="md:hidden">/ Title / Timeframe</h6>
                    <p v-for="jerb, key in jerbs" 
                        v-bind:key="key"
                        class="font-semibold text-gray-300 text-base">{{jerb.company}}
                        <span class="font-normal text-gray-500">/ {{jerb.location}} </span>
                        <br class="md:hidden"/>
                        <span class="font-normal text-gray-300">/ {{jerb.title}} </span>
                        <span class="font-normal text-gray-500">/ {{jerb.timeframe}}</span>
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
<script>

export default {
data() {
        return {
            jerbs: [
                {
                    company: 'Pocketnest',
                    location: 'Detroit, MI -> Fully Remote',
                    title: 'Senior Software Engineer, Backend',
                    timeframe: '2022-' + new Date().getFullYear() ,
                },
                {
                    company: 'Sonic Boom Wellness',
                    location: 'San Diego, CA -> Fully Remote',
                    title: 'Backend Developer',
                    timeframe: '2020-2022',
                },
                {
                    company: 'Internet Things -> The Media Lab',
                    location: 'San Diego, CA',
                    title: 'PHP Developer -> Lead PHP Developer',
                    timeframe: '2019-2020',
                }
            ]
        }
    }
    
}

</script>
