import { defineNuxtConfig } from 'nuxt/config'

export default defineNuxtConfig({
  compatibilityDate: '2024-04-03',
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],
  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {},
    },
  },
  runtimeConfig: {
    public: {
      API_BASE_URL: process.env.API_BASE || 'http://localhost:8000',
    },
  },
  modules: ['@pinia/nuxt', 'nuxt-auth-sanctum'],
  sanctum: {
    baseUrl: 'http://localhost:8000',
    endpoints: {
      csrf: '/sanctum/csrf-cookie',
      login: '/api/auth/login',
      logout: '/api/auth/logout',
      user: '/api/user',
    },
  },
  plugins: [
    '~/plugins/axios.ts',
  ],
})