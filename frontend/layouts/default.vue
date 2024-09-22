<template>
  <div class="flex h-screen overflow-auto">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main content area -->
    <div
        class="h-screen flex-grow bg-gray-900 overflow-y-auto transition-all duration-300">

      <!-- Main page content -->
      <NuxtPage />
    </div>
  </div>
</template>

<script setup>
import Sidebar from '~/components/Sidebar.vue';

const { isAuthenticated } = useSanctumAuth();

</script>

<style scoped>
</style>
