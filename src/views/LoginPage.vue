<template>
  <AuthShell>
    <LoginCard @authenticated="$emit('authenticated')" />
  </AuthShell>
</template>

<script>
import AuthShell from '@/components/auth/AuthShell.vue'
import LoginCard from '@/components/auth/LoginCard.vue'

export default {
  name: 'LoginPage',
  components: {
    AuthShell,
    LoginCard
  }
}
</script>
