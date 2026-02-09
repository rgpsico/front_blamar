<template>
  <div class="profile-permissions">
    <div class="profile-permissions__header">
      <div>
        <h2>Perfis e Permissoes</h2>
        <p>Gerencie perfis (roles), permissoes e vinculacao via perfil_role.php.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" :loading="loading" @click="refreshAll">Atualizar</v-btn>
    </div>

    <v-card elevation="6">
      <v-tabs v-model="tab" grow>
        <v-tab>Perfis</v-tab>
        <v-tab>Permissoes</v-tab>
        <v-tab>Associacao</v-tab>
      </v-tabs>

      <v-tabs-items v-model="tab" class="pa-4">
        <v-tab-item>
          <v-row>
            <v-col cols="12" md="4">
              <v-card outlined class="pa-4">
                <div class="profile-permissions__section-title">Novo perfil</div>
                <v-text-field v-model="newProfile.name" label="Nome" outlined dense></v-text-field>
                <v-text-field v-model="newProfile.description" label="Descricao" outlined dense></v-text-field>
                <v-select
                  v-model="newProfile.permissions"
                  :items="permissions"
                  item-text="name"
                  item-value="id"
                  label="Permissoes"
                  multiple
                  chips
                  outlined
                  dense
                ></v-select>
                <v-btn color="primary" block :loading="saving" @click="createProfile">Adicionar perfil</v-btn>
              </v-card>
            </v-col>
            <v-col cols="12" md="8">
              <v-card outlined>
                <v-data-table
                  :headers="profileHeaders"
                  :items="profiles"
                  item-key="id"
                  dense
                  class="elevation-0"
                  :loading="loading"
                >
                  <template v-slot:item.actions="{ item }">
                    <v-btn icon color="primary" @click="openProfileEdit(item)">
                      <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn icon color="error" @click="deleteProfile(item)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </template>
                </v-data-table>
              </v-card>
            </v-col>
          </v-row>
        </v-tab-item>

        <v-tab-item>
          <v-row>
            <v-col cols="12" md="4">
              <v-card outlined class="pa-4">
                <div class="profile-permissions__section-title">Nova permissao</div>
                <v-text-field v-model="newPermission.name" label="Nome" outlined dense></v-text-field>
                <v-text-field v-model="newPermission.description" label="Descricao" outlined dense></v-text-field>
                <v-btn color="primary" block :loading="saving" @click="createPermission">Adicionar permissao</v-btn>
              </v-card>
            </v-col>
            <v-col cols="12" md="8">
              <v-card outlined>
                <v-data-table
                  :headers="permissionHeaders"
                  :items="permissions"
                  item-key="id"
                  dense
                  class="elevation-0"
                  :loading="loading"
                >
                  <template v-slot:item.actions="{ item }">
                    <v-btn icon color="primary" @click="openPermissionEdit(item)">
                      <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn icon color="error" @click="deletePermission(item)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </template>
                </v-data-table>
              </v-card>
            </v-col>
          </v-row>
        </v-tab-item>

        <v-tab-item>
          <v-card outlined class="pa-4">
            <div class="profile-permissions__section-title">Associar permissoes ao perfil</div>
            <v-row>
              <v-col cols="12" md="4">
                <v-select
                  v-model="selectedProfileId"
                  :items="profiles"
                  item-text="name"
                  item-value="id"
                  label="Selecione o perfil"
                  outlined
                  dense
                  @change="loadProfilePermissions"
                ></v-select>
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="permissionFilter"
                  label="Buscar permissao"
                  outlined
                  dense
                  clearable
                ></v-text-field>
              </v-col>
            </v-row>

            <v-row v-if="selectedProfileId">
              <v-col cols="12">
                <v-card outlined class="pa-3 profile-permissions__permissions-list">
                  <v-row>
                    <v-col
                      v-for="perm in filteredPermissions"
                      :key="perm.id"
                      cols="12"
                      md="6"
                    >
                      <v-checkbox
                        :label="perm.name"
                        :value="perm.id"
                        v-model="selectedPermissionIds"
                        hide-details
                        dense
                      ></v-checkbox>
                      <div class="profile-permissions__perm-desc">{{ perm.description }}</div>
                    </v-col>
                  </v-row>
                </v-card>
              </v-col>
              <v-col cols="12" class="d-flex justify-end">
                <v-btn color="primary" :loading="saving" @click="saveProfilePermissions">
                  Salvar permissoes
                </v-btn>
              </v-col>
            </v-row>
            <v-row v-else>
              <v-col cols="12">
                <v-alert type="info" border="left" colored-border>
                  Selecione um perfil para editar permissoes.
                </v-alert>
              </v-col>
            </v-row>
          </v-card>
        </v-tab-item>
      </v-tabs-items>
    </v-card>

    <v-dialog v-model="profileDialog" max-width="520px">
      <v-card>
        <v-card-title>
          Editar perfil
          <v-spacer></v-spacer>
          <v-btn icon @click="profileDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-text-field v-model="profileForm.name" label="Nome" outlined dense></v-text-field>
          <v-text-field v-model="profileForm.description" label="Descricao" outlined dense></v-text-field>
          <v-select
            v-model="profileForm.permissions"
            :items="permissions"
            item-text="name"
            item-value="id"
            label="Permissoes"
            multiple
            chips
            outlined
            dense
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="profileDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="updateProfile">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="permissionDialog" max-width="520px">
      <v-card>
        <v-card-title>
          Editar permissao
          <v-spacer></v-spacer>
          <v-btn icon @click="permissionDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-text-field v-model="permissionForm.name" label="Nome" outlined dense></v-text-field>
          <v-text-field v-model="permissionForm.description" label="Descricao" outlined dense></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="permissionDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="updatePermission">Salvar</v-btn>
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

const PROFILE_API = `${api.defaults.baseURL}/perfil_role.php`
const PERMISSION_API = `${api.defaults.baseURL}/permissao_role.php`

export default {
  name: 'ProfilePermissionsManager',
  data() {
    return {
      tab: 0,
      loading: false,
      saving: false,
      profiles: [],
      permissions: [],
      selectedProfileId: null,
      selectedPermissionIds: [],
      permissionFilter: '',
      profileDialog: false,
      permissionDialog: false,
      newProfile: {
        name: '',
        description: '',
        permissions: []
      },
      newPermission: {
        name: '',
        description: ''
      },
      profileForm: {
        id: null,
        name: '',
        description: '',
        permissions: []
      },
      permissionForm: {
        id: null,
        name: '',
        description: ''
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      profileHeaders: [
        { text: 'Nome', value: 'name' },
        { text: 'Descricao', value: 'description' },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      permissionHeaders: [
        { text: 'Nome', value: 'name' },
        { text: 'Descricao', value: 'description' },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ]
    }
  },
  computed: {
    filteredPermissions() {
      if (!this.permissionFilter) {
        return this.permissions
      }
      const term = this.permissionFilter.toLowerCase()
      return this.permissions.filter(perm =>
        `${perm.name} ${perm.description}`.toLowerCase().includes(term)
      )
    }
  },
  mounted() {
    this.refreshAll()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    async parseResponse(response) {
      const text = await response.text()
      if (!text) {
        return {}
      }
      try {
        return JSON.parse(text)
      } catch (error) {
        throw new Error('Resposta inválida do servidor.')
      }
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    async refreshAll() {
      this.loading = true
      try {
        await Promise.all([this.fetchProfiles(), this.fetchPermissions()])
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchProfiles() {
      const response = await fetch(`${PROFILE_API}?request=listar_profiles&limit=200`, {
        headers: this.authHeaders()
      })
      const data = await this.parseResponse(response)
      this.profiles = Array.isArray(data) ? data : data.data || []
    },
    async fetchPermissions() {
      const response = await fetch(`${PERMISSION_API}?request=listar_permissions&limit=500`, {
        headers: this.authHeaders()
      })
      const data = await this.parseResponse(response)
      this.permissions = Array.isArray(data) ? data : data.data || []
    },
    async loadProfilePermissions() {
      if (!this.selectedProfileId) return
      try {
        const response = await fetch(
          `${PROFILE_API}?request=buscar_profile&id=${this.selectedProfileId}`,
          { headers: this.authHeaders() }
        )
        const data = await this.parseResponse(response)
        const perms = Array.isArray(data.permissions) ? data.permissions : []
        this.selectedPermissionIds = perms.map(item => item.id)
        this.profileForm = {
          id: data.id,
          name: data.name || '',
          description: data.description || '',
          permissions: this.selectedPermissionIds.slice()
        }
      } catch (error) {
        this.showMessage(`Erro ao carregar permissoes: ${error.message}`, 'error')
      }
    },
    async createProfile() {
      if (!this.newProfile.name.trim()) {
        this.showMessage('Informe o nome do perfil.', 'warning')
        return
      }
      this.saving = true
      try {
        const payload = {
          name: this.newProfile.name.trim(),
          description: this.newProfile.description.trim(),
          permissions: this.newProfile.permissions || []
        }
        const response = await fetch(`${PROFILE_API}?request=criar_profile`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
          body: JSON.stringify(payload)
        })
        const result = await this.parseResponse(response)
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao criar')
        }
        this.newProfile = { name: '', description: '', permissions: [] }
        await this.fetchProfiles()
        this.showMessage('Perfil criado com sucesso.')
      } catch (error) {
        this.showMessage(`Erro ao criar perfil: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    openProfileEdit(profile) {
      this.profileForm = {
        id: profile.id,
        name: profile.name || '',
        description: profile.description || '',
        permissions: []
      }
      this.profileDialog = true
      this.selectedProfileId = profile.id
      this.loadProfilePermissions()
    },
    async updateProfile() {
      if (!this.profileForm.id) return
      this.saving = true
      try {
        const payload = {
          name: this.profileForm.name.trim(),
          description: this.profileForm.description.trim(),
          permissions: this.profileForm.permissions || []
        }
        const response = await fetch(
          `${PROFILE_API}?request=atualizar_profile&id=${this.profileForm.id}`,
          {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
            body: JSON.stringify(payload)
          }
        )
        const result = await this.parseResponse(response)
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao atualizar')
        }
        this.profileDialog = false
        await this.fetchProfiles()
        this.showMessage('Perfil atualizado.')
      } catch (error) {
        this.showMessage(`Erro ao atualizar perfil: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async deleteProfile(profile) {
      if (!confirm('Deseja excluir este perfil?')) return
      this.saving = true
      try {
        const response = await fetch(
          `${PROFILE_API}?request=excluir_profile&id=${profile.id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await this.parseResponse(response)
        if (result.error) {
          throw new Error(result.error)
        }
        await this.fetchProfiles()
        this.showMessage('Perfil excluido.')
      } catch (error) {
        this.showMessage(`Erro ao excluir perfil: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async createPermission() {
      if (!this.newPermission.name.trim()) {
        this.showMessage('Informe o nome da permissao.', 'warning')
        return
      }
      this.saving = true
      try {
        const payload = {
          name: this.newPermission.name.trim(),
          description: this.newPermission.description.trim()
        }
        const response = await fetch(`${PERMISSION_API}?request=criar_permission`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
          body: JSON.stringify(payload)
        })
        const result = await this.parseResponse(response)
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao criar')
        }
        this.newPermission = { name: '', description: '' }
        await this.fetchPermissions()
        this.showMessage('Permissao criada com sucesso.')
      } catch (error) {
        this.showMessage(`Erro ao criar permissao: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    openPermissionEdit(permission) {
      this.permissionForm = {
        id: permission.id,
        name: permission.name || '',
        description: permission.description || ''
      }
      this.permissionDialog = true
    },
    async updatePermission() {
      if (!this.permissionForm.id) return
      this.saving = true
      try {
        const payload = {
          name: this.permissionForm.name.trim(),
          description: this.permissionForm.description.trim()
        }
        const response = await fetch(
          `${PERMISSION_API}?request=atualizar_permission&id=${this.permissionForm.id}`,
          {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
            body: JSON.stringify(payload)
          }
        )
        const result = await this.parseResponse(response)
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao atualizar')
        }
        this.permissionDialog = false
        await this.fetchPermissions()
        this.showMessage('Permissao atualizada.')
      } catch (error) {
        this.showMessage(`Erro ao atualizar permissao: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async deletePermission(permission) {
      if (!confirm('Deseja excluir esta permissao?')) return
      this.saving = true
      try {
        const response = await fetch(
          `${PERMISSION_API}?request=excluir_permission&id=${permission.id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await this.parseResponse(response)
        if (result.error) {
          throw new Error(result.error)
        }
        await this.fetchPermissions()
        this.showMessage('Permissao excluida.')
      } catch (error) {
        this.showMessage(`Erro ao excluir permissao: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async saveProfilePermissions() {
      if (!this.selectedProfileId) return
      this.saving = true
      try {
        const profile = this.profiles.find(item => item.id === this.selectedProfileId)
        const payload = {
          name: profile?.name || this.profileForm.name,
          description: profile?.description || this.profileForm.description,
          permissions: this.selectedPermissionIds
        }
        const response = await fetch(
          `${PROFILE_API}?request=atualizar_profile&id=${this.selectedProfileId}`,
          {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
            body: JSON.stringify(payload)
          }
        )
        const result = await this.parseResponse(response)
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.profileForm.permissions = this.selectedPermissionIds.slice()
        this.showMessage('Permissoes atualizadas.')
      } catch (error) {
        this.showMessage(`Erro ao salvar permissoes: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.profile-permissions__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.profile-permissions__header h2 {
  margin: 0;
  font-size: 22px;
}

.profile-permissions__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.profile-permissions__section-title {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #0f172a;
}

.profile-permissions__permissions-list {
  background: #f8fafc;
}

.profile-permissions__perm-desc {
  font-size: 12px;
  color: #64748b;
  margin-left: 30px;
  margin-top: -6px;
}
</style>
