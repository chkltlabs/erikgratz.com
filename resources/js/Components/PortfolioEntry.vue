<template>
    <div class="flex flex-row items-center justify-between p-3 mb-4">
        <img v-if="imgUrl && index % 2 !== 0" class="hidden md:block items-end w-1/4" :src="imgUrl"/>
        <div class="flex-col m-2">
            <p class="text-3xl font-light text-gray-300">
                {{ title }}
            </p>
            <img v-if="imgUrl" class="md:hidden w-auto p-4" :src="imgUrl" />
            <p class="text-gray-300">{{ text }}</p>
            <a v-if="link != null && linkText != null" v-href="link" >{{linkText}}</a>
        </div>
        <img v-if="imgUrl && index % 2 === 0" class="hidden md:block items-end w-1/4" :src="imgUrl"/>
    </div>
</template>
<script>

export default {
    props: ['title','text','imgUrl','index','link','linkText'],
    mounted() {
        // console.log(this.index)
    }
}
</script>
