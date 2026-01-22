<template>
  <div class="beach-manager">
    <div class="beach-manager__header">
      <div>
        <h2>Beach Houses</h2>
        <p>Listagem, criacao, edicao e exclusao via API.</p>
      </div>
      <v-spacer></v-spacer>
      <v-text-field
        v-model="search"
        append-icon="mdi-magnify"
        label="Buscar"
        dense
        outlined
        hide-details
        class="beach-manager__search"
      ></v-text-field>
      <v-btn color="primary" class="ml-3" @click="openCreate">Novo Beach House</v-btn>
    </div>

    <v-card elevation="6">
      <v-data-table
        :headers="headers"
        :items="filteredItems"
        :loading="loading"
        item-key="pk_beach"
        class="elevation-0"
      >
        <template slot="item.imagens" slot-scope="{ item }">
          <v-avatar size="40" class="beach-manager__avatar">
            <img :src="primaryImage(item)" :alt="item.name || item.nome" @error="onImageError" />
          </v-avatar>
        </template>
        <template slot="item.ativo" slot-scope="{ item }">
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

    <v-dialog v-model="dialog" max-width="720px" persistent>
      <v-card>
        <v-card-title class="beach-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field v-model="editedItem.nome" label="Nome" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="editedItem.cor"
                  :items="groupOptions"
                  label="Grupo"
                  outlined
                  dense
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editedItem.cidade"
                  :items="cidades"
                  item-text="name"
                  item-value="id"
                  label="Cidade"
                  outlined
                  dense
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="editedItem.ativo" label="Ativo" inset></v-switch>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.descritivo"
                  label="Descritivo"
                  outlined
                  rows="3"
                ></v-textarea>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.foto1" label="Foto 1" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.foto2" label="Foto 2" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.foto3" label="Foto 3" outlined dense></v-text-field>
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
            Tem certeza que deseja excluir o Beach House
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
const API_BASE = '/api/'

export default {
  name: 'BeachHouseManager',
  data() {
    return {
      loading: false,
      saving: false,
      search: '',
      items: [],
      cidades: [],
      defaultImage: require('@/assets/default.png'),
      dialog: false,
      dialogDelete: false,
      editedIndex: -1,
      editedItem: {
        pk_beach: null,
        nome: '',
        cor: '',
        cidade: '',
        descritivo: '',
        foto1: '',
        foto2: '',
        foto3: '',
        ativo: true
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Foto', value: 'imagens', sortable: false },
        { text: 'Nome', value: 'nome' },
        { text: 'Grupo', value: 'grupo' },
        { text: 'Cidade', value: 'cidade_nome' },
        { text: 'Status', value: 'ativo', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      groupOptions: ['1', '2', '3', '4', '5']
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Beach House' : 'Editar Beach House'
    },
    filteredItems() {
      if (!this.search) {
        return this.items
      }
      const term = this.search.toLowerCase()
      return this.items.filter(item => {
        return (
          String(item.nome || '').toLowerCase().includes(term) ||
          String(item.cidade_nome || '').toLowerCase().includes(term) ||
          String(item.grupo || '').toLowerCase().includes(term)
        )
      })
    }
  },
  mounted() {
    this.fetchCidades()
    this.fetchBeachHouses()
  },
  methods: {
    primaryImage(item) {
      if (item.imagens && item.imagens.length) {
        return item.imagens[0].image_url
      }
      if (item.foto1) {
        return `http://www.blumar.com.br/global/main_site/images/beach_house/${item.foto1}`
      }
      return this.defaultImage
    },
    isActive(item) {
      return item.is_active === true || item.ativo === 't' || item.ativo === true
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    onImageError(event) {
      event.target.src = this.defaultImage
    },
    async fetchBeachHouses() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}beach.php?request=listar_beach_houses&limit=200`)
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
        const response = await fetch(`${API_BASE}beach.php?request=listar_cidades`)
        const data = await response.json()
        this.cidades = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar cidades: ${error.message}`, 'error')
      }
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = {
        pk_beach: null,
        nome: '',
        cor: '',
        cidade: '',
        descritivo: '',
        foto1: '',
        foto2: '',
        foto3: '',
        ativo: true
      }
      this.dialog = true
    },
    openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = {
        pk_beach: item.pk_beach,
        nome: item.nome || item.name || '',
        cor: item.cor || '',
        cidade: item.cidade || item.id_cidade || '',
        descritivo: item.descritivo || '',
        foto1: item.foto1 || '',
        foto2: item.foto2 || '',
        foto3: item.foto3 || '',
        ativo: this.isActive(item)
      }
      this.dialog = true
    },
    openDelete(item) {
      this.editedItem = {
        pk_beach: item.pk_beach,
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
        const request = isEdit ? 'atualizar_beach_house' : 'criar_beach_house'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}beach.php?request=${request}&id=${this.editedItem.pk_beach}`
          : `${API_BASE}beach.php?request=${request}`

        const payload = {
          nome: this.editedItem.nome,
          cor: this.editedItem.cor,
          cidade: this.editedItem.cidade,
          descritivo: this.editedItem.descritivo,
          foto1: this.editedItem.foto1,
          foto2: this.editedItem.foto2,
          foto3: this.editedItem.foto3,
          ativo: this.editedItem.ativo ? 't' : 'f'
        }

        const response = await fetch(url, {
          method,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        })
        const result = await response.json()
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.showMessage(isEdit ? 'Beach House atualizado.' : 'Beach House criado.')
        this.dialog = false
        await this.fetchBeachHouses()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.pk_beach) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}beach.php?request=excluir_beach_house&id=${this.editedItem.pk_beach}`,
          { method: 'DELETE' }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Beach House excluido.')
        this.dialogDelete = false
        await this.fetchBeachHouses()
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
.beach-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.beach-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.beach-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.beach-manager__search {
  max-width: 260px;
}

.beach-manager__dialog-title {
  display: flex;
  align-items: center;
}

.beach-manager__avatar img {
  object-fit: cover;
}

@media (max-width: 959px) {
  .beach-manager__search {
    max-width: 100%;
    width: 100%;
  }
}
</style>
