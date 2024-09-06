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
        <div v-for="account in accounts" :key="account.id" class="bg-gray-800 p-4 rounded-lg shadow-lg">
          <img :src="`${apiBase}/storage/${account.images}`" :alt="account.title" class="w-full h-32 object-contain rounded-lg mb-4" />
          <h2 class="text-2xl font-bold mb-2">{{ account.title }}</h2>
          <p class="text-lg mb-4">{{ account.description }}</p>
          <div v-if="account.discount" class="flex items-center justify-center gap-2">
            <span class="line-through">${{ account.price }}</span>
            <span>${{ account.price - account.discount }}</span>
          </div>
          <div v-else class="">
            <span class="line-through">${{ account.price }}</span>
          </div>
          <div class="flex items-center justify-center gap-4 mt-2">
            <button class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
              Details
            </button>
            <button class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
              Purchase
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRuntimeConfig } from '#app'
import { useAccountStore } from '~/stores/accountStore'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const accountStore = useAccountStore()
const { loading, error, fetchAccounts } = accountStore
const { accounts } = storeToRefs(accountStore)

// Debugging statement to check the API base URL
console.log('API Base URL:', apiBase)

onMounted(() => {
  fetchAccounts()
})
</script>

<style lang="scss" scoped>
/* Add any scoped styles here */
</style>
