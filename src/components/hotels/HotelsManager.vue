<template>
  <div class="hotels-manager">
    <div class="hotels-manager__header">
      <div>
        <h2>Hoteis</h2>
        <p>Listagem, criacao, edicao e exclusao via API.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Hotel</v-btn>
      <v-btn outlined color="primary" @click="fetchHotels">Atualizar</v-btn>
    </div>

    <v-card class="hotels-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.nome"
            label="Buscar hotel"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.cidade"
            label="Cidade"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model.number="filters.limit"
            label="Limite"
            type="number"
            min="1"
            dense
            outlined
            @change="fetchHotels"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2" class="d-flex align-center">
          <v-btn color="primary" block @click="fetchHotels">Aplicar</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="hotels"
        :loading="loading"
        item-key="frncod"
        class="elevation-0"
      >
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

    <v-card elevation="4" class="mt-4 hotels-manager__menu">
      <v-card-title>Incentivos</v-card-title>
      <v-card-text>
        <v-row>
          <v-col
            v-for="item in incentivesMenu"
            :key="item.key"
            cols="12"
            sm="4"
            md="3"
          >
            <v-btn block outlined color="primary" @click="handleIncentiveAction(item)">
              <v-icon left>{{ item.icon }}</v-icon>
              {{ item.label }}
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-dialog v-model="createDialog" max-width="880px" persistent>
      <v-card>
        <v-card-title class="hotels-manager__dialog-title">
          <span>Novo Hotel</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeCreateDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.mneu_for" label="Codigo" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.nome" label="Nome" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.cidade" label="Cidade" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto_fachada" label="Foto fachada" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.url_video" label="URL video" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.url_htl_360" label="URL tour 360" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.facilities" label="Facilities" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.descricao_pt" label="Descricao PT" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.descricao_en" label="Descricao EN" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.descricao_esp" label="Descricao ES" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.flaghtl" label="Ativo" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.resort" label="Resort" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.luxury" label="Luxury" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.allinclusive" label="All inclusive" inset></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeCreateDialog">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveCreate">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="editDialog" max-width="880px" persistent>
      <v-card>
        <v-card-title class="hotels-manager__dialog-title">
          <span>Editar Hotel</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeEditDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.mneu_for" label="Codigo" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.nome" label="Nome" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.cidade" label="Cidade" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto_fachada" label="Foto fachada" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.url_video" label="URL video" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.url_htl_360" label="URL tour 360" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.facilities" label="Facilities" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.descricao_pt" label="Descricao PT" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.descricao_en" label="Descricao EN" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editedItem.descricao_esp" label="Descricao ES" outlined rows="3"></v-textarea>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.flaghtl" label="Ativo" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.resort" label="Resort" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.luxury" label="Luxury" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.allinclusive" label="All inclusive" inset></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeEditDialog">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveEdit">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDelete" max-width="420px">
      <v-card>
        <v-card-title>Confirmar exclusao</v-card-title>
        <v-card-text>
          <v-alert type="warning" border="left" colored-border>
            Tem certeza que deseja excluir o hotel
            <strong>{{ editedItem.nome || editedItem.mneu_for }}</strong>?
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
  frncod: null,
  mneu_for: '',
  nome: '',
  cidade: '',
  descricao_pt: '',
  descricao_en: '',
  descricao_esp: '',
  foto_fachada: '',
  url_video: '',
  url_htl_360: '',
  facilities: '',
  flaghtl: true,
  resort: false,
  luxury: false,
  allinclusive: false
})

export default {
  name: 'HotelsManager',
  data() {
    return {
      loading: false,
      saving: false,
      hotels: [],
      filterTimer: null,
      filters: {
        nome: '',
        cidade: '',
        limit: 200
      },
      createDialog: false,
      editDialog: false,
      dialogDelete: false,
      editedIndex: -1,
      editedItem: blankItem(),
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Codigo', value: 'codigo' },
        { text: 'Nome', value: 'nome' },
        { text: 'Cidade', value: 'cidade' },
        { text: 'UF', value: 'uf' },
        { text: 'Estrelas', value: 'estrelas' },
        { text: 'Quartos', value: 'quartos' },
        { text: 'Status', value: 'status', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      incentivesMenu: [
        { key: 'novo', label: 'Novo incentivo', icon: 'mdi-plus-box' },
        { key: 'campanhas', label: 'Campanhas', icon: 'mdi-bullhorn' },
        { key: 'relatorios', label: 'Relatorios', icon: 'mdi-file-chart' }
      ]
    }
  },
  mounted() {
    this.fetchHotels()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    isActive(item) {
      return item.status === true || item.status === 't' || item.flaghtl === true
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    handleIncentiveAction(item) {
      this.showMessage(`Menu "${item.label}" em breve.`, 'info')
    },
    scheduleFetch() {
      if (this.filterTimer) {
        clearTimeout(this.filterTimer)
      }
      this.filterTimer = setTimeout(() => {
        this.fetchHotels()
      }, 400)
    },
    async fetchHotels() {
      this.loading = true
      try {
        const params = new URLSearchParams({ request: 'listar_hoteis' })
        if (this.filters.cidade) {
          params.set('cidade', this.filters.cidade)
        }
        if (this.filters.nome) {
          params.set('nome', this.filters.nome)
        }
        if (this.filters.limit) {
          params.set('limit', String(this.filters.limit))
        }

        const response = await fetch(`${API_BASE}hotels.php?${params.toString()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.hotels = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = blankItem()
      this.createDialog = true
    },
    openEdit(item) {
      this.editedIndex = this.hotels.indexOf(item)
      this.editedItem = {
        frncod: item.frncod,
        mneu_for: item.codigo || item.mneu_for || '',
        nome: item.nome || '',
        cidade: item.cidade || '',
        descricao_pt: item.descricao || item.descricao_pt || '',
        descricao_en: item.descricao_ingles || item.descricao_en || '',
        descricao_esp: item.descricao_espanhol || item.descricao_esp || '',
        foto_fachada: item.htlimgfotofachada || item.imagem_fachada || '',
        url_video: item.url_video || item.video_url || '',
        url_htl_360: item.url_htl_360 || item.tour_360_url || '',
        facilities: item.facilities || '',
        flaghtl: item.flaghtl === true || item.flaghtl === 't' || item.status === true,
        resort: item.resort === true || item.resort === 't',
        luxury: item.luxury === true || item.luxury === 't',
        allinclusive: item.allinclusive === true || item.allinclusive === 't'
      }
      this.editDialog = true
    },
    openDelete(item) {
      this.editedItem = {
        frncod: item.frncod,
        mneu_for: item.codigo || item.mneu_for || '',
        nome: item.nome || ''
      }
      this.dialogDelete = true
    },
    closeCreateDialog() {
      this.createDialog = false
    },
    closeEditDialog() {
      this.editDialog = false
    },
    async saveCreate() {
      if (!this.editedItem.mneu_for) {
        this.showMessage('Informe o codigo (mneu_for).', 'warning')
        return
      }
      this.saving = true
      try {
        const request = 'criar_hotel'
        const method = 'POST'
        const url = `${API_BASE}hotels.php?request=${request}`

        const payload = {
          mneu_for: this.editedItem.mneu_for,
          descricao_pt: this.editedItem.descricao_pt,
          descricao_en: this.editedItem.descricao_en,
          descricao_esp: this.editedItem.descricao_esp,
          foto_fachada: this.editedItem.foto_fachada,
          url_video: this.editedItem.url_video,
          url_htl_360: this.editedItem.url_htl_360,
          flaghtl: this.editedItem.flaghtl,
          resort: this.editedItem.resort,
          luxury: this.editedItem.luxury,
          allinclusive: this.editedItem.allinclusive,
          facilities: this.editedItem.facilities
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
        this.showMessage('Hotel criado.')
        this.createDialog = false
        await this.fetchHotels()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async saveEdit() {
      if (!this.editedItem.mneu_for) {
        this.showMessage('Informe o codigo (mneu_for).', 'warning')
        return
      }
      this.saving = true
      try {
        const request = 'atualizar_hotel'
        const method = 'PUT'
        const url = `${API_BASE}hotels.php?request=${request}&id=${this.editedItem.frncod}`

        const payload = {
          mneu_for: this.editedItem.mneu_for,
          descricao_pt: this.editedItem.descricao_pt,
          descricao_en: this.editedItem.descricao_en,
          descricao_esp: this.editedItem.descricao_esp,
          foto_fachada: this.editedItem.foto_fachada,
          url_video: this.editedItem.url_video,
          url_htl_360: this.editedItem.url_htl_360,
          flaghtl: this.editedItem.flaghtl,
          resort: this.editedItem.resort,
          luxury: this.editedItem.luxury,
          allinclusive: this.editedItem.allinclusive,
          facilities: this.editedItem.facilities
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
        this.showMessage('Hotel atualizado.')
        this.editDialog = false
        await this.fetchHotels()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.frncod) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}hotels.php?request=excluir_hotel&id=${this.editedItem.frncod}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Hotel excluido.')
        this.dialogDelete = false
        await this.fetchHotels()
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
.hotels-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.hotels-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.hotels-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.hotels-manager__filters {
  padding: 16px;
}

.hotels-manager__dialog-title {
  display: flex;
  align-items: center;
}
</style>
