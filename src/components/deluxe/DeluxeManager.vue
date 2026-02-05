<template>
  <div class="deluxe-manager">
    <div class="deluxe-manager__header">
      <div>
        <h2>Deluxe</h2>
        <p>Listagem, criacao, edicao e exclusao via API.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Deluxe</v-btn>
      <v-btn outlined color="primary" @click="fetchDeluxes">Atualizar</v-btn>
    </div>

    <v-card class="deluxe-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.nome" label="Nome" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.regiao" label="Regiao" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.cidade" label="Cidade" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo"
            :items="statusOptions"
            item-text="text"
            item-value="value"
            label="Status"
            dense
            outlined
          ></v-select>
        </v-col>
        <v-col cols="12" md="1">
          <v-text-field
            v-model.number="filters.limit"
            label="Limite"
            type="number"
            min="1"
            dense
            outlined
          ></v-text-field>
        </v-col>
      </v-row>
      <div class="deluxe-manager__filters-actions">
        <v-btn color="primary" @click="fetchDeluxes">Aplicar</v-btn>
        <v-btn text @click="clearFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        item-key="pk_deluxe"
        class="elevation-0"
      >
        <template slot="item.imagens" slot-scope="{ item }">
          <v-avatar size="40" class="deluxe-manager__avatar">
            <img :src="primaryImage(item)" :alt="item.nome" @error="onImageError" />
          </v-avatar>
        </template>
        <template slot="item.status" slot-scope="{ item }">
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

    <v-dialog v-model="dialog" max-width="820px" persistent>
      <v-card>
        <v-card-title class="deluxe-manager__dialog-title">
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
                <v-text-field v-model="editedItem.layout" label="Layout" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-combobox
                  v-model="editedItem.cidade"
                  :items="cidades"
                  item-text="name"
                  item-value="id"
                  label="Cidade"
                  outlined
                  dense
                ></v-combobox>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.regiao" label="Regiao" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.estado" label="Estado" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto1" label="Foto 1" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto2" label="Foto 2" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="editedItem.foto3" label="Foto 3" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.txt1_pt" label="Texto PT" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.txt1_ing" label="Texto EN" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.txt1_esp" label="Texto ES" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="editedItem.ativo_deluxe" label="Ativo Deluxe" inset></v-switch>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="editedItem.ativo_blumar" label="Ativo Blumar" inset></v-switch>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="editedItem.ativo_nacional" label="Ativo Nacional" inset></v-switch>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="editedItem.ativo_latino" label="Ativo Latino" inset></v-switch>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="editedItem.ativo_resort" label="Ativo Resort" inset></v-switch>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="editedItem.ativo_lua_de_mel" label="Ativo Lua de Mel" inset></v-switch>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="editedItem.ativo_riolife" label="Ativo Riolife" inset></v-switch>
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
            Tem certeza que deseja excluir o Deluxe
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

const blankItem = () => ({
  pk_deluxe: null,
  nome: '',
  mneu_for: '',
  localiz: '',
  regiao: '',
  cidade: '',
  estado: '',
  foto1: '',
  foto2: '',
  foto3: '',
  mapa: '',
  txt1_pt: '',
  txt1_esp: '',
  txt1_ing: '',
  layout: '',
  ativo_blumar: true,
  ativo_nacional: true,
  ativo_latino: false,
  ativo_resort: false,
  ativo_deluxe: true,
  ativo_lua_de_mel: false,
  ativo_riolife: false
})

export default {
  name: 'DeluxeManager',
  data() {
    return {
      loading: false,
      saving: false,
      items: [],
      cidades: [],
      defaultImage: require('@/assets/default.png'),
      dialog: false,
      dialogDelete: false,
      editedIndex: -1,
      editedItem: blankItem(),
      filters: {
        nome: '',
        ativo: 'all',
        regiao: '',
        cidade: '',
        limit: 200
      },
      statusOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Ativo', value: 'true' },
        { text: 'Inativo', value: 'false' }
      ],
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Foto', value: 'imagens', sortable: false },
        { text: 'Nome', value: 'nome' },
        { text: 'Cidade', value: 'city_name' },
        { text: 'Regiao', value: 'regiao' },
        { text: 'Status', value: 'status', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Deluxe' : 'Editar Deluxe'
    }
  },
  mounted() {
    this.fetchCidades()
    this.fetchDeluxes()
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
    primaryImage(item) {
      if (item.imagens && item.imagens.length) {
        return item.imagens[0].image_url
      }
      if (item.foto1) {
        return `${item.foto1}`
      }
      return this.defaultImage
    },
    isActive(item) {
      return item.is_active === true || item.ativo_deluxe === 't' || item.ativo_deluxe === true
    },
    onImageError(event) {
      event.target.src = this.defaultImage
    },
    clearFilters() {
      this.filters = {
        nome: '',
        ativo: 'all',
        regiao: '',
        cidade: '',
        limit: 200
      }
      this.fetchDeluxes()
    },
    async fetchDeluxes() {
      this.loading = true
      try {
        const params = new URLSearchParams({ request: 'listar_deluxes' })
        if (this.filters.nome) {
          params.set('filtro_nome', this.filters.nome)
        }
        if (this.filters.ativo) {
          params.set('filtro_ativo', this.filters.ativo)
        }
        if (this.filters.regiao) {
          params.set('filtro_regiao', this.filters.regiao)
        }
        if (this.filters.cidade) {
          params.set('cidade', this.filters.cidade)
        }
        if (this.filters.limit) {
          params.set('limit', String(this.filters.limit))
        }
        const response = await fetch(`${API_BASE}deluxe.php?${params.toString()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.items = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchCidades() {
      try {
        const response = await fetch(`${API_BASE}deluxe.php?request=listar_cidades`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.cidades = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar cidades: ${error.message}`, 'error')
      }
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = blankItem()
      this.dialog = true
    },
    openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = {
        pk_deluxe: item.pk_deluxe,
        nome: item.nome || item.name || '',
        mneu_for: item.mneu_for || '',
        localiz: item.localiz || '',
        regiao: item.regiao || '',
        cidade: item.cidade || '',
        estado: item.estado || '',
        foto1: item.foto1 || '',
        foto2: item.foto2 || '',
        foto3: item.foto3 || '',
        mapa: item.mapa || '',
        txt1_pt: item.txt1_pt || '',
        txt1_esp: item.txt1_esp || '',
        txt1_ing: item.txt1_ing || '',
        layout: item.layout || '',
        ativo_blumar: item.ativo_blumar === true || item.ativo_blumar === 't',
        ativo_nacional: item.ativo_nacional === true || item.ativo_nacional === 't',
        ativo_latino: item.ativo_latino === true || item.ativo_latino === 't',
        ativo_resort: item.ativo_resort === true || item.ativo_resort === 't',
        ativo_deluxe: this.isActive(item),
        ativo_lua_de_mel: item.ativo_lua_de_mel === true || item.ativo_lua_de_mel === 't',
        ativo_riolife: item.ativo_riolife === true || item.ativo_riolife === 't'
      }
      this.dialog = true
    },
    openDelete(item) {
      this.editedItem = {
        pk_deluxe: item.pk_deluxe,
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
        const request = isEdit ? 'atualizar_deluxe' : 'criar_deluxe'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}deluxe.php?request=${request}&id=${this.editedItem.pk_deluxe}`
          : `${API_BASE}deluxe.php?request=${request}`

        const basePayload = {
          nome: this.editedItem.nome,
          mneu_for: this.editedItem.mneu_for,
          localiz: this.editedItem.localiz,
          regiao: this.editedItem.regiao,
          cidade: this.editedItem.cidade,
          estado: this.editedItem.estado,
          foto1: this.editedItem.foto1,
          foto2: this.editedItem.foto2,
          foto3: this.editedItem.foto3,
          mapa: this.editedItem.mapa,
          txt1_pt: this.editedItem.txt1_pt,
          txt1_esp: this.editedItem.txt1_esp,
          txt1_ing: this.editedItem.txt1_ing,
          layout: this.editedItem.layout
        }

        const activePayload = {
          blumar: this.editedItem.ativo_blumar,
          nacional: this.editedItem.ativo_nacional,
          latino: this.editedItem.ativo_latino,
          resort: this.editedItem.ativo_resort,
          deluxe: this.editedItem.ativo_deluxe,
          lua_de_mel: this.editedItem.ativo_lua_de_mel,
          riolife: this.editedItem.ativo_riolife
        }

        const payload = isEdit
          ? { ...basePayload, ativos: activePayload }
          : {
              ...basePayload,
              ativo_blumar: this.editedItem.ativo_blumar,
              ativo_nacional: this.editedItem.ativo_nacional,
              ativo_latino: this.editedItem.ativo_latino,
              ativo_resort: this.editedItem.ativo_resort,
              ativo_deluxe: this.editedItem.ativo_deluxe,
              ativo_lua_de_mel: this.editedItem.ativo_lua_de_mel,
              ativo_riolife: this.editedItem.ativo_riolife
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
        this.showMessage(isEdit ? 'Deluxe atualizado.' : 'Deluxe criado.')
        this.dialog = false
        await this.fetchDeluxes()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.pk_deluxe) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}deluxe.php?request=excluir_deluxe&id=${this.editedItem.pk_deluxe}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Deluxe excluido.')
        this.dialogDelete = false
        await this.fetchDeluxes()
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
.deluxe-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.deluxe-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.deluxe-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.deluxe-manager__filters {
  padding: 16px;
}

.deluxe-manager__filters-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.deluxe-manager__dialog-title {
  display: flex;
  align-items: center;
}

.deluxe-manager__avatar img {
  object-fit: cover;
}
</style>
