import {defineStore} from 'pinia'
import {ref} from 'vue'
import axios from 'axios'

export const useGameStore = defineStore('games', () => {
    // State
    const games = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Actions
    const fetchGames = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await axios.get('http://localhost:8000/api/games')
            games.value = response.data
        } catch (err) {
            error.value = err.message
        } finally {
            loading.value = false
        }
    }

    // Expose state and actions
    return {
        games, loading, error, fetchGames
    }
})
