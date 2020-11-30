const colors = require('tailwindcss/colors');

module.exports = {
    theme: {
        extend: {
            colors: {
                //gray: colors.blueGray
                'blue-gray': colors.blueGray,
                'light-blue': colors.lightBlue,
                cyan: colors.cyan,
            }
        }
    },
    variants: {
        backgroundColor: ['hover', 'focus'],
        textColor: ['hover', 'focus'],
    },
    corePlugins: {
        backgroundAttachment: false,
        backgroundPosition: false,
        backgroundRepeat: false,
        backgroundSize: false,

        placeholderColor: ['focus', 'hover', 'active'],
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('@tailwindcss/aspect-ratio'),
    ]
};
