window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// let token = document.head.querySelector('meta[name="csrf-token"]');
// if (token) {
//     window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
//     window.axios.defaults.headers.common['X-XSRF-TOKEN'] = token.content;
//     window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
// } else {
//     console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
// }
// console.log(window.axios.defaults.headers.common)
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Pusher.log = function(message){
     //window.console.log(message)
}

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     wsHost: process.env.MIX_PUSHER_HOST,
//     wsPort: 6001,
//     forceTLS: false,
//     disableStats: true,
//     scheme: process.env.MIX_PUSHER_SCHEME
// });
