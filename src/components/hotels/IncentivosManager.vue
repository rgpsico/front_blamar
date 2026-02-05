<template>
  <div class="incentivos-manager">
    <div class="incentivos-manager__header">
      <div>
        <h2>Incentivos</h2>
        <p>Listagem e manutencao completa do modulo de incentivos.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Incentivo</v-btn>
      <v-btn outlined color="primary" @click="fetchIncentives">Atualizar</v-btn>
    </div>

    <v-card class="incentivos-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.nome"
            label="Buscar por nome"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.cidade"
            label="Cidade"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.status"
            :items="statusFilterOptions"
            label="Status"
            dense
            outlined
            clearable
          ></v-select>
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
      </v-row>
      <div class="incentivos-manager__filter-actions">
        <v-btn outlined color="primary" @click="applyFilters">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6">
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        item-key="inc_id"
        class="elevation-0"
      >
        <template slot="item.inc_is_active" slot-scope="{ item }">
          <v-chip :color="item.inc_is_active ? 'success' : 'grey'" small>
            {{ item.inc_is_active ? 'Ativo' : 'Inativo' }}
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
        <v-card-title class="incentivos-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-tabs v-model="activeTab" background-color="transparent" grow>
              <v-tab>Programa</v-tab>
              <v-tab>Midias</v-tab>
              <v-tab>Quartos</v-tab>
              <v-tab>Dining</v-tab>
              <v-tab>Facilities</v-tab>
              <v-tab>Convention</v-tab>
              <v-tab>Salas</v-tab>
              <v-tab>Notas</v-tab>
            </v-tabs>

            <v-tabs-items v-model="activeTab" class="mt-4">
              <v-tab-item>
                <v-row>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="editedItem.inc_name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="editedItem.inc_status"
                      :items="statusOptions"
                      label="Status"
                      outlined
                      dense
                    ></v-select>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="editedItem.inc_description"
                      label="Descricao"
                      outlined
                      rows="3"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="editedItem.hotel_ref_id"
                      label="Hotel ref ID"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field
                      v-model="editedItem.hotel_name_snapshot"
                      label="Hotel snapshot"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.city_name" label="Cidade" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="editedItem.country_code"
                      label="Pais (ISO)"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="editedItem.inc_is_active" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Midias</div>
                  <v-btn small outlined color="primary" @click="addMedia">Adicionar</v-btn>
                </div>
                <v-row v-for="(media, index) in editedItem.media" :key="`media-${index}`">
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="media.media_type"
                      :items="mediaTypeOptions"
                      label="Tipo"
                      outlined
                      dense
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="7">
                    <v-text-field v-model="media.media_url" label="URL" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="1">
                    <v-text-field
                      v-model.number="media.position"
                      label="#"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="6" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeMedia(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="media.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Categorias de quartos</div>
                  <v-btn small outlined color="primary" @click="addRoomCategory">Adicionar</v-btn>
                </div>
                <v-row v-for="(room, index) in editedItem.room_categories" :key="`room-${index}`">
                  <v-col cols="12" md="5">
                    <v-text-field v-model="room.room_name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model.number="room.quantity" label="Qtd" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="room.notes" label="Notas" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="1">
                    <v-text-field v-model.number="room.position" label="#" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeRoomCategory(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="room.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Dining</div>
                  <v-btn small outlined color="primary" @click="addDining">Adicionar</v-btn>
                </div>
                <v-row v-for="(dining, index) in editedItem.dining" :key="`dining-${index}`">
                  <v-col cols="12" md="4">
                    <v-text-field v-model="dining.name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="dining.cuisine" label="Cozinha" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model.number="dining.capacity" label="Cap." type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model="dining.schedule" label="Horario" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="dining.description" label="Descricao" outlined rows="2"></v-textarea>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="dining.image_url" label="Imagem" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-switch v-model="dining.is_michelin" label="Michelin" inset></v-switch>
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-switch v-model="dining.can_be_private" label="Privativo" inset></v-switch>
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-text-field v-model.number="dining.position" label="#" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="2" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeDining(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="dining.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Facilities</div>
                  <v-btn small outlined color="primary" @click="addFacility">Adicionar</v-btn>
                </div>
                <v-row v-for="(facility, index) in editedItem.facilities" :key="`facility-${index}`">
                  <v-col cols="12" md="5">
                    <v-text-field v-model="facility.name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="facility.icon" label="Icon" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-switch v-model="facility.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeFacility(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <v-row>
                  <v-col cols="12">
                    <v-textarea
                      v-model="editedItem.convention.description"
                      label="Descricao"
                      outlined
                      rows="3"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="editedItem.convention.total_rooms"
                      label="Total de salas"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="editedItem.convention.has_360" label="Tour 360" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Salas de evento</div>
                  <v-btn small outlined color="primary" @click="addConventionRoom">Adicionar</v-btn>
                </div>
                <div
                  v-for="(room, index) in editedItem.convention_rooms"
                  :key="`croom-${index}`"
                  class="incentivos-manager__room-group"
                >
                  <v-row class="incentivos-manager__room-row">
                    <v-col cols="12" md="4">
                      <v-text-field v-model="room.name" label="Nome" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.area_m2" label="Area m2" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_auditorium" label="Audit." type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_banquet" label="Banquet" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_classroom" label="Class" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_u_shape" label="U" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field v-model="room.notes" label="Notas" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="1" class="d-flex align-center">
                      <v-btn icon color="error" @click="removeConventionRoom(index)">
                        <v-icon>mdi-delete</v-icon>
                      </v-btn>
                    </v-col>
                  </v-row>
                  <v-divider v-if="index < editedItem.convention_rooms.length - 1" class="my-2"></v-divider>
                </div>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Notas</div>
                  <v-btn small outlined color="primary" @click="addNote">Adicionar</v-btn>
                </div>
                <v-row v-for="(note, index) in editedItem.notes" :key="`note-${index}`">
                  <v-col cols="12" md="2">
                    <v-text-field v-model="note.language" label="Idioma" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="9">
                    <v-textarea v-model="note.note" label="Nota" outlined rows="2"></v-textarea>
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeNote(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
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
            Tem certeza que deseja excluir o incentivo
            <strong>{{ editedItem.inc_name || editedItem.inc_id }}</strong>?
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
  inc_id: null,
  inc_name: '',
  inc_description: '',
  hotel_ref_id: null,
  hotel_name_snapshot: '',
  city_name: '',
  country_code: '',
  inc_status: 'active',
  inc_is_active: true,
  media: [],
  room_categories: [],
  dining: [],
  facilities: [],
  convention: {
    description: '',
    total_rooms: null,
    has_360: false
  },
  convention_rooms: [],
  notes: []
})

export default {
  name: 'IncentivosManager',
  data() {
    return {
      loading: false,
      saving: false,
      dialog: false,
      dialogDelete: false,
      activeTab: 0,
      items: [],
      filters: {
        nome: '',
        cidade: '',
        status: '',
        ativo: 'all'
      },
      editedIndex: -1,
      editedItem: blankItem(),
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'ID', value: 'inc_id' },
        { text: 'Nome', value: 'inc_name' },
        { text: 'Status', value: 'inc_status' },
        { text: 'Cidade', value: 'city_name' },
        { text: 'Ativo', value: 'inc_is_active', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      statusOptions: ['active', 'inactive', 'draft'],
      statusFilterOptions: [
        { text: 'Ativo', value: 'active' },
        { text: 'Inativo', value: 'inactive' },
        { text: 'Rascunho', value: 'draft' },
        { text: 'Arquivado', value: 'archived' }
      ],
      activeFilterOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Sim', value: 'true' },
        { text: 'Nao', value: 'false' }
      ],
      mediaTypeOptions: ['banner', 'gallery', 'video', 'map']
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Incentivo' : 'Editar Incentivo'
    }
  },
  mounted() {
    this.fetchIncentives()
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
    normalizeItem(item) {
      return {
        inc_id: item.inc_id || item.id || null,
        inc_name: item.inc_name || item.name || '',
        inc_description: item.inc_description || item.description || '',
        hotel_ref_id: item.hotel_ref_id || null,
        hotel_name_snapshot: item.hotel_name_snapshot || '',
        city_name: item.city_name || '',
        country_code: item.country_code || '',
        inc_status: item.inc_status || 'active',
        inc_is_active: item.inc_is_active !== undefined ? item.inc_is_active : true,
        media: Array.isArray(item.media) ? item.media : [],
        room_categories: Array.isArray(item.room_categories) ? item.room_categories : [],
        dining: Array.isArray(item.dining) ? item.dining : [],
        facilities: Array.isArray(item.facilities) ? item.facilities : [],
        convention: item.convention || { description: '', total_rooms: null, has_360: false },
        convention_rooms: Array.isArray(item.convention_rooms) ? item.convention_rooms : [],
        notes: Array.isArray(item.notes) ? item.notes : []
      }
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_incentives')
      if (this.filters.nome) params.append('filtro_nome', this.filters.nome)
      if (this.filters.cidade) params.append('filtro_cidade', this.filters.cidade)
      if (this.filters.status) params.append('filtro_status', this.filters.status)
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      return params.toString()
    },
    async fetchIncentives() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}api_incentives.php?${this.buildQuery()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        const list = Array.isArray(data) ? data : data.data
        this.items = Array.isArray(list) ? list.map(this.normalizeItem) : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      this.fetchIncentives()
    },
    resetFilters() {
      this.filters = {
        nome: '',
        cidade: '',
        status: '',
        ativo: 'all'
      }
      this.fetchIncentives()
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = blankItem()
      this.activeTab = 0
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = this.normalizeItem(item)
      this.activeTab = 0
      this.dialog = true

      if (item && item.inc_id) {
        await this.fetchIncentiveDetail(item.inc_id)
      }
    },
    openDelete(item) {
      this.editedItem = this.normalizeItem(item)
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    addMedia() {
      this.editedItem.media.push({
        inc_media_id: null,
        media_type: 'banner',
        media_url: '',
        position: 0,
        is_active: true
      })
    },
    removeMedia(index) {
      this.editedItem.media.splice(index, 1)
    },
    addRoomCategory() {
      this.editedItem.room_categories.push({
        inc_room_id: null,
        room_name: '',
        quantity: null,
        notes: '',
        position: 0,
        is_active: true
      })
    },
    removeRoomCategory(index) {
      this.editedItem.room_categories.splice(index, 1)
    },
    addDining() {
      this.editedItem.dining.push({
        inc_dining_id: null,
        name: '',
        description: '',
        cuisine: '',
        capacity: null,
        schedule: '',
        is_michelin: false,
        can_be_private: false,
        image_url: '',
        position: 0,
        is_active: true
      })
    },
    removeDining(index) {
      this.editedItem.dining.splice(index, 1)
    },
    addFacility() {
      this.editedItem.facilities.push({
        inc_facility_id: null,
        name: '',
        icon: '',
        is_active: true
      })
    },
    removeFacility(index) {
      this.editedItem.facilities.splice(index, 1)
    },
    addConventionRoom() {
      this.editedItem.convention_rooms.push({
        inc_room_id: null,
        name: '',
        area_m2: null,
        capacity_auditorium: null,
        capacity_banquet: null,
        capacity_classroom: null,
        capacity_u_shape: null,
        notes: ''
      })
    },
    removeConventionRoom(index) {
      this.editedItem.convention_rooms.splice(index, 1)
    },
    addNote() {
      this.editedItem.notes.push({
        inc_note_id: null,
        language: '',
        note: ''
      })
    },
    removeNote(index) {
      this.editedItem.notes.splice(index, 1)
    },
    async fetchIncentiveDetail(id) {
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}api_incentives.php?request=buscar_incentive&id=${id}`,
          { headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result && (result.error || result.success === false)) {
          throw new Error(result.error || result.message || 'Erro ao carregar incentivo')
        }
        const data = result && result.data ? result.data : result
        this.editedItem = this.normalizeItem(data || {})
      } catch (error) {
        this.showMessage(`Erro ao carregar incentivo: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async save() {
      if (!this.editedItem.inc_name) {
        this.showMessage('Informe o nome do incentivo.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'atualizar_incentive' : 'criar_incentive'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}api_incentives.php?request=${request}&id=${this.editedItem.inc_id}`
          : `${API_BASE}api_incentives.php?request=${request}`

        const payload = {
          inc_id: this.editedItem.inc_id,
          inc_name: this.editedItem.inc_name,
          inc_description: this.editedItem.inc_description,
          hotel_ref_id: this.editedItem.hotel_ref_id,
          hotel_name_snapshot: this.editedItem.hotel_name_snapshot,
          city_name: this.editedItem.city_name,
          country_code: this.editedItem.country_code,
          inc_status: this.editedItem.inc_status,
          inc_is_active: this.editedItem.inc_is_active,
          media: this.editedItem.media,
          room_categories: this.editedItem.room_categories,
          dining: this.editedItem.dining,
          facilities: this.editedItem.facilities,
          convention: this.editedItem.convention,
          convention_rooms: this.editedItem.convention_rooms,
          notes: this.editedItem.notes
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
        this.showMessage(isEdit ? 'Incentivo atualizado.' : 'Incentivo criado.')
        this.dialog = false
        await this.fetchIncentives()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.inc_id) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}incentives.php?request=excluir_incentivo&id=${this.editedItem.inc_id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Incentivo excluido.')
        this.dialogDelete = false
        await this.fetchIncentives()
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
.incentivos-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.incentivos-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.incentivos-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.incentivos-manager__filters {
  padding: 16px;
  margin-bottom: 16px;
}

.incentivos-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.incentivos-manager__dialog-title {
  display: flex;
  align-items: center;
}

.incentivos-manager__tab-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.incentivos-manager__room-group {
  padding: 8px 4px;
}

.incentivos-manager__room-row {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px;
  margin: 0;
  background: #f8fafc;
}
</style>
