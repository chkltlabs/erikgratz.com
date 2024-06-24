<template>
    <my-header class="hidden md:block"></my-header>
    <mobile-header class="md:hidden"></mobile-header>
    <article>
        <div class="bg-black h-full flex flex-col min-h-screen text-purple-600 font-sans">
            <div class="pt-20"/>
            <slot/>
            <p class="text-center text-sm">Copyright&copy; {{ currentYear }} Erik V Gratz</p>
        </div>
    </article>
</template>
<script>
import MyHeader from "./MyHeader.vue";
import MobileHeader from "./MobileHeader.vue";

export default {
    components: {MobileHeader, MyHeader},
    data() {
        return {
            currentYear: new Date().getFullYear(),
        }
    }
}
</script>
