<template>
  <v-app>
    <LoginPage v-if="!authenticated" @authenticated="onAuthenticated" />
    <DashboardPage v-else @logout="onLogout" />
  </v-app>
</template>

<script>
import LoginPage from './views/LoginPage.vue'
import DashboardPage from './views/DashboardPage.vue'

export default {
  name: 'App',
  components: {
    LoginPage,
    DashboardPage
  },
  data() {
    return {
      authenticated: Boolean(localStorage.getItem('auth_token'))
    }
  },
  methods: {
    onAuthenticated() {
      this.authenticated = true
    },
    onLogout() {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      localStorage.removeItem('auth_permissions')
      localStorage.removeItem('auth_profile')
      this.authenticated = false
    }
  }
}
</script>
