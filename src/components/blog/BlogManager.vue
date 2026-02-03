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
      <v-divider></v-divider>
      <v-card-actions class="blog-manager__pagination">
        <v-select
          v-model="pagination.perPage"
          :items="pagination.perPageOptions"
          label="Por pagina"
          dense
          outlined
          hide-details
          class="blog-manager__per-page"
          @change="changePerPage"
        ></v-select>
        <v-spacer></v-spacer>
        <v-pagination
          v-model="pagination.page"
          :length="pagination.lastPage"
          total-visible="7"
          @input="fetchPosts"
        ></v-pagination>
      </v-card-actions>
    </v-card>

    <v-dialog v-model="dialogCreate" max-width="860px" persistent>
      <v-card>
        <v-card-title class="blog-manager__dialog-title">
          <span>Novo Post</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeCreateDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field v-model="createItem.titulo" label="Titulo" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="createItem.data_post" label="Data" type="date" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <div class="blog-manager__editor-label">Descritivo Blumar</div>
                <TinyEditor v-model="createItem.descritivo_blumar" :init="editorInit" />
              </v-col>
              <v-col cols="12">
                <div class="blog-manager__editor-label">Descritivo BE</div>
                <TinyEditor v-model="createItem.descritivo_be" :init="editorInit" />
              </v-col>
              <v-col cols="12">
                <div class="blog-manager__editor-label">FAQ (Perguntas e Respostas)</div>
                <v-card outlined class="pa-3">
                  <v-row v-for="(qa, index) in createItem.faq" :key="index" class="mb-2">
                    <v-col cols="12" md="5">
                      <v-text-field v-model="qa.pergunta" label="Pergunta" outlined dense />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-textarea
                        v-model="qa.resposta"
                        label="Resposta"
                        outlined
                        dense
                        rows="2"
                        auto-grow
                      />
                    </v-col>
                    <v-col cols="12" md="1" class="d-flex align-center justify-end">
                      <v-btn
                        icon
                        color="error"
                        @click="removeFaq(createItem, index)"
                        :disabled="createItem.faq.length === 1"
                      >
                        <v-icon>mdi-delete</v-icon>
                      </v-btn>
                    </v-col>
                  </v-row>
                  <v-btn outlined color="primary" @click="addFaq(createItem)">
                    Adicionar pergunta
                  </v-btn>
                </v-card>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="createItem.foto_capa" label="Foto capa" outlined dense></v-text-field>
                <v-btn
                  small
                  text
                  color="primary"
                  class="blog-manager__image-edit"
                  :disabled="!createItem.foto_capa"
                  @click="openImageEditor('create', 'foto_capa')"
                >
                  Editar imagem
                </v-btn>
                <div class="blog-manager__image-preview" v-if="createItem.foto_capa">
                  <img :src="resolveImage(createItem.foto_capa)" alt="Preview capa" @error="onImageError" />
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="createItem.foto_topo" label="Foto topo" outlined dense></v-text-field>
                <v-btn
                  small
                  text
                  color="primary"
                  class="blog-manager__image-edit"
                  :disabled="!createItem.foto_topo"
                  @click="openImageEditor('create', 'foto_topo')"
                >
                  Editar imagem
                </v-btn>
                <div class="blog-manager__image-preview" v-if="createItem.foto_topo">
                  <img :src="resolveImage(createItem.foto_topo)" alt="Preview topo" @error="onImageError" />
                </div>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="createItem.classif"
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
                  v-model="createItem.citie"
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
                  v-model="createItem.regiao"
                  :items="regioes"
                  item-text="label"
                  item-value="value"
                  label="Regiao"
                  dense
                  outlined
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="createItem.url_video" label="URL video" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="createItem.meta_description"
                  label="Meta description"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-switch v-model="createItem.ativo" label="Ativo" inset></v-switch>
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

    <v-dialog v-model="dialogEdit" max-width="860px" persistent>
      <v-card>
        <v-card-title class="blog-manager__dialog-title">
          <span>Editar Post</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeEditDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field v-model="editItem.titulo" label="Titulo" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editItem.data_post" label="Data" type="date" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <div class="blog-manager__editor-label">Descritivo Blumar</div>
                <TinyEditor v-model="editItem.descritivo_blumar" :init="editorInit" />
              </v-col>
              <v-col cols="12">
                <div class="blog-manager__editor-label">Descritivo BE</div>
                <TinyEditor v-model="editItem.descritivo_be" :init="editorInit" />
              </v-col>

              <v-col cols="12">
                <div class="blog-manager__editor-label">FAQ (Perguntas e Respostas)</div>
                <v-card outlined class="pa-3">
                  <v-row v-for="(qa, index) in editItem.faq" :key="index" class="mb-2">
                    <v-col cols="12" md="5">
                      <v-text-field v-model="qa.pergunta" label="Pergunta" outlined dense />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-textarea
                        v-model="qa.resposta"
                        label="Resposta"
                        outlined
                        dense
                        rows="2"
                        auto-grow
                      />
                    </v-col>
                    <v-col cols="12" md="1" class="d-flex align-center justify-end">
                      <v-btn
                        icon
                        color="error"
                        @click="removeFaq(editItem, index)"
                        :disabled="editItem.faq.length === 1"
                      >
                        <v-icon>mdi-delete</v-icon>
                      </v-btn>
                    </v-col>
                  </v-row>
                  <v-btn outlined color="primary" @click="addFaq(editItem)">
                    Adicionar pergunta
                  </v-btn>
                </v-card>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field v-model="editItem.foto_capa" label="Foto capa" outlined dense></v-text-field>
                <v-btn
                  small
                  text
                  color="primary"
                  class="blog-manager__image-edit"
                  :disabled="!editItem.foto_capa"
                  @click="openImageEditor('edit', 'foto_capa')"
                >
                  Editar imagem
                </v-btn>
                <div class="blog-manager__image-preview" v-if="editItem.foto_capa">
                  <img :src="resolveImage(editItem.foto_capa)" alt="Preview capa" @error="onImageError" />
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editItem.foto_topo" label="Foto topo" outlined dense></v-text-field>
                <v-btn
                  small
                  text
                  color="primary"
                  class="blog-manager__image-edit"
                  :disabled="!editItem.foto_topo"
                  @click="openImageEditor('edit', 'foto_topo')"
                >
                  Editar imagem
                </v-btn>
                <div class="blog-manager__image-preview" v-if="editItem.foto_topo">
                  <img :src="resolveImage(editItem.foto_topo)" alt="Preview topo" @error="onImageError" />
                </div>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="editItem.classif"
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
                  v-model="editItem.citie"
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
                  v-model="editItem.regiao"
                  :items="regioes"
                  item-text="label"
                  item-value="value"
                  label="Regiao"
                  dense
                  outlined
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editItem.url_video" label="URL video" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editItem.meta_description"
                  label="Meta description"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-switch v-model="editItem.ativo" label="Ativo" inset></v-switch>
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
            Tem certeza que deseja excluir o post
            <strong>{{ deleteItem.titulo }}</strong>?
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

    <v-dialog v-model="imageEditor.open" max-width="720px" persistent>
      <v-card>
        <v-card-title class="blog-manager__dialog-title">
          <span>Editar imagem</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeImageEditor">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <div v-if="imageEditor.error" class="blog-manager__image-error">
            {{ imageEditor.error }}
          </div>
          <div class="blog-manager__image-editor">
            <div class="blog-manager__image-editor-preview">
              <img :src="imageEditor.src" alt="Original" />
            </div>
            <div class="blog-manager__image-editor-crop">
              <div class="blog-manager__editor-label">Preview cortado</div>
              <div class="blog-manager__image-crop-preview">
                <img v-if="imageEditor.preview" :src="imageEditor.preview" alt="Corte" />
                <div v-else class="blog-manager__image-placeholder">Ajuste o corte ou proporcao</div>
              </div>
            </div>
          </div>
          <v-row class="mt-4">
            <v-col cols="12" md="4">
              <v-select
                v-model="imageEditor.aspect"
                :items="imageEditor.aspectOptions"
                label="Proporcao"
                dense
                outlined
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-slider
                v-model="imageEditor.offsetX"
                :min="0"
                :max="100"
                :step="1"
                label="Corte horizontal"
                thumb-label
              ></v-slider>
            </v-col>
            <v-col cols="12" md="4">
              <v-slider
                v-model="imageEditor.offsetY"
                :min="0"
                :max="100"
                :step="1"
                label="Corte vertical"
                thumb-label
              ></v-slider>
            </v-col>
          </v-row>
          <canvas ref="imageEditorCanvas" class="blog-manager__image-canvas"></canvas>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeImageEditor">Cancelar</v-btn>
          <v-btn color="primary" :disabled="!imageEditor.preview" @click="applyImageEdit">
            Aplicar corte
          </v-btn>
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
      dialogCreate: false,
      dialogEdit: false,
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
      pagination: {
        page: 1,
        perPage: 30,
        total: 0,
        lastPage: 1,
        perPageOptions: [10, 20, 30, 50, 100]
      },
      statsItem: {},
      createItem: {
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
        ativo: true,
        faq: [{ pergunta: '', resposta: '' }]
      },
      editItem: {
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
        ativo: true,
        faq: [{ pergunta: '', resposta: '' }]
      },
      deleteItem: {
        id: null,
        titulo: ''
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
      ],
      imageEditor: {
        open: false,
        src: '',
        target: null,
        offsetX: 50,
        offsetY: 50,
        aspect: '16:9',
        aspectOptions: ['16:9', '4:3', '1:1', '3:4', '9:16'],
        preview: '',
        error: '',
        naturalWidth: 0,
        naturalHeight: 0
      }
    }
  },
  computed: {
    cityNoDataText() {
      if (!this.citySearch || this.citySearch.length < 4) {
        return 'Digite 4 caracteres'
      }
      return 'Nenhuma cidade encontrada'
    }
  },
  watch: {
    'imageEditor.aspect'() {
      this.updateCropPreview()
    },
    'imageEditor.offsetX'() {
      this.updateCropPreview()
    },
    'imageEditor.offsetY'() {
      this.updateCropPreview()
    }
  },
  mounted() {
    this.fetchSupportLists()
    this.fetchPosts()
  },
  methods: {
    emptyItem() {
      return {
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
        ativo: true,
        faq: [{ pergunta: '', resposta: '' }]
      }
    },
    addFaq(targetItem) {
      targetItem.faq.push({ pergunta: '', resposta: '' })
    },
    removeFaq(targetItem, index) {
      targetItem.faq.splice(index, 1)
      if (targetItem.faq.length === 0) {
        targetItem.faq.push({ pergunta: '', resposta: '' })
      }
    },

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
    resolveImage(value) {
      if (!value) {
        return this.defaultImage
      }
      if (value.startsWith('data:')) {
        return value
      }
      if (value.startsWith('http')) {
        return value
      }
      return `${IMAGE_BASE}${value}`
    },
    proxyImage(value) {
      if (!value || value.startsWith('data:') || !value.startsWith('http')) {
        return value
      }
      return `${API_BASE}blog.php?request=proxy_image&url=${encodeURIComponent(value)}`
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
    openImageEditor(target, field) {
      const source = target === 'create' ? this.createItem[field] : this.editItem[field]
      if (!source) {
        return
      }
      this.imageEditor.open = true
      const resolved = this.resolveImage(source)
      this.imageEditor.src = this.proxyImage(resolved)
      this.imageEditor.target = { target, field }
      this.imageEditor.offsetX = 50
      this.imageEditor.offsetY = 50
      this.imageEditor.aspect = '16:9'
      this.imageEditor.preview = ''
      this.imageEditor.error = ''
      this.loadImageForEdit()
    },
    closeImageEditor() {
      this.imageEditor.open = false
      this.imageEditor.preview = ''
      this.imageEditor.error = ''
    },
    loadImageForEdit() {
      const img = new Image()
      img.crossOrigin = 'anonymous'
      img.onload = () => {
        this.imageEditor.naturalWidth = img.naturalWidth
        this.imageEditor.naturalHeight = img.naturalHeight
        this.updateCropPreview(img)
      }
      img.onerror = () => {
        this.imageEditor.error = 'Nao foi possivel carregar a imagem.'
      }
      img.src = this.imageEditor.src
    },
    parseAspect(value) {
      const parts = String(value).split(':')
      if (parts.length !== 2) {
        return 16 / 9
      }
      const w = Number(parts[0])
      const h = Number(parts[1])
      if (!w || !h) {
        return 16 / 9
      }
      return w / h
    },
    updateCropPreview(preloaded) {
      if (!this.imageEditor.open) {
        return
      }
      const img = preloaded || new Image()
      if (!preloaded) {
        img.crossOrigin = 'anonymous'
        img.onload = () => this.renderCrop(img)
        img.onerror = () => {
          this.imageEditor.error = 'Nao foi possivel carregar a imagem.'
        }
        img.src = this.imageEditor.src
        return
      }
      this.renderCrop(img)
    },
    renderCrop(img) {
      const canvas = this.$refs.imageEditorCanvas
      if (!canvas) {
        return
      }
      const ctx = canvas.getContext('2d')
      const aspect = this.parseAspect(this.imageEditor.aspect)
      const imgW = img.naturalWidth || this.imageEditor.naturalWidth
      const imgH = img.naturalHeight || this.imageEditor.naturalHeight
      if (!imgW || !imgH) {
        return
      }

      let cropW = imgW
      let cropH = cropW / aspect
      if (cropH > imgH) {
        cropH = imgH
        cropW = cropH * aspect
      }

      const maxX = Math.max(0, imgW - cropW)
      const maxY = Math.max(0, imgH - cropH)
      const offsetX = Math.min(100, Math.max(0, this.imageEditor.offsetX || 0)) / 100
      const offsetY = Math.min(100, Math.max(0, this.imageEditor.offsetY || 0)) / 100
      const sx = maxX * offsetX
      const sy = maxY * offsetY

      const maxOut = 1400
      let outW = cropW
      let outH = cropH
      if (outW > maxOut) {
        const scale = maxOut / outW
        outW *= scale
        outH *= scale
      }

      canvas.width = Math.round(outW)
      canvas.height = Math.round(outH)
      ctx.clearRect(0, 0, canvas.width, canvas.height)
      ctx.drawImage(img, sx, sy, cropW, cropH, 0, 0, outW, outH)

      try {
        this.imageEditor.preview = canvas.toDataURL('image/jpeg', 0.9)
        this.imageEditor.error = ''
      } catch (error) {
        this.imageEditor.error = 'Nao foi possivel gerar o corte (CORS).'
        this.imageEditor.preview = ''
      }
    },
    applyImageEdit() {
      if (!this.imageEditor.preview || !this.imageEditor.target) {
        return
      }
      const { target, field } = this.imageEditor.target
      if (target === 'create') {
        this.createItem[field] = this.imageEditor.preview
      } else {
        this.editItem[field] = this.imageEditor.preview
      }
      this.closeImageEditor()
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_posts')
      if (this.filters.titulo) params.append('filtro_titulo', this.filters.titulo)
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      if (this.filters.classif) params.append('filtro_classif', this.filters.classif)
      if (this.filters.citie) params.append('filtro_citie', this.filters.citie)
      if (this.filters.regiao) params.append('filtro_regiao', this.filters.regiao)
      params.append('page', String(this.pagination.page))
      params.append('per_page', String(this.pagination.perPage))
      return params.toString()
    },
    async fetchPosts() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}blog.php?${this.buildQuery()}`)
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
      this.pagination.page = 1
      this.fetchPosts()
    },
    applyFilters() {
      this.pagination.page = 1
      this.fetchPosts()
    },
    changePerPage() {
      this.pagination.page = 1
      this.fetchPosts()
    },
    openCreate() {
      this.createItem = this.emptyItem()
      this.dialogCreate = true
    },
    openEdit(item) {
    
      this.editItem = {
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
        ativo: item.is_active === true,
        faq: item.faq || [{ pergunta: '', resposta: '' }]
      }
      this.dialogEdit = true
    },
    openDelete(item) {
      this.deleteItem = {
        id: item.id,
        titulo: item.title || ''
      }
      this.dialogDelete = true
    },
    openStats(item) {
      this.statsItem = item || {}
      this.dialogStats = true
    },
    closeCreateDialog() {
      this.dialogCreate = false
    },
    closeEditDialog() {
      this.dialogEdit = false
    },
    async saveCreate() {
      await this.saveItem(this.createItem, false)
    },
    async saveEdit() {
      await this.saveItem(this.editItem, true)
    },
    async saveItem(item, isEdit) {
      if (!item.titulo || !item.data_post) {
        this.showMessage('Informe titulo e data.', 'warning')
        return
      }
      this.saving = true
      try {
        const request = isEdit ? 'atualizar_post' : 'criar_post'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}blog.php?request=${request}&id=${item.id}`
          : `${API_BASE}blog.php?request=${request}`

        const payload = {
          titulo: item.titulo,
          descritivo_blumar: item.descritivo_blumar,
          descritivo_be: item.descritivo_be,
          data_post: item.data_post,
          foto_capa: item.foto_capa,
          foto_topo: item.foto_topo,
          classif: item.classif,
          citie: item.citie,
          regiao: item.regiao,
          url_video: item.url_video,
          meta_description: item.meta_description,
          ativo: item.ativo,
           faq: (item.faq || []).filter(x => x.pergunta?.trim() || x.resposta?.trim())
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
        if (isEdit) {
          this.dialogEdit = false
        } else {
          this.dialogCreate = false
        }
        await this.fetchPosts()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.deleteItem.id) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}blog.php?request=excluir_post&id=${this.deleteItem.id}`,
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

.blog-manager__pagination {
  flex-wrap: wrap;
  gap: 12px;
}

.blog-manager__per-page {
  max-width: 160px;
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

.blog-manager__image-preview {
  margin-top: 8px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: #f8fafc;
  max-height: 180px;
}

.blog-manager__image-preview img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: cover;
}

.blog-manager__image-edit {
  margin-top: -6px;
  margin-left: -8px;
}

.blog-manager__image-editor {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  align-items: start;
}

.blog-manager__image-editor-preview img,
.blog-manager__image-crop-preview img {
  width: 100%;
  height: auto;
  border-radius: 12px;
  display: block;
  border: 1px solid rgba(148, 163, 184, 0.4);
}

.blog-manager__image-crop-preview {
  min-height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px dashed rgba(148, 163, 184, 0.5);
  padding: 12px;
}

.blog-manager__image-placeholder {
  font-size: 12px;
  color: #64748b;
}

.blog-manager__image-canvas {
  display: none;
}

.blog-manager__image-error {
  margin-bottom: 12px;
  color: #b91c1c;
  font-size: 13px;
  font-weight: 600;
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
