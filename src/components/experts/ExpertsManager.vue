<template>
  <div class="experts-manager">
    <div class="experts-manager__header">
      <div>
        <h2>Brazilian Experts</h2>
        <p>Listagem e busca de experts cadastrados.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Expert</v-btn>
      <v-btn outlined color="primary" @click="fetchExperts">Atualizar</v-btn>
    </div>

    <v-card class="experts-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field v-model="filters.query" label="Buscar" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.field"
            :items="fieldOptions"
            label="Campo"
            dense
            outlined
          ></v-select>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.receptivo"
            :items="statusOptions"
            label="Status"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="pagination.limit"
            :items="limitOptions"
            label="Por pagina"
            dense
            outlined
          ></v-select>
        </v-col>
      </v-row>
      <div class="experts-manager__filter-actions">
        <v-btn outlined color="primary" @click="applySearch">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="experts"
        :loading="loading"
        item-key="pk_br_experts"
        class="elevation-0"
        hide-default-footer
      >
        <template slot="item.foto" slot-scope="{ item }">
          <v-avatar size="40" class="experts-manager__avatar">
            <img :src="photoUrl(item.foto)" :alt="item.nome" @error="onImageError" />
          </v-avatar>
        </template>
        <template slot="item.receptivo" slot-scope="{ item }">
          <v-chip :color="item.receptivo === 't' ? 'success' : 'grey'" small>
            {{ item.receptivo === 't' ? 'Ativo' : 'Inativo' }}
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
      <div class="experts-manager__pagination">
        <v-btn icon :disabled="pagination.page === 1" @click="changePage(-1)">
          <v-icon>mdi-chevron-left</v-icon>
        </v-btn>
        <span>Pagina {{ pagination.page }} de {{ pagination.pages }}</span>
        <v-btn icon :disabled="pagination.page >= pagination.pages" @click="changePage(1)">
          <v-icon>mdi-chevron-right</v-icon>
        </v-btn>
      </div>
    </v-card>

    <v-dialog v-model="dialog" max-width="860px" persistent>
      <v-card>
        <v-card-title class="experts-manager__dialog-title">
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
                <v-text-field v-model="editedItem.email" label="Email" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto" label="Foto" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto_news" label="Foto news" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.idiomas" label="Idiomas" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.skype" label="Skype" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.texto"
                  label="Texto"
                  outlined
                  rows="3"
                ></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.texto_ing"
                  label="Texto ingles"
                  outlined
                  rows="3"
                ></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.texto_esp"
                  label="Texto espanhol"
                  outlined
                  rows="3"
                ></v-textarea>
              </v-col>
              <v-col cols="12" md="4">
                <v-switch v-model="editedItem.receptivo" label="Ativo" inset></v-switch>
              </v-col>
              <v-col cols="12" md="4">
                <v-switch v-model="editedItem.lb" label="LB" inset></v-switch>
              </v-col>
              <v-col cols="12" md="4">
                <v-switch v-model="editedItem.lm" label="LM" inset></v-switch>
              </v-col>
              <v-col cols="12" md="4">
                <v-switch v-model="editedItem.messenger" label="Messenger" inset></v-switch>
              </v-col>
              <v-col cols="12" md="4">
                <v-switch v-model="editedItem.volayo" label="Volayo" inset></v-switch>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.fk_usuario" label="FK usuario" outlined dense></v-text-field>
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
            Tem certeza que deseja excluir o expert
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
const API_BASE = `${api.defaults.baseURL}/`
const IMAGE_BASE = 'https://www.blumar.com.br/'

export default {
  name: 'ExpertsManager',
  data() {
    return {
      loading: false,
      saving: false,
      experts: [],
      defaultImage: require('@/assets/default.png'),
      dialog: false,
      dialogDelete: false,
      filters: {
        query: '',
        field: 'nome',
        receptivo: ''
      },
      pagination: {
        page: 1,
        pages: 1,
        limit: 10
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Foto', value: 'foto', sortable: false },
        { text: 'Nome', value: 'nome' },
        { text: 'Email', value: 'email' },
        { text: 'Idiomas', value: 'idiomas' },
        { text: 'Status', value: 'receptivo', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      fieldOptions: [
        { text: 'Nome', value: 'nome' },
        { text: 'Email', value: 'email' }
      ],
      statusOptions: [
        { text: 'Todos', value: '' },
        { text: 'Ativo', value: 't' },
        { text: 'Inativo', value: 'f' }
      ],
      limitOptions: [10, 20, 50, 100],
      editedIndex: -1,
      editedItem: {
        pk_br_experts: null,
        nome: '',
        email: '',
        foto: '',
        foto_news: '',
        idiomas: '',
        texto: '',
        texto_ing: '',
        texto_esp: '',
        skype: '',
        receptivo: true,
        lb: false,
        lm: false,
        messenger: false,
        volayo: false,
        fk_usuario: ''
      }
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Expert' : 'Editar Expert'
    }
  },
  mounted() {
    this.fetchExperts()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    photoUrl(path) {
      if (!path) {
        return this.defaultImage
      }
      if (path.startsWith('http')) {
        return path
      }
      return `${IMAGE_BASE}${path}`
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    onImageError(event) {
      event.target.src = this.defaultImage
    },
    buildUrl() {
      const params = new URLSearchParams()
      const hasQuery = this.filters.query && this.filters.query.trim().length > 0
      params.append('action', hasQuery ? 'search_experts' : 'list_experts')
      params.append('page', String(this.pagination.page))
      params.append('limit', String(this.pagination.limit))
      if (hasQuery) {
        params.append('query', this.filters.query.trim())
        params.append('field', this.filters.field)
      }
      if (this.filters.receptivo) {
        params.append('receptivo', this.filters.receptivo)
      }
      return `${API_BASE}experts.php?${params.toString()}`
    },
    async fetchExperts() {
      this.loading = true
      try {
        const response = await fetch(this.buildUrl())
        const data = await response.json()
        this.experts = Array.isArray(data.experts) ? data.experts : []
        this.pagination.pages = data.pages || 1
        this.pagination.page = data.page || this.pagination.page
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    applySearch() {
      this.pagination.page = 1
      this.fetchExperts()
    },
    resetFilters() {
      this.filters = {
        query: '',
        field: 'nome',
        receptivo: ''
      }
      this.pagination.page = 1
      this.fetchExperts()
    },
    changePage(delta) {
      const nextPage = this.pagination.page + delta
      if (nextPage < 1 || nextPage > this.pagination.pages) {
        return
      }
      this.pagination.page = nextPage
      this.fetchExperts()
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = {
        pk_br_experts: null,
        nome: '',
        email: '',
        foto: '',
        foto_news: '',
        idiomas: '',
        texto: '',
        texto_ing: '',
        texto_esp: '',
        skype: '',
        receptivo: true,
        lb: false,
        lm: false,
        messenger: false,
        volayo: false,
        fk_usuario: ''
      }
      this.dialog = true
    },
    openEdit(item) {
      this.editedIndex = this.experts.indexOf(item)
      this.editedItem = {
        pk_br_experts: item.pk_br_experts,
        nome: item.nome || '',
        email: item.email || '',
        foto: item.foto || '',
        foto_news: item.foto_news || '',
        idiomas: item.idiomas || '',
        texto: item.texto || '',
        texto_ing: item.texto_ing || '',
        texto_esp: item.texto_esp || '',
        skype: item.skype || '',
        receptivo: item.receptivo === 't',
        lb: item.lb === 't',
        lm: item.lm === 't',
        messenger: item.messenger === 't',
        volayo: item.volayo === 't',
        fk_usuario: item.fk_usuario || ''
      }
      this.dialog = true
    },
    openDelete(item) {
      this.editedItem = {
        pk_br_experts: item.pk_br_experts,
        nome: item.nome || ''
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
        const action = isEdit ? 'update_expert' : 'create_expert'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}experts.php?action=${action}&id=${this.editedItem.pk_br_experts}`
          : `${API_BASE}experts.php?action=${action}`

        const payload = {
          nome: this.editedItem.nome,
          email: this.editedItem.email,
          foto: this.editedItem.foto,
          foto_news: this.editedItem.foto_news,
          idiomas: this.editedItem.idiomas,
          texto: this.editedItem.texto,
          texto_ing: this.editedItem.texto_ing,
          texto_esp: this.editedItem.texto_esp,
          skype: this.editedItem.skype,
          receptivo: this.editedItem.receptivo ? 't' : 'f',
          lb: this.editedItem.lb ? 't' : 'f',
          lm: this.editedItem.lm ? 't' : 'f',
          messenger: this.editedItem.messenger ? 't' : 'f',
          volayo: this.editedItem.volayo ? 't' : 'f',
          fk_usuario: this.editedItem.fk_usuario
        }

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
        this.showMessage(isEdit ? 'Expert atualizado.' : 'Expert criado.')
        this.dialog = false
        await this.fetchExperts()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.pk_br_experts) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}experts.php?action=delete_expert&id=${this.editedItem.pk_br_experts}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Expert excluido.')
        this.dialogDelete = false
        await this.fetchExperts()
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
.experts-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.experts-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.experts-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.experts-manager__filters {
  padding: 16px;
}

.experts-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.experts-manager__dialog-title {
  display: flex;
  align-items: center;
}

.experts-manager__pagination {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  padding: 12px 16px 16px;
  color: #475569;
}

.experts-manager__avatar img {
  object-fit: cover;
}
</style>
