<div>
    <main>
        <nav
            class="bg-black shadow animated fixed flex w-full"
            :class="{ 'scrolled': !view.atTopOfPage}"
            role="navigation"
        >
            <div x-data="{ unselectedColor: text-gray-300,  selectedColor: text-purple-600 }" class="container mx-auto p-4 flex flex-wrap items-center md:flex-no-wrap">
                <div class="mr-4 md:mr-8">
                    <a href="/admin/login" rel="login" wire:navigate>
                        <svg class="w-10 h-10 text-purple-600 transition duration-1000 transform hover:scale-150"
                             width="54" height="54" viewBox="0 0 305.033 305.033" xmlns="http://www.w3.org/2000/svg">
                            <title>Chocolate Labs</title>
                            <path fill="currentColor"
                                  d="M303.273,193.23l-67.419-67.419l3.21-0.994c2.009-0.621,3.545-2.249,4.05-4.29c0.505-2.041-0.095-4.197-1.582-5.684  l-31.166-31.166c-1.126-1.126-2.651-1.758-4.243-1.758c-1.591,0-3.118,0.632-4.243,1.758l-4.081,4.081L111.803,1.758  C110.678,0.632,109.152,0,107.561,0c-1.591,0-3.118,0.632-4.243,1.758L52.562,52.516c-0.007,0.007-0.017,0.012-0.024,0.02  c-0.007,0.008-0.012,0.017-0.02,0.024L1.76,103.318c-2.343,2.343-2.343,6.142,0,8.484l45.103,45.103  c0.007,0.007,0.011,0.015,0.018,0.021c0.006,0.007,0.015,0.011,0.021,0.018l40.859,40.859l-4.081,4.081  c-2.343,2.343-2.343,6.142,0,8.484l31.163,31.166c1.139,1.14,2.671,1.758,4.243,1.758c0.48,0,0.964-0.058,1.441-0.176  c2.041-0.505,3.669-2.042,4.291-4.05l0.994-3.211l67.418,67.419c1.126,1.126,2.651,1.758,4.243,1.758  c1.591,0,3.118-0.632,4.243-1.758l101.558-101.561C305.616,199.372,305.616,195.573,303.273,193.23z M147.025,138.537  l-36.636-36.636l42.292-42.294l36.635,36.636L147.025,138.537z M107.561,14.485l36.636,36.637l-42.293,42.294L65.268,56.78  L107.561,14.485z M56.783,65.265l36.637,36.637l-42.295,42.296l-36.636-36.637L56.783,65.265z M59.609,152.682l42.295-42.296  l36.636,36.637l-42.294,42.296L59.609,152.682z M96.408,206.127l4.08-4.08c0.001-0.001,0.001-0.001,0.001-0.001  c0.001,0,0.001-0.001,0.001-0.001l50.765-50.768c0.004-0.004,0.01-0.007,0.015-0.012c0.004-0.005,0.007-0.01,0.012-0.015  l54.843-54.846l19.89,19.89l-35.63,11.028c-1.892,0.585-3.373,2.066-3.958,3.957l-13.033,42.113l-42.112,13.033  c-1.892,0.585-3.373,2.066-3.958,3.958l-11.027,35.632L96.408,206.127z M197.473,290.548l-67.65-67.65l8.029-25.943l42.112-13.033  c1.892-0.585,3.372-2.066,3.958-3.958l13.033-42.112l25.94-8.028l67.65,67.65L197.473,290.548z"/>
                        </svg>
                    </a>
                </div>
                <div class="ml-auto md:hidden">
                    <button @click="isNavOpen = !isNavOpen"
                            class="flex items-center px-3 py-2 border rounded border-purple-600" type="button">
                        <svg class="h-3 w-3 text-purple-600" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <title>Menu</title>
                            <path fill="currentColor" d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/>
                        </svg>
                    </button>
                </div>
                <div class="w-full md:w-auto md:flex-grow md:flex md:items-center">
                    <ul v-if="isNavOpen"
                        class="flex flex-col mt-4 -mx-4 pt-4 border-t md:flex-row md:items-center md:mx-0 md:mt-0 md:pt-0 md:mr-4 lg:mr-8 md:border-0">
                        <li>
                            <a class="block px-4 py-1 md:p-2 lg:px-4"
                               :class=" 'text-gray-300': $memo.name == 'page.home', 'text-purple-600': $memo.name !== 'page.home'"
                               href="/redo" wire:navigate
                               title="Link">Home
                            </a>
                        </li>
{{--                        <li>--}}
{{--                            <a class="block px-4 py-1 md:p-2 lg:px-4 text-purple-600"--}}
{{--                               :class="($page.component === 'Blog') ? selectedColor : unselectedColor"--}}
{{--                               href="redo/blog" wire:navigate--}}
{{--                               title="Active Link">Blog--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li>--}}
{{--                            <a class="block px-4 py-1 md:p-2 lg:px-4 text-purple-600"--}}
{{--                                :class="($page.component === 'Portfolio') ? selectedColor : unselectedColor"--}}
{{--                                href="/redo/portfolio"  wire:navigate--}}
{{--                                title="Link">Portfolio--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li v-if="!['Home','Blog','Portfolio'].includes( $page.component)">--}}
{{--                            <a class="block px-4 py-1 md:p-2 lg:px-4 text-purple-600"--}}
{{--                                         :class="selectedColor"--}}
{{--                                         :href="$page.url" title="Link"> name--}}
{{--                            </a>--}}
{{--                        </li>--}}
                    </ul>
                    <ul class="flex flex-col mt-4 -mx-4 pt-4 border-t md:flex-row md:items-center md:mx-0 md:ml-auto md:mt-0 md:pt-0 md:border-0">
                        <li>
                            <a href="https://docs.google.com/document/d/1Ow6s1QOBQPHEGn06V042dchp2wSQ8XfkAA4gcKqa_lg/edit?usp=sharing">
                                <button class="mx-8 px-7 py-3 md:px-9 md:py-4
                            bg-black font-medium md:font-semibold
                            text-gray-300 text-md rounded-md
                            hover:bg-purple-600 hover:text-white
                            transition ease-linear duration-300"
                                >Get my CV</button>
                            </a>
                        </li>
                        <li>
                            <img :src="this.imgUrl"
                                 alt="..."
                                 class="ring-mint shadow rounded-full h-12 align-middle border-none transition duration-1000 transform hover:scale-150"
                                 v-on:click="toggleContactMenu"
                                 ref="faceButton"
                            />
                            <contact-box
                                v-if="this.isMenuOpen"
                                v-click-away="closeContactMenu"
                                v-on:close-box-from-inside="this.isMenuOpen = false"
                                :class="{ 'scrolled': !view.atTopOfPage}"
                            />
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </main>
    {{ $slot }}
</div>