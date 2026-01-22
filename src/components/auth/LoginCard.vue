<template>
  <v-card class="login-card" elevation="12" dark>
    <div class="login-card__header">
      <div class="login-card__logo">BLUMAR</div>
      <div class="login-card__meta">Acesso seguro ao ERP Turismo</div>
    </div>
    <LoginForm @authenticated="$emit('authenticated')" />
    <div class="login-card__footer">
      <span>Precisa de acesso?</span>
      <a href="#" @click.prevent>Fale com o suporte</a>
    </div>
  </v-card>
</template>

<script>
import LoginForm from './LoginForm.vue'

export default {
  name: 'LoginCard',
  components: {
    LoginForm
  }
}
</script>

<style scoped>
.login-card {
  padding: 32px 28px 24px;
  border-radius: 22px;
  background: linear-gradient(150deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.92));
  color: #e2e8f0;
  box-shadow: 0 30px 60px rgba(2, 6, 23, 0.45);
}

.login-card__header {
  margin-bottom: 24px;
}

.login-card__logo {
  font-family: 'Playfair Display', serif;
  font-size: 28px;
  letter-spacing: 1px;
  color: #f8fafc;
}

.login-card__meta {
  font-size: 13px;
  color: #94a3b8;
  margin-top: 4px;
}

.login-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  color: #94a3b8;
  margin-top: 20px;
}

.login-card__footer a {
  color: #fcd34d;
  text-decoration: none;
  font-weight: 600;
}
</style>
