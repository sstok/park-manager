const colors = require('tailwindcss/colors');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./assets/**/*.js", "./templates/*.html.twig", "./templates/**/*.html.twig",
    ],
    theme: {
        extend: {
            colors: {
                //gray: colors.blueGray
                'blue-gray': colors.slate,
                'light-blue': colors.sky,
                cyan: colors.cyan,
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('@tailwindcss/aspect-ratio'),
    ],
}
