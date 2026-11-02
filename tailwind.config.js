export default {
    content: ['./resources/**/*.blade.php', './resources/**/*.js', './public/assets/app.js'],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                mono: ['JetBrains Mono', 'monospace'],
            },
            colors: {
                brand: { DEFAULT: '#00B090', 50: '#EDFAF8', 100: '#D5F4EF', 200: '#ADE8DF', 500: '#00B090', 600: '#009B7E', 700: '#007F67' },
            },
        },
    },
    plugins: [],
};
