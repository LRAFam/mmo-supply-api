<template>
  <div class="p-6 bg-gray-900 min-h-screen text-white">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <h2 class="text-3xl font-bold mb-4">🌌 Summoning Epic Deals... 🌌</h2>
        <p class="text-xl">Hold tight, your adventure is loading!</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center bg-red-600 p-6 rounded-lg shadow-lg">
        <h2 class="text-3xl font-bold mb-4">⚠️ Oops! Something Went Wrong! ⚠️</h2>
        <p class="text-xl mb-4">{{ error }}</p>
        <button @click="retry" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
          Try Again
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div v-for="game in games" :key="game.id" class="bg-gray-800 p-4 rounded-lg shadow-lg">
          <img :src="`${apiBase}/storage/${game.logo}`" :alt="game.title" class="w-full h-32 object-contain rounded-lg mb-4" />
          <h2 class="text-2xl font-bold mb-2">{{ game.title }}</h2>
          <p class="text-lg mb-4">Providers: {{ game.provider_count }}</p>
          <button class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">
            View Details
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRuntimeConfig } from '#app'
import { useGameStore } from '~/stores/gameStore'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const gameStore = useGameStore()
const { loading, error, fetchGames } = gameStore
const { games } = storeToRefs(gameStore)

// Debugging statement to check the API base URL
console.log('API Base URL:', apiBase)

onMounted(() => {
  fetchGames()
})
</script>

<style lang="scss" scoped>
/* Add any scoped styles here */
</style>
