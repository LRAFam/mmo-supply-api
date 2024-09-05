import {defineStore} from 'pinia'
import {ref} from 'vue'
import axios from 'axios'

export const useItemStore = defineStore('items', () => {
    // State
    const data = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Actions
    const fetchItems = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await axios.get('http://localhost:8000/api/items')
            data.value = response.data
        } catch (err) {
            error.value = err.message
        } finally {
            loading.value = false
        }
    }

    // Expose state and actions
    return {
        data, loading, error, fetchItems
    }
})
