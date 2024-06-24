import '../css/app.css';

// Import modules...
import {createApp, h} from 'vue';
// import {App as InertiaApp, plugin as InertiaPlugin} from '@inertiajs/inertia-vue3';
import {createInertiaApp} from '@inertiajs/vue3'
import {dom, library} from "@fortawesome/fontawesome-svg-core";
import {fas} from "@fortawesome/free-solid-svg-icons";
import {fab} from "@fortawesome/free-brands-svg-icons";
import Headers from './Layouts/Headers.vue';
// import('./Filament/filament-chart-plugins');
import ChartDataLabels from 'chartjs-plugin-datalabels'

window.filamentChartJsPlugins ??= []
window.filamentChartJsPlugins.push(ChartDataLabels)
// window.getImageUrl ??= name => new URL(`/${name}`, import.meta.url).href


dom.watch()

const el = document.getElementById('app');
library.add(fas)
library.add(fab)

// let app = createApp({
//     render: () =>
//         h(InertiaApp, {
//             initialPage: JSON.parse(el.dataset.page),
//             resolveComponent: name => import(`./Pages/${name}.vue`)
//                 .then(({default: page}) => {
//                     if (page.layout === undefined) {
//                         // console.log(page)
//                         if (page.components && !Object.keys(page.components)
//                             .includes('BreezeAuthenticatedLayout')){
//                             page.layout = Headers
//                         }
//                     }
//                     return page
//                 }),
//         }),
// })
//     .mixin({methods: {route}})
//     .use(InertiaPlugin)
//     // .use(ChartDataLabels)
//     .use(Toaster)
//     .mount(el);
let app = createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })

        let page = pages[`./Pages/${name}.vue`]

            if (page.layout === undefined) {
                if (page.default.components
                    && !Object.keys(page.default.components)
                    .includes('BreezeAuthenticatedLayout')){
                    page.default.layout = Headers
                }
            }
        return page
    },
    progress: {
        color: '#05FaB7'
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})



// Echo.channel('contacts').listen('ContactReqCreated', (e) => {
//     app.$toast.success(e.name + " just made contact!", {
//         onClick: function () {
//             app.$inertia.get('/contact')
//         },
//     })
// })
//
// window.Echo.connector.pusher.connection.bind('connected', (payload) => {
//     app.$toast.success("Websocket Connected!")
// })
