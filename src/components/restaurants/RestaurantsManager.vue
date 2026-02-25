<template>
  <div class="restaurants-manager">
    <div class="restaurants-manager__header">
      <div>
        <h2>Restaurantes</h2>
        <p>CRUD completo via API de restaurantes.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Restaurante</v-btn>
      <v-btn outlined color="primary" @click="fetchRestaurants">Atualizar</v-btn>
    </div>

    <v-card class="restaurants-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.nome"
            label="Nome"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.especialidade"
            label="Especialidade"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.classif"
            :items="classificacoes"
            item-text="label"
            item-value="value"
            label="Classificacao"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="3">
          <v-autocomplete
            v-model="filters.cidade"
            :items="cidades"
            item-text="label"
            item-value="value"
            label="Cidade"
            dense
            outlined
            clearable
          ></v-autocomplete>
        </v-col>
        <v-col cols="12" md="1">
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
      <div class="restaurants-manager__filter-actions">
        <v-btn outlined color="primary" @click="applyFilters">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        item-key="id"
        class="elevation-0"
      >
        <template slot="item.classif" slot-scope="{ item }">
          <v-chip small outlined color="primary">
            {{ item.classif || 0 }}&#9733;
          </v-chip>
        </template>
        <template slot="item.cidade" slot-scope="{ item }">
          {{ cityLabel(item.cidade_cod) }}
        </template>
        <template slot="item.ativo" slot-scope="{ item }">
          <v-chip :color="item.ativo ? 'success' : 'grey'" small>
            {{ item.ativo ? 'Ativo' : 'Inativo' }}
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
      <v-divider></v-divider>
      <v-card-actions class="restaurants-manager__pagination">
        <v-select
          v-model="pagination.perPage"
          :items="pagination.perPageOptions"
          label="Por pagina"
          dense
          outlined
          hide-details
          class="restaurants-manager__per-page"
          @change="changePerPage"
        ></v-select>
        <v-spacer></v-spacer>
        <v-pagination
          v-model="pagination.page"
          :length="pagination.lastPage"
          total-visible="7"
          @input="fetchRestaurants"
        ></v-pagination>
      </v-card-actions>
    </v-card>

    <v-dialog v-model="dialog" max-width="980px" persistent>
      <v-card>
        <v-card-title class="restaurants-manager__dialog-title">
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
                <v-text-field v-model="editedItem.especialidade" label="Especialidade" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="editedItem.classif"
                  :items="classificacoes"
                  item-text="label"
                  item-value="value"
                  label="Classificacao"
                  dense
                  outlined
                ></v-select>
              </v-col>
              <v-col cols="12" md="8">
                <v-autocomplete
                  v-model="editedItem.cidade_cod"
                  :items="cidades"
                  item-text="label"
                  item-value="value"
                  label="Cidade"
                  dense
                  outlined
                ></v-autocomplete>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.endereco" label="Endereco" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="editedItem.telefone" label="Telefone" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="editedItem.instagram" label="Instagram" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto1" label="Foto 1" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto2" label="Foto 2" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.mneu_for" label="Menu For" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.cod_serv" label="Cod Serv" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.descritivo"
                  label="Descritivo"
                  outlined
                  dense
                  rows="3"
                ></v-textarea>
              </v-col>
              <v-col cols="12" md="6">
                <v-textarea
                  v-model="editedItem.descritivo_pt"
                  label="Descritivo PT"
                  outlined
                  dense
                  rows="3"
                ></v-textarea>
              </v-col>
              <v-col cols="12" md="6">
                <v-textarea
                  v-model="editedItem.descritivo_esp"
                  label="Descritivo ES"
                  outlined
                  dense
                  rows="3"
                ></v-textarea>
              </v-col>
              <v-col cols="12">
                <div class="restaurants-manager__section-title">Status</div>
                <v-row>
                  <v-col cols="12" sm="6" md="3">
                    <v-switch v-model="editedItem.ativo" label="Ativo" inset></v-switch>
                  </v-col>
                  <v-col cols="12" sm="6" md="3">
                    <v-switch v-model="editedItem.ativo_riolife" label="Ativo Riolife" inset></v-switch>
                  </v-col>
                  <v-col cols="12" sm="6" md="3">
                    <v-switch v-model="editedItem.favorito_riolife" label="Favorito Riolife" inset></v-switch>
                  </v-col>
                  <v-col cols="12" sm="6" md="3">
                    <v-switch v-model="editedItem.welkome" label="Welkome" inset></v-switch>
                  </v-col>
                </v-row>
              </v-col>
              <v-col cols="12">
                <div class="restaurants-manager__section-title">Selos</div>
                <v-row>
                  <v-col cols="12" sm="6" md="3" v-for="(label, key) in seloLabels" :key="key">
                    <v-checkbox v-model="editedItem.selos[key]" :label="label" dense></v-checkbox>
                  </v-col>
                </v-row>
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
            Tem certeza que deseja excluir o restaurante
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

export default {
  name: 'RestaurantsManager',
  data() {
    return {
      loading: false,
      saving: false,
      items: [],
      cidades: [],
      classificacoes: [],
      dialog: false,
      dialogDelete: false,
      editedIndex: -1,
      filters: {
        nome: '',
        especialidade: '',
        classif: '',
        cidade: '',
        ativo: ''
      },
      pagination: {
        page: 1,
        perPage: 30,
        total: 0,
        lastPage: 1,
        perPageOptions: [10, 20, 30, 50, 100]
      },
      editedItem: {
        id: null,
        nome: '',
        especialidade: '',
        descritivo: '',
        descritivo_pt: '',
        descritivo_esp: '',
        classif: '',
        cidade_cod: '',
        endereco: '',
        telefone: '',
        instagram: '',
        foto1: '',
        foto2: '',
        mneu_for: '',
        cod_serv: '',
        ativo: true,
        ativo_riolife: false,
        favorito_riolife: false,
        welkome: false,
        selos: {
          fav: false,
          wview: false,
          boteco: false,
          budget: false,
          highend: false,
          livemusic: false,
          romantic: false,
          selfservice: false,
          trendy: false,
          veggie: false,
          michelin: false
        }
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Nome', value: 'nome' },
        { text: 'Especialidade', value: 'especialidade' },
        { text: 'Classificacao', value: 'classif' },
        { text: 'Cidade', value: 'cidade' },
        { text: 'Status', value: 'ativo', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      statusOptions: [
        { text: 'Ativo', value: 'true' },
        { text: 'Inativo', value: 'false' },
        { text: 'Todos', value: 'all' }
      ],
      seloLabels: {
        fav: 'Favorito',
        wview: 'WView',
        boteco: 'Boteco',
        budget: 'Budget',
        highend: 'Highend',
        livemusic: 'Live Music',
        romantic: 'Romantic',
        selfservice: 'Self-service',
        trendy: 'Trendy',
        veggie: 'Veggie',
        michelin: 'Michelin'
      }
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Restaurante' : 'Editar Restaurante'
    }
  },
  mounted() {
    this.fetchSupportLists()
    this.fetchRestaurants()
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
    cityLabel(code) {
      if (!code) return ''
      const found = this.cidades.find(item => item.value === code)
      return found ? found.label : code
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_restaurantes')
      if (this.filters.nome) params.append('filtro_nome', this.filters.nome)
      if (this.filters.cidade) params.append('filtro_cidade', this.filters.cidade)
      if (this.filters.especialidade) params.append('filtro_especialidade', this.filters.especialidade)
      if (this.filters.classif) params.append('filtro_classif', this.filters.classif)
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      params.append('page', String(this.pagination.page))
      params.append('per_page', String(this.pagination.perPage))
      return params.toString()
    },
    async fetchRestaurants() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}api_restaurante.php?${this.buildQuery()}`)
        const data = await response.json()
        if (data && Array.isArray(data.data)) {
          this.items = data.data
          const pagination = data.pagination || {}
          this.pagination.total = pagination.total || 0
          this.pagination.lastPage = Math.max(1, pagination.last_page || 1)
          this.pagination.page = pagination.current_page || this.pagination.page
          this.pagination.perPage = pagination.per_page || this.pagination.perPage
        } else {
          this.items = Array.isArray(data) ? data : []
          this.pagination.total = this.items.length
          this.pagination.lastPage = 1
        }
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchSupportLists() {
      try {
        const [classifRes, cidadesRes] = await Promise.all([
          fetch(`${API_BASE}api_restaurante.php?request=listar_classificacoes`),
          fetch(`${API_BASE}api_restaurante.php?request=listar_cidades`)
        ])
        const [classifData, cidadesData] = await Promise.all([
          classifRes.json(),
          cidadesRes.json()
        ])
        this.classificacoes = Array.isArray(classifData) ? classifData : []
        this.cidades = Array.isArray(cidadesData)
          ? cidadesData.map(city => ({
              value: city.value,
              label: `${city.label_pt} (${city.label_en})`
            }))
          : []
      } catch (error) {
        this.showMessage(`Erro ao carregar listas: ${error.message}`, 'error')
      }
    },
    resetFilters() {
      this.filters = {
        nome: '',
        especialidade: '',
        classif: '',
        cidade: '',
        ativo: ''
      }
      this.pagination.page = 1
      this.fetchRestaurants()
    },
    applyFilters() {
      this.pagination.page = 1
      this.fetchRestaurants()
    },
    changePerPage() {
      this.pagination.page = 1
      this.fetchRestaurants()
    },
    emptyItem() {
      return {
        id: null,
        nome: '',
        especialidade: '',
        descritivo: '',
        descritivo_pt: '',
        descritivo_esp: '',
        classif: '',
        cidade_cod: '',
        endereco: '',
        telefone: '',
        instagram: '',
        foto1: '',
        foto2: '',
        mneu_for: '',
        cod_serv: '',
        ativo: true,
        ativo_riolife: false,
        favorito_riolife: false,
        welkome: false,
        selos: {
          fav: false,
          wview: false,
          boteco: false,
          budget: false,
          highend: false,
          livemusic: false,
          romantic: false,
          selfservice: false,
          trendy: false,
          veggie: false,
          michelin: false
        }
      }
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = this.emptyItem()
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      if (!item || !item.id) {
        return
      }
      this.loading = true
      try {
        const response = await fetch(
          `${API_BASE}api_restaurante.php?request=buscar_restaurante&id=${item.id}`
        )
        const data = await response.json()
        if (data && !data.error) {
          this.editedItem = {
            id: data.id,
            nome: data.nome || '',
            especialidade: data.especialidade || '',
            descritivo: data.descritivo || '',
            descritivo_pt: data.descritivo_pt || '',
            descritivo_esp: data.descritivo_esp || '',
            classif: data.classif || '',
            cidade_cod: data.cidade_cod || '',
            endereco: data.endereco || '',
            telefone: data.telefone || '',
            instagram: data.instagram || '',
            foto1: data.foto1 || '',
            foto2: data.foto2 || '',
            mneu_for: data.mneu_for || '',
            cod_serv: data.cod_serv || '',
            ativo: data.ativo === true,
            ativo_riolife: data.ativo_riolife === true,
            favorito_riolife: data.favorito_riolife === true,
            welkome: data.welkome === true,
            selos: {
              fav: data.selos?.fav === true,
              wview: data.selos?.wview === true,
              boteco: data.selos?.boteco === true,
              budget: data.selos?.budget === true,
              highend: data.selos?.highend === true,
              livemusic: data.selos?.livemusic === true,
              romantic: data.selos?.romantic === true,
              selfservice: data.selos?.selfservice === true,
              trendy: data.selos?.trendy === true,
              veggie: data.selos?.veggie === true,
              michelin: data.selos?.michelin === true
            }
          }
          this.dialog = true
        } else {
          throw new Error(data.error || 'Erro ao carregar detalhes')
        }
      } catch (error) {
        this.showMessage(`Erro ao abrir: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    openDelete(item) {
      this.editedItem = {
        id: item.id,
        nome: item.nome || ''
      }
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    async save() {
      if (!this.editedItem.nome || !this.editedItem.classif || !this.editedItem.cidade_cod) {
        this.showMessage('Informe nome, classificacao e cidade.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1 && this.editedItem.id
        const request = isEdit ? 'atualizar_restaurante' : 'criar_restaurante'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}api_restaurante.php?request=${request}&id=${this.editedItem.id}`
          : `${API_BASE}api_restaurante.php?request=${request}`

        const payload = {
          nome: this.editedItem.nome,
          especialidade: this.editedItem.especialidade,
          descritivo: this.editedItem.descritivo,
          descritivo_pt: this.editedItem.descritivo_pt,
          descritivo_esp: this.editedItem.descritivo_esp,
          classif: this.editedItem.classif,
          cidade_cod: this.editedItem.cidade_cod,
          fk_cod_cidade: this.editedItem.cidade_cod,
          address: this.editedItem.endereco,
          tel: this.editedItem.telefone,
          url_insta: this.editedItem.instagram,
          foto1: this.editedItem.foto1,
          foto2: this.editedItem.foto2,
          mneu_for: this.editedItem.mneu_for,
          cod_serv: this.editedItem.cod_serv,
          ativo: this.editedItem.ativo,
          ativo_riolife: this.editedItem.ativo_riolife,
          fav_riolife: this.editedItem.favorito_riolife,
          welkome: this.editedItem.welkome,
          selos: this.editedItem.selos
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
        this.showMessage(isEdit ? 'Restaurante atualizado.' : 'Restaurante criado.')
        this.dialog = false
        await this.fetchRestaurants()
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
          `${API_BASE}api_restaurante.php?request=excluir_restaurante&id=${this.editedItem.id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Restaurante excluido.')
        this.dialogDelete = false
        await this.fetchRestaurants()
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
.restaurants-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.restaurants-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.restaurants-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.restaurants-manager__filters {
  padding: 16px;
}

.restaurants-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.restaurants-manager__dialog-title {
  display: flex;
  align-items: center;
}

.restaurants-manager__pagination {
  flex-wrap: wrap;
  gap: 12px;
}

.restaurants-manager__per-page {
  max-width: 160px;
}

.restaurants-manager__section-title {
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  color: #475569;
}
</style>
