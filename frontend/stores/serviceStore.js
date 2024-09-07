import {defineStore} from 'pinia'
import {ref} from 'vue'
import axios from 'axios'

export const useServiceStore = defineStore('services', () => {
    // State
    const services = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Actions
    const fetchServices = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await axios.get('http://localhost:8000/api/services')
            services.value = response.data
        } catch (err) {
            error.value = err.message
        } finally {
            loading.value = false
        }
    }

    // Expose state and actions
    return {
        services, loading, error, fetchServices
    }
})
