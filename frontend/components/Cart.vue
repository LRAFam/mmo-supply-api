<template>
  <div>
    <!-- Cart Button -->
    <button
        v-if="!cartOpen"
        @click="toggleCart"
        class="fixed top-4 right-4 bg-orange-500 text-white font-bold py-2 px-4 rounded-lg z-50"
    >
      🛒 Open Cart ({{ totalItems }})
    </button>

    <!-- Sidebar (Cart) -->
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
import {ref} from 'vue'
import {useCartStore} from '~/stores/cartStore'

const cartStore = useCartStore()

const cartOpen = ref(false)
const toggleCart = () => {
  cartOpen.value = !cartOpen.value
}

// Expose the store properties and methods
const {cartItems, totalItems, totalPrice, addItemToCart, removeItemFromCart, updateItemQuantity} = cartStore
</script>
