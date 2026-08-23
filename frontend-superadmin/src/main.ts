import { mount } from 'svelte';
import './app.css';
import App from './App.svelte';

const appElement = document.getElementById('app');

if (!appElement) {
  throw new Error('Elemen #app tidak ditemukan pada DOM.');
}

const app = mount(App, {
  target: appElement,
});

export default app;
