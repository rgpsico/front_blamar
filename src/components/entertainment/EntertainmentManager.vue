<template>
  <div class="entertainment-manager">
    <div class="entertainment-manager__header">
      <div>
        <h2>Entertainment</h2>
        <p>Cadastro de entretenimentos do incentive.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Entertainment</v-btn>
      <v-btn outlined color="primary" @click="fetchEntertainments">Atualizar</v-btn>
    </div>

    <v-card class="entertainment-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.title"
            label="Titulo"
            dense
            outlined
            clearable
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.category"
            :items="categorias"
            item-text="slug"
            item-value="id"
            label="Categoria"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.type"
            label="Tipo"
            dense
            outlined
            clearable
          ></v-text-field>
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
            clearable
          ></v-select>
        </v-col>
      </v-row>
      <div class="entertainment-manager__filter-actions">
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
        <template slot="item.category_slug" slot-scope="{ item }">
          <v-chip small outlined color="deep-purple">{{ item.category_slug || '-' }}</v-chip>
        </template>
        <template slot="item.is_active" slot-scope="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'grey'" small>
            {{ item.is_active ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.cover_image_url" slot-scope="{ item }">
          <v-avatar v-if="item.cover_image_url" size="40" tile rounded>
            <img :src="item.cover_image_url" :alt="item.title" style="object-fit:cover;" />
          </v-avatar>
          <span v-else class="grey--text">—</span>
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
      <v-card-actions class="entertainment-manager__pagination">
        <v-select
          v-model="pagination.perPage"
          :items="pagination.perPageOptions"
          label="Por pagina"
          dense
          outlined
          hide-details
          class="entertainment-manager__per-page"
          @change="changePerPage"
        ></v-select>
        <v-spacer></v-spacer>
        <v-pagination
          v-model="pagination.page"
          :length="pagination.lastPage"
          total-visible="7"
          @input="fetchEntertainments"
        ></v-pagination>
      </v-card-actions>
    </v-card>

    <!-- Dialog Criar / Editar -->
    <v-dialog v-model="dialog" max-width="980px" persistent>
      <v-card>
        <v-card-title class="entertainment-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>

        <v-card-text>
          <v-form>
            <!-- Informacoes basicas -->
            <div class="entertainment-manager__section-title">Informacoes basicas</div>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.title"
                  label="Titulo *"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.slug"
                  label="Slug"
                  outlined
                  dense
                  hint="Deixe em branco para gerar automaticamente"
                  persistent-hint
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="editedItem.category_id"
                  :items="categorias"
                  item-text="slug"
                  item-value="id"
                  label="Categoria *"
                  dense
                  outlined
                ></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.type"
                  label="Tipo *"
                  outlined
                  dense
                  hint="Ex: show, music, festival"
                  persistent-hint
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.price_range"
                  label="Faixa de preco"
                  outlined
                  dense
                  hint="Ex: $, $$, $$$"
                  persistent-hint
                ></v-text-field>
              </v-col>
            </v-row>

            <!-- Localizacao -->
            <div class="entertainment-manager__section-title mt-3">Localizacao</div>
            <v-row>
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="editedItem.city_id"
                  :items="cidades"
                  item-text="label"
                  item-value="value"
                  label="Cidade *"
                  dense
                  outlined
                  @change="onCityChange"
                ></v-autocomplete>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editedItem.location_id"
                  :items="locations"
                  item-text="name"
                  item-value="id"
                  label="Location"
                  dense
                  outlined
                  clearable
                  :loading="loadingLocations"
                  no-data-text="Selecione uma cidade primeiro"
                ></v-select>
              </v-col>
            </v-row>

            <!-- Descricoes -->
            <div class="entertainment-manager__section-title mt-3">Descricoes</div>
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="editedItem.short_desc"
                  label="Descricao curta"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.description"
                  label="Descricao completa"
                  outlined
                  dense
                  rows="4"
                ></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.personal_note"
                  label="Nota pessoal (interna)"
                  outlined
                  dense
                  rows="2"
                ></v-textarea>
              </v-col>
            </v-row>

            <!-- Imagem de capa -->
            <div class="entertainment-manager__section-title mt-3">Imagem de capa</div>
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="editedItem.cover_image_url"
                  label="URL da imagem de capa"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4" v-if="editedItem.cover_image_url">
                <v-img
                  :src="editedItem.cover_image_url"
                  max-height="80"
                  contain
                  class="rounded"
                ></v-img>
              </v-col>
            </v-row>

            <!-- Galeria de imagens -->
            <div class="entertainment-manager__section-title mt-3">
              Galeria de imagens
              <v-btn x-small outlined color="primary" class="ml-2" @click="addImage">
                <v-icon x-small>mdi-plus</v-icon> Adicionar
              </v-btn>
            </div>
            <v-row v-for="(img, idx) in editedItem.images" :key="idx" align="center">
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="img.url"
                  :label="'URL Imagem ' + (idx + 1)"
                  outlined
                  dense
                  hide-details
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="img.caption"
                  label="Legenda"
                  outlined
                  dense
                  hide-details
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="1">
                <v-text-field
                  v-model.number="img.position"
                  label="Pos."
                  type="number"
                  outlined
                  dense
                  hide-details
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="1">
                <v-btn icon small color="error" @click="removeImage(idx)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </v-col>
            </v-row>

            <!-- Status -->
            <div class="entertainment-manager__section-title mt-4">Status</div>
            <v-row>
              <v-col cols="12" sm="4">
                <v-switch v-model="editedItem.is_active" label="Ativo" inset></v-switch>
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

    <!-- Dialog Delete -->
    <v-dialog v-model="dialogDelete" max-width="420px">
      <v-card>
        <v-card-title>Confirmar exclusao</v-card-title>
        <v-card-text>
          <v-alert type="warning" border="left" colored-border>
            Tem certeza que deseja excluir
            <strong>{{ editedItem.title }}</strong>?
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
  name: 'EntertainmentManager',
  data() {
    return {
      loading: false,
      saving: false,
      loadingLocations: false,
      items: [],
      categorias: [],
      cidades: [],
      locations: [],
      dialog: false,
      dialogDelete: false,
      editedIndex: -1,
      filters: {
        title: '',
        category: '',
        type: '',
        ativo: 'true'
      },
      pagination: {
        page: 1,
        perPage: 30,
        total: 0,
        lastPage: 1,
        perPageOptions: [10, 20, 30, 50, 100]
      },
      editedItem: this.emptyItemFn(),
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Capa', value: 'cover_image_url', sortable: false, width: '60px' },
        { text: 'Titulo', value: 'title' },
        { text: 'Categoria', value: 'category_slug', sortable: false },
        { text: 'Tipo', value: 'type' },
        { text: 'Cidade', value: 'cidade_nome' },
        { text: 'Status', value: 'is_active', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      statusOptions: [
        { text: 'Ativo', value: 'true' },
        { text: 'Inativo', value: 'false' },
        { text: 'Todos', value: 'all' }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Entertainment' : 'Editar Entertainment'
    }
  },
  mounted() {
    this.fetchSupportLists()
    this.fetchEntertainments()
  },
  methods: {
    emptyItemFn() {
      return {
        id: null,
        title: '',
        slug: '',
        category_id: null,
        city_id: null,
        location_id: null,
        type: '',
        short_desc: '',
        description: '',
        cover_image_url: '',
        price_range: '',
        personal_note: '',
        is_active: true,
        images: []
      }
    },
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_entertainment')
      if (this.filters.title) params.append('filtro_title', this.filters.title)
      if (this.filters.category) params.append('filtro_category', this.filters.category)
      if (this.filters.type) params.append('filtro_type', this.filters.type)
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      params.append('page', String(this.pagination.page))
      params.append('per_page', String(this.pagination.perPage))
      return params.toString()
    },
    async fetchEntertainments() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}api_entertainments.php?${this.buildQuery()}`)
        const data = await response.json()
        if (data && Array.isArray(data.data)) {
          this.items = data.data
          const p = data.pagination || {}
          this.pagination.total = p.total || 0
          this.pagination.lastPage = Math.max(1, p.last_page || 1)
          this.pagination.page = p.current_page || this.pagination.page
          this.pagination.perPage = p.per_page || this.pagination.perPage
        } else {
          this.items = []
          this.pagination.total = 0
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
        const [catRes, cidadesRes] = await Promise.all([
          fetch(`${API_BASE}api_entertainments.php?request=listar_categorias`),
          fetch(`${API_BASE}cidades.php?request=listar_cidades`)
        ])
        const [catData, cidadesData] = await Promise.all([
          catRes.json(),
          cidadesRes.json()
        ])
        this.categorias = Array.isArray(catData) ? catData : []
        this.cidades = Array.isArray(cidadesData)
          ? cidadesData.map(c => ({ value: c.value, label: `${c.label_pt} (${c.label_en})` }))
          : []
      } catch (error) {
        this.showMessage(`Erro ao carregar listas: ${error.message}`, 'error')
      }
    },
    async fetchLocations(cityId) {
      if (!cityId) {
        this.locations = []
        return
      }
      this.loadingLocations = true
      try {
        const response = await fetch(
          `${API_BASE}api_entertainments.php?request=listar_locations&city_id=${cityId}`
        )
        const data = await response.json()
        this.locations = Array.isArray(data) ? data : []
      } catch {
        this.locations = []
      } finally {
        this.loadingLocations = false
      }
    },
    onCityChange(cityId) {
      this.editedItem.location_id = null
      this.fetchLocations(cityId)
    },
    applyFilters() {
      this.pagination.page = 1
      this.fetchEntertainments()
    },
    resetFilters() {
      this.filters = { title: '', category: '', type: '', ativo: 'true' }
      this.pagination.page = 1
      this.fetchEntertainments()
    },
    changePerPage() {
      this.pagination.page = 1
      this.fetchEntertainments()
    },
    addImage() {
      this.editedItem.images.push({ url: '', caption: '', position: this.editedItem.images.length })
    },
    removeImage(idx) {
      this.editedItem.images.splice(idx, 1)
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = this.emptyItemFn()
      this.locations = []
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      if (!item || !item.id) return
      this.loading = true
      try {
        const response = await fetch(
          `${API_BASE}api_entertainments.php?request=buscar_entertainment&id=${item.id}`
        )
        const data = await response.json()
        if (data && !data.error) {
          this.editedItem = {
            id: data.id,
            title: data.title || '',
            slug: data.slug || '',
            category_id: data.category_id || null,
            city_id: data.city_id || null,
            location_id: data.location_id || null,
            type: data.type || '',
            short_desc: data.short_desc || '',
            description: data.description || '',
            cover_image_url: data.cover_image_url || '',
            price_range: data.price_range || '',
            personal_note: data.personal_note || '',
            is_active: data.is_active === true,
            images: Array.isArray(data.images) ? data.images.map(img => ({
              url: img.url || '',
              caption: img.caption || '',
              position: img.position || 0
            })) : []
          }
          if (data.city_id) await this.fetchLocations(data.city_id)
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
      this.editedItem = { id: item.id, title: item.title || '' }
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    async save() {
      if (!this.editedItem.title || !this.editedItem.category_id || !this.editedItem.city_id || !this.editedItem.type) {
        this.showMessage('Informe titulo, categoria, cidade e tipo.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1 && this.editedItem.id
        const request = isEdit ? 'atualizar_entertainment' : 'criar_entertainment'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}api_entertainments.php?request=${request}&id=${this.editedItem.id}`
          : `${API_BASE}api_entertainments.php?request=${request}`

        const payload = {
          title: this.editedItem.title,
          slug: this.editedItem.slug || undefined,
          category_id: this.editedItem.category_id,
          city_id: this.editedItem.city_id,
          location_id: this.editedItem.location_id || undefined,
          type: this.editedItem.type,
          short_desc: this.editedItem.short_desc,
          description: this.editedItem.description,
          cover_image_url: this.editedItem.cover_image_url,
          price_range: this.editedItem.price_range,
          personal_note: this.editedItem.personal_note,
          is_active: this.editedItem.is_active,
          images: this.editedItem.images.filter(img => img.url)
        }

        const response = await fetch(url, {
          method,
          headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
          body: JSON.stringify(payload)
        })
        const result = await response.json()
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.showMessage(isEdit ? 'Entertainment atualizado.' : 'Entertainment criado.')
        this.dialog = false
        await this.fetchEntertainments()
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
        const response = await fetch(
          `${API_BASE}api_entertainments.php?request=excluir_entertainment&id=${this.editedItem.id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) throw new Error(result.error)
        this.showMessage('Entertainment excluido.')
        this.dialogDelete = false
        await this.fetchEntertainments()
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
.entertainment-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.entertainment-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.entertainment-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.entertainment-manager__filters {
  padding: 16px;
}

.entertainment-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.entertainment-manager__dialog-title {
  display: flex;
  align-items: center;
}

.entertainment-manager__pagination {
  flex-wrap: wrap;
  gap: 12px;
}

.entertainment-manager__per-page {
  max-width: 160px;
}

.entertainment-manager__section-title {
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
</style>
