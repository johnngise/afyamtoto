import { resolve } from "node:path";
import { defineConfig } from "vite";

export default defineConfig({
  esbuild: {
    jsx: "automatic",
  },
  build: {
    emptyOutDir: false,
    minify: true,
    outDir: resolve(import.meta.dirname, "../public/assets/js"),
    rollupOptions: {
      input: resolve(import.meta.dirname, "php-ui/site-navigation.tsx"),
      output: {
        entryFileNames: "react-site.js",
        chunkFileNames: "react-chunk-[hash].js",
        assetFileNames: "react-asset-[hash][extname]",
      },
    },
    sourcemap: false,
  },
});
