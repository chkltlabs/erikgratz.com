
// import tailwindPrimary from "./tailwind.primary.config.mjs";
// import tailwindSecondary from "./tailwind.secondary.config.mjs";
//
// export default {
//     tailwindCss(tailwindPrimary),
//     tailwindCss(tailwindSecondary)],
// };

import tailwindPrimary from "./tailwind.config.js"

module.exports = {
    plugins: {
        // 'postcss-preset-env': {},
        // 'postcss-import': {},
        tailwindcss: {},
        autoprefixer: {},
    }
};
