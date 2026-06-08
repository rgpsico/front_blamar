<template>
  <div class="guias-manager">
    <div class="guias-manager__header">
      <div>
        <h2>Guias</h2>
        <p>Listagem, criacao, edicao e exclusao de guias.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Guia</v-btn>
      <v-btn outlined color="primary" @click="fetchGuias">Atualizar</v-btn>
    </div>

    <v-card class="guias-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.nome"
            label="Buscar por nome"
            dense
            outlined
            clearable
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.email"
            label="Buscar por email"
            dense
            outlined
            clearable
            @input="scheduleFetch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.ativar"
            :items="statusOptions"
            label="Status"
            dense
            outlined
            @change="fetchGuias"
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model.number="filters.limit"
            label="Limite"
            type="number"
            min="1"
            dense
            outlined
            @change="fetchGuias"
          ></v-text-field>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="guias"
        :loading="loading"
        item-key="id_guias"
        class="elevation-0"
      >
        <template slot="item.foto_perfil_url" slot-scope="{ item }">
          <v-avatar size="36" class="my-1">
            <v-img v-if="item.foto_perfil_url" :src="item.foto_perfil_url" />
            <v-icon v-else>mdi-account</v-icon>
          </v-avatar>
        </template>
        <template slot="item.ativar" slot-scope="{ item }">
          <v-chip :color="item.ativar ? 'success' : 'grey'" small dark>
            {{ item.ativar ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn icon small color="primary" @click="openEdit(item)" title="Editar">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon small color="teal" @click="openPacotes(item)" title="Ver Pacotes">
            <v-icon>mdi-folder-open-outline</v-icon>
          </v-btn>
          <v-btn icon small color="error" @click="openDelete(item)" title="Excluir">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Dialog Criar -->
    <v-dialog v-model="createDialog" max-width="900px" persistent>
      <v-card>
        <v-card-title class="guias-manager__dialog-title">
          <span>Novo Guia</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="createDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-tabs v-model="formTab">
            <v-tab>Dados Pessoais</v-tab>
            <v-tab>Documentos</v-tab>
            <v-tab>Endereco</v-tab>
            <v-tab>Profissional</v-tab>
          </v-tabs>
          <v-tabs-items v-model="formTab" class="mt-4">
            <v-tab-item><FormDadosPessoais :item="editedItem" /></v-tab-item>
            <v-tab-item><FormDocumentos :item="editedItem" /></v-tab-item>
            <v-tab-item><FormEndereco :item="editedItem" /></v-tab-item>
            <v-tab-item><FormProfissional :item="editedItem" /></v-tab-item>
          </v-tabs-items>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="createDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveCreate">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog Editar -->
    <v-dialog v-model="editDialog" max-width="900px" persistent>
      <v-card>
        <v-card-title class="guias-manager__dialog-title">
          <span>Editar Guia</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="editDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-tabs v-model="formTab">
            <v-tab>Dados Pessoais</v-tab>
            <v-tab>Documentos</v-tab>
            <v-tab>Endereco</v-tab>
            <v-tab>Profissional</v-tab>
          </v-tabs>
          <v-tabs-items v-model="formTab" class="mt-4">
            <v-tab-item><FormDadosPessoais :item="editedItem" /></v-tab-item>
            <v-tab-item><FormDocumentos :item="editedItem" /></v-tab-item>
            <v-tab-item><FormEndereco :item="editedItem" /></v-tab-item>
            <v-tab-item><FormProfissional :item="editedItem" /></v-tab-item>
          </v-tabs-items>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="editDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveEdit">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog Excluir -->
    <v-dialog v-model="dialogDelete" max-width="420px">
      <v-card>
        <v-card-title>Confirmar exclusao</v-card-title>
        <v-card-text>
          <v-alert type="warning" border="left" colored-border>
            Tem certeza que deseja excluir o guia
            <strong>{{ editedItem.nome }}</strong>?
            Esta acao nao pode ser desfeita.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="error" :loading="saving" @click="confirmDelete">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog Pacotes -->
    <v-dialog v-model="pacotesDialog" max-width="1000px">
      <v-card>
        <v-card-title class="guias-manager__dialog-title">
          <v-icon left>mdi-folder-open-outline</v-icon>
          Pacotes de {{ guiaSelecionado.nome }}
          <v-spacer></v-spacer>
          <v-btn icon @click="pacotesDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>

        <v-card-subtitle class="pb-0 pt-2">
          <v-chip x-small class="mr-1" color="orange" dark>Pendentes: {{ resumo.pendentes_aceitacao }}</v-chip>
          <v-chip x-small class="mr-1" color="green" dark>Aceitos: {{ resumo.aceitos }}</v-chip>
          <v-chip x-small class="mr-1" color="red" dark>Recusados: {{ resumo.recusados }}</v-chip>
          <v-chip x-small class="mr-1" color="blue" dark>Pagos: {{ resumo.pagos }}</v-chip>
          <v-chip x-small class="mr-1" color="grey darken-1" dark>Cancelados: {{ resumo.cancelados }}</v-chip>
          <v-chip x-small class="mr-1" color="purple" dark>Lib. Pagamento: {{ resumo.liberados_pagamento }}</v-chip>
        </v-card-subtitle>

        <v-card-text class="pt-3">
          <v-tabs v-model="pacotesTab" @change="onPacotesTabChange" show-arrows>
            <v-tab>Pendentes ({{ resumo.pendentes_aceitacao }})</v-tab>
            <v-tab>Aceitos ({{ resumo.aceitos }})</v-tab>
            <v-tab>Recusados ({{ resumo.recusados }})</v-tab>
            <v-tab>Pagos ({{ resumo.pagos }})</v-tab>
            <v-tab>Cancelados ({{ resumo.cancelados }})</v-tab>
            <v-tab>Lib. Pagamento ({{ resumo.liberados_pagamento }})</v-tab>
          </v-tabs>

          <div v-if="loadingPacotes" class="text-center pa-8">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
          </div>
          <div v-else class="mt-3">
            <div v-if="pacotes.length === 0" class="text-center pa-6 grey--text">
              Nenhum pacote encontrado nesta categoria.
            </div>
            <v-card
              v-for="pacote in pacotes"
              :key="pacote.pk_osfile"
              class="mb-3"
              outlined
            >
              <v-card-title class="subtitle-2 py-2 d-flex flex-wrap">
                <span>
                  File <strong>{{ pacote.file }}</strong>
                  <span v-if="pacote.pax"> &nbsp;|&nbsp; PAX: {{ pacote.pax }}</span>
                  <span v-if="pacote.primeira_data"> &nbsp;|&nbsp; 1º serviço: <strong>{{ pacote.primeira_data }}</strong></span>
                  <span v-if="pacote.data_envio"> &nbsp;|&nbsp; Enviado: {{ pacote.data_envio }}</span>
                </span>
                <v-spacer></v-spacer>
                <v-chip x-small color="error" dark v-if="pacote.cancelado">Cancelado</v-chip>
              </v-card-title>
              <v-divider></v-divider>
              <v-card-text class="py-2">
                <div
                  v-for="srv in pacote.servicos"
                  :key="srv.pk_osguia"
                  class="d-flex align-center py-1 servico-linha"
                >
                  <span class="servico-data caption font-weight-bold mr-3">{{ srv.dia_servico }} {{ srv.hora_servico }}</span>
                  <span class="servico-descritivo flex-grow-1">{{ srv.descritivo }}</span>
                  <v-chip x-small :color="statusCorServico(srv.status_srv)" dark class="ml-2">{{ srv.status_label }}</v-chip>
                </div>
                <div v-if="!pacote.servicos || pacote.servicos.length === 0" class="grey--text caption">
                  Sem servicos listados.
                </div>

                <div v-if="pacote.anexos && pacote.anexos.length > 0" class="mt-2">
                  <span class="caption grey--text">Anexos: </span>
                  <a
                    v-for="anx in pacote.anexos"
                    :key="anx.id"
                    :href="anx.url"
                    target="_blank"
                    class="mr-3 caption"
                  >{{ anx.nome }}</a>
                </div>

                <v-alert
                  v-if="pacote.alteracao"
                  type="warning"
                  dense
                  class="mt-2 mb-0"
                  border="left"
                  colored-border
                >
                  <strong>Alteracao em {{ pacote.alteracao.data_update }}:</strong>
                  {{ pacote.alteracao.conteudo_alteracao }}
                </v-alert>
              </v-card-text>
            </v-card>
          </div>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="pacotesDialog = false">Fechar</v-btn>
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
  id_guias: null,
  nome: '',
  apelido: '',
  descritivo: '',
  email: '',
  telefone_celular: '',
  telefone_casa: '',
  telefone_outro: '',
  nextel: '',
  id_nextel: '',
  cpf: '',
  cpf3: '',
  pis: '',
  pis3: '',
  identidade: '',
  id_emissor: '',
  data_exp_id: '',
  cnh: '',
  validade_cnh: '',
  categoria_cnh: '',
  data_1_cnh: '',
  cnh_org_exp: '',
  cnh_data_exp: '',
  cnh_uf: '',
  foto: '',
  foto_perfil: '',
  embratur: '',
  ativar: true,
  fk_cod_cidade: null,
  endereco: '',
  endereco_numero: '',
  endereco_complemento: '',
  endereco_bairro: '',
  endereco_cep: '',
  endereco_uf: '',
  nascimento: '',
  mun_nasc: '',
  uf_nasc: '',
  nacion: '',
  nome_mae: '',
  nome_pai: '',
  estado_civil: null,
  escolaridade: '',
  formacao: '',
  rne_num: '',
  rne_orgao: '',
  rne_data: '',
  obs_blumar: '',
  operadoras: '',
  login: '',
  categ: null,
  procedimentos: false,
  cert_vacinacao: '',
  all_vacinated: false
})

const FormDadosPessoais = {
  name: 'FormDadosPessoais',
  props: ['item'],
  template: `
    <v-row>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.nome" label="Nome *" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.apelido" label="Apelido" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12">
        <v-textarea v-model="item.descritivo" label="Descritivo" outlined rows="2"></v-textarea>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.email" label="Email" outlined dense type="email"></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.nascimento" label="Nascimento (YYYY-MM-DD)" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.telefone_celular" label="Celular" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.telefone_casa" label="Telefone Casa" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.telefone_outro" label="Telefone Outro" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.cpf" label="CPF" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.estado_civil" label="Estado Civil" outlined dense type="number"></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.nacion" label="Nacionalidade" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.mun_nasc" label="Municipio Nasc." outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.uf_nasc" label="UF Nasc." outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.escolaridade" label="Escolaridade" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.nome_mae" label="Nome da Mae" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.nome_pai" label="Nome do Pai" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12">
        <v-text-field v-model="item.formacao" label="Formacao" outlined dense></v-text-field>
      </v-col>
    </v-row>
  `
}

const FormDocumentos = {
  name: 'FormDocumentos',
  props: ['item'],
  template: `
    <v-row>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.identidade" label="RG / Identidade" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="3">
        <v-text-field v-model="item.id_emissor" label="Orgao Emissor" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="3">
        <v-text-field v-model="item.data_exp_id" label="Data Exp. RG (YYYY-MM-DD)" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.embratur" label="Embratur" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.pis" label="PIS" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.cnh" label="CNH" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.categoria_cnh" label="Categoria CNH" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.validade_cnh" label="Validade CNH (YYYY-MM-DD)" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.cnh_uf" label="UF CNH" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.cnh_org_exp" label="Orgao Exp. CNH" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.cnh_data_exp" label="Data Exp. CNH (YYYY-MM-DD)" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.data_1_cnh" label="Data 1a CNH (YYYY-MM-DD)" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.rne_num" label="RNE Numero" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.rne_orgao" label="RNE Orgao" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.rne_data" label="RNE Data (YYYY-MM-DD)" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.cert_vacinacao" label="Cert. Vacinacao" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="3">
        <v-switch v-model="item.all_vacinated" label="Totalmente vacinado" inset></v-switch>
      </v-col>
      <v-col cols="12" md="3">
        <v-switch v-model="item.procedimentos" label="Procedimentos" inset></v-switch>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.nextel" label="Nextel" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.id_nextel" label="ID Nextel" outlined dense></v-text-field>
      </v-col>
    </v-row>
  `
}

const FormEndereco = {
  name: 'FormEndereco',
  props: ['item'],
  template: `
    <v-row>
      <v-col cols="12" md="8">
        <v-text-field v-model="item.endereco" label="Logradouro" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.endereco_numero" label="Numero" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.endereco_complemento" label="Complemento" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.endereco_bairro" label="Bairro" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.endereco_cep" label="CEP" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.endereco_uf" label="UF" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model.number="item.fk_cod_cidade" label="Cod. Cidade" outlined dense type="number"></v-text-field>
      </v-col>
    </v-row>
  `
}

const FormProfissional = {
  name: 'FormProfissional',
  props: ['item'],
  template: `
    <v-row>
      <v-col cols="12" md="4">
        <v-text-field v-model.number="item.categ" label="Categoria" outlined dense type="number"></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field v-model="item.login" label="Login" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-switch v-model="item.ativar" label="Ativo" inset color="success"></v-switch>
      </v-col>
      <v-col cols="12">
        <v-text-field v-model="item.operadoras" label="Operadoras" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12">
        <v-textarea v-model="item.obs_blumar" label="Observacoes Blumar" outlined rows="3"></v-textarea>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.foto" label="Foto (nome do arquivo)" outlined dense></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field v-model="item.foto_perfil" label="Foto Perfil (nome do arquivo)" outlined dense></v-text-field>
      </v-col>
    </v-row>
  `
}

export default {
  name: 'GuiasManager',
  components: { FormDadosPessoais, FormDocumentos, FormEndereco, FormProfissional },
  data() {
    return {
      loading: false,
      saving: false,
      guias: [],
      filterTimer: null,
      formTab: 0,
      filters: {
        nome: '',
        email: '',
        ativar: 'all',
        limit: 100
      },
      statusOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Ativos', value: 'true' },
        { text: 'Inativos', value: 'false' }
      ],
      pacotesDialog: false,
      pacotesTab: 0,
      guiaSelecionado: { id_guias: null, nome: '' },
      pacotes: [],
      loadingPacotes: false,
      resumo: {
        pendentes_aceitacao: 0,
        aceitos: 0,
        recusados: 0,
        pagos: 0,
        cancelados: 0,
        liberados_pagamento: 0
      },
      createDialog: false,
      editDialog: false,
      dialogDelete: false,
      editedItem: blankItem(),
      snackbar: { show: false, text: '', color: 'success' },
      headers: [
        { text: 'Foto', value: 'foto_perfil_url', sortable: false },
        { text: 'Nome', value: 'nome' },
        { text: 'Apelido', value: 'apelido' },
        { text: 'Email', value: 'email' },
        { text: 'Celular', value: 'telefone_celular' },
        { text: 'Embratur', value: 'embratur' },
        { text: 'Status', value: 'ativar', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ]
    }
  },
  mounted() {
    this.fetchGuias()
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
      if (this.filterTimer) clearTimeout(this.filterTimer)
      this.filterTimer = setTimeout(() => this.fetchGuias(), 400)
    },
    async fetchGuias() {
      this.loading = true
      try {
        const params = new URLSearchParams({ request: 'listar_guias' })
        if (this.filters.nome)   params.set('filtro_nome', this.filters.nome)
        if (this.filters.email)  params.set('filtro_email', this.filters.email)
        if (this.filters.ativar !== 'all') params.set('filtro_ativar', this.filters.ativar)
        if (this.filters.limit)  params.set('limit', String(this.filters.limit))

        const response = await fetch(`${API_BASE}api_guias.php?${params.toString()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        this.guias = Array.isArray(data) ? data : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    openCreate() {
      this.editedItem = blankItem()
      this.formTab = 0
      this.createDialog = true
    },
    openEdit(item) {
      this.editedItem = { ...blankItem(), ...item }
      this.formTab = 0
      this.editDialog = true
    },
    openDelete(item) {
      this.editedItem = { id_guias: item.id_guias, nome: item.nome }
      this.dialogDelete = true
    },
    buildPayload() {
      const item = this.editedItem
      const payload = {}
      const fields = [
        'nome', 'apelido', 'descritivo', 'email',
        'telefone_celular', 'telefone_casa', 'telefone_outro',
        'nextel', 'id_nextel', 'cpf', 'pis', 'identidade',
        'id_emissor', 'data_exp_id', 'cnh', 'validade_cnh',
        'categoria_cnh', 'data_1_cnh', 'cnh_org_exp', 'cnh_data_exp', 'cnh_uf',
        'foto', 'foto_perfil', 'embratur', 'ativar',
        'fk_cod_cidade', 'endereco', 'endereco_numero', 'endereco_complemento',
        'endereco_bairro', 'endereco_cep', 'endereco_uf',
        'nascimento', 'mun_nasc', 'uf_nasc', 'nacion',
        'nome_mae', 'nome_pai', 'estado_civil', 'escolaridade', 'formacao',
        'rne_num', 'rne_orgao', 'rne_data', 'obs_blumar', 'operadoras',
        'login', 'categ', 'procedimentos', 'cert_vacinacao', 'all_vacinated'
      ]
      fields.forEach(f => {
        if (item[f] !== '' && item[f] !== null && item[f] !== undefined) {
          payload[f] = item[f]
        }
      })
      return payload
    },
    async saveCreate() {
      if (!this.editedItem.nome) {
        this.showMessage('O campo Nome e obrigatorio.', 'warning')
        return
      }
      this.saving = true
      try {
        const response = await fetch(`${API_BASE}api_guias.php?request=criar_guia`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
          body: JSON.stringify(this.buildPayload())
        })
        const result = await response.json()
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao salvar')
        }
        this.showMessage('Guia criado com sucesso.')
        this.createDialog = false
        await this.fetchGuias()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async saveEdit() {
      if (!this.editedItem.nome) {
        this.showMessage('O campo Nome e obrigatorio.', 'warning')
        return
      }
      this.saving = true
      try {
        const url = `${API_BASE}api_guias.php?request=atualizar_guia&id=${this.editedItem.id_guias}`
        const response = await fetch(url, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', ...this.authHeaders() },
          body: JSON.stringify(this.buildPayload())
        })
        const result = await response.json()
        if (result.error || result.success === false) {
          throw new Error(result.error || result.message || 'Erro ao atualizar')
        }
        this.showMessage('Guia atualizado com sucesso.')
        this.editDialog = false
        await this.fetchGuias()
      } catch (error) {
        this.showMessage(`Erro ao atualizar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async openPacotes(item) {
      this.guiaSelecionado = { id_guias: item.id_guias, nome: item.nome }
      this.pacotesTab = 0
      this.pacotes = []
      this.resumo = { pendentes_aceitacao: 0, aceitos: 0, recusados: 0, pagos: 0, cancelados: 0, liberados_pagamento: 0 }
      this.pacotesDialog = true
      await this.fetchResumo(item.id_guias)
      await this.fetchPacotes(item.id_guias, 2)
    },
    async fetchResumo(id_guia) {
      try {
        const response = await fetch(
          `${API_BASE}api_guias.php?request=resumo_pacotes&id_guia=${id_guia}`,
          { headers: this.authHeaders() }
        )
        const data = await response.json()
        if (!data.error) this.resumo = data
      } catch (e) { /* silencioso */ }
    },
    async fetchPacotes(id_guia, idpg) {
      this.loadingPacotes = true
      this.pacotes = []
      try {
        const response = await fetch(
          `${API_BASE}api_guias.php?request=listar_pacotes&id_guia=${id_guia}&idpg=${idpg}`,
          { headers: this.authHeaders() }
        )
        const data = await response.json()
        this.pacotes = data.data || []
      } catch (error) {
        this.showMessage(`Erro ao carregar pacotes: ${error.message}`, 'error')
      } finally {
        this.loadingPacotes = false
      }
    },
    onPacotesTabChange(tab) {
      const idpg = [2, 3, 4, 5, 6, 7][tab]
      this.fetchPacotes(this.guiaSelecionado.id_guias, idpg)
    },
    statusCorServico(status) {
      return { 1: 'orange', 2: 'blue', 3: 'green', 4: 'red' }[Number(status)] || 'grey'
    },
    async confirmDelete() {
      if (!this.editedItem.id_guias) return
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}api_guias.php?request=excluir_guia&id=${this.editedItem.id_guias}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) throw new Error(result.error)
        this.showMessage('Guia excluido com sucesso.')
        this.dialogDelete = false
        await this.fetchGuias()
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
.guias-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.guias-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.guias-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.guias-manager__filters {
  padding: 16px;
}

.guias-manager__dialog-title {
  display: flex;
  align-items: center;
}

.servico-linha {
  border-bottom: 1px solid #f0f0f0;
}

.servico-linha:last-child {
  border-bottom: none;
}

.servico-data {
  min-width: 140px;
}

.servico-descritivo {
  font-size: 13px;
}
</style>
