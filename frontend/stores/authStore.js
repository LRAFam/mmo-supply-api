import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('authToken') || null)

    const setToken = (newToken) => {
        token.value = newToken
        localStorage.setItem('authToken', newToken)
    }

    const getToken = () => token.value

    return {
        token,
        setToken,
        getToken,
    }
})
