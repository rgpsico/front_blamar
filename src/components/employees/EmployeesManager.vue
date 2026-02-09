<template>
  <div class="employees-manager">
    <div class="employees-manager__header">
      <div>
        <h2>Funcionarios</h2>
        <p>Cadastro e controle de funcionarios com filtros e paginacao.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Funcionario</v-btn>
      <v-btn outlined color="primary" @click="fetchEmployees">Atualizar</v-btn>
    </div>

    <v-card class="employees-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.nome"
            label="Buscar nome"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.login"
            label="Login"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.cargo"
            label="Cargo (ID)"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo"
            :items="ativoOptions"
            item-text="text"
            item-value="value"
            label="Status"
            dense
            outlined
            @change="scheduleFetch"
          ></v-select>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="employees"
        :loading="loading"
        :options.sync="tableOptions"
        :server-items-length="total"
        item-key="id"
        class="elevation-0"
        :footer-props="{
          'items-per-page-options': [10, 20, 50, 100],
          showFirstLastPage: true
        }"
      >
        <template slot="item.ativo" slot-scope="{ item }">
          <v-chip :color="item.ativo ? 'success' : 'error'" small text-color="white">
            {{ item.ativo ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.profile_name" slot-scope="{ item }">
          <v-chip v-if="item.profile_name || item.perfil_id" small color="primary" text-color="white">
            {{ item.profile_name || profileName(item.perfil_id) || `#${item.perfil_id}` }}
          </v-chip>
          <span v-else>-</span>
        </template>
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn icon small color="info" @click="openProfileAssign(item)">
            <v-icon>mdi-shield-account</v-icon>
          </v-btn>
          <v-btn icon small color="primary" @click="openEdit(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon small color="error" @click="openDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="720px" persistent>
      <v-card>
        <v-card-title class="employees-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.nome" label="Nome" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.login" label="Login" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.email" label="Email" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="editedItem.fk_cargo" label="Cargo (ID)" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="3" class="d-flex align-center">
                <v-switch v-model="editedItem.ativo" label="Ativo" inset></v-switch>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editedItem.perfil_id"
                  :items="profiles"
                  item-text="name"
                  item-value="id"
                  label="Perfil"
                  outlined
                  dense
                  clearable
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.data_admissao"
                  label="Data admissao"
                  type="date"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.senha"
                  label="Senha"
                  type="password"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeDialog">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="save">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDelete" max-width="420px">
      <v-card>
        <v-card-title>Confirmar exclusao</v-card-title>
        <v-card-text>
          <v-alert type="warning" border="left" colored-border>
            Tem certeza que deseja excluir o funcionario
            <strong>{{ editedItem.nome }}</strong>?
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="error" :loading="saving" @click="confirmDelete">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogProfile" max-width="520px">
      <v-card>
        <v-card-title>
          Associar perfil ao funcionario
          <v-spacer></v-spacer>
          <v-btn icon @click="dialogProfile = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-alert type="info" border="left" colored-border>
            Funcionario: <strong>{{ profileAssign.nome || '-' }}</strong>
            <span v-if="profileAssign.cod_sis"> ({{ profileAssign.cod_sis }})</span>
          </v-alert>
          <v-select
            v-model="profileAssign.perfil_id"
            :items="profiles"
            item-text="name"
            item-value="id"
            label="Perfil"
            outlined
            dense
            clearable
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogProfile = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveProfileAssign">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000">
      {{ snackbar.text }}
      <template v-slot:action="{ attrs }">
        <v-btn text v-bind="attrs" @click="snackbar.show = false">Fechar</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script>
import axios from 'axios'
import api from '@/services/api'

const API_BASE = `${api.defaults.baseURL}/`

export default {
  name: 'EmployeesManager',
  data() {
    return {
      loading: false,
      saving: false,
      employees: [],
      total: 0,
      filterTimer: null,
      tableOptions: {
        page: 1,
        itemsPerPage: 20,
        sortBy: ['nome'],
        sortDesc: [false]
      },
      filters: {
        nome: '',
        login: '',
        cargo: '',
        ativo: 'all'
      },
      dialog: false,
      dialogDelete: false,
      dialogProfile: false,
      editedIndex: -1,
      editedItem: {},
      profiles: [],
      profileAssign: {
        id: null,
        cod_sis: '',
        nome: '',
        perfil_id: null
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      ativoOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Ativos', value: 'true' },
        { text: 'Inativos', value: 'false' }
      ],
      headers: [
        { text: 'ID', value: 'id', width: 70 },
        { text: 'Nome', value: 'nome' },
        { text: 'Cod SIS', value: 'cod_sis', width: 110 },
        { text: 'Login', value: 'login' },
        { text: 'Email', value: 'email', sortable: false },
        { text: 'Cargo', value: 'fk_cargo', width: 90 },
        { text: 'Perfil', value: 'profile_name', sortable: false },
        { text: 'Admissao', value: 'data_admissao', width: 120 },
        { text: 'Ativo', value: 'ativo', width: 90, sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end', width: 160 }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Funcionario' : 'Editar Funcionario'
    }
  },
  watch: {
    tableOptions: {
      deep: true,
      handler() {
        this.fetchEmployees()
      }
    }
  },
  mounted() {
    this.editedItem = this.defaultEmployee()
    this.fetchProfiles()
    this.fetchEmployees()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    defaultEmployee() {
      return {
        id: null,
        cod_sis: '',
        nome: '',
        login: '',
        email: '',
        fk_cargo: null,
        perfil_id: null,
        data_admissao: '',
        ativo: true,
        senha: ''
      }
    },
    profileName(id) {
      const profile = this.profiles.find(item => item.id === id)
      return profile ? profile.name : ''
    },
    async fetchProfiles() {
      try {
        const response = await fetch(`${API_BASE}perfil_role.php?request=listar_profiles&limit=500`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.profiles = Array.isArray(data) ? data : data.data || []
      } catch (error) {
        this.showMessage(`Erro ao carregar perfis: ${error.message}`, 'error')
      }
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    scheduleFetch() {
      if (this.filterTimer) {
        clearTimeout(this.filterTimer)
      }
      this.filterTimer = setTimeout(() => {
        const alreadyFirstPage = this.tableOptions.page === 1
        this.tableOptions.page = 1
        if (alreadyFirstPage) {
          this.fetchEmployees()
        }
      }, 400)
    },
    buildListParams() {
      const params = new URLSearchParams({ request: 'listar_funcionarios_paginate' })
      const { page, itemsPerPage, sortBy, sortDesc } = this.tableOptions
      params.set('page', String(page || 1))
      params.set('per_page', String(itemsPerPage || 20))

      const sortMap = {
        nome: 'nome',
        login: 'login',
        id: 'id',
        data_admissao: 'admissao'
      }
      const sortKey = sortBy && sortBy.length ? sortBy[0] : 'nome'
      params.set('ordem', sortMap[sortKey] || 'nome')
      params.set('direcao', sortDesc && sortDesc[0] ? 'DESC' : 'ASC')

      if (this.filters.nome) {
        params.set('filtro_nome', this.filters.nome)
      }
      if (this.filters.login) {
        params.set('filtro_login', this.filters.login)
      }
      if (this.filters.cargo) {
        params.set('filtro_cargo', this.filters.cargo)
      }
      if (this.filters.ativo !== 'all') {
        params.set('filtro_ativo', this.filters.ativo)
      }
      return params
    },
    async fetchEmployees() {
      this.loading = true
      try {
        const params = this.buildListParams()
        const response = await axios.get(`${API_BASE}funcionario.php?${params.toString()}`)
        const data = response.data || {}
        this.employees = Array.isArray(data.data) ? data.data : Array.isArray(data) ? data : []
        this.total = Number(data.total || this.employees.length || 0)
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = this.defaultEmployee()
      this.dialog = true
    },
    openEdit(item) {
      this.editedIndex = this.employees.indexOf(item)
      this.editedItem = {
        ...this.defaultEmployee(),
        ...item,
        senha: ''
      }
      this.dialog = true
    },
    openProfileAssign(item) {
      if (!item.cod_sis) {
        this.showMessage('Funcionario sem cod_sis. Nao e possivel associar perfil.', 'warning')
        return
      }
      this.editedItem = {
        ...this.defaultEmployee(),
        ...item,
        senha: ''
      }
      this.profileAssign = {
        id: item.id,
        cod_sis: item.cod_sis,
        nome: item.nome,
        perfil_id: item.perfil_id || null
      }
      this.dialogProfile = true
    },
    openDelete(item) {
      this.editedItem = {
        id: item.id,
        nome: item.nome
      }
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    payloadFromForm() {
      return {
        id: this.editedItem.id,
        nome: this.editedItem.nome,
        login: this.editedItem.login,
        email: this.editedItem.email || null,
        fk_cargo: this.editedItem.fk_cargo,
        perfil_id: this.editedItem.perfil_id,
        data_admissao: this.editedItem.data_admissao || null,
        ativo: this.editedItem.ativo,
        senha: this.editedItem.senha || undefined
      }
    },
    async saveProfileAssign() {
      if (!this.profileAssign.cod_sis) {
        this.showMessage('cod_sis obrigatorio para associar perfil.', 'warning')
        return
      }
      this.saving = true
      try {
        const url = `${API_BASE}perfil_role.php?request=associar_profile_funcionario`
        const payload = {
          cod_sis: this.profileAssign.cod_sis,
          profile_id: this.profileAssign.perfil_id || null
        }
        const response = await fetch(url, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            ...this.authHeaders()
          },
          body: JSON.stringify(payload)
        })
        const result = await response.json()
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.showMessage('Perfil associado ao funcionario.')
        this.dialogProfile = false
        await this.fetchEmployees()
      } catch (error) {
        this.showMessage(`Erro ao salvar perfil: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async save() {
      if (!this.editedItem.nome || !this.editedItem.login) {
        this.showMessage('Nome e login sao obrigatorios.', 'warning')
        return
      }
      const isEdit = this.editedIndex > -1
      if (!isEdit && !this.editedItem.senha) {
        this.showMessage('Senha e obrigatoria no cadastro.', 'warning')
        return
      }
      this.saving = true
      try {
        const request = isEdit ? 'editar_funcionario' : 'salvar_funcionario'
        const method = isEdit ? 'PUT' : 'POST'
        const url = `${API_BASE}funcionario.php?request=${request}`
        const payload = this.payloadFromForm()

        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            ...this.authHeaders()
          },
          body: JSON.stringify(payload)
        })
        const result = await response.json()
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.showMessage(isEdit ? 'Funcionario atualizado.' : 'Funcionario criado.')
        this.dialog = false
        await this.fetchEmployees()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.id) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}funcionario.php?request=excluir_funcionario&id=${this.editedItem.id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Funcionario excluido.')
        this.dialogDelete = false
        await this.fetchEmployees()
      } catch (error) {
        this.showMessage(`Erro ao excluir: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.employees-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.employees-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.employees-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.employees-manager__filters {
  padding: 16px;
}

.employees-manager__dialog-title {
  display: flex;
  align-items: center;
}
</style>
