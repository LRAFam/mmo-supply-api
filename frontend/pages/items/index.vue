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
        <div v-for="item in items" :key="item.id" class="bg-gray-800 p-4 rounded-lg shadow-lg">
          <img :src="`${apiBase}/storage/${item.images}`" :alt="item.name" class="w-full h-32 object-contain rounded-lg mb-4" />
          <h2 class="text-2xl font-bold mb-2">{{ item.name }}</h2>
          <p class="text-lg mb-4">{{ item.description }}</p>
          <div v-if="item.discount" class="flex items-center justify-center gap-2">
            <span class="line-through">${{ item.price }}</span>
            <span>${{ (item.price - item.discount).toFixed(2) }}</span>
          </div>
          <div v-else>
            <span>${{ item.price.toFixed(2) }}</span>
          </div>
          <div class="flex items-center justify-center gap-4 mt-2">
            <button class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
              Details
            </button>
            <button @click="addToCart(item)" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">
              Add to Cart
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- Cart Button -->
    <button
        v-if="!cartOpen"
        @click="toggleCart"
        class="fixed top-4 right-4 bg-orange-500 text-white font-bold py-2 px-4 rounded-lg z-50"
    >
      🛒 Open Cart ({{ totalItems }})
    </button>

    <!-- Cart Sidebar -->
    <div
        class="fixed top-0 right-0 w-80 h-full bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out z-40"
        :class="cartOpen ? 'translate-x-0' : 'translate-x-full'"
    >
      <div class="p-6 flex flex-col h-full">
        <!-- Close Button -->
        <button
            @click="toggleCart"
            class="self-end mb-4 text-white bg-red-600 hover:bg-red-700 py-2 px-4 rounded-lg"
        >
          ❌ Close
        </button>

        <!-- Cart Content -->
        <h2 class="text-2xl font-bold mb-4 text-orange-400">Your Cart</h2>

        <!-- Items List -->
        <div class="flex-grow overflow-y-auto">
          <div v-for="item in cartItems" :key="item.id" class="mb-4">
            <div class="flex justify-between">
              <span>{{ item.name }} (x{{ item.quantity }})</span>
              <span>${{ item.finalPrice.toFixed(2) }}</span>
            </div>
            <div class="flex items-center space-x-2 mt-2">
              <button @click="updateItemQuantity(item.id, item.quantity - 1)" class="bg-gray-700 text-white px-2">-</button>
              <span>{{ item.quantity }}</span>
              <button @click="updateItemQuantity(item.id, item.quantity + 1)" class="bg-gray-700 text-white px-2">+</button>
              <button @click="removeItemFromCart(item.id)" class="bg-red-600 text-white px-2 ml-auto">Remove</button>
            </div>
          </div>
        </div>

        <!-- Total Price -->
        <div class="mt-auto">
          <hr class="mb-4 border-gray-600">
          <div class="flex justify-between text-lg font-bold text-white">
            <span>Total:</span>
            <span>${{ totalPrice }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Background Overlay -->
    <div
        class="fixed inset-0 bg-black opacity-50 z-30 transition-opacity duration-300"
        v-if="cartOpen"
        @click="toggleCart"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRuntimeConfig } from '#app'
import { useItemStore } from '~/stores/itemStore'
import { useCartStore } from '~/stores/cartStore'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const itemStore = useItemStore()
const { loading, error, fetchItems } = itemStore
const { items } = storeToRefs(itemStore)

const cartStore = useCartStore()

const { addItemToCart, removeItemFromCart, updateItemQuantity } = cartStore
const { cartItems, totalItems, totalPrice } = storeToRefs(cartStore)

const cartOpen = ref(false)
const toggleCart = () => {
  cartOpen.value = !cartOpen.value
}

// Add item to cart
const addToCart = (item) => {
  const priceAfterDiscount = item.discount ? (item.price - item.discount) : item.price
  addItemToCart({
    id: item.id,
    name: item.name,
    finalPrice: priceAfterDiscount,
    quantity: 1
  })
}

// Fetch items on mount
onMounted(() => {
  fetchItems()
})
</script>
