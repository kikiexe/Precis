import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
let vitePreprocess;

try {
  const plugin = await import('@sveltejs/vite-plugin-svelte');
  vitePreprocess = plugin.vitePreprocess;
} catch {
  try {
    const plugin = require('@sveltejs/vite-plugin-svelte');
    vitePreprocess = plugin.vitePreprocess;
  } catch {
    vitePreprocess = () => ({});
  }
}

export default {
  preprocess: vitePreprocess()
};
