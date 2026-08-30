export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        ink: '#16302B',
        paper: '#F7F5F0',
        primary: {
          DEFAULT: '#2F6F62',
          dark: '#234F45',
          light: '#DCEAE6',
        },
        accent: '#E2A33D',
        link: '#C1584A',
        muted: '#8B9490',
      },
      fontFamily: {
        display: ['Fraunces', 'serif'],
        sans: ['Inter', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'monospace'],
      },
    },
  },
  plugins: [require('@tailwindcss/forms')],
};
