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
      <div class="h-screen overflow-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div v-for="currency in currencies" :key="currency.id" class="bg-gray-800 p-4 rounded-lg shadow-lg">
          {{ currency.user.name }}
          {{ currency.stock }}
          {{ currency.rate }}
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRuntimeConfig } from '#app'
import { useCurrencyStore } from '~/stores/currencyStore'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const currencyStore = useCurrencyStore()
const { loading, error, fetchCurrencies } = currencyStore
const { currencies } = storeToRefs(currencyStore)

// Debugging statement to check the API base URL
console.log('API Base URL:', apiBase)

onMounted(() => {
  fetchCurrencies()
})
</script>

<style lang="scss" scoped>
/* Add any scoped styles here */
</style>
