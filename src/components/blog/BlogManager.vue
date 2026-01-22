<template>
  <div class="blog-manager">
    <div class="blog-manager__header">
      <div>
        <h2>Blog Receptivo</h2>
        <p>Listagem, criacao, edicao e exclusao via API.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" @click="openCreate">Novo Post</v-btn>
    </div>

    <v-card class="blog-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.titulo" label="Titulo" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo"
            :items="statusOptions"
            label="Status"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="3">
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
        <v-col cols="12" md="2">
          <v-autocomplete
            v-model="filters.citie"
            :items="cidades"
            item-text="label"
            item-value="id"
            label="Cidade"
            dense
            outlined
            clearable
            :search-input.sync="citySearch"
            :no-data-text="cityNoDataText"
            :filter="cityFilter"
          ></v-autocomplete>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.regiao"
            :items="regioes"
            item-text="label"
            item-value="value"
            label="Regiao"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
      </v-row>
      <div class="blog-manager__filter-actions">
        <v-btn outlined color="primary" @click="fetchPosts">Aplicar</v-btn>
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
        <template slot="item.cover_photo" slot-scope="{ item }">
          <v-avatar size="40" class="blog-manager__avatar">
            <img :src="coverImage(item)" :alt="item.title" @error="onImageError" />
          </v-avatar>
        </template>
        <template slot="item.is_active" slot-scope="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'grey'" small>
            {{ item.is_active ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn icon small color="info" :href="postUrl(item)" target="_blank">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn icon small color="secondary" @click="openStats(item)">
            <v-icon>mdi-chart-bar</v-icon>
          </v-btn>
          <v-btn icon small color="primary" @click="openEdit(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon small color="error" @click="openDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="860px" persistent>
      <v-card>
        <v-card-title class="blog-manager__dialog-title">
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
                <v-text-field v-model="editedItem.titulo" label="Titulo" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.data_post" label="Data" type="date" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <div class="blog-manager__editor-label">Descritivo Blumar</div>
                <TinyEditor v-model="editedItem.descritivo_blumar" :init="editorInit" />
              </v-col>
              <v-col cols="12">
                <div class="blog-manager__editor-label">Descritivo BE</div>
                <TinyEditor v-model="editedItem.descritivo_be" :init="editorInit" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto_capa" label="Foto capa" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto_topo" label="Foto topo" outlined dense></v-text-field>
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
              <v-col cols="12" md="4">
                <v-autocomplete
                  v-model="editedItem.citie"
                  :items="cidades"
                  item-text="label"
                  item-value="id"
                  label="Cidade"
                  dense
                  outlined
                  :search-input.sync="citySearch"
                  :no-data-text="cityNoDataText"
                  :filter="cityFilter"
                ></v-autocomplete>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="editedItem.regiao"
                  :items="regioes"
                  item-text="label"
                  item-value="value"
                  label="Regiao"
                  dense
                  outlined
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.url_video" label="URL video" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.meta_description"
                  label="Meta description"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-switch v-model="editedItem.ativo" label="Ativo" inset></v-switch>
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
            Tem certeza que deseja excluir o post
            <strong>{{ editedItem.titulo }}</strong>?
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="error" :loading="saving" @click="confirmDelete">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogStats" max-width="600px">
      <v-card>
        <v-card-title class="blog-manager__dialog-title">
          <span>Insights do Post</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="dialogStats = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <div class="blog-manager__stats-title">
            {{ statsItem.title || statsItem.titulo }}
          </div>
          <v-row class="mt-4">
            <v-col cols="12" sm="4">
              <v-card class="blog-manager__stat" elevation="3">
                <span>Visualizacoes</span>
                <strong>{{ statsMock.views }}</strong>
              </v-card>
            </v-col>
            <v-col cols="12" sm="4">
              <v-card class="blog-manager__stat" elevation="3">
                <span>Tempo medio</span>
                <strong>{{ statsMock.avgTime }}</strong>
              </v-card>
            </v-col>
            <v-col cols="12" sm="4">
              <v-card class="blog-manager__stat" elevation="3">
                <span>Taxa de retorno</span>
                <strong>{{ statsMock.returnRate }}</strong>
              </v-card>
            </v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="blog-manager__stats-subtitle">Top locais</div>
          <v-list dense>
            <v-list-item v-for="(item, index) in statsMock.topPlaces" :key="index">
              <v-list-item-content>
                <v-list-item-title>{{ item.place }}</v-list-item-title>
              </v-list-item-content>
              <v-list-item-action>
                <v-chip small color="primary" outlined>{{ item.views }}</v-chip>
              </v-list-item-action>
            </v-list-item>
          </v-list>
        </v-card-text>
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
import TinyEditor from '@/components/shared/TinyEditor.vue'

const API_BASE = '/api/'
const IMAGE_BASE = 'https://www.blumar.com.br/blog/'

export default {
  name: 'BlogManager',
  components: {
    TinyEditor
  },
  data() {
    return {
      loading: false,
      saving: false,
      items: [],
      classificacoes: [],
      cidades: [],
      regioes: [],
      defaultImage: require('@/assets/default.png'),
      dialog: false,
      dialogDelete: false,
      dialogStats: false,
      citySearch: '',
      filters: {
        titulo: '',
        ativo: '',
        classif: '',
        citie: '',
        regiao: ''
      },
      editedIndex: -1,
      statsItem: {},
      editedItem: {
        id: null,
        titulo: '',
        descritivo_blumar: '',
        descritivo_be: '',
        data_post: '',
        foto_capa: '',
        foto_topo: '',
        classif: '',
        citie: '',
        regiao: '',
        url_video: '',
        meta_description: '',
        ativo: true
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Capa', value: 'cover_photo', sortable: false },
        { text: 'Titulo', value: 'title' },
        { text: 'Classificacao', value: 'classif_nome' },
        { text: 'Data', value: 'post_date' },
        { text: 'Status', value: 'is_active', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      editorInit: {
        images_upload_handler: (blobInfo, success, failure) => {
          const reader = new FileReader()
          reader.onload = () => success(reader.result)
          reader.onerror = () => failure('Falha ao carregar imagem.')
          reader.readAsDataURL(blobInfo.blob())
        }
      },
      statsMock: {
        views: '12.480',
        avgTime: '3m 28s',
        returnRate: '41%',
        topPlaces: [
          { place: 'Sao Paulo', views: '3.120' },
          { place: 'Rio de Janeiro', views: '2.450' },
          { place: 'Belo Horizonte', views: '1.870' },
          { place: 'Salvador', views: '1.420' }
        ]
      },
      statusOptions: [
        { text: 'Ativo', value: 'true' },
        { text: 'Inativo', value: 'false' },
        { text: 'Todos', value: 'all' }
      ],
      classifOptions: [
        { value: 0, label: '-----------------' },
        { value: 1, label: 'Hotels' },
        { value: 2, label: 'Tours' },
        { value: 3, label: 'Boats' },
        { value: 4, label: 'Flights' },
        { value: 5, label: 'Destinations' },
        { value: 6, label: 'Festivals' }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Post' : 'Editar Post'
    },
    cityNoDataText() {
      if (!this.citySearch || this.citySearch.length < 4) {
        return 'Digite 4 caracteres'
      }
      return 'Nenhuma cidade encontrada'
    }
  },
  mounted() {
    this.fetchSupportLists()
    this.fetchPosts()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    coverImage(item) {
      if (!item.cover_photo) {
        return this.defaultImage
      }
      if (item.cover_photo.startsWith('http')) {
        return item.cover_photo
      }
      return `${IMAGE_BASE}${item.cover_photo}`
    },
    postUrl(item) {
      const id = item.id || item.pk_blognacional || ''
      return `https://www.blumar.com.br/blog/post.php?post=${id}`
    },
    cityFilter(item, queryText, itemText) {
      if (!queryText || queryText.length < 4) {
        return false
      }
      const text = (itemText || '').toLowerCase()
      const query = queryText.toLowerCase()
      return text.includes(query)
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    onImageError(event) {
      event.target.src = this.defaultImage
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_posts')
      if (this.filters.titulo) params.append('filtro_titulo', this.filters.titulo)
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      if (this.filters.classif) params.append('filtro_classif', this.filters.classif)
      if (this.filters.citie) params.append('filtro_citie', this.filters.citie)
      if (this.filters.regiao) params.append('filtro_regiao', this.filters.regiao)
      params.append('limit', '200')
      return params.toString()
    },
    async fetchPosts() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}blog.php?${this.buildQuery()}`)
        const data = await response.json()
        this.items = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchSupportLists() {
      try {
        const [classifRes, cidadesRes, regioesRes] = await Promise.all([
          fetch(`${API_BASE}blog.php?request=listar_classificacoes`),
          fetch(`${API_BASE}api_buscar_cidades.php?request=listar`),
          fetch(`${API_BASE}blog.php?request=listar_regioes`)
        ])
        const [classifData, cidadesData, regioesData] = await Promise.all([
          classifRes.json(),
          cidadesRes.json(),
          regioesRes.json()
        ])
        this.classificacoes = Array.isArray(classifData) && classifData.length
          ? classifData
          : this.classifOptions
        this.cidades = Array.isArray(cidadesData)
          ? cidadesData.map(city => ({
              id: city.cidade_cod,
              label: `${city.nome_pt} (${city.nome_en})`
            }))
          : []
        this.regioes = Array.isArray(regioesData) ? regioesData : []
      } catch (error) {
        this.showMessage(`Erro ao carregar listas: ${error.message}`, 'error')
      }
    },
    resetFilters() {
      this.filters = {
        titulo: '',
        ativo: '',
        classif: '',
        citie: '',
        regiao: ''
      }
      this.fetchPosts()
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = {
        id: null,
        titulo: '',
        descritivo_blumar: '',
        descritivo_be: '',
        data_post: '',
        foto_capa: '',
        foto_topo: '',
        classif: '',
        citie: '',
        regiao: '',
        url_video: '',
        meta_description: '',
        ativo: true
      }
      this.dialog = true
    },
    openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = {
        id: item.id,
        titulo: item.title || '',
        descritivo_blumar: item.description || '',
        descritivo_be: item.description_be || '',
        data_post: item.post_date || '',
        foto_capa: item.cover_photo || '',
        foto_topo: item.top_photo || '',
        classif: item.classification || '',
        citie: item.city_code || '',
        regiao: item.region_id || '',
        url_video: item.video_url || '',
        meta_description: item.meta_description || '',
        ativo: item.is_active === true
      }
      this.dialog = true
    },
    openDelete(item) {
      this.editedItem = {
        id: item.id,
        titulo: item.title || ''
      }
      this.dialogDelete = true
    },
    openStats(item) {
      this.statsItem = item || {}
      this.dialogStats = true
    },
    closeDialog() {
      this.dialog = false
    },
    async save() {
      if (!this.editedItem.titulo || !this.editedItem.data_post) {
        this.showMessage('Informe titulo e data.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'atualizar_post' : 'criar_post'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}blog.php?request=${request}&id=${this.editedItem.id}`
          : `${API_BASE}blog.php?request=${request}`

        const payload = {
          titulo: this.editedItem.titulo,
          descritivo_blumar: this.editedItem.descritivo_blumar,
          descritivo_be: this.editedItem.descritivo_be,
          data_post: this.editedItem.data_post,
          foto_capa: this.editedItem.foto_capa,
          foto_topo: this.editedItem.foto_topo,
          classif: this.editedItem.classif,
          citie: this.editedItem.citie,
          regiao: this.editedItem.regiao,
          url_video: this.editedItem.url_video,
          meta_description: this.editedItem.meta_description,
          ativo: this.editedItem.ativo
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
        this.showMessage(isEdit ? 'Post atualizado.' : 'Post criado.')
        this.dialog = false
        await this.fetchPosts()
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
          `${API_BASE}blog.php?request=excluir_post&id=${this.editedItem.id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Post excluido.')
        this.dialogDelete = false
        await this.fetchPosts()
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
.blog-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.blog-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.blog-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.blog-manager__filters {
  padding: 16px;
}

.blog-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.blog-manager__dialog-title {
  display: flex;
  align-items: center;
}

.blog-manager__avatar img {
  object-fit: cover;
}

.blog-manager__editor-label {
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  color: #475569;
}

.blog-manager__stats-title {
  font-weight: 600;
  font-size: 16px;
}

.blog-manager__stats-subtitle {
  font-weight: 600;
  color: #475569;
  margin-bottom: 8px;
}

.blog-manager__stat {
  padding: 16px;
  border-radius: 16px;
  text-align: center;
}

.blog-manager__stat span {
  display: block;
  font-size: 12px;
  color: #64748b;
}

.blog-manager__stat strong {
  display: block;
  font-size: 18px;
  color: #0f172a;
}
</style>
