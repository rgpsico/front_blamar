<template>
  <div class="abt-manager">
    <div class="abt-manager__header">
      <div>
        <h2>Around Brazil Tours</h2>
        <p>Listagem, criacao, edicao e exclusao via API.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" @click="openCreate">Novo ABT</v-btn>
    </div>

    <v-card class="abt-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.nome" label="Nome" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.cidade"
            :items="cidades"
            item-text="name"
            item-value="id"
            label="Cidade"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo"
            :items="statusOptions"
            label="Status"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model="filters.data"
            label="Data"
            type="date"
            dense
            outlined
            clearable
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-btn outlined color="primary" class="mt-2" @click="fetchAbts">Atualizar</v-btn>
        </v-col>
      </v-row>
      <div class="abt-manager__filter-actions">
        <v-btn outlined color="primary" @click="fetchAbts">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        item-key="pk_abt"
        class="elevation-0"
      >
        <template slot="item.imagens" slot-scope="{ item }">
          <v-avatar size="40" class="abt-manager__avatar">
            <img :src="primaryImage(item)" :alt="item.name || item.nome" @error="onImageError" />
          </v-avatar>
        </template>
        <template slot="item.ativo" slot-scope="{ item }">
          <v-chip :color="isActive(item) ? 'success' : 'grey'" small>
            {{ isActive(item) ? 'Ativo' : 'Inativo' }}
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

    <v-dialog v-model="dialog" max-width="720px" persistent>
      <v-card>
        <v-card-title class="abt-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field v-model="editedItem.nome" label="Nome" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.data" label="Data" type="date" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="editedItem.titulo" label="Titulo" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.preco_abt"
                  label="Preco"
                  prefix="R$"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.tempo_abt"
                  label="Duracao (h)"
                  type="number"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-switch v-model="editedItem.ativo" label="Ativo" inset></v-switch>
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
            Tem certeza que deseja excluir o ABT
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

const API_BASE = 'abt.php'

export default {
  name: 'AbtManager',
  data() {
    return {
      loading: false,
      saving: false,
      items: [],
      cidades: [],
      defaultImage: require('@/assets/default.png'),
      dialog: false,
      dialogDelete: false,
      filters: {
        nome: '',
        cidade: '',
        ativo: '',
        data: ''
      },
      editedIndex: -1,
      editedItem: {
        pk_abt: null,
        nome: '',
        data: '',
        titulo: '',
        preco_abt: '',
        tempo_abt: '',
        ativo: true
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Foto', value: 'imagens', sortable: false },
        { text: 'Nome', value: 'nome' },
        { text: 'Cidade', value: 'cidade_nome' },
        { text: 'Data', value: 'data' },
        { text: 'Preco', value: 'preco_abt' },
        { text: 'Status', value: 'ativo', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      statusOptions: [
        { text: 'Ativo', value: 'true' },
        { text: 'Inativo', value: 'false' }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo ABT' : 'Editar ABT'
    }
  },
  mounted() {
    this.fetchCidades()
    this.fetchAbts()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    primaryImage(item) {
      if (item.imagens && item.imagens.length) {
        return item.imagens[0].image_url
      }
      return this.defaultImage
    },
    isActive(item) {
      return item.is_active === true || item.ativo === 't' || item.ativo === true
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    onImageError(event) {
      event.target.src = this.defaultImage
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_abts')
      if (this.filters.nome) params.append('filtro_nome', this.filters.nome)
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      if (this.filters.data) params.append('filtro_data', this.filters.data)
      if (this.filters.cidade) params.append('cidade', this.filters.cidade)
      params.append('limit', '200')
      return params.toString()
    },
    async fetchAbts() {
      this.loading = true
      try {
        const response = await api.get(`${API_BASE}?${this.buildQuery()}`, {
          headers: this.authHeaders()
        })
        const data = response.data
        this.items = Array.isArray(data) ? data : data.data || []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchCidades() {
      try {
        const response = await api.get(`${API_BASE}?request=listar_cidades`, {
          headers: this.authHeaders()
        })
        const data = response.data
        this.cidades = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar cidades: ${error.message}`, 'error')
      }
    },
    resetFilters() {
      this.filters = {
        nome: '',
        cidade: '',
        ativo: '',
        data: ''
      }
      this.fetchAbts()
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = {
        pk_abt: null,
        nome: '',
        data: '',
        titulo: '',
        preco_abt: '',
        tempo_abt: '',
        ativo: true
      }
      this.dialog = true
    },
    openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = {
        pk_abt: item.pk_abt,
        nome: item.nome || item.name || '',
        data: item.data || '',
        titulo: item.titulo || '',
        preco_abt: item.preco_abt || '',
        tempo_abt: item.tempo_abt || '',
        ativo: this.isActive(item)
      }
      this.dialog = true
    },
    openDelete(item) {
      this.editedItem = {
        pk_abt: item.pk_abt,
        nome: item.nome || item.name || ''
      }
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    async save() {
      if (!this.editedItem.nome) {
        this.showMessage('Informe o nome.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'atualizar_abt' : 'criar_abt'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}abt.php?request=${request}&id=${this.editedItem.pk_abt}`
          : `${API_BASE}abt.php?request=${request}`

        const payload = {
          nome: this.editedItem.nome,
          data: this.editedItem.data,
          titulo: this.editedItem.titulo,
          preco_abt: this.editedItem.preco_abt,
          tempo_abt: this.editedItem.tempo_abt,
          ativo: this.editedItem.ativo ? 't' : 'f'
        }

        const response = await api.request({
          url,
          method,
          headers: {
            'Content-Type': 'application/json',
            ...this.authHeaders()
          },
          data: payload
        })
        const result = response.data
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.showMessage(isEdit ? 'ABT atualizado.' : 'ABT criado.')
        this.dialog = false
        await this.fetchAbts()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.pk_abt) {
        return
      }
      this.saving = true
      try {
        const response = await api.delete(
          `${API_BASE}?request=excluir_abt&id=${this.editedItem.pk_abt}`,
          { headers: this.authHeaders() }
        )
        const result = response.data
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('ABT excluido.')
        this.dialogDelete = false
        await this.fetchAbts()
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
.abt-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.abt-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.abt-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.abt-manager__filters {
  padding: 16px;
}

.abt-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.abt-manager__dialog-title {
  display: flex;
  align-items: center;
}

.abt-manager__avatar img {
  object-fit: cover;
}
</style>
