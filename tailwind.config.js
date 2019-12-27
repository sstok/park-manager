module.exports = {
    theme: {
        extend: {}
    },
    variants: {
        backgroundColor: ['hover', 'focus'],
        textColor: ['hover', 'focus'],
        fontStyle: ['default'],
    },
    corePlugins: {
        backgroundAttachment: false,
        backgroundPosition: false,
        backgroundRepeat: false,
        backgroundSize: false,

        placeholderColor: ['focus', 'hover', 'active'],
    },
    plugins: []
};
