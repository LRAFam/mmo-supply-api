import {defineStore} from 'pinia'
import {ref} from 'vue'
import axios from 'axios'

export const useCurrencyStore = defineStore('currencies', () => {
    // State
    const currencies = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Actions
    const fetchCurrencies = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await axios.get('http://localhost:8000/api/currencies')
            currencies.value = response.data
        } catch (err) {
            error.value = err.message
        } finally {
            loading.value = false
        }
    }

    // Expose state and actions
    return {
        currencies, loading, error, fetchCurrencies
    }
})
