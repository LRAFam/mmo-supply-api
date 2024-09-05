import {defineStore} from 'pinia'
import {ref} from 'vue'
import axios from 'axios'

export const useAdvertisementStore = defineStore('advertisements', () => {
    // State
    const data = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Actions
    const fetchAdvertisements = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await axios.get('http://localhost:8000/api/advertisements')
            data.value = response.data
        } catch (err) {
            error.value = err.message
        } finally {
            loading.value = false
        }
    }

    // Expose state and actions
    return {
        data, loading, error, fetchAdvertisements
    }
})
