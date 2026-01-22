<template>
  <v-app>
    <LoginPage v-if="!authenticated" @authenticated="onAuthenticated" />
    <DashboardPage v-else />
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
    }
  }
}
</script>
