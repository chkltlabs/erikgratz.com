<template>
    <div>
        <component :is="page" />
    </div>
</template>
<script>
import Apple from "@/Mocks/Apple.vue";
import NoHeader from "@/Layouts/NoHeader.vue";
export default {
    components: {
        Apple
    },
    props: ['page'],
    layout: NoHeader,
}

</script>
