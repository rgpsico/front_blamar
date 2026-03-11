<template>
  <div class="venues-manager">
    <div class="venues-manager__header">
      <div>
        <h2>{{ managerTitleText }}</h2>
        <p>{{ managerDescriptionText }}</p>
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
              <v-tab>Banners</v-tab>
              <v-tab>Dados</v-tab>
              <v-tab>Localizacao</v-tab>
              <v-tab>Galeria</v-tab>
            </v-tabs>

            <v-tabs-items v-model="activeTab" class="mt-4">
              <v-tab-item>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.banner_main"
                      label="Banner principal (URL)"
                      outlined
                      dense
                    ></v-text-field>
                    <v-img
                      v-if="editedItem.banner_main"
                      :src="previewImageUrl(editedItem.banner_main)"
                      height="120"
                      cover
                    ></v-img>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.banner_2"
                      label="Banner 2 (URL)"
                      outlined
                      dense
                    ></v-text-field>
                    <v-img
                      v-if="editedItem.banner_2"
                      :src="previewImageUrl(editedItem.banner_2)"
                      height="120"
                      cover
                    ></v-img>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.banner_3"
                      label="Banner 3 (URL)"
                      outlined
                      dense
                    ></v-text-field>
                    <v-img
                      v-if="editedItem.banner_3"
                      :src="previewImageUrl(editedItem.banner_3)"
                      height="120"
                      cover
                    ></v-img>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.banner_4"
                      label="Banner 4 (URL)"
                      outlined
                      dense
                    ></v-text-field>
                    <v-img
                      v-if="editedItem.banner_4"
                      :src="previewImageUrl(editedItem.banner_4)"
                      height="120"
                      cover
                    ></v-img>
                  </v-col>
                  <v-col cols="12">
                    <v-divider class="my-2"></v-divider>
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field
                      v-model="editedItem.planta_img"
                      label="planta_img (URL)"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-img
                      v-if="editedItem.planta_img"
                      :src="previewImageUrl(editedItem.planta_img)"
                      height="120"
                      contain
                    ></v-img>
                  </v-col>
                </v-row>
              </v-tab-item>

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
                <v-row>
                  <v-col cols="12" class="d-flex align-center justify-space-between">
                    <span>Imagens da galeria</span>
                    <v-btn small outlined color="primary" @click="addGalleryImage">Adicionar imagem</v-btn>
                  </v-col>
                </v-row>

                <v-row
                  v-for="(img, index) in editedItem.gallery_images"
                  :key="`gallery-${index}`"
                >
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="img.image_url"
                      label="URL da galeria"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="img.alt_text"
                      label="Descricao (alt)"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2" class="d-flex align-center justify-end">
                    <v-btn icon color="error" @click="removeGalleryImage(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-img
                      v-if="img.image_url"
                      :src="previewImageUrl(img.image_url)"
                      height="120"
                      cover
                    ></v-img>
                  </v-col>
                </v-row>

                <v-row v-if="!editedItem.gallery_images.length">
                  <v-col cols="12">
                    <v-alert type="info" dense text>
                      Nenhuma imagem na galeria. Clique em "Adicionar imagem".
                    </v-alert>
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
  planta_img: '',
  banner_main: '',
  banner_2: '',
  banner_3: '',
  banner_4: '',
  gallery_images: []
})

export default {
  name: 'VenuesManager',
  props: {
    apiFile: {
      type: String,
      default: 'venues.php'
    },
    managerTitle: {
      type: String,
      default: 'Venues'
    },
    managerDescription: {
      type: String,
      default: 'CRUD completo de venues usando a API de venues.'
    }
  },
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
    managerTitleText() {
      return this.managerTitle || 'Venues'
    },
    managerDescriptionText() {
      return this.managerDescription || 'CRUD completo de venues usando a API de venues.'
    },
    isIncentiveApi() {
      return this.apiFile === 'api_venues.php'
    },
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
      const apiImages = Array.isArray(item.imagens)
        ? item.imagens
        : Array.isArray(item.images)
        ? item.images
        : []

      const getImageUrl = (index) => {
        if (!apiImages[index]) return ''
        return this.sanitizeImageUrl(apiImages[index].image_url || apiImages[index].url || '')
      }

      const location = item.location || {}

      return {
        cod_venues: item.cod_venues || item.venue_id || item.id || null,
        name: item.name || item.nome || '',
        short_description: item.short_description || item.especialidade || '',
        description: item.description || item.descritivo_pt || '',
        city_id: item.fk_cod_cidade || item.city_id || null,
        city_name: item.city_name || item.city || location.city || '',
        is_active: item.is_active ?? (item.ativo === true || item.ativo === 't'),
        capacity_min: item.capacity_min ?? null,
        capacity_max: item.capacity_max ?? item.capacity ?? null,
        price_range: item.price_range || '',
        address_line: item.address_line || item.address || location.address_line || '',
        state: item.state || location.state || '',
        country: item.country || location.country || '',
        latitude: item.latitude ?? location.latitude ?? null,
        longitude: item.longitude ?? location.longitude ?? null,
        insight: item.insight || item.insight_pt || '',
        planta_img: this.sanitizeImageUrl(item.planta_img || item.floor_plan_image || ''),
        banner_main: getImageUrl(0),
        banner_2: getImageUrl(1),
        banner_3: getImageUrl(2),
        banner_4: getImageUrl(3),
        gallery_images: apiImages.slice(4).map((img) => ({
          image_url: this.sanitizeImageUrl(img.image_url || img.url || ''),
          alt_text: img.alt_text || ''
        }))
      }
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_venues')
      if (this.filters.nome) params.append(this.isIncentiveApi ? 'nome' : 'filtro_nome', this.filters.nome)
      if (this.filters.cidade) params.append('cidade', this.filters.cidade)
      if (this.filters.ativo) params.append(this.isIncentiveApi ? 'ativo' : 'filtro_ativo', this.filters.ativo)
      if (!this.isIncentiveApi && this.filters.data) params.append('filtro_data', this.filters.data)
      params.append('limit', '200')
      return params.toString()
    },
    async fetchVenues() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}${this.apiFile}?${this.buildQuery()}`, {
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
      const mapCity = (city) => ({
        id: city.id ?? city.cod_cid ?? city.cidade_cod ?? city.city_id ?? null,
        name: city.name || city.nome_en || city.nome_pt || city.nome_cid || city.city || ''
      })
      const sortCities = (list) =>
        list.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'pt-BR', { sensitivity: 'base' }))

      try {
        const response = await fetch(`${API_BASE}cidades.php?request=listar_cidades&limit=500`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.cities = Array.isArray(data) ? sortCities(data.map(mapCity).filter((c) => c.id && c.name)) : []
      } catch (error) {
        // fallback para APIs legadas de venues
        try {
          const response = await fetch(`${API_BASE}${this.apiFile}?request=listar_cidades`, {
            headers: this.authHeaders()
          })
          const data = await response.json()
          this.cities = Array.isArray(data) ? sortCities(data.map(mapCity).filter((c) => c.id && c.name)) : []
        } catch (fallbackError) {
          this.cities = []
        }
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
    previewImageUrl(url) {
      const raw = this.sanitizeImageUrl(url)
      if (!raw) return ''
      if (/^https?:\/\//i.test(raw) || raw.startsWith('//')) return raw
      return `https://www.blumar.com.br/${raw.replace(/^\/+/, '')}`
    },
    sanitizeImageUrl(url) {
      let raw = (url || '').trim()
      if (!raw) return ''
      const idxHttps = raw.toLowerCase().indexOf('https://', 8)
      const idxHttp = raw.toLowerCase().indexOf('http://', 8)
      const idxCandidates = [idxHttps, idxHttp].filter((v) => v > 0)
      if (idxCandidates.length) {
        raw = raw.slice(Math.min(...idxCandidates))
      }
      return raw
    },
    addGalleryImage() {
      this.editedItem.gallery_images.push({ image_url: '', alt_text: '' })
    },
    removeGalleryImage(index) {
      this.editedItem.gallery_images.splice(index, 1)
    },
    buildImagesPayload() {
      const banners = [
        { image_url: this.sanitizeImageUrl(this.editedItem.banner_main), alt_text: 'Banner principal' },
        { image_url: this.sanitizeImageUrl(this.editedItem.banner_2), alt_text: 'Banner 2' },
        { image_url: this.sanitizeImageUrl(this.editedItem.banner_3), alt_text: 'Banner 3' },
        { image_url: this.sanitizeImageUrl(this.editedItem.banner_4), alt_text: 'Banner 4' }
      ].filter((img) => img.image_url !== '')

      const gallery = (this.editedItem.gallery_images || [])
        .map((img) => ({
          image_url: this.sanitizeImageUrl(img.image_url),
          alt_text: (img.alt_text || '').trim() || 'Galeria'
        }))
        .filter((img) => img.image_url !== '')

      const combined = [...banners, ...gallery]
      return combined.map((img, idx) => ({
        image_url: img.image_url,
        alt_text: img.alt_text,
        is_primary: idx === 0
      }))
    },
    toIntOrNull(value) {
      if (value === null || value === undefined || value === '') return null
      const n = Number(value)
      return Number.isFinite(n) ? Math.trunc(n) : null
    },
    toNumberOrNull(value) {
      if (value === null || value === undefined || value === '') return null
      const n = Number(value)
      return Number.isFinite(n) ? n : null
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
        const requestName = this.isIncentiveApi ? 'obter_venue' : 'buscar_venue'
        const response = await fetch(`${API_BASE}${this.apiFile}?request=${requestName}&id=${item.cod_venues}`, {
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
          `${API_BASE}${this.apiFile}?request=excluir_venue&id=${this.editedItem.cod_venues}`,
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
          ? `${API_BASE}${this.apiFile}?request=${request}&id=${this.editedItem.cod_venues}`
          : `${API_BASE}${this.apiFile}?request=${request}`

        const payload = this.isIncentiveApi
          ? {
              nome: this.editedItem.name,
              especialidade: this.editedItem.short_description,
              ativo: this.editedItem.is_active,
              fk_cod_cidade: this.toIntOrNull(this.editedItem.city_id),
              price_range: this.editedItem.price_range,
              capacity_min: this.toIntOrNull(this.editedItem.capacity_min),
              capacity_max: this.toIntOrNull(this.editedItem.capacity_max),
              product_link_url: '',
              location: {
                address_line: this.editedItem.address_line,
                city: this.editedItem.city_name,
                state: this.editedItem.state,
                country: this.editedItem.country,
                latitude: this.toNumberOrNull(this.editedItem.latitude),
                longitude: this.toNumberOrNull(this.editedItem.longitude)
              },
              images: this.buildImagesPayload().map((img, idx) => ({
                image_url: img.image_url,
                ordem: idx + 1,
                tipo: idx < 4 ? 'banner' : 'gallery'
              }))
            }
          : {
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
              planta_img: this.editedItem.planta_img,
              floor_plan_image: this.editedItem.planta_img,
              images: this.buildImagesPayload()
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

</style>
