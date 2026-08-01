import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { calculateSourceHash } from './scripts/build-source-hash.mjs';

export default defineConfig(async () => {
    const { hash: frontendSourceHash } = await calculateSourceHash();

    return {
        define: {
            __FRONTEND_SOURCE_HASH__: JSON.stringify(frontendSourceHash),
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/main.tsx'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            react(),
            tailwindcss(),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
