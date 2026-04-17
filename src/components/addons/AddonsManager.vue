<template>
  <div class="addons-manager">
    <div class="addons-manager__header">
      <div>
        <h2>Add-ons</h2>
        <p>Cadastro completo de add-ons do Incentive usando a API api_addons.php.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Add-on</v-btn>
      <v-btn outlined color="primary" @click="fetchAddons">Atualizar</v-btn>
    </div>

    <v-card class="addons-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field v-model="filters.nome" label="Buscar por nome" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="4">
          <v-select
            v-model="filters.cidade"
            :items="citySelectItems"
            label="Cidade"
            dense
            outlined
            clearable
            item-text="text"
            item-value="value"
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select v-model="filters.ativo" :items="booleanFilterOptions" label="Ativo" dense outlined clearable></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select v-model="filters.classic" :items="triStateOptions" label="Classic" dense outlined clearable></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select v-model="filters.favourite" :items="triStateOptions" label="Favourite" dense outlined clearable></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select v-model="filters.outOfBox" :items="triStateOptions" label="Out Box" dense outlined clearable></v-select>
        </v-col>
      </v-row>
      <div class="addons-manager__filter-actions">
        <v-btn outlined color="primary" @click="applyFilters">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6">
      <v-data-table :headers="headers" :items="items" :loading="loading" item-key="id" class="elevation-0">
        <template slot="item.foto_capa_url" slot-scope="{ item }">
          <v-avatar v-if="item.foto_capa_url" size="48" tile>
            <v-img :src="item.foto_capa_url" :alt="item.nome || 'Capa'"></v-img>
          </v-avatar>
          <span v-else>-</span>
        </template>
        <template slot="item.price_range_label" slot-scope="{ item }">
          <span>{{ item.price_range_label || '-' }}</span>
        </template>
        <template slot="item.tags" slot-scope="{ item }">
          <div class="addons-manager__chips">
            <v-chip v-if="item.is_classic" x-small color="blue lighten-4" text-color="blue darken-3">Classic</v-chip>
            <v-chip v-if="item.is_favourite" x-small color="amber lighten-4" text-color="amber darken-4">Favourite</v-chip>
            <v-chip v-if="item.is_out_of_box" x-small color="deep-purple lighten-5" text-color="deep-purple darken-2">Out of box</v-chip>
            <span v-if="!item.is_classic && !item.is_favourite && !item.is_out_of_box">-</span>
          </div>
        </template>
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

    <v-dialog v-model="dialog" max-width="1100px" persistent>
      <v-card>
        <v-card-title class="addons-manager__dialog-title">
          <div>
            <div>{{ dialogTitle }}</div>
            <div class="addons-manager__dialog-subtitle">{{ editedItem.slug || 'Preencha os dados do add-on' }}</div>
          </div>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-progress-linear v-if="loadingDetail" indeterminate color="primary" class="mb-4"></v-progress-linear>
          <v-form>
            <v-tabs v-model="activeTab" background-color="transparent" grow>
              <v-tab>Dados</v-tab>
              <v-tab>Localizacao</v-tab>
              <v-tab>Fotos</v-tab>
            </v-tabs>

            <v-tabs-items v-model="activeTab" class="mt-4">
              <v-tab-item>
                <v-row>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="editedItem.nome" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.slug" label="Slug" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-autocomplete
                      v-model="editedItem.cidade_id"
                      :items="citySelectItems"
                      label="Cidade"
                      outlined
                      dense
                      item-text="text"
                      item-value="value"
                      clearable
                    ></v-autocomplete>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model.number="editedItem.price_range" label="Price range" type="number" min="1" max="5" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-switch v-model="editedItem.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-switch v-model="editedItem.is_classic" label="Classic" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.is_favourite" label="Favourite" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.is_out_of_box" label="Out of box" inset></v-switch>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="editedItem.descricao_curta" label="Descricao curta" outlined rows="2"></v-textarea>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="editedItem.descricao_longa" label="Descricao longa" outlined rows="5"></v-textarea>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="editedItem.nota_equipe" label="Nota da equipe" outlined rows="3"></v-textarea>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <v-row>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.latitude" label="Latitude" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.longitude" label="Longitude" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="editedItem.mapa_google" label="Google Maps embed URL" outlined rows="3"></v-textarea>
                  </v-col>
                  <v-col cols="12" v-if="mapPreviewUrl">
                    <v-card outlined>
                      <iframe
                        :src="mapPreviewUrl"
                        width="100%"
                        height="280"
                        style="border:0; display:block;"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                      ></iframe>
                    </v-card>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="addons-manager__tab-head">
                  <div>Fotos do add-on</div>
                  <v-btn small outlined color="primary" @click="addPhoto">Adicionar foto</v-btn>
                </div>
                <v-row v-for="(foto, index) in editedItem.fotos" :key="foto.id || `new-photo-${index}`" class="addons-manager__photo-row">
                  <v-col cols="12" md="6">
                    <v-text-field v-model="foto.url" label="URL ou nome do arquivo" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model.number="foto.ordem" label="Ordem" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2" class="d-flex align-center">
                    <v-switch :input-value="foto.is_capa" label="Capa" inset @change="setCoverPhoto(index, $event)"></v-switch>
                  </v-col>
                  <v-col cols="12" md="2" class="d-flex align-center justify-end">
                    <v-btn icon color="error" @click="removePhoto(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-img v-if="previewPhotoUrl(foto.url)" :src="previewPhotoUrl(foto.url)" height="120" cover class="addons-manager__photo-preview"></v-img>
                  </v-col>
                </v-row>
                <v-alert v-if="!editedItem.fotos.length" type="info" dense text>
                  Nenhuma foto cadastrada.
                </v-alert>
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
            Tem certeza que deseja excluir o add-on
            <strong>{{ editedItem.nome || editedItem.id }}</strong>?
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
const IMAGE_BASE = 'http://www.blumar.com.br/global/main_site/images/incentive_addons/'

const blankAddon = () => ({
  id: null,
  cidade_id: '',
  cidade_nome: '',
  nome: '',
  slug: '',
  descricao_curta: '',
  descricao_longa: '',
  nota_equipe: '',
  price_range: null,
  price_range_label: '',
  latitude: null,
  longitude: null,
  mapa_google: '',
  is_classic: false,
  is_favourite: false,
  is_out_of_box: false,
  is_active: true,
  foto_capa_url: '',
  fotos: []
})

export default {
  name: 'AddonsManager',
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
        cidade: null,
        ativo: 'all',
        classic: null,
        favourite: null,
        outOfBox: null
      },
      editedIndex: -1,
      editedItem: blankAddon(),
      originalPhotos: [],
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Capa', value: 'foto_capa_url', sortable: false },
        { text: 'ID', value: 'id' },
        { text: 'Nome', value: 'nome' },
        { text: 'Cidade', value: 'cidade_nome' },
        { text: 'Preco', value: 'price_range_label' },
        { text: 'Tags', value: 'tags', sortable: false },
        { text: 'Ativo', value: 'is_active', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      booleanFilterOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Sim', value: 'true' },
        { text: 'Nao', value: 'false' }
      ],
      triStateOptions: [
        { text: 'Todos', value: null },
        { text: 'Sim', value: 'true' },
        { text: 'Nao', value: 'false' }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Add-on' : 'Editar Add-on'
    },
    citySelectItems() {
      return (this.cities || [])
        .map((city) => ({
          text: String(city.name || '').trim(),
          value: String(city.id ?? '')
        }))
        .filter((city) => city.text && city.value !== null && city.value !== undefined)
    },
    mapPreviewUrl() {
      const raw = String(this.editedItem.mapa_google || '').trim()
      if (!raw) {
        const lat = this.toNumberOrNull(this.editedItem.latitude)
        const lng = this.toNumberOrNull(this.editedItem.longitude)
        if (lat !== null && lng !== null) {
          return `https://maps.google.com/maps?q=${encodeURIComponent(`${lat},${lng}`)}&z=15&output=embed`
        }
        return ''
      }
      return raw
    }
  },
  watch: {
    'editedItem.nome'(value) {
      if (this.editedIndex !== -1 && this.editedItem.slug) {
        return
      }
      this.editedItem.slug = this.slugify(value)
    }
  },
  mounted() {
    this.fetchCities()
    this.fetchAddons()
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
    slugify(value) {
      return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
    },
    toIntOrNull(value) {
      if (value === null || value === undefined || value === '') return null
      const num = Number(value)
      return Number.isFinite(num) ? Math.trunc(num) : null
    },
    toNumberOrNull(value) {
      if (value === null || value === undefined || value === '') return null
      const num = Number(value)
      return Number.isFinite(num) ? num : null
    },
    normalizePhotoUrl(url) {
      const raw = String(url || '').trim()
      if (!raw) return ''
      return raw.replace(IMAGE_BASE, '')
    },
    previewPhotoUrl(url) {
      const raw = String(url || '').trim()
      if (!raw) return ''
      if (/^https?:\/\//i.test(raw) || raw.startsWith('//')) return raw
      return `${IMAGE_BASE}${raw.replace(/^\/+/, '')}`
    },
    normalizeAddon(item) {
      const base = { ...blankAddon(), ...(item || {}) }
      const amount = Number(base.price_range)
      const photos = Array.isArray(base.fotos) ? base.fotos : []
      return {
        ...base,
        id: base.id !== null && base.id !== undefined ? Number(base.id) : null,
        cidade_id:
          base.cidade_id !== null && base.cidade_id !== undefined && `${base.cidade_id}` !== ''
            ? String(base.cidade_id)
            : '',
        price_range: this.toIntOrNull(base.price_range),
        price_range_label:
          base.price_range_label || (Number.isFinite(amount) && amount > 0 ? '$'.repeat(amount) : ''),
        latitude: this.toNumberOrNull(base.latitude),
        longitude: this.toNumberOrNull(base.longitude),
        is_classic: Boolean(base.is_classic),
        is_favourite: Boolean(base.is_favourite),
        is_out_of_box: Boolean(base.is_out_of_box),
        is_active: base.is_active !== undefined ? Boolean(base.is_active) : true,
        foto_capa_url: base.foto_capa_url || '',
        fotos: photos.map((foto, index) => ({
          id: foto.id !== null && foto.id !== undefined ? Number(foto.id) : null,
          url: this.normalizePhotoUrl(foto.url || ''),
          is_capa: Boolean(foto.is_capa),
          ordem: this.toIntOrNull(foto.ordem) ?? index
        }))
      }
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_addons')
      params.append('limit', '200')
      if (this.filters.nome) params.append('filtro_nome', this.filters.nome)
      if (this.filters.cidade !== null && this.filters.cidade !== undefined && this.filters.cidade !== '') {
        params.append('filtro_cidade', this.filters.cidade)
      }
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      if (this.filters.classic !== null) params.append('filtro_classic', this.filters.classic)
      if (this.filters.favourite !== null) params.append('filtro_favourite', this.filters.favourite)
      if (this.filters.outOfBox !== null) params.append('filtro_outofbox', this.filters.outOfBox)
      return params.toString()
    },
    async fetchCities() {
      try {
        const response = await fetch(`${API_BASE}api_addons.php?request=listar_cidades`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.cities = Array.isArray(data)
          ? data.map((city) => ({
              id: city?.id,
              name: String(city?.name || '').trim()
            }))
          : []
      } catch (error) {
        this.cities = []
        this.showMessage(`Erro ao carregar cidades: ${error.message}`, 'error')
      }
    },
    async fetchAddons() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}api_addons.php?${this.buildQuery()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        if (!Array.isArray(data)) {
          throw new Error(data?.error || 'Resposta invalida da API')
        }
        this.items = data.map(this.normalizeAddon)
      } catch (error) {
        this.showMessage(`Erro ao carregar add-ons: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      this.fetchAddons()
    },
    resetFilters() {
      this.filters = {
        nome: '',
        cidade: null,
        ativo: 'all',
        classic: null,
        favourite: null,
        outOfBox: null
      }
      this.fetchAddons()
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = blankAddon()
      this.originalPhotos = []
      this.activeTab = 0
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.items.findIndex((entry) => entry.id === item.id)
      this.dialog = true
      this.activeTab = 0
      this.loadingDetail = true
      try {
        const response = await fetch(`${API_BASE}api_addons.php?request=buscar_addon&id=${item.id}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        if (data?.error) {
          throw new Error(data.error)
        }
        const normalized = this.normalizeAddon(data)
        this.editedItem = normalized
        this.originalPhotos = JSON.parse(JSON.stringify(normalized.fotos || []))
      } catch (error) {
        this.showMessage(`Erro ao carregar add-on: ${error.message}`, 'error')
      } finally {
        this.loadingDetail = false
      }
    },
    closeDialog() {
      this.dialog = false
      this.editedItem = blankAddon()
      this.originalPhotos = []
    },
    openDelete(item) {
      this.editedIndex = this.items.findIndex((entry) => entry.id === item.id)
      this.editedItem = this.normalizeAddon(item)
      this.dialogDelete = true
    },
    addPhoto() {
      const nextOrder = this.editedItem.fotos.length
      this.editedItem.fotos.push({
        id: null,
        url: '',
        is_capa: this.editedItem.fotos.length === 0,
        ordem: nextOrder
      })
    },
    removePhoto(index) {
      this.editedItem.fotos.splice(index, 1)
      if (!this.editedItem.fotos.some((foto) => foto.is_capa) && this.editedItem.fotos[0]) {
        this.editedItem.fotos[0].is_capa = true
      }
    },
    setCoverPhoto(index, value) {
      this.editedItem.fotos = this.editedItem.fotos.map((foto, fotoIndex) => ({
        ...foto,
        is_capa: fotoIndex === index ? Boolean(value) : false
      }))
      if (!this.editedItem.fotos.some((foto) => foto.is_capa) && this.editedItem.fotos[index]) {
        this.editedItem.fotos[index].is_capa = true
      }
    },
    buildAddonPayload() {
      const cidadeId = this.toIntOrNull(this.editedItem.cidade_id)
      return {
        cidade_id: cidadeId !== null ? String(cidadeId) : null,
        nome: String(this.editedItem.nome || '').trim(),
        slug: this.slugify(this.editedItem.slug || this.editedItem.nome),
        descricao_curta: String(this.editedItem.descricao_curta || '').trim(),
        descricao_longa: String(this.editedItem.descricao_longa || '').trim(),
        nota_equipe: String(this.editedItem.nota_equipe || '').trim(),
        price_range: this.toIntOrNull(this.editedItem.price_range),
        latitude: this.toNumberOrNull(this.editedItem.latitude),
        longitude: this.toNumberOrNull(this.editedItem.longitude),
        mapa_google: String(this.editedItem.mapa_google || '').trim(),
        is_classic: Boolean(this.editedItem.is_classic),
        is_favourite: Boolean(this.editedItem.is_favourite),
        is_out_of_box: Boolean(this.editedItem.is_out_of_box),
        is_active: Boolean(this.editedItem.is_active)
      }
    },
    async syncPhotos(addonId) {
      const originalById = new Map((this.originalPhotos || []).filter((foto) => foto.id).map((foto) => [foto.id, foto]))
      const currentPhotos = (this.editedItem.fotos || []).map((foto, index) => ({
        ...foto,
        ordem: this.toIntOrNull(foto.ordem) ?? index,
        url: this.normalizePhotoUrl(foto.url)
      }))

      const removedPhotos = (this.originalPhotos || []).filter(
        (foto) => foto.id && !currentPhotos.some((current) => current.id === foto.id)
      )

      for (const foto of removedPhotos) {
        await this.requestJson(`${API_BASE}api_addons.php?request=excluir_foto&id=${foto.id}`, {
          method: 'DELETE',
          headers: this.authHeaders()
        })
      }

      for (const foto of currentPhotos) {
        if (!foto.url) {
          continue
        }
        if (foto.id) {
          const original = originalById.get(foto.id) || {}
          const changed =
            original.url !== foto.url ||
            Boolean(original.is_capa) !== Boolean(foto.is_capa) ||
            (this.toIntOrNull(original.ordem) ?? null) !== (this.toIntOrNull(foto.ordem) ?? null)

          if (!changed) {
            continue
          }

          await this.requestJson(`${API_BASE}api_addons.php?request=atualizar_foto&id=${foto.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              ...this.authHeaders()
            },
            body: JSON.stringify({
              url: foto.url,
              is_capa: Boolean(foto.is_capa),
              ordem: this.toIntOrNull(foto.ordem)
            })
          })
          continue
        }

        await this.requestJson(`${API_BASE}api_addons.php?request=adicionar_foto&addon_id=${addonId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            ...this.authHeaders()
          },
          body: JSON.stringify({
            url: foto.url,
            is_capa: Boolean(foto.is_capa),
            ordem: this.toIntOrNull(foto.ordem)
          })
        })
      }
    },
    async requestJson(url, options = {}) {
      const response = await fetch(url, options)
      const raw = await response.text()
      const data = raw ? JSON.parse(raw) : null
      if (!response.ok || data?.error || data?.success === false) {
        throw new Error(data?.error || data?.message || `Erro HTTP ${response.status}`)
      }
      return data
    },
    async save() {
      if (!String(this.editedItem.nome || '').trim()) {
        this.showMessage('Informe o nome do add-on.', 'warning')
        return
      }

      this.saving = true
      try {
        const isEdit = this.editedIndex > -1 && this.editedItem.id
        const payload = this.buildAddonPayload()
        const url = isEdit
          ? `${API_BASE}api_addons.php?request=atualizar_addon&id=${this.editedItem.id}`
          : `${API_BASE}api_addons.php?request=criar_addon`
        const method = isEdit ? 'PUT' : 'POST'
        const result = await this.requestJson(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            ...this.authHeaders()
          },
          body: JSON.stringify(payload)
        })

        const addonId = isEdit ? this.editedItem.id : result.addon_id
        await this.syncPhotos(addonId)
        this.showMessage(isEdit ? 'Add-on atualizado.' : 'Add-on criado.')
        this.dialog = false
        await this.fetchAddons()
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
        await this.requestJson(`${API_BASE}api_addons.php?request=excluir_addon&id=${this.editedItem.id}`, {
          method: 'DELETE',
          headers: this.authHeaders()
        })
        this.showMessage('Add-on excluido.')
        this.dialogDelete = false
        await this.fetchAddons()
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
.addons-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.addons-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.addons-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.addons-manager__filters {
  padding: 16px;
  margin-bottom: 16px;
}

.addons-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.addons-manager__dialog-title {
  display: flex;
  align-items: center;
}

.addons-manager__dialog-subtitle {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.6);
}

.addons-manager__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.addons-manager__tab-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.addons-manager__photo-row {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 8px;
  margin: 0 0 12px;
  background: #f8fafc;
}

.addons-manager__photo-preview {
  border-radius: 10px;
  overflow: hidden;
}
</style>
