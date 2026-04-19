import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                xs:   ['0.8125rem', { lineHeight: '1.25rem'  }],  // 13px (was 12px)
                sm:   ['0.9375rem', { lineHeight: '1.5rem'   }],  // 15px (was 14px)
                base: ['1.0625rem', { lineHeight: '1.75rem'  }],  // 17px (was 16px)
                lg:   ['1.125rem',  { lineHeight: '1.75rem'  }],  // 18px
                xl:   ['1.25rem',   { lineHeight: '1.875rem' }],  // 20px
                '2xl':['1.5rem',    { lineHeight: '2rem'     }],
                '3xl':['1.875rem',  { lineHeight: '2.25rem'  }],
            },
            colors: {
                brand: {
                    50:  '#f0faf4',
                    100: '#dcf5e7',
                    200: '#bbe9cf',
                    300: '#86d4a9',
                    400: '#52b788',
                    500: '#2d6a4f',
                    600: '#1a4731',
                    700: '#163d29',
                    800: '#123322',
                    900: '#0d2619',
                },
            },
        },
    },

    plugins: [forms],
};
