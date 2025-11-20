/**
 * Tailwind configuration
 */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/**/*.php',
        './vendor/filament/**/*.blade.php',
        '../../node_modules/preline/dist/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {},
    },
    plugins: [],
};
