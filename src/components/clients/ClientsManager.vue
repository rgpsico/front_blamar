<template>
  <div class="clients-manager">
    <div class="clients-manager__header">
      <div>
        <h2>Clientes</h2>
        <p>Cadastro e gestao de clientes com filtros e paginacao.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Cliente</v-btn>
      <v-btn outlined color="primary" class="mr-2" :loading="exporting" :disabled="exporting" @click="exportFiltered">
        Exportar CSV
      </v-btn>
      <v-btn outlined color="primary" @click="fetchClients">Atualizar</v-btn>
    </div>

    <v-card class="clients-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.nome"
            label="Buscar nome"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.login"
            label="Login"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.ativo"
            :items="ativoOptions"
            item-text="text"
            item-value="value"
            label="Status"
            dense
            outlined
            @change="scheduleFetch"
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model="filters.nivel"
            label="Nivel/Depto"
            dense
            outlined
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="clients"
        :loading="loading"
        :options.sync="tableOptions"
        :server-items-length="total"
        item-key="id"
        class="elevation-0"
        :footer-props="{
          'items-per-page-options': [10, 20, 50, 100],
          showFirstLastPage: true
        }"
      >
        <template slot="item.ativo" slot-scope="{ item }">
          <v-chip :color="item.ativo ? 'success' : 'error'" small text-color="white">
            {{ item.ativo ? 'Ativo' : 'Inativo' }}
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

    <v-dialog v-model="dialog" max-width="960px" persistent>
      <v-card>
        <v-card-title class="clients-manager__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-tabs v-model="activeTab" grow>
            <v-tab>Basico</v-tab>
            <v-tab>Acesso</v-tab>
            <v-tab>Tarifario 1</v-tab>
            <v-tab>Tarifario 2</v-tab>
            <v-tab>Extras</v-tab>
          </v-tabs>
          <v-tabs-items v-model="activeTab">
            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.nome" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.login" label="Login" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.email" label="Email" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.nivel" label="Nivel/Depto" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3" class="d-flex align-center">
                    <v-switch v-model="editedItem.ativo" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-form>
            </v-tab-item>
            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.emp" label="Empresa" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.lang" label="Idioma" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.pass" label="Senha" type="password" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.root_htl" label="Root HTL" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.root_htl2" label="Root HTL 2" outlined dense></v-text-field>
                  </v-col>
                </v-row>
              </v-form>
            </v-tab-item>
            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.mkp_food" label="MKP Food" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.mkp_srv" label="MKP Srv" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.mkp_htl" label="MKP Htl" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.mkp_eco" label="MKP Eco" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_ny" label="MKP NY" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_carn" label="MKP Carn" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_winn" label="MKP Winn" type="number" outlined dense></v-text-field>
                  </v-col>
                </v-row>
              </v-form>
            </v-tab-item>
            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.mkp_food2" label="MKP Food 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.mkp_srv2" label="MKP Srv 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.mkp_htl2" label="MKP Htl 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.mkp_eco2" label="MKP Eco 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_ny2" label="MKP NY 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_carn2" label="MKP Carn 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_winn2" label="MKP Winn 2" type="number" outlined dense></v-text-field>
                  </v-col>
                </v-row>
              </v-form>
            </v-tab-item>
            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.wooba" label="Wooba" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.freq_pgto2" label="Freq. Pgto 2" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.limite_cred2" label="Limite Cred 2" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.de_freq_pgto2" label="De Pgto 2" type="date" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.ate_freq_pgto2" label="Ate Pgto 2" type="date" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.limite_cred_file" label="Limite Cred File" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.logo" label="Logo" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.logo_placa" label="Logo Placa" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="editedItem.descricao_tar" label="Descricao Tarifario" outlined dense rows="2"></v-textarea>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="editedItem.obs_guia" label="Obs Guia" outlined dense rows="2"></v-textarea>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.conteudo_riolife" label="Conteudo Riolife" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.desativar_tarifario" label="Desativar Tarifario" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.extranet" label="Extranet" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.ativo_virtuoso" label="Ativo Virtuoso" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.ativo_cote" label="Ativo Cote" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.bco_img" label="Banco Imagem" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.usa_allotment" label="Usa Allotment" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-switch v-model="editedItem.consome_allotment" label="Consome Allotment" inset></v-switch>
                  </v-col>
                </v-row>
              </v-form>
            </v-tab-item>
          </v-tabs-items>
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
            Tem certeza que deseja excluir o cliente
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
import axios from 'axios'
import api from '@/services/api'

const API_BASE = `${api.defaults.baseURL}/`

export default {
  name: 'ClientsManager',
  data() {
    return {
      loading: false,
      saving: false,
      exporting: false,
      clients: [],
      total: 0,
      filterTimer: null,
      tableOptions: {
        page: 1,
        itemsPerPage: 20,
        sortBy: ['nome'],
        sortDesc: [false]
      },
      filters: {
        nome: '',
        login: '',
        ativo: 'all',
        nivel: ''
      },
      dialog: false,
      dialogDelete: false,
      editedIndex: -1,
      editedItem: {},
      activeTab: 0,
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      ativoOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Ativos', value: 'true' },
        { text: 'Inativos', value: 'false' }
      ],
      headers: [
        { text: 'ID', value: 'id', width: 70 },
        { text: 'Nome', value: 'nome' },
        { text: 'Login', value: 'login' },
        { text: 'Email', value: 'email', sortable: false },
        { text: 'Nivel', value: 'nivel', width: 90 },
        { text: 'Ativo', value: 'ativo', width: 90, sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end', width: 120 }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Cliente' : 'Editar Cliente'
    }
  },
  watch: {
    tableOptions: {
      deep: true,
      handler() {
        this.fetchClients()
      }
    }
  },
  mounted() {
    this.editedItem = this.defaultClient()
    this.fetchClients()
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    defaultClient() {
      return {
        id: null,
        nome: '',
        login: '',
        email: '',
        nivel: null,
        ativo: true,
        emp: '',
        lang: '',
        pass: '',
        root_htl: '',
        root_htl2: '',
        mkp_food: null,
        mkp_srv: null,
        mkp_htl: null,
        mkp_eco: null,
        mkp_ny: null,
        mkp_carn: null,
        mkp_winn: null,
        mkp_food2: null,
        mkp_srv2: null,
        mkp_htl2: null,
        mkp_eco2: null,
        mkp_ny2: null,
        mkp_carn2: null,
        mkp_winn2: null,
        wooba: '',
        freq_pgto2: '',
        de_freq_pgto2: '',
        ate_freq_pgto2: '',
        limite_cred2: '',
        limite_cred_file: '',
        conteudo_riolife: false,
        descricao_tar: '',
        obs_guia: '',
        logo: '',
        logo_placa: '',
        desativar_tarifario: false,
        extranet: false,
        ativo_virtuoso: false,
        ativo_cote: false,
        bco_img: false,
        usa_allotment: false,
        consome_allotment: false
      }
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
        const alreadyFirstPage = this.tableOptions.page === 1
        this.tableOptions.page = 1
        if (alreadyFirstPage) {
          this.fetchClients()
        }
      }, 400)
    },
    buildListParams(overrides = {}) {
      const params = new URLSearchParams({ request: 'listar_clientes_paginate' })
      const { page, itemsPerPage, sortBy, sortDesc } = {
        ...this.tableOptions,
        ...overrides
      }
      params.set('page', String(page || 1))
      params.set('per_page', String(itemsPerPage || 20))

      const sortMap = {
        nome: 'nome',
        login: 'login',
        id: 'id',
        nivel: 'nivel'
      }
      const sortKey = sortBy && sortBy.length ? sortBy[0] : 'nome'
      params.set('ordem', sortMap[sortKey] || 'nome')
      params.set('direcao', sortDesc && sortDesc[0] ? 'DESC' : 'ASC')

      if (this.filters.nome) {
        params.set('filtro_nome', this.filters.nome)
      }
      if (this.filters.login) {
        params.set('filtro_login', this.filters.login)
      }
      if (this.filters.nivel) {
        params.set('filtro_depto', this.filters.nivel)
      }
      if (this.filters.ativo !== 'all') {
        params.set('filtro_ativo', this.filters.ativo)
      }
      return params
    },
    csvEscape(value, delimiter) {
      if (value === null || value === undefined) {
        return ''
      }
      const text = String(value)
      const needsQuotes =
        text.includes(delimiter) || text.includes('"') || text.includes('\n') || text.includes('\r')
      const escaped = text.replace(/"/g, '""')
      return needsQuotes ? `"${escaped}"` : escaped
    },
    formatExportValue(key, value) {
      if (key === 'ativo') {
        if (value === true || value === 'true' || value === 1 || value === '1') {
          return 'Ativo'
        }
        if (value === false || value === 'false' || value === 0 || value === '0') {
          return 'Inativo'
        }
        return value
      }
      return value
    },
    toCsv(items, columns, delimiter = ';') {
      const header = columns.map((col) => this.csvEscape(col.label, delimiter)).join(delimiter)
      const rows = items.map((item) => {
        const values = columns.map((col) => {
          const raw = this.formatExportValue(col.key, item[col.key])
          return this.csvEscape(raw, delimiter)
        })
        return values.join(delimiter)
      })
      return [`\uFEFF${header}`, ...rows].join('\r\n')
    },
    downloadCsv(content, filename) {
      const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', filename)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    },
    async exportFiltered() {
      if (this.exporting) {
        return
      }
      this.exporting = true
      try {
        const exportPerPage = 500
        let page = 1
        let total = 0
        let collected = []
        let keepGoing = true
        while (keepGoing) {
          const params = this.buildListParams({ page, itemsPerPage: exportPerPage })
          const response = await axios.get(`${API_BASE}clientes.php?${params.toString()}`)
          const data = response.data || {}
          const pageItems = Array.isArray(data.data) ? data.data : Array.isArray(data) ? data : []

          if (page === 1) {
            total = Number(data.total || pageItems.length || 0)
          }

          if (!pageItems.length) {
            keepGoing = false
            continue
          }

          collected = collected.concat(pageItems)
          if (total && collected.length >= total) {
            keepGoing = false
            continue
          }
          page += 1
        }

        if (!collected.length) {
          this.showMessage('Nenhum cliente encontrado para exportacao.', 'warning')
          return
        }

        const columns = [
          { key: 'id', label: 'ID' },
          { key: 'nome', label: 'Nome' },
          { key: 'login', label: 'Login' },
          { key: 'email', label: 'Email' },
          { key: 'nivel', label: 'Nivel' },
          { key: 'ativo', label: 'Status' }
        ]
        const csv = this.toCsv(collected, columns)
        const dateStamp = new Date().toISOString().slice(0, 10)
        this.downloadCsv(csv, `clientes_${dateStamp}.csv`)
        this.showMessage(`Exportados ${collected.length} clientes.`)
      } catch (error) {
        this.showMessage(`Erro ao exportar: ${error.message}`, 'error')
      } finally {
        this.exporting = false
      }
    },
    async fetchClients() {
      this.loading = true
      try {
        const params = this.buildListParams()
        const response = await axios.get(`${API_BASE}clientes.php?${params.toString()}`)
        const data = response.data || {}
        this.clients = Array.isArray(data.data) ? data.data : Array.isArray(data) ? data : []
        this.total = Number(data.total || this.clients.length || 0)
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = this.defaultClient()
      this.activeTab = 0
      this.dialog = true
    },
    openEdit(item) {
      this.editedIndex = this.clients.indexOf(item)
      this.editedItem = {
        ...this.defaultClient(),
        ...item
      }
      this.activeTab = 0
      this.dialog = true
    },
    openDelete(item) {
      this.editedItem = {
        id: item.id,
        nome: item.nome
      }
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    payloadFromForm() {
      return {
        id: this.editedItem.id,
        nome: this.editedItem.nome,
        login: this.editedItem.login,
        email: this.editedItem.email || null,
        nivel: this.editedItem.nivel,
        ativo: this.editedItem.ativo,
        emp: this.editedItem.emp || null,
        lang: this.editedItem.lang || null,
        pass: this.editedItem.pass || null,
        root_htl: this.editedItem.root_htl || null,
        root_htl2: this.editedItem.root_htl2 || null,
        mkp_food: this.editedItem.mkp_food,
        mkp_srv: this.editedItem.mkp_srv,
        mkp_htl: this.editedItem.mkp_htl,
        mkp_eco: this.editedItem.mkp_eco,
        mkp_ny: this.editedItem.mkp_ny,
        mkp_carn: this.editedItem.mkp_carn,
        mkp_winn: this.editedItem.mkp_winn,
        mkp_food2: this.editedItem.mkp_food2,
        mkp_srv2: this.editedItem.mkp_srv2,
        mkp_htl2: this.editedItem.mkp_htl2,
        mkp_eco2: this.editedItem.mkp_eco2,
        mkp_ny2: this.editedItem.mkp_ny2,
        mkp_carn2: this.editedItem.mkp_carn2,
        mkp_winn2: this.editedItem.mkp_winn2,
        wooba: this.editedItem.wooba || null,
        freq_pgto2: this.editedItem.freq_pgto2 || null,
        de_freq_pgto2: this.editedItem.de_freq_pgto2 || null,
        ate_freq_pgto2: this.editedItem.ate_freq_pgto2 || null,
        limite_cred2: this.editedItem.limite_cred2 || null,
        limite_cred_file: this.editedItem.limite_cred_file || null,
        conteudo_riolife: this.editedItem.conteudo_riolife,
        descricao_tar: this.editedItem.descricao_tar || null,
        obs_guia: this.editedItem.obs_guia || null,
        logo: this.editedItem.logo || null,
        logo_placa: this.editedItem.logo_placa || null,
        desativar_tarifario: this.editedItem.desativar_tarifario,
        extranet: this.editedItem.extranet,
        ativo_virtuoso: this.editedItem.ativo_virtuoso,
        ativo_cote: this.editedItem.ativo_cote,
        bco_img: this.editedItem.bco_img,
        usa_allotment: this.editedItem.usa_allotment,
        consome_allotment: this.editedItem.consome_allotment
      }
    },
    async save() {
      if (!this.editedItem.nome || !this.editedItem.login) {
        this.showMessage('Nome e login sao obrigatorios.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'editar_cliente' : 'salvar_cliente'
        const method = isEdit ? 'PUT' : 'POST'
        const url = `${API_BASE}clientes.php?request=${request}`
        const payload = this.payloadFromForm()

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
        this.showMessage(isEdit ? 'Cliente atualizado.' : 'Cliente criado.')
        this.dialog = false
        await this.fetchClients()
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
          `${API_BASE}clientes.php?request=excluir_cliente&id=${this.editedItem.id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Cliente excluido.')
        this.dialogDelete = false
        await this.fetchClients()
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
.clients-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.clients-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.clients-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.clients-manager__filters {
  padding: 16px;
}

.clients-manager__dialog-title {
  display: flex;
  align-items: center;
}
</style>
