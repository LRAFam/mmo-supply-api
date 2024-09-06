<template>
  <div class="flex h-screen">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main content area -->
    <div
        class="flex-grow bg-gray-100 overflow-y-auto transition-all duration-300">

      <!-- Main page content -->
      <NuxtPage />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from '~/components/Sidebar.vue';

// Sidebar collapse state
const isSidebarCollapsed = ref(false);

// Toggle sidebar collapse state
const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};
</script>

<style scoped>
/* Removed ml-64 and only kept ml-16 */
.ml-16 {
  margin-left: 4rem;
}
</style>
