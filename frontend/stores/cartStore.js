import { defineStore } from 'pinia'
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from './authStore'

export const useCartStore = defineStore('cart', () => {
    const cartItems = ref([])
    const apiUrl = 'http://localhost:8000/api/cart'
    const authStore = useAuthStore()

    // Fetch cart items from the API
    const fetchCartItems = async () => {
        try {
            const token = authStore.getToken()
            const response = await axios.get(apiUrl, { headers: { Authorization: `Bearer ${token}` } })
            cartItems.value = response.data
        } catch (error) {
            console.error('Failed to fetch cart items', error)
        }
    }

    // Add item to the cart
    const addItemToCart = async (newItem) => {
        try {
            const token = authStore.getToken()
            const response = await axios.post(`${apiUrl}/add`, { item: newItem }, { headers: { Authorization: `Bearer ${token}` } })
            cartItems.value = response.data
        } catch (error) {
            console.error('Failed to add item to cart', error)
        }
    }

    // Remove item from the cart
    const removeItemFromCart = async (itemId) => {
        try {
            const token = authStore.getToken()
            const response = await axios.post(`${apiUrl}/remove`, { itemId }, { headers: { Authorization: `Bearer ${token}` } })
            cartItems.value = response.data
        } catch (error) {
            console.error('Failed to remove item from cart', error)
        }
    }

    // Update item quantity in the cart
    const updateItemQuantity = async (itemId, newQuantity) => {
        try {
            const token = authStore.getToken()
            const response = await axios.post(`${apiUrl}/update`, { itemId, quantity: newQuantity }, { headers: { Authorization: `Bearer ${token}` } })
            cartItems.value = response.data
        } catch (error) {
            console.error('Failed to update item quantity in cart', error)
        }
    }

    // Fetch cart items on store initialization
    if (process.client) {
        onMounted(() => {
            fetchCartItems()
        })
    }

    // Total items in the cart
    const totalItems = computed(() => {
        return cartItems.value.reduce((total, item) => total + item.quantity, 0)
    })

    // Calculate total price of the cart
    const totalPrice = computed(() => {
        return cartItems.value.reduce((total, item) => total + (item.quantity * item.finalPrice), 0).toFixed(2)
    })

    return {
        cartItems,
        fetchCartItems,
        addItemToCart,
        removeItemFromCart,
        updateItemQuantity,
        totalItems,
        totalPrice
    }
})
