<template>
    <h1 class="font-normal text-gray-300 text-4xl md:text-7xl leading-none mb-8">
        {{ text }}
        <span class="input-cursor h-8 md:h-16"></span>
    </h1>
</template>
<script>

export default {
    data() {
        return {
            text: "LAMP Artisan",
            textOptions: [
                "PHP Backend Developer",
                "Self-Educated Programmer",
                "Microservice Architect",
                "Test-Coverage Obsessive",
                "Laravel Disciple",
                "LAMP Artisan",
            ],
        }
    },
    methods: {
        async typeSentence(sentence, delay = 100) {
            const letters = sentence.split("")
            let i = this.text.length;
            while (i < letters.length) {
                await this.waitForMs(delay)
                this.text += letters[i]
                i++
            }
        },
        async deleteSentence(delay = 100) {
            while (this.text.length > 0) {
                this.text = this.text.slice(0, -1)
                await this.waitForMs(delay)
            }
        },
        waitForMs(ms) {
            return new Promise(resolve => setTimeout(resolve, ms))
        },
    },
    async mounted() {
        let i = 0
        await this.waitForMs(4000)
        while (i < this.textOptions.length) {
            await this.deleteSentence()
            await this.waitForMs(1500)
            await this.typeSentence(this.textOptions[i])
            await this.waitForMs(4000)
            i++
            if (i === this.textOptions.length) {
                i = 0
            }
        }
    }
}

</script>
<style>

@keyframes blink {
    0% {
        opacity: 1;
    }
    45% {
        opacity: 1;
    }
    55% {
        opacity: 0;
    }
    100% {
        opacity: 0;
    }
}

.input-cursor {
    display: inline-block;
    width: 2px;
    /*height: 42px;*/
    background-color: white;
    margin-left: 4px;
    animation: blink .6s linear infinite alternate;
}

</style>
