<template>
    <div @click="isShowing = !isShowing">
        <h4>click me</h4>
    </div>
    <FadeTransition>
        <div v-show="isShowing" @click="isShowing = !isShowing">
            <h4>click me</h4>
        </div>
    </FadeTransition>
    <FadeTransition>
        <div v-show="!isShowing" @click="isShowing = !isShowing">
            <h4>NOICE</h4>
        </div>
    </FadeTransition>
</template>
<script>
import FadeTransition from "@/Components/FadeTransition.vue";
export default {
    components: {FadeTransition},
    data() {
        return {
            isShowing: true,
        }
    }
}
</script>
