<template>
  <div
    :class="['h-screen bg-gray-800 text-white flex flex-col transition-width duration-300', { 'w-64': !isCollapsed, 'w-16': isCollapsed }]">
    <!-- Sidebar Header with Toggle Button -->
    <div class="p-4 flex justify-between items-center border-b border-gray-700">
      <span v-if="!isCollapsed" class="text-xl font-bold whitespace-nowrap">MMO Supply</span>
      <button @click="toggleSidebar" class="text-gray-400 hover:text-white">
        <svg v-if="isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-7 6h7" />
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 12L6 6v12z" />
        </svg>
      </button>
    </div>

    <!-- Sidebar Navigation Links -->
    <ul class="flex-grow mt-4">
      <li v-for="item in menuItems" :key="item.name" class="mb-4">
        <nuxt-link
          :to="item.path"
          class="block px-4 py-2 rounded hover:bg-gray-700 transition-colors"
          :class="{ 'text-center': isCollapsed }">
          <span v-if="!isCollapsed">{{ item.name }}</span>
          <FontAwesomeIcon v-else :icon="item.icon" />
        </nuxt-link>
      </li>
    </ul>

    <!-- Sidebar Footer -->
    <div class="p-4 text-sm text-gray-400 border-t border-gray-700" v-if="!isCollapsed">
      © 2024 MMO Supply
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faHome, faGamepad, faCoins, faUser, faTools, faPhone, faShieldAlt } from '@fortawesome/free-solid-svg-icons'

const menuItems = [
  { name: 'Home', path: '/', icon: faHome },
  { name: 'Games', path: '/games', icon: faGamepad },
  { name: 'Currencies', path: '/currencies', icon: faCoins },
  { name: 'Items', path: '/items', icon: faShieldAlt },  // Icon like a shield for items
  { name: 'Accounts', path: '/accounts', icon: faUser },
  { name: 'Services', path: '/services', icon: faTools },
  { name: 'Contact', path: '/contact', icon: faPhone }
];

// Sidebar collapse state
const isCollapsed = ref(false);

// Toggle the sidebar collapsed/expanded state
const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value;
  if (process.client) {
    localStorage.setItem('isCollapsed', isCollapsed.value);
  }
};

// Load sidebar state from localStorage
onMounted(() => {
  if (process.client) {
    const savedState = localStorage.getItem('isCollapsed');
    if (savedState !== null) {
      isCollapsed.value = JSON.parse(savedState);
    }
  }
});
</script>

<style scoped>
.transition-width {
  transition-property: width;
}
</style>
