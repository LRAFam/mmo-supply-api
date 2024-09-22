import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
    }),
    actions: {
        async login(credentials) {
            const { login, isAuthenticated } = useSanctumAuth();

            try {
                await login(credentials);
                if (isAuthenticated.value) {
                    // Fetch user data after login to update the store
                    this.user = await this.fetchUser();
                }
            } catch (error) {
                console.error('Login failed:', error);
            }
        },

        async register(credentials) {
            const client = useSanctumClient();

            try {
                // Ensure you're sending credentials as form data
                const response = await client('/api/auth/register', {
                    method: 'POST',
                    body: new URLSearchParams(credentials),
                });

                if (response) {
                    this.user = response.user;
                }
            } catch (error) {
                console.error('Registration failed:', error);
            }
        },

        async fetchUser() {
            const client = useSanctumClient();

            try {
                const { data } = await client('/api/user');
                this.user = data;
                return data;
            } catch (error) {
                console.error('Failed to fetch user data:', error);
                return null;
            }
        },

        async logout() {
            const { logout } = useSanctumAuth();

            try {
                await logout();
                this.user = null;
            } catch (error) {
                console.error('Logout failed:', error);
            }
        },
    },
});
