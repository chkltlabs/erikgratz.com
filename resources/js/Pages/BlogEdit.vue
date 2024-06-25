<template>
    <breeze-authenticated-layout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ headerText }}
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <input hidden :value="formAttrs.id" name="id" id="id" type="hidden">
                            <input hidden :value="formAttrs.user_id" name="user_id" id="user_id" type="hidden">
                            <div class="mb-4">
                                <label class="text-xl text-gray-600">Title <span
                                    class="text-red-500">*</span></label><br>
                                <input type="text" class="border-2 border-gray-300 p-2 w-full" name="title" id="title"
                                       v-model="formAttrs.title" required>
                            </div>

                            <div class="mb-4">
                                <label class="text-l text-gray-600">Subtitle</label><br>
                                <input type="text" class="border-2 border-gray-300 p-2 w-full" name="description"
                                       id="description" placeholder="(Optional)" v-model="formAttrs.subtitle">
                            </div>
                            <div class="mb-8">
                                <Multiselect
                                    v-model="formAttrs.tags"
                                    :options="multiOptions"
                                    mode="tags"
                                    :searchable="true"
                                    :createTag="true"
                                    :classes="{
  container: 'relative mx-auto w-full flex items-center justify-end box-border cursor-pointer border border-gray-300 rounded bg-white text-base leading-snug outline-none',
  containerDisabled: 'cursor-default bg-gray-100',
  containerOpen: 'rounded-b-none',
  containerOpenTop: 'rounded-t-none',
  containerActive: 'ring ring-green-500 ring-opacity-30',
  singleLabel: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
  multipleLabel: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
  search: 'w-full absolute inset-0 outline-none appearance-none box-border border-0 text-base font-sans bg-white rounded pl-3.5',
  tags: 'flex-grow flex-shrink flex flex-wrap mt-1 pl-2',
  tag: 'bg-purple-600 text-white text-sm font-semibold py-0.5 pl-2 rounded mr-1 mb-1 flex items-center whitespace-nowrap',
  tagDisabled: 'pr-2 !bg-gray-400 text-white',
  tagRemove: 'flex items-center justify-center p-1 mx-0.5 rounded-sm hover:bg-black hover:bg-opacity-10 group',
  tagRemoveIcon: 'bg-multiselect-remove bg-black bg-center bg-no-repeat opacity-30 inline-block w-3 h-3 group-hover:opacity-60',
  tagsSearch: 'h-full border-0 outline-none appearance-none p-0 text-base font-sans mx-1 mb-1 box-border flex-grow flex-shrink',
  placeholder: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5 text-gray-400',
  caret: 'bg-multiselect-caret bg-center bg-no-repeat w-2.5 h-4 py-px box-content mr-3.5 relative z-10 opacity-40 flex-shrink-0 flex-grow-0 transition-transform transform',
  caretOpen: 'rotate-180',
  clear: 'pr-3.5 relative z-10 opacity-40 transition duration-300 flex-shrink-0 flex-grow-0 flex hover:opacity-80',
  clearIcon: 'bg-multiselect-remove bg-center bg-no-repeat w-2.5 h-4 py-px box-content inline-block',
  spinner: 'bg-multiselect-spinner bg-center bg-no-repeat w-4 h-4 z-10 mr-3.5 animate-spin flex-shrink-0 flex-grow-0',
  dropdown: 'absolute -left-px -right-px bottom-0 transform translate-y-full border border-gray-300 -mt-px overflow-y-scroll z-50 bg-white flex flex-col rounded-b',
  dropdownTop: '-translate-y-full top-px bottom-auto flex-col-reverse rounded-b-none rounded-t',
  options: 'flex flex-col p-0 m-0 list-none',
  optionsTop: 'flex-col-reverse',
  option: 'flex items-center justify-start box-border text-left cursor-pointer text-base leading-snug py-2 px-3',
  optionPointed: 'text-gray-800 bg-gray-100',
  optionSelected: 'text-white bg-green-500',
  optionDisabled: 'text-gray-300 cursor-not-allowed',
  optionSelectedPointed: 'text-white bg-green-500 opacity-90',
  optionSelectedDisabled: 'text-green-100 bg-green-500 bg-opacity-50 cursor-not-allowed',
  noOptions: 'py-2 px-3 text-gray-600 bg-white',
  noResults: 'py-2 px-3 text-gray-600 bg-white',
  fakeInput: 'bg-transparent absolute left-0 right-0 -bottom-px w-full h-px border-0 p-0 appearance-none outline-none text-transparent',
  spacer: 'h-9 py-px box-content',
}"
                                />
                            </div>
                            <div class="mb-8">
                                <label class="text-xl text-gray-600">Post Body <span
                                    class="text-red-500">*</span></label><br>
                                <div v-if="editor">
                                    <bubble-menu
                                        class="bubble-menu"
                                        :tippy-options="{ duration: 100 }"
                                        :editor="editor"
                                        v-if="editor"
                                    >
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleBold().run()"
                                                :class="{ 'is-active': editor.isActive('bold') }">
                                            bold
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleItalic().run()"
                                                :class="{ 'is-active': editor.isActive('italic') }">
                                            italic
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleStrike().run()"
                                                :class="{ 'is-active': editor.isActive('strike') }">
                                            strike
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                                                :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }">
                                            h1
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                                                :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }">
                                            h2
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                                                :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }">
                                            h3
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleHeading({ level: 4 }).run()"
                                                :class="{ 'is-active': editor.isActive('heading', { level: 4 }) }">
                                            h4
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleHeading({ level: 5 }).run()"
                                                :class="{ 'is-active': editor.isActive('heading', { level: 5 }) }">
                                            h5
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleHeading({ level: 6 }).run()"
                                                :class="{ 'is-active': editor.isActive('heading', { level: 6 }) }">
                                            h6
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleCode().run()"
                                                :class="{ 'is-active': editor.isActive('code') }">
                                            code inline
                                        </button>
                                    </bubble-menu>
                                    <floating-menu
                                        class="bubble-menu"
                                        :tippy-options="{ duration: 100, maxWidth: 10000 }"
                                        :editor="editor"
                                        v-if="editor"
                                    >

                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().setParagraph().run()"
                                                :class="{ 'is-active': editor.isActive('paragraph') }">
                                            paragraph
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleBulletList().run()"
                                                :class="{ 'is-active': editor.isActive('bulletList') }">
                                            bullet list
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleOrderedList().run()"
                                                :class="{ 'is-active': editor.isActive('orderedList') }">
                                            ordered list
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleCodeBlock().run()"
                                                :class="{ 'is-active': editor.isActive('codeBlock') }">
                                            code block
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().toggleBlockquote().run()"
                                                :class="{ 'is-active': editor.isActive('blockquote') }">
                                            blockquote
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().setHorizontalRule().run()">
                                            horizontal rule
                                        </button>
                                        <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                                @click="editor.chain().focus().setHardBreak().run()">
                                            hard break
                                        </button>
                                    </floating-menu>
                                    <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                            @click="editor.chain().focus().undo().run()">
                                        undo
                                    </button>
                                    <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                            @click="editor.chain().focus().redo().run()">
                                        redo
                                    </button>
                                    <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                            @click="editor.chain().focus().unsetAllMarks().run()">
                                        clear marks
                                    </button>
                                    <button type="button" class="border border-gray-500 m-1 p-1 bg-gray-50"
                                            @click="editor.chain().focus().clearNodes().run()">
                                        clear nodes
                                    </button>

                                    <editor-content class="border-2 border-gray-300 p-2 w-full" :editor="editor"
                                                    v-model="formAttrs.body"/>
                                </div>
                                <!--                                <div class="prose prose-sm sm:prose lg:prose-lg xl:prose-2xl m-5 focus:outline-none" v-html="formAttrs.body">-->
                                <!--                                </div>-->
                            </div>



                            <div class="flex p-1">
                                <select class="border-2 border-gray-300 border-r p-2" style="width: 200px" name="action"
                                        v-model="formAttrs.is_public">
                                    <option :value="true">Save and Publish</option>
                                    <option :value="false">Save Draft</option>
                                </select>
                                <button type="submit" class="p-3 bg-blue-500 text-white hover:bg-blue-400">
                                    Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </breeze-authenticated-layout>
</template>
<script>

import {Editor, EditorContent, BubbleMenu, FloatingMenu} from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import BreezeAuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Multiselect from '@vueform/multiselect'


export default {
    components: {
        BreezeAuthenticatedLayout,
        EditorContent,
        BubbleMenu,
        FloatingMenu,
        Multiselect
    },
    props: ['BlogPost', 'mode', 'multiOptions'],
    data() {
        return {
            // isEditing: this.mode == 'edit',
            // headerText: this.isEditing ? 'Editing Post...' : 'Write a new Masterpiece!',
            // postRoute: !this.isEditing ? '/blog/new' : ('/blog/edit/' + this.BlogPost.id),
            editor: new Editor({
                content: this.BlogPost.body,
                extensions: [ StarterKit ],
                editorProps: {
                    attributes: {
                        class:
                            "prose prose-sm sm:prose lg:prose-lg xl:prose-2xl m-5 focus:outline-none"
                    }
                }
            }),
            formAttrs: {
                id: this.BlogPost.id,
                title: this.BlogPost.title,
                subtitle: this.BlogPost.subtitle,
                body: this.BlogPost.body,
                is_public: this.BlogPost.is_public ?? false,
                user_id: this.$page.props.auth.user.id,
                tags: this.BlogPost.tags ?? [],
            },
        }
    },
    computed: {
        isEditing() {
            return this.mode === 'edit';
        },
        headerText() {
            return this.isEditing ? 'Editing Post...' : 'Write a new Masterpiece!';
        },
        postRoute() {
            return !this.isEditing ? '/blog/new' : ('/blog/edit/' + this.BlogPost.id);
        }
    },
    methods: {
        submit() {
            this.formAttrs.body = this.editor.getHTML()
            console.log(this.formAttrs.body)
            this.$inertia.post(this.postRoute, this.formAttrs)
        }
    },
    mounted() {
        // this.editor = new Editor({
        //     content: this.formAttrs.body,
        //     extensions: defaultExtensions(),
        //     editorProps: {
        //         attributes: {
        //             class:
        //                 "prose prose-sm sm:prose lg:prose-lg xl:prose-2xl m-5 focus:outline-none"
        //         }
        //     }
        // })
    },

    beforeUnmount() {
        this.editor.destroy()
    },
}
</script>
