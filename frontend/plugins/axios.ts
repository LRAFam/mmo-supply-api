import { defineNuxtPlugin } from '#app';
import axios from 'axios';
import { useRouter } from 'vue-router';

export default defineNuxtPlugin((nuxtApp) => {
    const runtimeConfig = useRuntimeConfig();
    const router = useRouter();

    const api = axios.create({
        baseURL: runtimeConfig.public.API_BASE_URL,
        withCredentials: true,
    });

    console.log('apiBase: ', runtimeConfig.public.API_BASE_URL);

    // Request interceptor to add the Authorization header
    api.interceptors.request.use((config) => {
        const authStore = useAuthStore();
        if (authStore.accessToken) {
            config.headers.Authorization = `Bearer ${authStore.accessToken}`;
        }
        return config;
    }, (error) => Promise.reject(error));

    // Response interceptor for global error handling
    api.interceptors.response.use((response) => response, (error) => {
        const status = error.response ? error.response.status : null;
        const authStore = useAuthStore(); // Access the auth store

        switch (status) {
            case 401:
                authStore.handleUnauthorized(); // Let the authStore handle 401
                router.push('/login'); // Redirect to login page
                break;

            case 403:
                authStore.handleForbidden(); // Let the authStore handle 403
                break;

            case 404:
                authStore.handleNotFound(); // Handle not found case
                break;

            case 500:
                authStore.handleServerError(); // Handle server error case
                break;

            default:
                if (!error.response) {
                    authStore.handleNetworkError(); // Handle network issues
                }
                break;
        }

        return Promise.reject(error); // Continue rejecting for individual handling
    });

    // Provide the axios instance globally in the Nuxt app
    nuxtApp.provide('axios', api);
});
