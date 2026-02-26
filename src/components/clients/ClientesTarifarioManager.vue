<template>
  <div class="clientes-tarifario">
    <div class="clientes-tarifario__header">
      <div>
        <h2>Clientes Tarifario</h2>
        <p>Cadastro e manutencao de clientes do tarifario.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Cliente</v-btn>
      <v-btn outlined color="primary" @click="fetchClientes">Atualizar</v-btn>
    </div>

    <v-card class="clientes-tarifario__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field v-model="filters.nome" label="Buscar nome" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.mneu" label="MNEU" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field v-model="filters.login" label="Login" dense outlined></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.ativo"
            :items="ativoOptions"
            label="Ativo"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model.number="filters.limit"
            label="Limite"
            type="number"
            dense
            outlined
            min="10"
            max="500"
          ></v-text-field>
        </v-col>
      </v-row>
      <div class="clientes-tarifario__filter-actions">
        <v-btn outlined color="primary" @click="applyFilters">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        item-key="mneu_cli"
        class="elevation-0"
      >
        <template slot="item.ativo" slot-scope="{ item }">
          <v-chip :color="item.ativo ? 'success' : 'grey'" small>
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

    <v-dialog v-model="dialog" max-width="980px" persistent>
      <v-card>
        <v-card-title class="clientes-tarifario__dialog-title">
          <span>{{ dialogTitle }}</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-tabs v-model="activeTab" grow>
            <v-tab>Basico</v-tab>
            <v-tab>Tarifario</v-tab>
            <v-tab>Flags</v-tab>
            <v-tab>Extras</v-tab>
          </v-tabs>
          <v-tabs-items v-model="activeTab" class="mt-4">
            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="editedItem.mneu_cli" label="MNEU" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.nome_cli" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3" class="d-flex align-center">
                    <v-switch v-model="editedItem.ativo" label="Ativo" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.email" label="Email" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.login" label="Login" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.pass" label="Senha" type="password" outlined dense></v-text-field>
                  </v-col>
                </v-row>
              </v-form>
            </v-tab-item>

            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_htl" label="MKP Htl" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_srv" label="MKP Srv" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_food" label="MKP Food" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_htl2" label="MKP Htl 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_srv2" label="MKP Srv 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.mkp_food2" label="MKP Food 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.desativar_tarifario" label="Desativar Tarifario" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.ativo2tar" label="Ativo 2 Tar" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="12">
                    <v-textarea v-model="editedItem.descricao_tar" label="Descricao Tarifario" outlined dense rows="2"></v-textarea>
                  </v-col>
                </v-row>
              </v-form>
            </v-tab-item>

            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.ativo_cote" label="Ativo Cote" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.ativo_virtuoso" label="Ativo Virtuoso" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.conteudo_riolife" label="Conteudo Riolife" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.bco_img" label="Banco Imagem" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.avulso" label="Avulso" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.extranet" label="Extranet" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.usa_allotment" label="Usa Allotment" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-switch v-model="editedItem.consome_allotment" label="Consome Allotment" inset></v-switch>
                  </v-col>
                </v-row>
              </v-form>
            </v-tab-item>

            <v-tab-item>
              <v-form>
                <v-row>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.cod_agrup" label="Cod Agrup" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="editedItem.lang" label="Idioma" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="editedItem.emp" label="Empresa" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="editedItem.fk_depto" label="Depto" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.root_htl" label="Root Htl" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="editedItem.root_htl2" label="Root Htl 2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.wooba" label="Wooba" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.freq_pgto2" label="Freq. Pgto 2" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.limite_cred2" label="Limite Cred 2" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.limite_cred_file" label="Limite Cred File" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.de_freq_pgto2" label="De Pgto 2" type="date" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.ate_freq_pgto2" label="Ate Pgto 2" type="date" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.logo" label="Logo" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.logo_placa" label="Logo Placa" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="editedItem.obs_guia" label="Obs Guia" outlined dense rows="2"></v-textarea>
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
            <strong>{{ editedItem.nome_cli || editedItem.mneu_cli }}</strong>?
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
  pk_cad_cli: null,
  mneu_cli: '',
  nome_cli: '',
  ativo: true,
  email: '',
  login: '',
  pass: '',
  ativo_cote: false,
  usa_allotment: false,
  consome_allotment: false,
  avulso: false,
  ativo_virtuoso: false,
  conteudo_riolife: false,
  bco_img: false,
  extranet: false,
  desativar_tarifario: false,
  ativo2tar: false,
  mkp_htl: null,
  mkp_srv: null,
  mkp_food: null,
  mkp_htl2: null,
  mkp_srv2: null,
  mkp_food2: null,
  descricao_tar: '',
  obs_guia: '',
  cod_agrup: null,
  lang: '',
  emp: '',
  root_htl: null,
  root_htl2: null,
  fk_depto: null,
  wooba: '',
  limite_cred2: '',
  freq_pgto2: '',
  de_freq_pgto2: '',
  ate_freq_pgto2: '',
  limite_cred_file: '',
  logo: '',
  logo_placa: ''
})

export default {
  name: 'ClientesTarifarioManager',
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
        mneu: '',
        login: '',
        ativo: null,
        limit: 200
      },
      editedIndex: -1,
      editedItem: blankItem(),
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      ativoOptions: [
        { text: 'Todos', value: null },
        { text: 'Ativo', value: 'true' },
        { text: 'Inativo', value: 'false' }
      ],
      headers: [
        { text: 'MNEU', value: 'mneu_cli', width: 80 },
        { text: 'Nome', value: 'nome_cli' },
        { text: 'Email', value: 'email', sortable: false },
        { text: 'Login', value: 'login', sortable: false },
        { text: 'Ativo', value: 'ativo', sortable: false, width: 90 },
        { text: 'MKP Htl', value: 'mkp_htl_v', sortable: false, width: 100 },
        { text: 'MKP Srv', value: 'mkp_srv_v', sortable: false, width: 100 },
        { text: 'MKP Food', value: 'mkp_food_v', sortable: false, width: 100 },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end', width: 120 }
      ]
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Cliente' : 'Editar Cliente'
    }
  },
  mounted() {
    this.fetchClientes()
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
    normalizeBool(value) {
      if (value === 't' || value === 'true' || value === 1 || value === '1') return true
      if (value === 'f' || value === 'false' || value === 0 || value === '0') return false
      return value
    },
    normalizeItem(item) {
      const normalized = { ...item }
      ;[
        'ativo',
        'ativo_cote',
        'usa_allotment',
        'consome_allotment',
        'avulso',
        'ativo_virtuoso',
        'conteudo_riolife',
        'bco_img',
        'extranet',
        'desativar_tarifario',
        'ativo2tar'
      ].forEach((key) => {
        if (key in normalized) {
          normalized[key] = this.normalizeBool(normalized[key])
        }
      })

      normalized.mkp_htl_v = normalized.mkp_htl_v ?? normalized.mkp_htl
      normalized.mkp_srv_v = normalized.mkp_srv_v ?? normalized.mkp_srv
      normalized.mkp_food_v = normalized.mkp_food_v ?? normalized.mkp_food
      return normalized
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_clientes')
      if (this.filters.nome) params.append('filtro_nome', this.filters.nome)
      if (this.filters.mneu) params.append('filtro_mneu', this.filters.mneu)
      if (this.filters.login) params.append('filtro_login', this.filters.login)
      if (this.filters.ativo !== null && this.filters.ativo !== '') params.append('ativo', this.filters.ativo)
      const limit = Number(this.filters.limit) || 200
      params.append('limit', String(Math.max(10, Math.min(500, limit))))
      return params.toString()
    },
    async fetchClientes() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}api_tarifario_cadastro_clientes.php?${this.buildQuery()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.items = Array.isArray(data) ? data.map(this.normalizeItem) : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      this.fetchClientes()
    },
    resetFilters() {
      this.filters = {
        nome: '',
        mneu: '',
        login: '',
        ativo: null,
        limit: 200
      }
      this.fetchClientes()
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = blankItem()
      this.activeTab = 0
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = { ...blankItem(), ...this.normalizeItem(item) }
      this.activeTab = 0
      this.dialog = true

      if (item && item.mneu_cli) {
        await this.fetchClienteDetail(item.mneu_cli)
      }
    },
    openDelete(item) {
      this.editedItem = this.normalizeItem(item)
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    async fetchClienteDetail(mneu) {
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}api_tarifario_cadastro_clientes.php?request=buscar_cliente&mneu_cli=${encodeURIComponent(mneu)}`,
          { headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result && result.error) {
          throw new Error(result.error)
        }
        this.editedItem = { ...blankItem(), ...this.normalizeItem(result || {}) }
      } catch (error) {
        this.showMessage(`Erro ao carregar cliente: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    payloadFromForm() {
      return {
        pk_cad_cli: this.editedItem.pk_cad_cli,
        mneu_cli: this.editedItem.mneu_cli,
        nome_cli: this.editedItem.nome_cli,
        ativo: this.editedItem.ativo,
        email: this.editedItem.email,
        login: this.editedItem.login,
        pass: this.editedItem.pass,
        ativo_cote: this.editedItem.ativo_cote,
        usa_allotment: this.editedItem.usa_allotment,
        consome_allotment: this.editedItem.consome_allotment,
        avulso: this.editedItem.avulso,
        ativo_virtuoso: this.editedItem.ativo_virtuoso,
        conteudo_riolife: this.editedItem.conteudo_riolife,
        bco_img: this.editedItem.bco_img,
        extranet: this.editedItem.extranet,
        desativar_tarifario: this.editedItem.desativar_tarifario,
        ativo2tar: this.editedItem.ativo2tar,
        mkp_htl: this.editedItem.mkp_htl,
        mkp_srv: this.editedItem.mkp_srv,
        mkp_food: this.editedItem.mkp_food,
        mkp_htl2: this.editedItem.mkp_htl2,
        mkp_srv2: this.editedItem.mkp_srv2,
        mkp_food2: this.editedItem.mkp_food2,
        descricao_tar: this.editedItem.descricao_tar,
        obs_guia: this.editedItem.obs_guia,
        cod_agrup: this.editedItem.cod_agrup,
        lang: this.editedItem.lang,
        emp: this.editedItem.emp,
        root_htl: this.editedItem.root_htl,
        root_htl2: this.editedItem.root_htl2,
        fk_depto: this.editedItem.fk_depto,
        wooba: this.editedItem.wooba,
        limite_cred2: this.editedItem.limite_cred2,
        freq_pgto2: this.editedItem.freq_pgto2,
        de_freq_pgto2: this.editedItem.de_freq_pgto2,
        ate_freq_pgto2: this.editedItem.ate_freq_pgto2,
        limite_cred_file: this.editedItem.limite_cred_file,
        logo: this.editedItem.logo,
        logo_placa: this.editedItem.logo_placa
      }
    },
    async save() {
      if (!this.editedItem.mneu_cli || !this.editedItem.nome_cli) {
        this.showMessage('MNEU e nome sao obrigatorios.', 'warning')
        return
      }
      const mneu = String(this.editedItem.mneu_cli).trim()
      this.editedItem.mneu_cli = mneu
      if (mneu.length < 1 || mneu.length > 4) {
        this.showMessage('MNEU deve ter 1 a 4 caracteres.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'atualizar_cliente' : 'criar_cliente'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}api_tarifario_cadastro_clientes.php?request=${request}&mneu_cli=${encodeURIComponent(mneu)}`
          : `${API_BASE}api_tarifario_cadastro_clientes.php?request=${request}`

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
        await this.fetchClientes()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.mneu_cli) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}api_tarifario_cadastro_clientes.php?request=excluir_cliente&mneu_cli=${encodeURIComponent(
            this.editedItem.mneu_cli
          )}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Cliente excluido.')
        this.dialogDelete = false
        await this.fetchClientes()
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
.clientes-tarifario__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.clientes-tarifario__header h2 {
  margin: 0;
  font-size: 24px;
}

.clientes-tarifario__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.clientes-tarifario__filters {
  padding: 16px;
}

.clientes-tarifario__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.clientes-tarifario__dialog-title {
  display: flex;
  align-items: center;
}
</style>
