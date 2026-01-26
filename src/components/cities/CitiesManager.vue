<template>
  <div class="cities-manager">
    <div class="cities-manager__header">
      <div>
        <h2>Cidades</h2>
        <p>Lista de cidades com imagens disponíveis.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Nova Cidade</v-btn>
      <v-btn outlined color="primary" @click="fetchCities">Atualizar</v-btn>
    </div>

    <v-card class="cities-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.nome"
            label="Buscar cidade"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.regiao"
            :items="regiaoOptions"
            item-text="text"
            item-value="value"
            label="Regiao"
            dense
            outlined
            @change="scheduleFetch"
          ></v-select>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model.number="filters.limit"
            label="Limite"
            type="number"
            min="1"
            dense
            outlined
            @change="fetchCities"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2" class="d-flex align-center">
          <v-btn color="primary" block @click="fetchCities">Aplicar</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="cities"
        :loading="loading"
        item-key="cidade_cod"
        class="elevation-0"
      >
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

    <v-dialog v-model="dialog" max-width="520px" persistent>
      <v-card>
        <v-card-title class="cities-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-text-field v-model="editedItem.cidade_cod" label="Codigo" outlined dense></v-text-field>
            <v-text-field v-model="editedItem.nome_pt" label="Nome PT" outlined dense></v-text-field>
            <v-text-field v-model="editedItem.nome_en" label="Nome EN" outlined dense></v-text-field>
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
            Tem certeza que deseja excluir a cidade
            <strong>{{ editedItem.nome_pt }}</strong>?
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
import axios from 'axios'

const API_BASE = '/api/'

export default {
  name: 'CitiesManager',
  data() {
    return {
      loading: false,
      saving: false,
      cities: [],
      filterTimer: null,
      filters: {
        nome: '',
        regiao: 0,
        limit: 200
      },
      dialog: false,
      dialogDelete: false,
      editedIndex: -1,
      editedItem: {
        cidade_cod: '',
        nome_en: '',
        nome_pt: ''
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      regiaoOptions: [
        { text: 'Todas', value: 0 },
        { text: 'Norte', value: 1 },
        { text: 'Nordeste', value: 2 },
        { text: 'Sudeste', value: 3 },
        { text: 'Centro-Oeste', value: 4 },
        { text: 'Sul', value: 5 }
      ],
      headers: [
        { text: 'Codigo', value: 'cidade_cod' },
        { text: 'Nome PT', value: 'nome_pt' },
        { text: 'Nome EN', value: 'nome_en' },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ]
    }
  }, 
  computed: {
      dialogTitle() {
      return this.editedIndex === -1 ? 'Nova Cidade' : 'Editar Cidade'
    }
  },
  mounted() {
    this.fetchCities()
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
    scheduleFetch() {
      if (this.filterTimer) {
        clearTimeout(this.filterTimer)
      }
      this.filterTimer = setTimeout(() => {
        this.fetchCities()
      }, 400)
    },
    async fetchCities() {
      this.loading = true
      try {
        const params = new URLSearchParams({ request: 'listar_cidades' })
        const limit = Number(this.filters.limit)
        if (Number.isFinite(limit) && limit > 0) {
          params.set('limit', String(limit))
        }
        if (this.filters.nome) {
          params.set('filtro_nome', this.filters.nome)
        }
        if (this.filters.regiao) {
          params.set('filtro_regiao', String(this.filters.regiao))
        }
        const response = await axios.get(`${API_BASE}cidades.php?${params.toString()}`)
        const data = response.data
        this.cities = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = {
        cidade_cod: '',
        nome_en: '',
        nome_pt: ''
      }
      this.dialog = true
    },
    openEdit(item) {
      this.editedIndex = this.cities.indexOf(item)
      this.editedItem = {
        cidade_cod: item.cidade_cod,
        nome_en: item.nome_en || '',
        nome_pt: item.nome_pt || ''
      }
      this.dialog = true
    },
    openDelete(item) {
      this.editedItem = {
        cidade_cod: item.cidade_cod,
        nome_pt: item.nome_pt || item.nome_en || ''
      }
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    async save() {
      if (!this.editedItem.cidade_cod) {
        this.showMessage('Informe o codigo.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'atualizar_cidade' : 'criar_cidade'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}cidades.php?request=${request}&cidade_cod=${this.editedItem.cidade_cod}`
          : `${API_BASE}cidades.php?request=${request}`

        const payload = {
          cidade_cod: this.editedItem.cidade_cod,
          nome_en: this.editedItem.nome_en,
          nome_pt: this.editedItem.nome_pt
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
        this.showMessage(isEdit ? 'Cidade atualizada.' : 'Cidade criada.')
        this.dialog = false
        await this.fetchCities()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.cidade_cod) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}cidades.php?request=excluir_cidade&cidade_cod=${this.editedItem.cidade_cod}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Cidade excluida.')
        this.dialogDelete = false
        await this.fetchCities()
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
.cities-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.cities-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.cities-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.cities-manager__filters {
  padding: 16px;
}

.cities-manager__dialog-title {
  display: flex;
  align-items: center;
}
</style>
