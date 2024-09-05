import {defineStore} from 'pinia'
import {ref} from 'vue'
import axios from 'axios'

export const useAccountStore = defineStore('accounts', () => {
    // State
    const data = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Actions
    const fetchAccounts = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await axios.get('http://localhost:8000/api/accounts')
            data.value = response.data
        } catch (err) {
            error.value = err.message
        } finally {
            loading.value = false
        }
    }

    // Expose state and actions
    return {
        data, loading, error, fetchAccounts
    }
})
