<template>
  <div class="system-updates">
    <div class="system-updates__header">
      <div>
        <h2>Atualizacoes do Sistema</h2>
        <p>Gerencie todas as alteracoes e melhorias do sistema</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Cadastrar</v-btn>
      <v-btn outlined color="error" class="mr-2" @click="clearFilters">Limpar</v-btn>
      <v-btn color="error" :loading="loading" @click="runSearch">Buscar</v-btn>
    </div>

    <v-card class="system-updates__filters" elevation="6">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.query"
            label="Buscar por titulo ou descricao"
            dense
            outlined
            @keyup.enter="runSearch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.modulo"
            :items="moduloOptions"
            label="Modulo"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.tipo"
            :items="tipoOptions"
            label="Tipo"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.publico"
            :items="publicoOptions"
            label="Publico"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model.number="filters.limit"
            label="Limite"
            dense
            outlined
            type="number"
            min="1"
          ></v-text-field>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="filteredUpdates"
        :loading="loading"
        item-key="id"
        class="elevation-0"
        :items-per-page="itemsPerPage"
        :page.sync="page"
        :footer-props="{
          'items-per-page-options': [10, 20, 50],
          showFirstLastPage: true
        }"
      >
        <template v-slot:item.tipo="{ item }">
          <v-chip :color="tipoColor(item.tipo)" small text-color="white">
            {{ item.tipo || '-' }}
          </v-chip>
        </template>
        <template v-slot:item.publico="{ item }">
          <v-chip :color="item.publico ? 'success' : 'grey'" small text-color="white">
            {{ item.publico ? 'Publico' : 'Interno' }}
          </v-chip>
        </template>
        <template v-slot:item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon small color="primary" @click="openEdit(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon small color="error" @click="openDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
        <template v-slot:no-data>
          <div class="system-updates__empty">Nenhum resultado encontrado</div>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="720px" persistent>
      <v-card>
        <v-card-title class="system-updates__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12">
                <v-text-field v-model="form.titulo" label="Titulo" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.descricao"
                  label="Descricao"
                  outlined
                  rows="4"
                ></v-textarea>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.modulo"
                  :items="moduloOptions"
                  label="Modulo"
                  outlined
                  dense
                  clearable
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.tipo"
                  :items="tipoOptionsFiltered"
                  label="Tipo"
                  outlined
                  dense
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.publico"
                  :items="publicoFormOptions"
                  label="Publico"
                  outlined
                  dense
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.created_by" label="Criado por" outlined dense></v-text-field>
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
            Tem certeza que deseja excluir a atualizacao
            <strong>{{ deleteItem?.titulo || deleteItem?.id }}</strong>?
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

const API_BASE = 'api_sistema_atualizacoes.php'

const blankForm = () => ({
  id: null,
  titulo: '',
  descricao: '',
  modulo: '',
  tipo: 'feature',
  publico: true,
  created_by: ''
})

export default {
  name: 'AtualizacoesSistema',
  data() {
    return {
      loading: false,
      saving: false,
      dialog: false,
      dialogDelete: false,
      updates: [],
      deleteItem: null,
      page: 1,
      itemsPerPage: 10,
      filters: {
        query: '',
        modulo: '',
        tipo: '',
        publico: '',
        limit: 200
      },
      form: blankForm(),
      headers: [
        { text: 'ID', value: 'id', width: 80 },
        { text: 'Titulo', value: 'titulo' },
        { text: 'Modulo', value: 'modulo', width: 160 },
        { text: 'Tipo', value: 'tipo', width: 120 },
        { text: 'Publico', value: 'publico', width: 110 },
        { text: 'Criado por', value: 'created_by', width: 140 },
        { text: 'Data', value: 'created_at', width: 150 },
        { text: 'Acoes', value: 'actions', width: 120, sortable: false, align: 'end' }
      ],
      moduloOptions: ['Todos', 'Banco de Video', 'Incentive', 'Auth', 'Agendamento', 'Outro'],
      tipoOptions: ['Todos', 'feature', 'bugfix', 'improvement', 'refactor', 'security'],
      publicoOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Publico', value: 'true' },
        { text: 'Interno', value: 'false' }
      ],
      publicoFormOptions: [
        { text: 'Publico', value: true },
        { text: 'Interno', value: false }
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
      return this.form.id ? 'Editar atualizacao' : 'Nova atualizacao'
    },
    tipoOptionsFiltered() {
      return this.tipoOptions.filter(item => item !== 'Todos')
    },
    filteredUpdates() {
      const query = this.filters.query.trim().toLowerCase()
      if (!query) {
        return this.updates
      }
      return this.updates.filter(item => {
        const titulo = String(item.titulo || '').toLowerCase()
        const descricao = String(item.descricao || '').toLowerCase()
        return titulo.includes(query) || descricao.includes(query)
      })
    }
  },
  methods: {
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    normalizeItem(item) {
      return {
        id: item.id,
        titulo: item.titulo || '',
        descricao: item.descricao || '',
        modulo: item.modulo || '',
        tipo: item.tipo || '',
        publico: item.publico === true || item.publico === 't',
        created_by: item.created_by || '',
        created_at: item.created_at || ''
      }
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_atualizacoes')
      if (this.filters.modulo && this.filters.modulo !== 'Todos') {
        params.append('filtro_modulo', this.filters.modulo)
      }
      if (this.filters.tipo && this.filters.tipo !== 'Todos') {
        params.append('filtro_tipo', this.filters.tipo)
      }
      if (this.filters.publico && this.filters.publico !== 'all') {
        params.append('publico', this.filters.publico)
      }
      if (this.filters.limit) {
        params.append('limit', String(this.filters.limit))
      }
      return params.toString()
    },
    async runSearch() {
      this.loading = true
      try {
        const response = await api.get(`${API_BASE}?${this.buildQuery()}`)
        const data = response?.data || []
        const list = Array.isArray(data) ? data : data.data
        const normalized = Array.isArray(list) ? list.map(this.normalizeItem) : []
        this.updates = normalized.sort((a, b) => {
          const dateA = new Date(a.created_at || 0).getTime()
          const dateB = new Date(b.created_at || 0).getTime()
          return dateB - dateA
        })
        this.page = 1
      } catch (error) {
        this.showMessage(`Erro ao buscar atualizacoes: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    clearFilters() {
      this.filters = {
        query: '',
        modulo: '',
        tipo: '',
        publico: '',
        limit: 200
      }
      this.updates = []
      this.page = 1
    },
    openCreate() {
      this.form = blankForm()
      this.dialog = true
    },
    openEdit(item) {
      this.form = {
        id: item.id,
        titulo: item.titulo || '',
        descricao: item.descricao || '',
        modulo: item.modulo || '',
        tipo: item.tipo || 'feature',
        publico: item.publico === true,
        created_by: item.created_by || ''
      }
      this.dialog = true
    },
    closeDialog() {
      this.dialog = false
    },
    async save() {
      if (!this.form.titulo || !this.form.descricao) {
        this.showMessage('Informe titulo e descricao.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = !!this.form.id
        const request = isEdit ? 'atualizar_atualizacao' : 'criar_atualizacao'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}?request=${request}&id=${this.form.id}`
          : `${API_BASE}?request=${request}`

        const payload = {
          titulo: this.form.titulo,
          descricao: this.form.descricao,
          modulo: this.form.modulo || null,
          tipo: this.form.tipo || null,
          publico: this.form.publico,
          created_by: this.form.created_by || null
        }

        const response = await api.request({
          url,
          method,
          data: payload
        })
        const result = response?.data || {}
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.showMessage(isEdit ? 'Atualizacao atualizada.' : 'Atualizacao criada.')
        this.dialog = false
        await this.runSearch()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    openDelete(item) {
      this.deleteItem = item
      this.dialogDelete = true
    },
    async confirmDelete() {
      if (!this.deleteItem?.id) return
      this.saving = true
      try {
        const response = await api.delete(`${API_BASE}?request=excluir_atualizacao&id=${this.deleteItem.id}`)
        const result = response?.data || {}
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao excluir')
        }
        this.showMessage('Atualizacao excluida.')
        this.dialogDelete = false
        await this.runSearch()
      } catch (error) {
        this.showMessage(`Erro ao excluir: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    tipoColor(tipo) {
      switch (String(tipo || '').toLowerCase()) {
        case 'feature':
          return 'primary'
        case 'bugfix':
          return 'error'
        case 'improvement':
          return 'success'
        case 'refactor':
          return 'orange'
        case 'security':
          return 'purple'
        default:
          return 'grey'
      }
    },
    formatDate(value) {
      if (!value) return '-'
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) {
        return value
      }
      return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.system-updates__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.system-updates__header h2 {
  margin: 0;
  font-size: 24px;
}

.system-updates__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.system-updates__filters {
  padding: 16px;
}

.system-updates__dialog-title {
  display: flex;
  align-items: center;
}

.system-updates__empty {
  padding: 16px;
  color: #64748b;
}
</style>
