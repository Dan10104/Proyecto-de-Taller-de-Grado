import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
export default defineConfig({publicDir:false,plugins:[vue()],base:'/build/qr-test/',build:{outDir:'public/build/qr-test',emptyOutDir:true,rollupOptions:{input:'tests/Browser/qr-scanner.html'}}});
