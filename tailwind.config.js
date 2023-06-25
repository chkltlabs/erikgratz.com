const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require('tailwindcss/colors');
colors.black = '#12151F';
module.exports = {
    purge: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        colors: colors,
        extend: {
            colors: {
                danger: colors.rose,
                primary: colors.blue,
                success: colors.green,
                warning: colors.yellow,
            },
            typography: (theme) => ({
                dark: {
                    css: {
                        color: theme('colors.gray.300'),
                        blockquote: {
                            color: theme('colors.gray.300'),
                        },
                        a: {
                            color: '#A78BFA',
                            '&:hover': {
                                color: '#A78BFA',
                            },
                        },
                        code: {
                            backgroundColor: theme('colors.blue.700'),
                            paddingLeft: '5px',
                            paddingRight: '5px',
                            borderRadius: '4px',
                        },
                        svg: {
                            color: theme('colors.gray.100')
                        },

                        h1: {
                            color: theme('colors.gray.300'),
                        },
                        h2: {
                            color: theme('colors.gray.300'),
                        },
                        h3: {
                            color: theme('colors.gray.300'),
                        },
                        h4: {
                            color: theme('colors.gray.300'),
                        },
                        h5: {
                            color: theme('colors.gray.300'),
                        },
                        h6: {
                            color: theme('colors.gray.300'),
                        },

                        strong: {
                            color: theme('colors.gray.300'),
                        },

                        // code: {
                        //     color: theme('colors.gray.300'),
                        // },

                        figcaption: {
                            color: theme('colors.gray.500'),
                        },
                    },
                }
            }),
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'heart-purple': '#371BB1',
                'mint': '#05FaB7',
                'dark-black': '#000',
                'pale-gold': '#e6c792',
                'forest-green': '#25372f',
                'dopegray' : '#515159',
            },
            backgroundImage: theme => ({
                'kid-face': "url('/storage/images/webp/kid-face.webp')",
                'code-wall': "url('/storage/images/webp/purp-code.webp')",
                'bread-roses': "url('/storage/images/bread-and-roses.jpeg')",
                'landscape': "url('/storage/images/webp/landscape.webp')",
                'amy-erik': "url('/storage/images/webp/us.webp')",
            }),
        },
    },

    variants: {
        extend: {
            opacity: ['disabled'],
            cursor: ['disabled'],
            typography: ['dark', 'responsive'],
        },
    },

    content: [
        './resources/**/*.blade.php',
        './vendor/filament/**/*.blade.php', 
    ],

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),

    ],
};
