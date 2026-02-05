<template>
  <v-form @submit.prevent="handleSubmit">
    <v-text-field
      v-model="login"
      label="Login"
      outlined
      dense
      dark
      hide-details="auto"
      class="mb-3"
    ></v-text-field>
    <v-text-field
      v-model="password"
      label="Senha de acesso"
      :type="showPassword ? 'text' : 'password'"
      outlined
      dense
      dark
      hide-details="auto"
      :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
      @click:append="showPassword = !showPassword"
    ></v-text-field>

    <v-alert
      v-if="error"
      type="error"
      dense
      text
      class="mt-3"
    >
      {{ error }}
    </v-alert>

    <div class="login-form__row">
      <v-checkbox v-model="remember" label="Manter conectado" hide-details dense dark></v-checkbox>
      <a href="#" class="login-form__link" @click.prevent>Esqueceu a senha?</a>
    </div>

    <v-btn
      type="submit"
      color="primary"
      block
      large
      class="login-form__button"
      :loading="loading"
      dark
    >
      Entrar no painel
    </v-btn>

    <div class="login-form__divider">
      <v-divider></v-divider>
      <span>ou</span>
      <v-divider></v-divider>
    </div>

    <v-btn text block color="secondary" class="login-form__ghost" dark>
      <v-icon left size="20">mdi-shield-key-outline</v-icon>
      Entrar com token corporativo
    </v-btn>
  </v-form>
</template>

<script>
import api from '@/services/api'

export default {
  name: 'LoginForm',
  data() {
    return {
      login: '',
      password: '',
      remember: true,
      showPassword: false,
      loading: false,
      error: ''
    }
  },
  methods: {
    async handleSubmit() {
      if (!this.login || !this.password) {
        this.error = 'Informe login e senha 10.'
        return
      }

      this.loading = true
      this.error = ''
      try {
        const { data } = await api.post('auth.php?request=autenticar', {
          login: this.login,
          senha: this.password
        })
        if (!data || data.error || data.success === false) {
          throw new Error(data?.error || 'Credenciais invalidas.')
        }

        localStorage.setItem('auth_token', data.token)
        localStorage.setItem('auth_user', JSON.stringify(data.user || {}))
        this.$emit('authenticated')
      } catch (error) {
        this.error = error?.response?.data?.error || error.message
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.login-form__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 8px 0 16px;
  color: #cbd5f5;
}

.login-form__link {
  font-size: 12px;
  color: #fcd34d;
  text-decoration: none;
  font-weight: 600;
}

.login-form__button {
  margin-top: 4px;
  font-weight: 600;
}

.login-form__divider {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 10px;
  margin: 16px 0;
  color: #94a3b8;
  font-size: 12px;
}

.login-form__ghost {
  color: #e2e8f0;
  text-transform: none;
  font-weight: 600;
}
</style>
