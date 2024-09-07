import { defineStore } from 'pinia'
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

export const useCartStore = defineStore('cart', () => {
    const userId = 'user_id';
    const cartItems = ref([]);
    const apiUrl = 'http://localhost:8000/api/cart';

    // Fetch cart items from the backend
    const fetchCartItems = async () => {
        try {
            const response = await axios.get(apiUrl, { headers: { Authorization: `Bearer ${yourAuthToken}` } });
            cartItems.value = response.data;
        } catch (error) {
            console.error('Failed to fetch cart items', error);
        }
    }

    // Add an item to the cart
    const addItemToCart = async (newItem) => {
        try {
            const response = await axios.post(`${apiUrl}/add`, { item: newItem }, { headers: { Authorization: `Bearer ${yourAuthToken}` } });
            cartItems.value = response.data;
        } catch (error) {
            console.error('Failed to add item to cart', error);
        }
    }

    // Remove an item from the cart
    const removeItemFromCart = async (itemId) => {
        try {
            const response = await axios.post(`${apiUrl}/remove`, { itemId }, { headers: { Authorization: `Bearer ${yourAuthToken}` } });
            cartItems.value = response.data;
        } catch (error) {
            console.error('Failed to remove item from cart', error);
        }
    }

    // Update item quantity
    const updateItemQuantity = async (items) => {
        try {
            const response = await axios.post(`${apiUrl}/update`, { items }, { headers: { Authorization: `Bearer ${yourAuthToken}` } });
            cartItems.value = response.data;
        } catch (error) {
            console.error('Failed to update item quantity', error);
        }
    }

    // Total items in the cart
    const totalItems = computed(() => {
        return cartItems.value.reduce((total, item) => total + item.quantity, 0);
    })

    // Calculate total price of the cart
    const totalPrice = computed(() => {
        return cartItems.value.reduce((total, item) => total + (item.quantity * item.finalPrice), 0).toFixed(2);
    })

    onMounted(fetchCartItems);

    return {
        cartItems,
        addItemToCart,
        removeItemFromCart,
        updateItemQuantity,
        totalItems,
        totalPrice,
    }
})
