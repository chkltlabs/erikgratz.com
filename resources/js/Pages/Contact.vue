<template>

    <div class="w-full h-screen font-sans bg-cover bg-landscape">
        <div class="w-full flex flex-row my-8 items-center justify-center">
            <social-button
                :link="resumeUrl"
                name="My Resume"
                alt-text="A Link to My Resume"
            ></social-button>
            <social-button
                :link="'mailto:' + email"
                name="Direct Email"
                alt-text="A Link to send me an Email"
            ></social-button>
            <social-button
                :link="'tel:' + phone"
                name="Call me!"
                alt-text="A Link to open a phone call to me"
            ></social-button>
        </div>
        <div class="container flex items-center justify-center flex-1 h-auto mx-auto">
            <div class="w-full max-w-lg">
                <div class="leading-loose">
                    <form
                        id="note-form"
                        class="max-w-sm p-10 m-auto bg-white bg-opacity-25 rounded shadow-xl"
                        @submit.prevent="submit"
                    >
                        <p class="mb-8 text-2xl font-light text-center text-white">
                            Say Something Kind?
                        </p>
                        <div
                            v-if="$page.props.flash.message"
                            class="text-l text-red-500 bg-black font-light text-center"
                        >
                            <b>{{ $page.props.flash.message }}</b>
                        </div>
                        <div
                            v-if="$page.props.flash.success"
                            class="rounded-lg border-transparent border mb-2 text-l text-green-500 bg-green-200 font-light text-center"
                        >
                            <b>{{ $page.props.flash.success }}</b>
                        </div>
                        <div
                            v-if="$page.props.flash.info"
                            class="text-l text-purple-500 bg-purple-200 font-light text-center"
                        >
                            <b>{{ $page.props.flash.info }}</b>
                        </div>
                        <div class="mb-2">
                            <div class=" relative ">
                                <input
                                    type="text"
                                    required
                                    id="email"
                                    class=" rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                                    placeholder="email or phone"
                                    name="contact"
                                    v-model="form.contact"
                                />
                                <p v-if="$attrs.errors.contact" class="text-red-500 text-xs">{{
                                        $attrs.errors.contact
                                    }}</p>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class=" relative ">
                                <input
                                    type="text"
                                    id="name"
                                    class=" rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                                    placeholder="name"
                                    name="name"
                                    v-model="form.name"
                                />
                                <p v-if="$attrs.errors.name" class="text-red-500 text-xs">{{ $attrs.errors.name }}</p>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class=" relative ">
                                <textarea
                                    form="note-form"
                                    type="text"
                                    id="message"
                                    class=" rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                                    placeholder="..."
                                    name="message"
                                    v-model="form.message"
                                />
                                <p v-if="$attrs.errors.message" class="text-red-500 text-xs">{{
                                        $attrs.errors.message
                                    }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-4">
                            <button type="submit"
                                    class="py-2 px-4  bg-purple-600 hover:bg-purple-800 focus:ring-indigo-500 focus:ring-offset-indigo-200 text-white w-full transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2  rounded-lg ">
                                {{ submitButtonText }}
                            </button>
                        </div>
                        <!--                        <div class="text-center">-->
                        <!--                            <a class="right-0 inline-block text-sm font-light align-baseline text-500 hover:text-gray-800">-->
                        <!--                                Create an account-->
                        <!--                            </a>-->
                        <!--                        </div>-->
                    </form>
                </div>
            </div>
        </div>
    </div>


</template>
<script>

import {reactive} from 'vue'
import { router as Inertia } from '@inertiajs/vue3'
import QRCodeElement from "../Components/QRCodeElement.vue";
import SocialButton from "../Components/SocialButton.vue";


export default {
    components: {SocialButton, QRCodeElement},
    props: ['phone', 'email', 'resumeUrl', 'submitButtonText'],
    setup() {
        const form = reactive({
            name: null,
            contact: null,
            message: null,
        })

        function submit() {
            Inertia.post('/contacts', form)
        }

        return {form, submit}
    },

}
</script>
