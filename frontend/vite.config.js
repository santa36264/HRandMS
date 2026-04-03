import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [
      vue(),
    ],

    resolve: {
      alias: {
        // Use @/ as shorthand for src/
        '@': resolve(__dirname, 'src'),
      },
    },

    build: {
      // Output directory (served by nginx / Apache)
      outDir: 'dist',

      // Emit source maps in staging; disable in production for smaller bundles
      sourcemap: mode === 'staging',

      // Warn when a chunk exceeds 500 kB
      chunkSizeWarningLimit: 500,

      rollupOptions: {
        output: {
          // Split vendor code into a separate chunk for better long-term caching
          manualChunks(id) {
            if (id.includes('node_modules')) {
              if (id.includes('vue') || id.includes('pinia')) return 'vue'
              if (id.includes('chart.js')) return 'charts'
              if (id.includes('qrcode')) return 'qrcode'
            }
          },
          // Deterministic filenames with content hash for cache-busting
          chunkFileNames:  'assets/js/[name]-[hash].js',
          entryFileNames:  'assets/js/[name]-[hash].js',
          assetFileNames:  'assets/[ext]/[name]-[hash].[ext]',
        },
      },

      // Minify with oxc (Vite 8 default)
      minify: 'oxc',
    },

    server: {
      port: 5173,
      host: true,
      proxy: {
        '/api': {
          target: 'http://localhost:8000',
          changeOrigin: true,
          secure: false,
        },
      },
    },

    preview: {
      port: 4173,
      host: true,
      proxy: {
        '/api': {
          target: 'http://localhost:8000',
          changeOrigin: true,
          secure: false,
        },
      },
    },
  }
})
