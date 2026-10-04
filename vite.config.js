import { defineConfig } from 'vite';
import liveReload from 'vite-plugin-live-reload';

export default defineConfig({
  publicDir: false,
  plugins: [
    // Automatically reloads the browser when you save a PHP view/layout
    liveReload(['app/Views/**/*.php']),
  ],
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: {
        app: 'resources/js/app.js',
        style: 'resources/css/app.css',
      },
    },
  },
  server: {
    strictPort: true,
    port: 5173,
    cors: true,
  },
});