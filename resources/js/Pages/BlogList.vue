<template>
    <breeze-authenticated-layout>
        <template #header>
            <div class="flex flex-row items-center justify-between w-full">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight ">
                Edit your blog posts
            </h2>
            <button :onclick="newPost" class="p-3 bg-blue-500 text-white hover:bg-blue-400 m-2 " >New Post!</button>
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
                                            Author
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Title (Sub)
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Posted On
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Impressions/Conversions
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Edit</span>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                    v-for="post in BlogPosts"
                                    :key="post.id"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
<!--                                                <div class="flex-shrink-0 h-10 w-10">-->
<!--                                                    <img class="h-10 w-10 rounded-full" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=4&w=256&h=256&q=60" alt="">-->
<!--                                                </div>-->
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{post.author.name}}
                                                    </div>
<!--                                                    <div class="text-sm text-gray-500">-->
<!--                                                        {{post.author.email}}-->
<!--                                                    </div>-->
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ post.title }}</div>
                                            <div class="text-sm text-gray-500">{{ post.subtitle }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ post.posted }}</div>
                                            <div class="text-sm text-gray-500">{{ post.edited }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            0 (Not yet implemented)
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="post.is_public ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'">
                  {{ post.is_public ? 'Public' : 'Draft' }}
                </span>
                                            <inertia-link :href="'/blog/edit/' + post.id" class="text-indigo-600 hover:text-indigo-900">Edit</inertia-link>
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

export default {
    components: {
        BreezeAuthenticatedLayout,
    },
    props: ['BlogPosts'],
    methods: {
        newPost(){
            //console.log('spoot')
            this.$inertia.get('/blog/new');
        }
    }
}
</script>
