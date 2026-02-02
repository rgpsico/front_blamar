<template>
  <div class="users-manager">
    <div class="users-manager__header">
      <div>
        <h2>Usuarios</h2>
        <p>Listagem de usuarios do sistema (mock).</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn outlined color="primary" @click="resetFilters">Limpar filtros</v-btn>
    </div>

    <v-card class="users-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.nome"
            label="Buscar nome"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.email"
            label="Buscar email"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4">
          <v-select
            v-model="filters.ativo"
            :items="statusOptions"
            item-text="text"
            item-value="value"
            label="Status"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="filteredUsers"
        item-key="id"
        class="elevation-0"
        :footer-props="{
          'items-per-page-options': [10, 20, 50],
          showFirstLastPage: true
        }"
      >
        <template slot="item.ativo" slot-scope="{ item }">
          <v-chip :color="item.ativo ? 'success' : 'error'" small text-color="white">
            {{ item.ativo ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script>
export default {
  name: 'UsersManager',
  data() {
    return {
      filters: {
        nome: '',
        email: '',
        ativo: null
      },
      users: [
        { id: 1, nome: 'Renata Gomes', email: 'renata@blumar.com.br', perfil: 'Coordenacao', ativo: true },
        { id: 2, nome: 'Carlos Menezes', email: 'carlos@blumar.com.br', perfil: 'Operacional', ativo: true },
        { id: 3, nome: 'Juliana Prado', email: 'juliana@blumar.com.br', perfil: 'Comercial', ativo: false },
        { id: 4, nome: 'Fernando Lima', email: 'fernando@blumar.com.br', perfil: 'Financeiro', ativo: true },
        { id: 5, nome: 'Camila Torres', email: 'camila@blumar.com.br', perfil: 'Marketing', ativo: true }
      ],
      statusOptions: [
        { text: 'Ativos', value: 'true' },
        { text: 'Inativos', value: 'false' }
      ],
      headers: [
        { text: 'ID', value: 'id', width: 80 },
        { text: 'Nome', value: 'nome' },
        { text: 'Email', value: 'email' },
        { text: 'Perfil', value: 'perfil' },
        { text: 'Status', value: 'ativo', sortable: false, width: 120 }
      ]
    }
  },
  computed: {
    filteredUsers() {
      const nome = this.filters.nome.trim().toLowerCase()
      const email = this.filters.email.trim().toLowerCase()
      const ativo = this.filters.ativo
      return this.users.filter(user => {
        const matchNome = !nome || user.nome.toLowerCase().includes(nome)
        const matchEmail = !email || user.email.toLowerCase().includes(email)
        const matchAtivo =
          ativo === null ||
          (ativo === 'true' && user.ativo) ||
          (ativo === 'false' && !user.ativo)
        return matchNome && matchEmail && matchAtivo
      })
    }
  },
  methods: {
    resetFilters() {
      this.filters = {
        nome: '',
        email: '',
        ativo: null
      }
    }
  }
}
</script>

<style scoped>
.users-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.users-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.users-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.users-manager__filters {
  padding: 16px;
}
</style>
