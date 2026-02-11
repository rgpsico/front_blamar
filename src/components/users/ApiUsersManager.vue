<template>
  <div class="api-users-manager">
    <div class="api-users-manager__header">
      <div>
        <h2>Usuarios API</h2>
        <p>Cadastro e controle de usuarios da API.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Usuario API</v-btn>
      <v-btn outlined color="primary" @click="fetchUsers">Atualizar</v-btn>
    </div>

    <v-card class="api-users-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.username" label="Buscar username" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.email" label="Buscar email" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field v-model="filters.cod_sis" label="Cod SIS" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.role"
            :items="roleOptions"
            label="Role"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.active"
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
        :loading="loading"
        item-key="id"
        class="elevation-0"
        :footer-props="{
          'items-per-page-options': [10, 20, 50],
          showFirstLastPage: true
        }"
      >
        <template slot="item.is_active" slot-scope="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" small text-color="white">
            {{ item.is_active ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn icon small color="primary" @click="openEdit(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon small color="error" @click="openDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="640px" persistent>
      <v-card>
        <v-card-title class="api-users-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.username" label="Username" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.email" label="Email" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.cod_sis" label="Cod SIS" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.password"
                  label="Senha"
                  type="password"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="editedItem.role"
                  :items="roleOptions"
                  label="Role"
                  outlined
                  dense
                ></v-select>
              </v-col>
              <v-col cols="12" md="3" class="d-flex align-center">
                <v-switch v-model="editedItem.is_active" label="Ativo" inset></v-switch>
              </v-col>
              <v-col cols="12">
                <v-combobox
                  v-model="editedItem.permissions"
                  label="Permissoes"
                  multiple
                  chips
                  clearable
                  outlined
                  dense
                  hint="Digite e pressione Enter"
                  persistent-hint
                ></v-combobox>
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
            Tem certeza que deseja excluir o usuario
            <strong>{{ editedItem.username }}</strong>?
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="error" :loading="saving" @click="confirmDelete">Excluir</v-btn>
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
import api from '@/services/api'

const API_BASE = 'apimanager.php'

export default {
  name: 'ApiUsersManager',
  data() {
    return {
      loading: false,
      saving: false,
      dialog: false,
      dialogDelete: false,
      users: [],
      editedIndex: -1,
      editedItem: {},
      filters: {
        username: '',
        email: '',
        cod_sis: '',
        role: null,
        active: null
      },
      roleOptions: ['master', 'full', 'limited', 'viewer'],
      statusOptions: [
        { text: 'Ativos', value: 'true' },
        { text: 'Inativos', value: 'false' }
      ],
      headers: [
        { text: 'ID', value: 'id', width: 70 },
        { text: 'Username', value: 'username' },
        { text: 'Email', value: 'email' },
        { text: 'Cod SIS', value: 'cod_sis', width: 120 },
        { text: 'Role', value: 'role', width: 110 },
        { text: 'Status', value: 'is_active', width: 100, sortable: false },
        { text: 'Criado em', value: 'created_at', width: 140 },
        { text: 'Acoes', value: 'actions', width: 120, sortable: false, align: 'end' }
      ],
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      }
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Usuario API' : 'Editar Usuario API'
    },
    filteredUsers() {
      const username = this.filters.username.trim().toLowerCase()
      const email = this.filters.email.trim().toLowerCase()
      const cod_sis = this.filters.cod_sis.trim().toLowerCase()
      const role = this.filters.role
      const active = this.filters.active
      return this.users.filter(user => {
        const matchUsername = !username || String(user.username || '').toLowerCase().includes(username)
        const matchEmail = !email || String(user.email || '').toLowerCase().includes(email)
        const matchCodSis = !cod_sis || String(user.cod_sis || '').toLowerCase().includes(cod_sis)
        const matchRole = !role || user.role === role
        const matchActive =
          active === null ||
          (active === 'true' && user.is_active) ||
          (active === 'false' && !user.is_active)
        return matchUsername && matchEmail && matchCodSis && matchRole && matchActive
      })
    }
  },
  mounted() {
    this.editedItem = this.defaultUser()
    this.fetchUsers()
  },
  methods: {
    normalizePermissions(list) {
  if (!Array.isArray(list)) return []

  return list
    .map(item => {
      if (typeof item === 'string') return item.trim()

      if (item && typeof item === 'object') {
        return (
          item.name ||
          item.value ||
          item.text ||
          item.label ||
          item.title ||
          ''
        ).trim()
      }

      return ''
    })
    .filter(item => item.length > 0)
}

,
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    defaultUser() {
      return {
        id: null,
        username: '',
        email: '',
        cod_sis: '',
        password: '',
        role: 'viewer',
        permissions: [],
        is_active: true
      }
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    normalizeUser(item) {
      return {
        ...item,
        is_active: item.is_active === true || item.is_active === 't' || item.is_active === 'true'
      }
    },
    async fetchUsers() {
      this.loading = true
      try {
        const response = await api.get(`${API_BASE}?request=listar_usuarios_api`, {
          headers: this.authHeaders()
        })
        const data = response?.data || {}
        const users = Array.isArray(data.usuarios) ? data.usuarios : []
        this.users = users.map(this.normalizeUser)
      } catch (error) {
        this.showMessage(`Erro ao carregar usuarios: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchUserById(id) {
      const response = await api.get(`${API_BASE}?request=buscar_usuario_api&id=${id}`, {
        headers: this.authHeaders()
      })
      const data = response?.data || {}
      if (!data.success) {
        throw new Error(data.error || 'Usuario nao encontrado')
      }
      const usuario = this.normalizeUser(data.usuario || {})
      usuario.permissions = this.normalizePermissions(usuario.permissions)
      return usuario
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = this.defaultUser()
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.users.indexOf(item)
      try {
        const full = await this.fetchUserById(item.id)
        this.editedItem = {
          ...this.defaultUser(),
          ...full,
          password: ''
        }
        this.dialog = true
      } catch (error) {
        this.showMessage(`Erro ao carregar usuario: ${error.message}`, 'error')
      }
    },
    openDelete(item) {
      this.editedIndex = this.users.indexOf(item)
      this.editedItem = { ...item }
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
  buildPayload() {
  return {
    username: this.editedItem.username?.trim(),
    email: this.editedItem.email?.trim(),
    cod_sis: this.editedItem.cod_sis?.trim() || null,
    role: this.editedItem.role,
    permissions: (this.editedItem.permissions || []).map(p => {
      if (typeof p === 'string') return p
      if (p && typeof p === 'object') return p.value || p.text || ''
      return ''
    }).filter(Boolean),
    is_active: Boolean(this.editedItem.is_active)
  }
}

,
    async save() {
      const isEdit = this.editedIndex > -1
      if (!this.editedItem.username || !this.editedItem.email) {
        this.showMessage('Username e email sao obrigatorios.', 'warning')
        return
      }
      if (!isEdit && !this.editedItem.password) {
        this.showMessage('Senha e obrigatoria no cadastro.', 'warning')
        return
      }
      this.saving = true
      try {
        const payload = this.buildPayload()
        const url = isEdit
          ? `${API_BASE}?request=atualizar_usuario_api&id=${this.editedItem.id}`
          : `${API_BASE}?request=criar_usuario_api`
        const method = isEdit ? 'PUT' : 'POST'
        const response = await api.request({
          url,
          method,
          headers: this.authHeaders(),
          data: payload
        })
        const result = response?.data || {}
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.showMessage(isEdit ? 'Usuario atualizado.' : 'Usuario criado.')
        this.dialog = false
        await this.fetchUsers()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.id) return
      this.saving = true
      try {
        const response = await api.delete(
          `${API_BASE}?request=excluir_usuario_api&id=${this.editedItem.id}`,
          { headers: this.authHeaders() }
        )
        const result = response?.data || {}
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Usuario excluido.')
        this.dialogDelete = false
        await this.fetchUsers()
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
.api-users-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.api-users-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.api-users-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.api-users-manager__filters {
  padding: 16px;
}

.api-users-manager__dialog-title {
  display: flex;
  align-items: center;
}
</style>
