import js from '@eslint/js';
import ts from 'typescript-eslint';
import svelte from 'eslint-plugin-svelte';
import svelteParser from 'svelte-eslint-parser';
import tailwind from 'eslint-plugin-tailwindcss';

export default ts.config(
  js.configs.recommended,
  ...ts.configs.recommended,
  ...svelte.configs['flat/recommended'],
  {
    ...tailwind.configs.recommended,
    files: ['frontend-*/**/*.svelte', 'frontend-*/**/*.ts', 'frontend-*/**/*.js'],
  },
  {
    ignores: [
      '**/node_modules/**',
      '**/dist/**',
      '**/build/**',
      '**/.svelte-kit/**',
      '**/backend/**',
      '**/vendor/**',
      '**/.tempmediaStorage/**',
      '**/.user_uploaded/**',
    ],
  },
  {
    plugins: {
      tailwindcss: tailwind,
    },
    settings: {
      tailwindcss: {
        css: 'frontend-app/src/app.css',
      },
    },
    languageOptions: {
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
      },
    },
    rules: {
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/no-unused-vars': [
        'warn',
        { argsIgnorePattern: '^_', varsIgnorePattern: '^_' },
      ],
      'no-undef': 'off',
      'no-useless-assignment': 'warn',
      'tailwindcss/no-custom-classname': 'off',
      'tailwindcss/enforces-shorthand': 'warn',
    },
  },
  {
    files: ['**/*.svelte', '**/*.svelte.ts', '**/*.svelte.js'],
    languageOptions: {
      parser: svelteParser,
      parserOptions: {
        parser: ts.parser,
        extraFileExtensions: ['.svelte'],
      },
    },
    rules: {
      'prefer-const': 'off', // Svelte 5 runes ($props, $state, $derived) use let
      'svelte/no-at-html-tags': 'warn',
      'svelte/valid-compile': 'off',
      'svelte/require-each-key': 'off',
      'svelte/no-useless-mustaches': 'warn',
      'svelte/prefer-svelte-reactivity': 'off',
      'svelte/prefer-writable-derived': 'off',
      'tailwindcss/no-custom-classname': 'off',
    },
  }
);
