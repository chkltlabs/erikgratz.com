<template>
    <breeze-authenticated-layout>
        <template #header>
            <div class="flex flex-row items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight ">
                    Review Contact Requests
                </h2>
                <button :onclick="refresh" class="p-3 bg-blue-500 text-white hover:bg-blue-400 m-2 " >refresh</button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-col">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contact Info
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Message...
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Delete
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="post in contacts"
                                        :key="post.id"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <!--                                                <div class="flex-shrink-0 h-10 w-10">-->
                                                <!--                                                    <img class="h-10 w-10 rounded-full" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=4&w=256&h=256&q=60" alt="">-->
                                                <!--                                                </div>-->
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{post.contact}}
                                                    </div>
                                                    <!--                                                    <div class="text-sm text-gray-500">-->
                                                    <!--                                                        {{post.author.email}}-->
                                                    <!--                                                    </div>-->
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ post.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-wrap">
                                            <div class="text-sm text-gray-900">{{ post.message }}</div>
                                        </td>
                                        <td>
                                            <inertia-link :href="'/contacts/' + post.id" method="delete" as="button" class="text-indigo-600 hover:text-indigo-900">Delete</inertia-link>
                                        </td>
                                    </tr>

                                    <!-- More people... -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </breeze-authenticated-layout>
</template>
<script>
import BreezeAuthenticatedLayout from '@/Layouts/Authenticated.vue'
import { router as Inertia } from '@inertiajs/vue3'

export default {
    components: {
        BreezeAuthenticatedLayout,
    },
    props: ['contacts'],
    methods: {
        refresh(){
            // let res = axios.get('/api/contacts')
            //     .then(response => (this.contacts = response.data))
            Inertia.reload({
                only: ['contacts']
            })
        }
    }
}
</script>
