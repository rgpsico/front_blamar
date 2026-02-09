<template>
  <div class="profile-permissions">
    <div class="profile-permissions__header">
      <div>
        <h2>Perfis e Permissoes</h2>
        <p>Crie perfis, defina permissoes e associe cada perfil.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" @click="resetDemo">Resetar demo</v-btn>
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
                <v-btn color="primary" block @click="addProfile">Adicionar perfil</v-btn>
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
                >
                  <template v-slot:item.actions="{ item }">
                    <v-btn icon color="error" @click="removeProfile(item)">
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
                <v-btn color="primary" block @click="addPermission">Adicionar permissao</v-btn>
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
                >
                  <template v-slot:item.actions="{ item }">
                    <v-btn icon color="error" @click="removePermission(item)">
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
            <div class="profile-permissions__section-title">Associar permissao ao perfil</div>
            <v-simple-table class="profile-permissions__matrix">
              <thead>
                <tr>
                  <th>Perfil</th>
                  <th v-for="perm in permissions" :key="perm.id">{{ perm.name }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="profile in profiles" :key="profile.id">
                  <td>{{ profile.name }}</td>
                  <td v-for="perm in permissions" :key="perm.id">
                    <v-checkbox
                      :input-value="hasPermission(profile.id, perm.id)"
                      @change="togglePermission(profile.id, perm.id)"
                      hide-details
                      dense
                    ></v-checkbox>
                  </td>
                </tr>
              </tbody>
            </v-simple-table>
          </v-card>
        </v-tab-item>
      </v-tabs-items>
    </v-card>
  </div>
</template>

<script>
export default {
  name: 'ProfilePermissionsManager',
  data() {
    return {
      tab: 0,
      profiles: [
        { id: 1, name: 'Administrador', description: 'Acesso total' },
        { id: 2, name: 'Editor', description: 'Conteudos e newsletters' },
        { id: 3, name: 'Operacao', description: 'Painel e clientes' }
      ],
      permissions: [
        { id: 1, name: 'Gerenciar usuarios', description: 'Criar e editar usuarios' },
        { id: 2, name: 'Editar conteudo', description: 'Blogs, newsletters e paginas' },
        { id: 3, name: 'Ver relatorios', description: 'Indicadores e estatisticas' }
      ],
      assignments: {
        1: new Set([1, 2, 3]),
        2: new Set([2]),
        3: new Set([3])
      },
      newProfile: {
        name: '',
        description: ''
      },
      newPermission: {
        name: '',
        description: ''
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
  methods: {
    addProfile() {
      const name = this.newProfile.name.trim()
      if (!name) return
      const id = Date.now()
      this.profiles.push({ id, name, description: this.newProfile.description.trim() })
      this.assignments[id] = new Set()
      this.newProfile = { name: '', description: '' }
    },
    removeProfile(profile) {
      this.profiles = this.profiles.filter(item => item.id !== profile.id)
      delete this.assignments[profile.id]
    },
    addPermission() {
      const name = this.newPermission.name.trim()
      if (!name) return
      const id = Date.now()
      this.permissions.push({ id, name, description: this.newPermission.description.trim() })
      this.newPermission = { name: '', description: '' }
    },
    removePermission(permission) {
      this.permissions = this.permissions.filter(item => item.id !== permission.id)
      Object.keys(this.assignments).forEach(profileId => {
        this.assignments[profileId].delete(permission.id)
      })
    },
    hasPermission(profileId, permId) {
      return this.assignments[profileId]?.has(permId)
    },
    togglePermission(profileId, permId) {
      if (!this.assignments[profileId]) {
        this.assignments[profileId] = new Set()
      }
      if (this.assignments[profileId].has(permId)) {
        this.assignments[profileId].delete(permId)
      } else {
        this.assignments[profileId].add(permId)
      }
      this.assignments = { ...this.assignments }
    },
    resetDemo() {
      this.tab = 0
      this.profiles = [
        { id: 1, name: 'Administrador', description: 'Acesso total' },
        { id: 2, name: 'Editor', description: 'Conteudos e newsletters' },
        { id: 3, name: 'Operacao', description: 'Painel e clientes' }
      ]
      this.permissions = [
        { id: 1, name: 'Gerenciar usuarios', description: 'Criar e editar usuarios' },
        { id: 2, name: 'Editar conteudo', description: 'Blogs, newsletters e paginas' },
        { id: 3, name: 'Ver relatorios', description: 'Indicadores e estatisticas' }
      ]
      this.assignments = {
        1: new Set([1, 2, 3]),
        2: new Set([2]),
        3: new Set([3])
      }
      this.newProfile = { name: '', description: '' }
      this.newPermission = { name: '', description: '' }
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

.profile-permissions__matrix th,
.profile-permissions__matrix td {
  text-align: center;
}

.profile-permissions__matrix td:first-child {
  text-align: left;
}
</style>
