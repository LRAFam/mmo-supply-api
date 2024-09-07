import { defineStore } from 'pinia'
import { computed, ref, watch, onMounted } from 'vue'

export const useCartStore = defineStore('cart', () => {
    const CART_STORAGE_KEY = 'cartItems'

    // Initialize cartItems as a ref
    const cartItems = ref([])

    // Load initial cart from localStorage (client-side only)
    const loadCartItems = () => {
        if (process.client) {
            const storedCart = localStorage.getItem(CART_STORAGE_KEY)
            cartItems.value = storedCart ? JSON.parse(storedCart) : []
        }
    }

    onMounted(loadCartItems)

    // Add an item to the cart
    const addItemToCart = (newItem) => {
        const existingItem = cartItems.value.find(item => item.id === newItem.id)
        if (existingItem) {
            existingItem.quantity += newItem.quantity
        } else {
            cartItems.value.push({ ...newItem, quantity: newItem.quantity, finalPrice: Number(newItem.finalPrice) })
        }
        // Save to localStorage
        saveCartItems()
    }

    // Remove an item from the cart
    const removeItemFromCart = (itemId) => {
        cartItems.value = cartItems.value.filter(item => item.id !== itemId)
        saveCartItems()
    }

    // Update item quantity
    const updateItemQuantity = (itemId, newQuantity) => {
        cartItems.value = cartItems.value.map(item => {
            if (item.id === itemId) {
                return { ...item, quantity: newQuantity <= 0 ? 0 : newQuantity }
            }
            return item
        }).filter(item => item.quantity > 0)
        saveCartItems()
    }

    // Save cartItems to localStorage
    const saveCartItems = () => {
        if (process.client) {
            localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cartItems.value))
        }
    }

    // Total items in the cart
    const totalItems = computed(() => {
        return cartItems.value.reduce((total, item) => total + item.quantity, 0)
    })

    // Calculate total price of the cart
    const totalPrice = computed(() => {
        return cartItems.value.reduce((total, item) => total + (item.quantity * item.finalPrice), 0).toFixed(2)
    })

    // Watch cartItems and save to localStorage (client-side only)
    if (process.client) {
        watch(cartItems, saveCartItems, { deep: true })
    }

    return {
        cartItems,
        addItemToCart,
        removeItemFromCart,
        updateItemQuantity,
        totalItems,
        totalPrice,
    }
})
