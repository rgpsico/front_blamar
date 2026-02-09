<template>
  <div class="newsletters-manager">
    <div class="newsletters-manager__header">
      <div>
        <h2>Newsletters</h2>
        <p>Cadastro, edicao e exclusao via API newslatters.php.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Nova Newsletter</v-btn>
      <v-btn outlined color="primary" @click="fetchNewsletters">Atualizar</v-btn>
    </div>

    <v-card class="newsletters-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field v-model="filters.nome" label="Nome" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo_web"
            :items="statusOptions"
            label="Ativo Web"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo_home"
            :items="statusOptions"
            label="Ativo Home"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo_passion"
            :items="statusOptions"
            label="Ativo Passion"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo_be"
            :items="statusOptions"
            label="Ativo BE"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.empresa"
            label="Empresa"
            dense
            outlined
            type="number"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model.number="filters.limit"
            label="Limite"
            type="number"
            min="1"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2" class="d-flex align-center">
          <v-btn color="primary" block @click="applyFilters">Aplicar</v-btn>
        </v-col>
        <v-col cols="12" md="2" class="d-flex align-center">
          <v-btn text block @click="resetFilters">Limpar</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        item-key="id"
        class="elevation-0"
      >
        <template slot="item.img_topo" slot-scope="{ item }">
          <v-avatar size="40" class="newsletters-manager__avatar">
            <img :src="resolveImage(item.img_topo)" :alt="item.nome" @error="onImageError" />
          </v-avatar>
        </template>
        <template slot="item.ativo_web" slot-scope="{ item }">
          <v-chip :color="item.ativo_web ? 'success' : 'grey'" small>
            {{ item.ativo_web ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.ativo_home" slot-scope="{ item }">
          <v-chip :color="item.ativo_home ? 'success' : 'grey'" small>
            {{ item.ativo_home ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.ativo_passion" slot-scope="{ item }">
          <v-chip :color="item.ativo_passion ? 'success' : 'grey'" small>
            {{ item.ativo_passion ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.ativo_be" slot-scope="{ item }">
          <v-chip :color="item.ativo_be ? 'success' : 'grey'" small>
            {{ item.ativo_be ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn icon small color="info" :href="viewUrl(item)" target="_blank">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn icon small color="secondary" @click="openDuplicate(item)">
            <v-icon>mdi-content-copy</v-icon>
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

    <v-dialog v-model="dialog" max-width="980px" persistent>
      <v-card>
        <v-card-title class="newsletters-manager__dialog-title">
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
                <v-text-field v-model="editedItem.nome" label="Nome" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.titulo" label="Titulo" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.data_extenso"
                  label="Data extenso"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="editedItem.empresa" label="Empresa" type="number" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="editedItem.lingua" label="Lingua" type="number" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editedItem.cor_pe" label="Cor pe" outlined dense></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.img_topo" label="Imagem topo" outlined dense></v-text-field>
                <div class="newsletters-manager__image-preview" v-if="editedItem.img_topo">
                  <img :src="resolveImage(editedItem.img_topo)" alt="Topo" @error="onImageError" />
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.alt_topo" label="Alt topo" outlined dense></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.foto_bloco" label="Foto bloco" outlined dense></v-text-field>
                <div class="newsletters-manager__image-preview" v-if="editedItem.foto_bloco">
                  <img :src="resolveImage(editedItem.foto_bloco)" alt="Foto bloco" @error="onImageError" />
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.alt_livre" label="Alt bloco" outlined dense></v-text-field>
              </v-col>

              <v-col cols="12">
                <div class="newsletters-manager__editor-label">Bloco livre</div>
                <TinyEditor v-model="editedItem.bloco_livre" :init="editorInit" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.chamada1_bloco" label="Chamada 1" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.chamada_bloco" label="Chamada 2" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.pdf" label="PDF" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editedItem.more_poducts" label="More products" outlined dense></v-text-field>
              </v-col>

              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.ativo_web" label="Ativo Web" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.ativo_home" label="Ativo Home" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.ativo_passion" label="Ativo Passion" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.ativo_be" label="Ativo BE" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-radio-group v-model="editedItem.is_header_italiano" row>
                  <v-radio :value="true" label="Header Italiano: Sim"></v-radio>
                  <v-radio :value="false" label="Header Italiano: Nao"></v-radio>
                </v-radio-group>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.titulo_ativo" label="Titulo ativo" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.recep" label="Recep" inset></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="editedItem.novo_layout" label="Novo layout" inset></v-switch>
              </v-col>

              <v-col cols="12">
                <div class="newsletters-manager__section-title">Destaques</div>
                <v-card outlined class="pa-3">
                  <v-row v-for="(destaque, index) in editedItem.destaques" :key="index" class="mb-4">
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="destaque.ordem" label="Ordem" type="number" outlined dense />
                    </v-col>
                    <v-col cols="12" md="5">
                      <v-text-field v-model="destaque.titulo" label="Titulo" outlined dense />
                    </v-col>
                    <v-col cols="12" md="5">
                      <v-text-field v-model="destaque.subtitulo" label="Subtitulo" outlined dense />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea v-model="destaque.descricao" label="Descricao" outlined dense rows="2" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="destaque.imagem" label="Imagem" outlined dense />
                      <div class="newsletters-manager__image-preview" v-if="destaque.imagem">
                        <img :src="resolveImage(destaque.imagem)" alt="Destaque" @error="onImageError" />
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="destaque.imagem_reduzida" label="Imagem reduzida" outlined dense />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field v-model="destaque.alt" label="Alt" outlined dense />
                    </v-col>
                    <v-col cols="12" md="8">
                      <v-text-field v-model="destaque.link_endereco" label="Link" outlined dense />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field v-model="destaque.img_link" label="Imagem link" outlined dense />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field v-model="destaque.layout" label="Layout" outlined dense />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field v-model="destaque.especialista" label="Especialista" outlined dense />
                    </v-col>
                    <v-col cols="12" md="3">
                      <v-switch v-model="destaque.link_ativo" label="Link ativo" inset></v-switch>
                    </v-col>
                    <v-col cols="12" md="3">
                      <v-switch v-model="destaque.exibir" label="Exibir" inset></v-switch>
                    </v-col>
                    <v-col cols="12" class="d-flex justify-end">
                      <v-btn icon color="error" @click="removeDestaque(index)">
                        <v-icon>mdi-delete</v-icon>
                      </v-btn>
                    </v-col>
                    <v-divider class="my-2"></v-divider>
                  </v-row>
                  <v-btn outlined color="primary" @click="addDestaque">
                    Adicionar destaque
                  </v-btn>
                </v-card>
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
            Tem certeza que deseja excluir a newsletter
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

    <v-dialog v-model="dialogDuplicate" max-width="420px">
      <v-card>
        <v-card-title>Confirmar duplicacao</v-card-title>
        <v-card-text>
          <v-alert type="info" border="left" colored-border>
            Deseja duplicar a newsletter
            <strong>{{ duplicateItemRef.nome }}</strong>?
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogDuplicate = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="confirmDuplicate">Duplicar</v-btn>
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
import TinyEditor from '@/components/shared/TinyEditor.vue'

const API_BASE = `${api.defaults.baseURL}/`
const IMAGE_BASE = 'https://www.blumar.com.br/'

export default {
  name: 'NewslettersManager',
  components: {
    TinyEditor
  },
  data() {
    return {
      loading: false,
      saving: false,
      dialog: false,
      dialogDelete: false,
      dialogDuplicate: false,
      editedIndex: -1,
      items: [],
      defaultImage: require('@/assets/default.png'),
      editedItem: this.emptyItem(),
      duplicateItemRef: {
        id: null,
        nome: ''
      },
      filters: {
        nome: '',
        ativo_web: 'all',
        ativo_home: 'all',
        ativo_passion: 'all',
        ativo_be: 'all',
        empresa: '',
        limit: 200
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'Topo', value: 'img_topo', sortable: false },
        { text: 'Nome', value: 'nome' },
        { text: 'Data', value: 'data_formatada' },
        { text: 'Empresa', value: 'empresa' },
        { text: 'Web', value: 'ativo_web', sortable: false },
        { text: 'Home', value: 'ativo_home', sortable: false },
        { text: 'Passion', value: 'ativo_passion', sortable: false },
        { text: 'BE', value: 'ativo_be', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      statusOptions: [
        { text: 'Ativo', value: 'true' },
        { text: 'Inativo', value: 'false' },
        { text: 'Todos', value: 'all' }
      ],
      editorInit: {
        height: 240
      }
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Nova Newsletter' : 'Editar Newsletter'
    }
  },
  mounted() {
    this.fetchNewsletters()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    emptyItem() {
      return {
        id: null,
        nome: '',
        titulo: '',
        data_extenso: '',
        img_topo: '',
        alt_topo: '',
        bloco_livre: '',
        foto_bloco: '',
        alt_livre: '',
        chamada1_bloco: '',
        chamada_bloco: '',
        pdf: '',
        more_poducts: '',
        empresa: '',
        ativo_web: true,
        ativo_home: false,
        titulo_ativo: true,
        recep: false,
        novo_layout: false,
        ativo_passion: false,
        ativo_be: false,
        is_header_italiano: false,
        cor_pe: 'F9020E',
        lingua: 2,
        destaques: []
      }
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    resolveImage(value) {
      if (!value) {
        return this.defaultImage
      }
      if (value.startsWith('http') || value.startsWith('data:')) {
        return value
      }
      return `${IMAGE_BASE}${value}`
    },
    onImageError(event) {
      event.target.src = this.defaultImage
    },
    buildQuery() {
      const params = new URLSearchParams({ request: 'listar_news' })
      if (this.filters.nome) params.set('filtro_nome', this.filters.nome)
      if (this.filters.ativo_web) params.set('filtro_ativo_web', this.filters.ativo_web)
      if (this.filters.ativo_home) params.set('filtro_ativo_home', this.filters.ativo_home)
      if (this.filters.ativo_passion) params.set('filtro_ativo_passion', this.filters.ativo_passion)
      if (this.filters.ativo_be) params.set('filtro_ativo_be', this.filters.ativo_be)
      if (this.filters.empresa) params.set('filtro_empresa', this.filters.empresa)
      if (this.filters.limit) params.set('limit', String(this.filters.limit))
      return params.toString()
    },
    viewUrl(item) {
      const id = item?.id || item?.pk_news || ''
      return `https://www.blumar.com.br/client_area/newsletter_blumar.php?pk_news=${id}`
    },
    async fetchNewsletters() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}newslatters.php?${this.buildQuery()}`)
        const data = await response.json()
        this.items = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      this.fetchNewsletters()
    },
    resetFilters() {
      this.filters = {
        nome: '',
        ativo_web: 'all',
        ativo_home: 'all',
        ativo_passion: 'all',
        ativo_be: 'all',
        empresa: '',
        limit: 200
      }
      this.fetchNewsletters()
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = this.emptyItem()
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = this.emptyItem()
      this.dialog = true
      try {
        const response = await fetch(`${API_BASE}newslatters.php?request=buscar_news&id=${item.id}`)
        const data = await response.json()
        this.editedItem = this.mapApiItem(data)
      } catch (error) {
        this.showMessage(`Erro ao carregar detalhes: ${error.message}`, 'error')
      }
    },
    openDelete(item) {
      this.editedItem = {
        id: item.id,
        nome: item.nome || ''
      }
      this.dialogDelete = true
    },
    openDuplicate(item) {
      this.duplicateItemRef = {
        id: item?.id || item?.pk_news || null,
        nome: item?.nome || ''
      }
      this.dialogDuplicate = true
    },
    closeDialog() {
      this.dialog = false
    },
    mapApiItem(data) {
      const base = this.emptyItem()
      if (!data) {
        return base
      }
      return {
        ...base,
        id: data.id || data.pk_news || null,
        nome: data.nome || '',
        titulo: data.titulo || '',
        data_extenso: data.data_extenso || '',
        img_topo: data.img_topo || '',
        alt_topo: data.alt_topo || '',
        bloco_livre: data.bloco_livre || '',
        foto_bloco: data.foto_bloco || '',
        alt_livre: data.alt_livre || '',
        chamada1_bloco: data.chamada1_bloco || '',
        chamada_bloco: data.chamada_bloco || '',
        pdf: data.pdf || '',
        more_poducts: data.more_poducts || '',
        empresa: data.empresa || '',
        ativo_web: Boolean(data.ativo_web),
        ativo_home: Boolean(data.ativo_home),
        titulo_ativo: Boolean(data.titulo_ativo),
        recep: Boolean(data.recep),
        novo_layout: Boolean(data.novo_layout),
        ativo_passion: Boolean(data.ativo_passion),
        ativo_be: Boolean(data.ativo_be),
        is_header_italiano: Boolean(data.is_header_italiano),
        cor_pe: data.cor_pe || 'F9020E',
        lingua: data.lingua || 2,
        destaques: Array.isArray(data.destaques)
          ? data.destaques.map(this.mapDestaque)
          : []
      }
    },
    mapDestaque(destaque) {
      return {
        ordem: destaque.ordem || destaque.dia_conteudo || 1,
        titulo: destaque.titulo || destaque.titulo_news || '',
        subtitulo: destaque.subtitulo || '',
        descricao: destaque.descricao || destaque.descritivo_conteudo || '',
        imagem: destaque.imagem || destaque.img1_conteudo || '',
        imagem_reduzida: destaque.imagem_reduzida || destaque.img_reduz || '',
        alt: destaque.alt || '',
        link_endereco: destaque.link_endereco || '',
        link_ativo: Boolean(destaque.link_ativo),
        img_link: destaque.img_link || '',
        layout: destaque.layout || destaque.layout_news || '1',
        especialista: destaque.especialista || destaque.expert || '',
        exibir: destaque.exibir !== undefined ? Boolean(destaque.exibir) : true
      }
    },
    addDestaque() {
      this.editedItem.destaques.push({
        ordem: this.editedItem.destaques.length + 1,
        titulo: '',
        subtitulo: '',
        descricao: '',
        imagem: '',
        imagem_reduzida: '',
        alt: '',
        link_endereco: '',
        link_ativo: false,
        img_link: '',
        layout: '1',
        especialista: '',
        exibir: true
      })
    },
    removeDestaque(index) {
      this.editedItem.destaques.splice(index, 1)
    },
    serializeDestaques(list) {
      return (list || []).map(item => ({
        dia_conteudo: item.ordem || 1,
        titulo_news: item.titulo || '',
        subtitulo: item.subtitulo || '',
        descritivo_conteudo: item.descricao || '',
        img1_conteudo: item.imagem || '',
        img_reduz: item.imagem_reduzida || '',
        alt: item.alt || '',
        link_endereco: item.link_endereco || '',
        link_ativo: Boolean(item.link_ativo),
        img_link: item.img_link || '',
        layout_news: item.layout || '1',
        expert: item.especialista || '',
        exibe_destaque: item.exibir !== undefined ? Boolean(item.exibir) : true
      }))
    },
    async save() {
      if (!this.editedItem.nome) {
        this.showMessage('Informe o nome da newsletter.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'atualizar_news' : 'criar_news'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}newslatters.php?request=${request}&id=${this.editedItem.id}`
          : `${API_BASE}newslatters.php?request=${request}`

        const payload = {
          nome: this.editedItem.nome,
          titulo: this.editedItem.titulo,
          data_extenso: this.editedItem.data_extenso,
          img_topo: this.editedItem.img_topo,
          alt_topo: this.editedItem.alt_topo,
          bloco_livre: this.editedItem.bloco_livre,
          foto_bloco: this.editedItem.foto_bloco,
          alt_livre: this.editedItem.alt_livre,
          chamada1_bloco: this.editedItem.chamada1_bloco,
          chamada_bloco: this.editedItem.chamada_bloco,
          pdf: this.editedItem.pdf,
          more_poducts: this.editedItem.more_poducts,
          empresa: this.editedItem.empresa,
          ativo_web: this.editedItem.ativo_web,
          ativo_home: this.editedItem.ativo_home,
          titulo_ativo: this.editedItem.titulo_ativo,
          recep: this.editedItem.recep,
          novo_layout: this.editedItem.novo_layout,
          ativo_passion: this.editedItem.ativo_passion,
          ativo_be: this.editedItem.ativo_be,
          is_header_italiano: this.editedItem.is_header_italiano,
          cor_pe: this.editedItem.cor_pe,
          lingua: this.editedItem.lingua,
          destaques: this.serializeDestaques(this.editedItem.destaques)
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
        this.showMessage(isEdit ? 'Newsletter atualizada.' : 'Newsletter criada.')
        this.dialog = false
        await this.fetchNewsletters()
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
          `${API_BASE}newslatters.php?request=excluir_news&id=${this.editedItem.id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Newsletter excluida.')
        this.dialogDelete = false
        await this.fetchNewsletters()
      } catch (error) {
        this.showMessage(`Erro ao excluir: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDuplicate() {
      const id = this.duplicateItemRef?.id
      if (!id) {
        this.showMessage('Nao foi possivel identificar a newsletter.', 'warning')
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}newslatters.php?request=duplicar_news&id=${id}`,
          { method: 'POST', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao duplicar')
        }
        this.showMessage('Newsletter duplicada.')
        this.dialogDuplicate = false
        await this.fetchNewsletters()
      } catch (error) {
        this.showMessage(`Erro ao duplicar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.newsletters-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.newsletters-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.newsletters-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.newsletters-manager__filters {
  padding: 16px;
}

.newsletters-manager__dialog-title {
  display: flex;
  align-items: center;
}

.newsletters-manager__avatar img {
  object-fit: cover;
}

.newsletters-manager__image-preview {
  margin-top: 8px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: #f8fafc;
  max-height: 180px;
}

.newsletters-manager__image-preview img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: cover;
}

.newsletters-manager__editor-label {
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  color: #475569;
}

.newsletters-manager__section-title {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #0f172a;
}
</style>
