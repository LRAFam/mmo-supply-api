<template>
  <div class="bg-cover bg-no-repeat bg-center">
    <div class="min-h-screen bg-gradient-to-r from-gray-900 via-gray-950 to-gray-900 text-gray-100 py-10">
      <div class="container">
        <div class="min-h-screen flex justify-center items-center">
          <form
              class="w-1/2 bg-[#2E2529]/60 shadow-md rounded border border-[#29130E] px-8 pt-6 pb-8 mb-4"
              @submit.prevent="handleRegister">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-2xl font-bold">Register</h2>
              <div @click="loginWithDiscord" class="group flex items-center justify-between gap-2">
                <span class="group-hover:text-primary text-gray-200 text-sm font-bold cursor-pointer">
                  Register with Discord
                </span>
                <FontAwesomeIcon iconName="discord" variant="fab" size="md"
                                 extraClasses="group-hover:text-primary text-gray-200"/>
              </div>
            </div>
            <div class="mb-4">
              <label class="block cursor-pointer mb-1" for="name">Name:</label>
              <input v-model="name" class="w-full text-black border border-gray-300 rounded px-3 py-2"
                     id="name" type="text" placeholder="Choose a name" required>
            </div>
            <div class="mb-4">
              <label class="block cursor-pointer mb-1" for="email">Email:</label>
              <input v-model="email" class="w-full text-black border border-gray-300 rounded px-3 py-2" id="email"
                     type="email" placeholder="Enter your email" required>
            </div>
            <div class="mb-6">
              <label class="block cursor-pointer mb-1" for="password">Password:</label>
              <input v-model="password" class="w-full text-black border border-gray-300 rounded px-3 py-2"
                     id="password" type="password" placeholder="Choose a password" required>
            </div>
            <div>
              <button
                  class="bg-primary hover:bg-secondary border border-[#29130E] text-[#2E2529] font-bold py-2 px-5 rounded"
                  type="submit">
                Register
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import FontAwesomeIcon from '../../components/common/FontAwesomeIcon.vue';
import {useAuthStore} from "~/stores/authStore.js";

const name = ref('');
const email = ref('');
const password = ref('');

const authStore = useAuthStore();

const {loginWithDiscord, register} = authStore;

const router = useRouter();
const route = useRoute();

const handleDiscordLogin = () => {
  loginWithDiscord();
};

const handleDiscordCallback = async () => {
  const code = route.query.code;
  if (code) {
    await authStore.handleDiscordCallback(code);
  }
};

// Register method
const handleRegister = async () => {
  const credentials = {
    name: name.value,
    email: email.value,
    password: password.value,
  };

  try {
    console.log('Trying to register with credentials: ', credentials);
    await register(credentials);
    await router.push('/');
  } catch (error) {
    console.error('Registration failed:', error);
  }
};

onMounted(() => {
  handleDiscordCallback();
});
</script>

<style>
/* Add your custom styles here */
</style>
