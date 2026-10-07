import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * Tailwind only styles the pagination links (Laravel + Livewire views).
 * The rest of the app uses the custom CSS modules in resources/css/modules,
 * so preflight (global reset) and the `container` utility are disabled to
 * avoid overriding them.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/livewire/livewire/src/Features/SupportPagination/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    corePlugins: {
        preflight: false,
        container: false,
    },

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [],
};
