import { mount } from 'svelte';
import './app.css';
import App from './App.svelte';

// Aktifkan Eruda Mobile DevTools pada development mode
if (import.meta.env.DEV) {
  import('eruda')
    .then((eruda) => {
      eruda.default.init();
    })
    .catch(() => {
      // abaikan jika gagal memuat eruda
    });
}

const target = document.getElementById('app');

if (!target) {
  throw new Error('Root element #app not found.');
}

const app = mount(App, {
  target,
});

export default app;
