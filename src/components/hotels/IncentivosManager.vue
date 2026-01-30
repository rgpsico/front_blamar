<template>
  <div class="incentivos-manager">
    <div class="incentivos-manager__header">
      <div>
        <h2>Incentivos</h2>
        <p>Listagem temporaria usando a API de hoteis.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn outlined color="primary" @click="fetchHotels">Atualizar</v-btn>
    </div>

    <v-card elevation="6">
      <v-data-table
        :headers="headers"
        :items="hotels"
        :loading="loading"
        item-key="frncod"
        class="elevation-0"
      ></v-data-table>
    </v-card>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000">
      {{ snackbar.text }}
      <template v-slot:action="{ attrs }">
        <v-btn text v-bind="attrs" @click="snackbar.show = false">Fechar</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script>
const API_BASE = '/api/'

export default {
  name: 'IncentivosManager',
  data() {
    return {
      loading: false,
      hotels: [],
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Codigo', value: 'codigo' },
        { text: 'Nome', value: 'nome' },
        { text: 'Cidade', value: 'cidade' },
        { text: 'UF', value: 'uf' },
        { text: 'Estrelas', value: 'estrelas' },
        { text: 'Quartos', value: 'quartos' }
      ]
    }
  },
  mounted() {
    this.fetchHotels()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    async fetchHotels() {
      this.loading = true
      try {
        const params = new URLSearchParams({ request: 'listar_hoteis' })
        const response = await fetch(`${API_BASE}hotels.php?${params.toString()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.hotels = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.incentivos-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.incentivos-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.incentivos-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}
</style>
