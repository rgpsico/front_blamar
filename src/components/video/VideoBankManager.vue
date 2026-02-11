<template>
  <div class="video-bank">
    <div class="video-bank__header">
      <div>
        <h2>Banco de Video</h2>
        <p>Gerencie videos cadastrados por cidade, hotel ou busca por termo.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn outlined color="secondary" class="mr-2" @click="openUpload">Cadastrar</v-btn>
      <v-btn outlined color="primary" class="mr-2" @click="clearFilters">Limpar</v-btn>
      <v-btn color="primary" :loading="loading" @click="runSearch">Buscar</v-btn>
    </div>

    <v-card class="video-bank__filters" elevation="6">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.query"
            label="Buscar por titulo, descricao ou autor"
            dense
            outlined
            @keyup.enter="runSearch"
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4">
          <v-select
            v-model="filters.cityId"
            :items="cities"
            item-text="nome_en"
            item-value="cidade_cod"
            label="Cidade"
            dense
            outlined
          ></v-select>
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.hotelId"
            label="Hotel (mneu_for)"
            dense
            outlined
          ></v-text-field>
        </v-col>
      </v-row>
    </v-card>

    <v-card class="video-bank__summary" elevation="4">
      <div class="video-bank__summary-item">
        <span>Resultados</span>
        <strong>{{ videos.length }}</strong>
      </div>
      <div class="video-bank__summary-item">
        <span>Cidade</span>
        <strong>{{ selectedCityName }}</strong>
      </div>
      <div class="video-bank__summary-item">
        <span>Hotel</span>
        <strong>{{ filters.hotelId || 'Sem hotel' }}</strong>
      </div>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table
        :headers="headers"
        :items="videos"
        :loading="loading"
        item-key="pk_bco_video"
        class="elevation-0"
        :footer-props="{
          'items-per-page-options': [10, 20, 50],
          showFirstLastPage: true
        }"
      >
        <template slot="item.ativo" slot-scope="{ item }">
          <v-chip :color="item.ativo ? 'success' : 'error'" small text-color="white">
            {{ item.ativo ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn icon small color="info" @click="openPreview(item)">
            <v-icon>mdi-play-circle</v-icon>
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

    <v-dialog v-model="dialogUpload" max-width="720px" persistent>
      <v-card>
        <v-card-title class="video-bank__dialog-title">
          <span>Novo video</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="dialogUpload = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="6">
                <v-file-input
                  v-model="uploadForm.file"
                  label="Arquivo de video"
                  outlined
                  dense
                  accept="video/*"
                ></v-file-input>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="uploadForm.autor" label="Autor" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="uploadForm.titulo_pt" label="Titulo PT" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="uploadForm.titulo_en" label="Titulo EN" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="uploadForm.titulo_esp" label="Titulo ESP" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="uploadForm.description" label="Descricao" outlined dense></v-textarea>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="uploadForm.cid" label="Cidade (cod)" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="uploadForm.mneu_for" label="Hotel (mneu_for)" outlined dense></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogUpload = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="submitUpload">Enviar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogEdit" max-width="720px" persistent>
      <v-card>
        <v-card-title class="video-bank__dialog-title">
          <span>Editar video</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="dialogEdit = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12">
                <div class="video-bank__preview">
                  <video v-if="editForm.url && !isYoutube(editForm.url)" :src="editForm.url" controls></video>
                  <iframe
                    v-else-if="editForm.url"
                    :src="embedUrl(editForm.url)"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                  ></iframe>
                  <div v-else class="video-bank__preview-empty">Video sem URL</div>
                </div>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editForm.titulo_pt" label="Titulo PT" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editForm.titulo_en" label="Titulo EN" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editForm.titulo_esp" label="Titulo ESP" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="editForm.description" label="Descricao" outlined dense></v-textarea>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editForm.autor" label="Autor" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editForm.cid" label="Cidade (cod)" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="editForm.mneu_for" label="Hotel (mneu_for)" outlined dense></v-text-field>
              </v-col>
              <v-col cols="12" md="4" class="d-flex align-center">
                <v-switch v-model="editForm.ativo" label="Ativo" inset></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogEdit = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="submitEdit">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDelete" max-width="420px">
      <v-card>
        <v-card-title>Confirmar exclusao</v-card-title>
        <v-card-text>
          <v-alert type="warning" border="left" colored-border>
            Tem certeza que deseja desativar este video?
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="error" :loading="saving" @click="confirmDelete">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogPreview" max-width="900px">
      <v-card>
        <v-card-title class="video-bank__dialog-title">
          <span>Preview</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="dialogPreview = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <div class="video-bank__preview">
            <video v-if="previewVideo?.url && !isYoutube(previewVideo.url)" :src="previewVideo.url" controls></video>
            <iframe
              v-else-if="previewVideo?.url"
              :src="embedUrl(previewVideo.url)"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
            ></iframe>
            <div v-else class="video-bank__preview-empty">Video sem URL</div>
          </div>
          <div class="video-bank__preview-meta">
            <span>PK: {{ previewVideo?.pk_bco_video }}</span>
            <span v-if="previewVideo?.autor">Autor: {{ previewVideo.autor }}</span>
            <span v-if="previewVideo?.cid">Cidade: {{ previewVideo.cid }}</span>
            <span v-if="previewVideo?.mneu_for">Hotel: {{ previewVideo.mneu_for }}</span>
          </div>
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
import api from '@/services/api'

const API_BASE = 'api_banco_de_video.php'

export default {
  name: 'VideoBankManager',
  data() {
    return {
      loading: false,
      saving: false,
      videos: [],
      cities: [],
      filters: {
        query: '',
        cityId: null,
        hotelId: ''
      },
      dialogUpload: false,
      dialogEdit: false,
      dialogDelete: false,
      dialogPreview: false,
      uploadForm: {
        file: null,
        titulo_pt: '',
        titulo_en: '',
        titulo_esp: '',
        description: '',
        autor: '',
        mneu_for: '',
        cid: ''
      },
      editForm: {},
      deleteItem: null,
      previewVideo: null,
      headers: [
        { text: 'ID', value: 'pk_bco_video', width: 90 },
        { text: 'Titulo PT', value: 'titulo_pt' },
        { text: 'Autor', value: 'autor', width: 140 },
        { text: 'Cidade', value: 'cid', width: 90 },
        { text: 'Hotel', value: 'mneu_for', width: 110 },
        { text: 'Ativo', value: 'ativo', width: 90, sortable: false },
        { text: 'Acoes', value: 'actions', width: 140, sortable: false, align: 'end' }
      ],
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      }
    }
  },
  computed: {
    selectedCityName() {
      const city = this.cities.find(item => String(item.cidade_cod) === String(this.filters.cityId))
      return city ? city.nome_en : 'Sem cidade'
    }
  },
  mounted() {
    this.fetchCities()
  },
  methods: {
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    clearFilters() {
      this.filters = {
        query: '',
        cityId: null,
        hotelId: ''
      }
      this.videos = []
    },
    async fetchCities() {
      try {
        const response = await api.get(`${API_BASE}?action=list_cities_with_videos`)
        const data = response?.data || {}
        this.cities = Array.isArray(data.cities) ? data.cities : []
      } catch (error) {
        this.showMessage(`Erro ao carregar cidades: ${error.message}`, 'error')
      }
    },
    async runSearch() {
      this.loading = true
      try {
        const query = this.filters.query.trim()
        const cityId = String(this.filters.cityId || '').trim()
        const hotelId = this.filters.hotelId.trim()
        let url = ''
        if (query) {
          url = `${API_BASE}?action=search_videos&query=${encodeURIComponent(query)}`
        } else if (cityId) {
          url = `${API_BASE}?action=videos_by_city&cidade_cod=${encodeURIComponent(cityId)}`
        } else if (hotelId) {
          url = `${API_BASE}?action=videos_by_hotel&hotel_id=${encodeURIComponent(hotelId)}`
        } else {
          this.videos = []
          return
        }
        const response = await api.get(url)
        const data = response?.data || {}
        this.videos = Array.isArray(data.videos) ? data.videos : []
      } catch (error) {
        this.showMessage(`Erro ao buscar videos: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    openUpload() {
      this.uploadForm = {
        file: null,
        titulo_pt: '',
        titulo_en: '',
        titulo_esp: '',
        description: '',
        autor: '',
        mneu_for: '',
        cid: ''
      }
      this.dialogUpload = true
    },
    async submitUpload() {
      if (!this.uploadForm.file) {
        this.showMessage('Selecione um arquivo de video.', 'warning')
        return
      }
      this.saving = true
      try {
        const form = new FormData()
        form.append('video', this.uploadForm.file)
        form.append('titulo_pt', this.uploadForm.titulo_pt || '')
        form.append('titulo_en', this.uploadForm.titulo_en || '')
        form.append('titulo_esp', this.uploadForm.titulo_esp || '')
        form.append('description', this.uploadForm.description || '')
        form.append('autor', this.uploadForm.autor || '')
        form.append('mneu_for', this.uploadForm.mneu_for || '')
        if (this.uploadForm.cid) {
          form.append('cid', this.uploadForm.cid)
        }

        const response = await api.post(`${API_BASE}?action=upload_video`, form, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        const data = response?.data || {}
        if (data.error || data.success === false) {
          throw new Error(data.error || 'Erro ao enviar video')
        }
        this.showMessage('Video cadastrado com sucesso.')
        this.dialogUpload = false
        await this.runSearch()
      } catch (error) {
        this.showMessage(`Erro ao enviar video: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    openEdit(item) {
      this.editForm = {
        pk_bco_video: item.pk_bco_video,
        titulo_pt: item.titulo_pt || '',
        titulo_en: item.titulo_en || '',
        titulo_esp: item.titulo_esp || '',
        description: item.description || '',
        autor: item.autor || '',
        cid: item.cid || '',
        mneu_for: item.mneu_for || '',
        url: item.url || '',
        ativo: item.ativo === true || item.ativo === 't'
      }
      this.dialogEdit = true
    },
    async submitEdit() {
      this.saving = true
      try {
        const payload = { ...this.editForm }
        const response = await api.post(`${API_BASE}?action=update_video_metadata`, payload)
        const data = response?.data || {}
        if (data.error || data.success === false) {
          throw new Error(data.error || 'Erro ao atualizar')
        }
        this.showMessage('Video atualizado.')
        this.dialogEdit = false
        await this.runSearch()
      } catch (error) {
        this.showMessage(`Erro ao atualizar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    openDelete(item) {
      this.deleteItem = item
      this.dialogDelete = true
    },
    async confirmDelete() {
      if (!this.deleteItem) return
      this.saving = true
      try {
        const response = await api.post(`${API_BASE}?action=delete_video`, {
          pk_bco_video: this.deleteItem.pk_bco_video
        })
        const data = response?.data || {}
        if (data.error || data.success === false) {
          throw new Error(data.error || 'Erro ao excluir')
        }
        this.showMessage('Video desativado.')
        this.dialogDelete = false
        await this.runSearch()
      } catch (error) {
        this.showMessage(`Erro ao excluir: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    openPreview(item) {
      this.previewVideo = item
      this.dialogPreview = true
    },
    isYoutube(url) {
      if (!url) return false
      return /youtu\.be|youtube\.com/.test(url)
    },
    embedUrl(url) {
      if (!url) return ''
      const value = String(url)
      const shortMatch = value.match(/youtu\.be\/([a-zA-Z0-9_-]+)/)
      if (shortMatch) {
        return `https://www.youtube.com/embed/${shortMatch[1]}`
      }
      const longMatch = value.match(/[?&]v=([a-zA-Z0-9_-]+)/)
      if (longMatch) {
        return `https://www.youtube.com/embed/${longMatch[1]}`
      }
      const embedMatch = value.match(/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/)
      if (embedMatch) {
        return `https://www.youtube.com/embed/${embedMatch[1]}`
      }
      return value
    }
  }
}
</script>

<style scoped>
.video-bank__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.video-bank__header h2 {
  margin: 0;
  font-size: 24px;
}

.video-bank__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.video-bank__filters {
  padding: 16px;
}

.video-bank__summary {
  display: flex;
  gap: 24px;
  padding: 16px;
  margin-top: 12px;
}

.video-bank__summary-item span {
  display: block;
  font-size: 12px;
  color: #64748b;
}

.video-bank__summary-item strong {
  font-size: 18px;
}

.video-bank__dialog-title {
  display: flex;
  align-items: center;
}

.video-bank__preview {
  background: #0f172a;
  border-radius: 12px;
  overflow: hidden;
}

.video-bank__preview video {
  width: 100%;
  height: 560px;
  max-height: 640px;
}

.video-bank__preview iframe {
  width: 100%;
  height: 560px;
  max-height: 640px;
}

.video-bank__preview-empty {
  padding: 24px;
  color: #cbd5f5;
}

.video-bank__preview-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 12px;
  color: #475569;
  font-size: 13px;
}
</style>
