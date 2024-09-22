<template>
  <div class="bg-cover bg-no-repeat bg-center">
    <div class="min-h-screen bg-gradient-to-r from-gray-900 via-gray-950 to-gray-900 text-gray-100 py-10">
      <div class="container">
        <div class="min-h-screen flex justify-center items-center">
          <form
              class="w-1/2 bg-[#2E2529] shadow-md rounded border border-[#29130E] px-8 pt-6 pb-8 mb-4"
              @submit.prevent="handleLogin">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-2xl font-bold">Login</h2>
              <div @click="loginWithDiscord" class="group flex items-center justify-between gap-2">
                                <span
                                    class="group-hover:text-primary text-gray-200 text-sm font-bold cursor-pointer">Login
                                    with Discord</span>
                <FontAwesomeIcon iconName="discord" variant="fab" size="md"
                                 extraClasses="group-hover:text-primary text-gray-200" />
              </div>
            </div>
            <div class="my-4">
              <label class="block cursor-pointer text-sm font-bold mb-2" for="name">
                Name
              </label>
              <input v-model="name"
                     class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                     id="name" type="name" placeholder="Enter name">
            </div>
            <div class="mb-6">
              <label class="block cursor-pointer text-sm font-bold mb-2" for="password">
                Password
              </label>
              <input v-model="password"
                     class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                     id="password" type="password" placeholder="Enter your password">
            </div>
            <div class="flex items-center justify-between">
              <button class="bg-primary hover:bg-secondary border border-[#29130E] text-[#2E2529] font-bold py-2 px-5 rounded"
                      type="submit">
                Login
              </button>
              <router-link
                  class="inline-block align-baseline font-bold text-sm text-naw-primary-blue hover:text-secondary"
                  to="#">
                Forgot Password?
              </router-link>
              <router-link
                  class="inline-block align-baseline font-bold text-sm text-naw-primary-blue hover:text-secondary"
                  to="/auth/register">
                Register
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import FontAwesomeIcon from '../../components/common/FontAwesomeIcon.vue';

// Form fields
const name = ref('');
const password = ref('');

// Use router for redirection
const router = useRouter();

// Access login method from useSanctumAuth
const { login, isAuthenticated } = useSanctumAuth();

// Login handler
const handleLogin = async () => {
  try {
    // Pass user credentials to login method
    const userCredentials = {
      name: name.value,
      password: password.value,
    };

    await login(userCredentials);

    // Check if authentication was successful
    if (isAuthenticated.value) {
      console.log('Login successful');
      await router.push('/');
    }
  } catch (error) {
    console.error('Login failed:', error);
  }
};

// Placeholder for Discord login
const loginWithDiscord = () => {
  console.log('Logging in with Discord...');
};
</script>

<style></style>
