<template>
  <div class="venues-manager">
    <div class="venues-manager__header">
      <div>
        <h2>Venues</h2>
        <p>CRUD completo de venues usando a API de venues.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Venue</v-btn>
      <v-btn outlined color="primary" @click="fetchVenues">Atualizar</v-btn>
    </div>

    <v-card class="venues-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field v-model="filters.nome" label="Buscar por nome" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field v-model="filters.cidade" label="Cidade" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo"
            :items="activeFilterOptions"
            label="Ativo"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model="filters.data"
            label="Data cadastro"
            type="date"
            dense
            outlined
          ></v-text-field>
        </v-col>
      </v-row>
      <div class="venues-manager__filter-actions">
        <v-btn outlined color="primary" @click="applyFilters">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6">
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        item-key="cod_venues"
        class="elevation-0"
      >
        <template slot="item.is_active" slot-scope="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'grey'" small>
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

    <v-dialog v-model="dialog" max-width="980px" persistent>
      <v-card>
        <v-card-title class="venues-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-progress-linear
            v-if="loadingDetail"
            indeterminate
            color="primary"
            class="mb-4"
          ></v-progress-linear>
          <v-form>
            <v-tabs v-model="activeTab" background-color="transparent" grow>
              <v-tab>Dados</v-tab>
              <v-tab>Localizacao</v-tab>
              <v-tab>Imagens</v-tab>
            </v-tabs>

            <v-tabs-items v-model="activeTab" class="mt-4">
              <v-tab-item>
                <v-row>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="editedItem.name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="editedItem.city_id"
                      :items="cityOptions"
                      label="Cidade"
                      outlined
                      dense
                      item-text="name"
                      item-value="id"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.short_description"
                      label="Descricao curta"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="editedItem.description"
                      label="Descricao"
                      outlined
                      rows="3"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="editedItem.price_range"
                      label="Faixa de preco"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="editedItem.capacity_min"
                      label="Capacidade minima"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="editedItem.capacity_max"
                      label="Capacidade maxima"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="editedItem.insight"
                      label="Insight"
                      outlined
                      rows="2"
                    ></v-textarea>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <v-row>
                  <v-col cols="12" md="8">
                    <v-text-field
                      v-model="editedItem.address_line"
                      label="Endereco"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.state" label="UF" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.country" label="Pais" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="editedItem.latitude"
                      label="Latitude"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="editedItem.longitude"
                      label="Longitude"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="venues-manager__tab-head">
                  <div>Imagens</div>
                  <v-btn small outlined color="primary" @click="addImage">Adicionar</v-btn>
                </div>
                <v-row v-for="(img, index) in editedItem.images" :key="`img-${index}`">
                  <v-col cols="12" md="6">
                    <v-text-field v-model="img.image_url" label="URL" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="img.alt_text" label="Alt text" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-switch v-model="img.is_primary" label="Principal" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeImage(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-img
                      v-if="img.image_url"
                      :src="img.image_url"
                      height="120"
                      cover
                    ></v-img>
                  </v-col>
                </v-row>
              </v-tab-item>
            </v-tabs-items>
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
            Tem certeza que deseja excluir o venue
            <strong>{{ editedItem.name || editedItem.cod_venues }}</strong>?
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

const blankVenue = () => ({
  cod_venues: null,
  name: '',
  short_description: '',
  description: '',
  city_id: null,
  city_name: '',
  is_active: true,
  capacity_min: null,
  capacity_max: null,
  price_range: '',
  address_line: '',
  state: '',
  country: '',
  latitude: null,
  longitude: null,
  insight: '',
  images: []
})

export default {
  name: 'VenuesManager',
  data() {
    return {
      loading: false,
      loadingDetail: false,
      saving: false,
      dialog: false,
      dialogDelete: false,
      activeTab: 0,
      items: [],
      cities: [],
      filters: {
        nome: '',
        cidade: '',
        ativo: 'all',
        data: ''
      },
      editedIndex: -1,
      editedItem: blankVenue(),
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'ID', value: 'cod_venues' },
        { text: 'Nome', value: 'name' },
        { text: 'Cidade', value: 'city_name' },
        { text: 'Ativo', value: 'is_active', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      activeFilterOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Sim', value: 'true' },
        { text: 'Nao', value: 'false' }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Venue' : 'Editar Venue'
    },
    cityOptions() {
      return Array.isArray(this.cities) ? this.cities : []
    }
  },
  mounted() {
    this.fetchCities()
    this.fetchVenues()
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
    normalizeVenue(item) {
      if (!item) return blankVenue()
      return {
        cod_venues: item.cod_venues || item.id || null,
        name: item.name || item.nome || '',
        short_description: item.short_description || item.especialidade || '',
        description: item.description || item.descritivo_pt || '',
        city_id: item.fk_cod_cidade || item.city_id || null,
        city_name: item.city_name || item.city || '',
        is_active: item.is_active ?? (item.ativo === 't'),
        capacity_min: item.capacity_min ?? null,
        capacity_max: item.capacity_max ?? item.capacity ?? null,
        price_range: item.price_range || '',
        address_line: item.address_line || item.address || '',
        state: item.state || '',
        country: item.country || '',
        latitude: item.latitude ?? null,
        longitude: item.longitude ?? null,
        insight: item.insight || item.insight_pt || '',
        images: Array.isArray(item.imagens)
          ? item.imagens.map((img) => ({
              image_url: img.image_url || '',
              is_primary: !!img.is_primary,
              alt_text: img.alt_text || ''
            }))
          : Array.isArray(item.images)
          ? item.images
          : []
      }
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_venues')
      if (this.filters.nome) params.append('filtro_nome', this.filters.nome)
      if (this.filters.cidade) params.append('cidade', this.filters.cidade)
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      if (this.filters.data) params.append('filtro_data', this.filters.data)
      params.append('limit', '200')
      return params.toString()
    },
    async fetchVenues() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}venues.php?${this.buildQuery()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        const list = Array.isArray(data) ? data : []
        this.items = list.map(this.normalizeVenue)
      } catch (error) {
        this.showMessage(`Erro ao carregar venues: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchCities() {
      try {
        const response = await fetch(`${API_BASE}venues.php?request=listar_cidades`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.cities = Array.isArray(data) ? data : []
      } catch (error) {
        this.cities = []
      }
    },
    applyFilters() {
      this.fetchVenues()
    },
    resetFilters() {
      this.filters = {
        nome: '',
        cidade: '',
        ativo: 'all',
        data: ''
      }
      this.fetchVenues()
    },
    addImage() {
      this.editedItem.images.push({ image_url: '', is_primary: false, alt_text: '' })
    },
    removeImage(index) {
      this.editedItem.images.splice(index, 1)
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = blankVenue()
      this.activeTab = 0
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.activeTab = 0
      this.dialog = true
      this.loadingDetail = true
      try {
        const response = await fetch(`${API_BASE}venues.php?request=buscar_venue&id=${item.cod_venues}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.editedItem = this.normalizeVenue(data)
      } catch (error) {
        this.showMessage(`Erro ao carregar venue: ${error.message}`, 'error')
      } finally {
        this.loadingDetail = false
      }
    },
    closeDialog() {
      this.dialog = false
      this.editedItem = blankVenue()
    },
    openDelete(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = { ...item }
      this.dialogDelete = true
    },
    async confirmDelete() {
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}venues.php?request=excluir_venue&id=${this.editedItem.cod_venues}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const data = await response.json()
        if (data && data.error) throw new Error(data.error)
        this.showMessage('Venue excluido.')
        this.dialogDelete = false
        this.fetchVenues()
      } catch (error) {
        this.showMessage(`Erro ao excluir: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async save() {
      if (!this.editedItem.name) {
        this.showMessage('Informe o nome do venue.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'atualizar_venue' : 'criar_venue'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}venues.php?request=${request}&id=${this.editedItem.cod_venues}`
          : `${API_BASE}venues.php?request=${request}`

        const payload = {
          name: this.editedItem.name,
          short_description: this.editedItem.short_description,
          description: this.editedItem.description,
          city: this.editedItem.city_id,
          is_active: this.editedItem.is_active,
          capacity_min: this.editedItem.capacity_min,
          capacity_max: this.editedItem.capacity_max,
          price_range: this.editedItem.price_range,
          address_line: this.editedItem.address_line,
          state: this.editedItem.state,
          country: this.editedItem.country,
          latitude: this.editedItem.latitude,
          longitude: this.editedItem.longitude,
          insight: this.editedItem.insight,
          images: this.editedItem.images
        }

        const response = await fetch(url, {
          method,
          headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
          body: JSON.stringify(payload)
        })
        const data = await response.json()
        if (data && data.error) throw new Error(data.error)
        if (data && data.success === false) throw new Error(data.message || 'Erro ao salvar')
        this.showMessage('Venue salvo.')
        this.dialog = false
        this.fetchVenues()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.venues-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.venues-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.venues-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.venues-manager__filters {
  padding: 16px;
  margin-bottom: 16px;
}

.venues-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.venues-manager__dialog-title {
  display: flex;
  align-items: center;
}

.venues-manager__tab-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
</style>
