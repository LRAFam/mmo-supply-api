<template>
  <div class="p-6 bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-lg bg-gray-800 p-8 rounded-xl shadow-lg">
      <h2 class="text-4xl font-bold text-center mb-8 text-orange-400">Contact Us</h2>

      <form @submit.prevent="submitForm">
        <!-- Name Input -->
        <div class="mb-6">
          <label for="name" class="block text-sm font-semibold mb-2 text-gray-300">Name</label>
          <input
              type="text"
              id="name"
              v-model="name"
              class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition duration-300 ease-in-out"
              placeholder="Enter your name"
              required
          />
        </div>

        <!-- Email Input -->
        <div class="mb-6">
          <label for="email" class="block text-sm font-semibold mb-2 text-gray-300">Email</label>
          <input
              type="email"
              id="email"
              v-model="email"
              class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition duration-300 ease-in-out"
              placeholder="Enter your email"
              required
          />
        </div>

        <!-- Message Input -->
        <div class="mb-6">
          <label for="message" class="block text-sm font-semibold mb-2 text-gray-300">Message</label>
          <textarea
              id="message"
              v-model="message"
              rows="4"
              class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition duration-300 ease-in-out"
              placeholder="Enter your message"
              required
          ></textarea>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-600 transition duration-300 ease-in-out"
        >
          Send Message
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const name = ref('')
const email = ref('')
const message = ref('')

const submitForm = () => {
  // You can handle the form submission logic here, like sending data to an API
  console.log({
    name: name.value,
    email: email.value,
    message: message.value,
  })

  // Reset the form
  name.value = ''
  email.value = ''
  message.value = ''

  alert('Message sent!')
}
</script>

<style lang="scss" scoped>
/* Customize the input hover and focus */
input, textarea {
  transition: all 0.3s ease;
}

input:focus, textarea:focus {
  border-color: #ff8400;
}

button {
  transition: background-color 0.3s ease;
}
</style>
