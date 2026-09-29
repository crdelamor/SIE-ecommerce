import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  // Rutas relativas en el build: el contenido de dist/ se sube tal cual a public_html/
  base: './',
  server: {
    // En local, las llamadas a /api las atiende el servidor de PHP (ver README)
    proxy: {
      '/api': 'http://localhost:8000',
    },
  },
});
