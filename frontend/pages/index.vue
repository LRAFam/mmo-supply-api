<template>
  <div>
    <div v-if="gameLoading" class="">Loading..</div>
    <div v-if="gameError" class="">{{ gameError }}</div>
    {{ gameData }}
  </div>
</template>

<script setup>
  import { onMounted } from 'vue'
  import {storeToRefs} from "pinia";

  const gameStore = useGameStore()
  const itemStore = useItemStore()
  const accountStore = useAccountStore()

  const { loading: gameLoading, error: gameError, fetchGames } = gameStore;
  const { data: gameData } = storeToRefs(gameStore);

  const { loading: itemLoading, error: itemError, fetchItems } = itemStore;
  const { data: itemData } = storeToRefs(itemStore);

  const { loading: accountLoading, error: accountError, fetchAccounts } = accountStore;
  const { data: accountData } = storeToRefs(accountStore);

  onMounted(() => {
    fetchGames()
    fetchItems()
    fetchAccounts()
  })
</script>

<style lang="scss" scoped>

</style>