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
            <span>${{ account.price }}</span>
          </div>
          <div class="flex items-center justify-center gap-4 mt-2">
            <button @click="addToCart(account)" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
              Add to Cart
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- Cart Sidebar -->
    <div
        v-if="cartOpen"
        class="fixed top-0 right-0 w-64 bg-gray-800 h-full shadow-lg p-4 overflow-auto"
        @click="toggleCart"
    >
      <h2 class="text-2xl font-bold mb-4">🛒 Cart</h2>
      <ul v-if="cartItems.length > 0" class="space-y-4">
        <li v-for="item in cartItems" :key="item.id" class="border-b border-gray-600 pb-2">
          <div class="flex justify-between">
            <span>{{ item.name }} (x{{ item.quantity }})</span>
            <span>${{ item.finalPrice * item.quantity }}</span>
          </div>
          <div class="flex gap-2 mt-2">
            <button @click="updateItemQuantity(item.id, item.quantity - 1)" class="bg-red-500 text-white px-2 rounded">-</button>
            <button @click="updateItemQuantity(item.id, item.quantity + 1)" class="bg-green-500 text-white px-2 rounded">+</button>
            <button @click="removeItemFromCart(item.id)" class="bg-red-500 text-white px-2 rounded">Remove</button>
          </div>
        </li>
      </ul>
      <p v-else>Your cart is empty.</p>
      <div class="mt-4">
        <p class="text-lg font-bold">Total: ${{ totalPrice }}</p>
        <button class="bg-green-500 w-full py-2 mt-2 rounded">Checkout</button>
      </div>
    </div>

    <!-- Toggle Cart Button -->
    <button @click="toggleCart" class="fixed top-4 right-4 bg-yellow-500 text-black p-4 rounded-full">
      Cart ({{ totalItems }})
    </button>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRuntimeConfig } from '#app'
import { useAccountStore } from '~/stores/accountStore'
import { useCartStore } from '~/stores/cartStore.js'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const accountStore = useAccountStore()
const { loading, error, fetchAccounts } = accountStore
const { accounts } = storeToRefs(accountStore)

const cartStore = useCartStore()
const { addItemToCart, removeItemFromCart, updateItemQuantity } = cartStore
const { cartItems, totalItems, totalPrice } = storeToRefs(cartStore)

const cartOpen = ref(false)

const toggleCart = () => {
  cartOpen.value = !cartOpen.value
}

const addToCart = (item) => {
  const priceAfterDiscount = item.discount ? (item.price - item.discount) : item.price
  addItemToCart({
    id: item.id,
    name: item.title,
    finalPrice: priceAfterDiscount,
    quantity: 1
  })
}

onMounted(() => {
  fetchAccounts()
})
</script>
