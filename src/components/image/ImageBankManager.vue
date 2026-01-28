<template>
  <div class="image-bank">
    <div class="image-bank__header">
      <div>
        <h2>Banco de Imagem</h2>
        <p>Busque imagens por termo, cidade ou hotel e filtre por tipo (hotel, tour, cidade).</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn outlined color="primary" class="mr-2" @click="clearFilters">Limpar</v-btn>
      <v-btn color="primary" :loading="loading" @click="runSearch">Buscar</v-btn>
    </div>

    <v-card class="image-bank__filters" elevation="6">
      <v-row>
        <v-col cols="12" md="3">
          <v-select
            v-model="mode"
            :items="modeOptions"
            item-text="text"
            item-value="value"
            label="Modo de busca"
            dense
            outlined
          ></v-select>
        </v-col>

        <v-col cols="12" md="4" v-if="mode === 'search'">
          <v-text-field
            v-model="searchTerm"
            label="Buscar por nome, legenda ou autor"
            dense
            outlined
            @keyup.enter="runSearch"
          ></v-text-field>
        </v-col>

        <v-col cols="12" md="4" v-else-if="mode === 'city'">
          <v-autocomplete
            v-model="selectedCity"
            :items="cityOptions"
            item-text="nome_en"
            item-value="cidade_cod"
            label="Cidade"
            dense
            outlined
          ></v-autocomplete>
        </v-col>

        <v-col cols="12" md="4" v-else>
          <v-autocomplete
            v-model="selectedCity"
            :items="cityOptions"
            item-text="nome_en"
            item-value="cidade_cod"
            label="Cidade do hotel"
            dense
            outlined
            @change="fetchHotelsByCity"
          ></v-autocomplete>
        </v-col>

        <v-col cols="12" md="4" v-if="mode === 'hotel'">
          <v-select
            v-model="selectedHotel"
            :items="hotelOptions"
            item-text="nome_for"
            item-value="mneu_for"
            return-object
            label="Hotel"
            dense
            outlined
            :disabled="!selectedCity"
          ></v-select>
        </v-col>

        <v-col cols="12" md="3">
          <v-select
            v-model="typeFilter"
            :items="typeOptions"
            item-text="text"
            item-value="value"
            label="Tipo"
            dense
            outlined
          ></v-select>
        </v-col>

        <v-col cols="12" md="2" v-if="typeFilter === 'custom'">
          <v-text-field
            v-model="customType"
            label="Tipo ID (ex: 5)"
            dense
            outlined
          ></v-text-field>
        </v-col>
      </v-row>
    </v-card>

    <v-card class="image-bank__summary" elevation="4">
      <div class="image-bank__summary-item">
        <span>Resultados</span>
        <strong>{{ filteredImages.length }}</strong>
      </div>
      <div class="image-bank__summary-item">
        <span>Filtro</span>
        <strong>{{ typeLabel(typeFilterValue) }}</strong>
      </div>
      <div class="image-bank__summary-item">
        <span>Modo</span>
        <strong>{{ modeLabel }}</strong>
      </div>
    </v-card>

    <v-row class="image-bank__grid" v-if="filteredImages.length">
      <v-col
        v-for="image in filteredImages"
        :key="image.pk_bco_img"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <v-card class="image-bank__card" elevation="4">
          <v-img :src="imagePreview(image)" height="180" class="image-bank__img">
            <div class="image-bank__chip">
              <v-chip small color="primary" text-color="white">
                {{ typeLabel(image.tp_produto) }}
              </v-chip>
            </div>
          </v-img>
          <div class="image-bank__card-body">
            <div class="image-bank__card-title">
              {{ image.legenda || 'Sem legenda' }}
            </div>
            <div class="image-bank__meta">
              <span>PK: {{ image.pk_bco_img }}</span>
              <span v-if="image.mneu_for">Hotel: {{ image.mneu_for }}</span>
              <span v-if="image.fk_cidcod">Cidade: {{ image.fk_cidcod }}</span>
              <span v-if="image.autor">Autor: {{ image.autor }}</span>
            </div>
            <div class="image-bank__actions">
              <v-btn text small color="primary" @click="openPreview(image)">Preview</v-btn>
              <v-btn text small @click="copyUrl(image)">Copiar URL</v-btn>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-card v-else class="image-bank__empty" elevation="0">
      <v-icon size="36" color="primary">mdi-image-off</v-icon>
      <p>Nenhuma imagem encontrada. Ajuste filtros e tente novamente.</p>
    </v-card>

    <v-dialog v-model="previewDialog" max-width="820px">
      <v-card>
        <v-card-title class="image-bank__dialog-title">
          <span>Preview</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="previewDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-img :src="previewUrl" height="420"></v-img>
          <div class="image-bank__dialog-meta">
            <span>PK: {{ previewImage?.pk_bco_img }}</span>
            <span>Tipo: {{ typeLabel(previewImage?.tp_produto) }}</span>
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
import axios from 'axios'

const API_BASE = 'api/galeria.php'

export default {
  name: 'ImageBankManager',
  data() {
    return {
      mode: 'search',
      searchTerm: '',
      cityOptions: [],
      selectedCity: null,
      hotelOptions: [],
      selectedHotel: null,
      typeFilter: 'all',
      customType: '',
      loading: false,
      images: [],
      previewDialog: false,
      previewImage: null,
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      modeOptions: [
        { text: 'Busca livre', value: 'search' },
        { text: 'Por cidade', value: 'city' },
        { text: 'Por hotel', value: 'hotel' }
      ],
      typeOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Hotel (1)', value: 1 },
        { text: 'Tour (5)', value: 5 },
        { text: 'Cidade (10)', value: 10 },
        { text: 'Outro (custom)', value: 'custom' }
      ]
    }
  },
  computed: {
    filteredImages() {
      const typeValue = this.typeFilterValue
      if (typeValue === 'all') {
        return this.images
      }
      return this.images.filter(image => String(image.tp_produto) === String(typeValue))
    },
    typeFilterValue() {
      if (this.typeFilter === 'custom') {
        return this.customType || 'custom'
      }
      return this.typeFilter
    },
    modeLabel() {
      const found = this.modeOptions.find(option => option.value === this.mode)
      return found ? found.text : 'Busca'
    },
    previewUrl() {
      if (!this.previewImage) {
        return ''
      }
      return this.imagePreview(this.previewImage)
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
    async fetchCities() {
      try {
        const response = await axios.get(`${API_BASE}?action=list_cities`)
        const data = response.data
        this.cityOptions = Array.isArray(data?.cities) ? data.cities : []
      } catch (error) {
        this.showMessage(`Erro ao carregar cidades: ${error.message}`, 'error')
      }
    },
    async fetchHotelsByCity() {
      try {
        if (!this.selectedCity) {
          this.hotelOptions = []
          this.selectedHotel = null
          return
        }
        const response = await axios.get(
          `${API_BASE}?action=hotels_by_city&cidade_cod=${encodeURIComponent(this.selectedCity)}`
        )
        const data = response.data
        this.hotelOptions = Array.isArray(data?.hotels) ? data.hotels : []
      } catch (error) {
        this.showMessage(`Erro ao buscar hoteis: ${error.message}`, 'error')
      }
    },
    async runSearch() {
      this.loading = true
      try {
        if (this.mode === 'search') {
          if (!this.searchTerm) {
            this.showMessage('Informe um termo de busca.', 'warning')
            return
          }
          const response = await axios.get(
            `${API_BASE}?action=search_by_name&termo=${encodeURIComponent(this.searchTerm)}`
          )
          this.images = Array.isArray(response.data?.images) ? response.data.images : []
          return
        }
        if (this.mode === 'city') {
          if (!this.selectedCity) {
            this.showMessage('Selecione uma cidade.', 'warning')
            return
          }
          const response = await axios.get(
            `${API_BASE}?action=city_generic_images&cidade_cod=${encodeURIComponent(this.selectedCity)}`
          )
          this.images = Array.isArray(response.data?.images) ? response.data.images : []
          return
        }
        if (this.mode === 'hotel') {
          if (!this.selectedCity) {
            this.showMessage('Selecione uma cidade para listar os hoteis.', 'warning')
            return
          }
          if (!this.selectedHotel?.mneu_for) {
            this.showMessage('Selecione um hotel.', 'warning')
            return
          }
          const response = await axios.get(
            `${API_BASE}?action=hotel_images&hotel_id=${encodeURIComponent(this.selectedHotel.mneu_for)}`
          )
          this.images = Array.isArray(response.data?.images) ? response.data.images : []
        }
      } catch (error) {
        this.showMessage(`Erro ao buscar imagens: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    clearFilters() {
      this.mode = 'search'
      this.searchTerm = ''
      this.selectedCity = null
      this.selectedHotel = null
      this.typeFilter = 'all'
      this.customType = ''
      this.images = []
    },
    imagePreview(image) {
      return (
        image?.preview_url ||
        image?.urls?.tam_4 ||
        image?.urls?.tam_3 ||
        image?.urls?.tam_2 ||
        image?.urls?.tam_1 ||
        'https://via.placeholder.com/600x400?text=Sem+Imagem'
      )
    },
    typeLabel(value) {
      if (String(value) === '1') return 'Hotel'
      if (String(value) === '5') return 'Tour'
      if (String(value) === '10') return 'Cidade'
      if (value === 'all') return 'Todos'
      if (value === 'custom') return 'Custom'
      if (!value) return 'Sem tipo'
      return `Tipo ${value}`
    },
    openPreview(image) {
      this.previewImage = image
      this.previewDialog = true
    },
    async copyUrl(image) {
      const url = this.imagePreview(image)
      try {
        await navigator.clipboard.writeText(url)
        this.showMessage('URL copiada.')
      } catch (error) {
        this.showMessage('Nao foi possivel copiar.', 'error')
      }
    }
  }
}
</script>

<style scoped>
.image-bank__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.image-bank__header h2 {
  margin: 0;
  font-size: 24px;
}

.image-bank__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.image-bank__filters {
  padding: 16px;
  border-radius: 16px;
  background: linear-gradient(140deg, rgba(249, 2, 14, 0.06), rgba(14, 116, 144, 0.06));
}

.image-bank__summary {
  margin-top: 16px;
  padding: 14px 18px;
  border-radius: 16px;
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
  align-items: center;
}

.image-bank__summary-item span {
  display: block;
  font-size: 12px;
  color: #64748b;
}

.image-bank__summary-item strong {
  font-size: 18px;
  color: #0f172a;
}

.image-bank__grid {
  margin-top: 8px;
}

.image-bank__card {
  border-radius: 18px;
  overflow: hidden;
}

.image-bank__img {
  position: relative;
}

.image-bank__chip {
  position: absolute;
  top: 12px;
  left: 12px;
}

.image-bank__card-body {
  padding: 14px 16px 16px;
}

.image-bank__card-title {
  font-weight: 600;
  margin-bottom: 8px;
  color: #0f172a;
}

.image-bank__meta {
  display: grid;
  gap: 4px;
  font-size: 12px;
  color: #64748b;
}

.image-bank__actions {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

.image-bank__empty {
  margin-top: 24px;
  padding: 32px;
  text-align: center;
  color: #64748b;
}

.image-bank__dialog-title {
  display: flex;
  align-items: center;
}

.image-bank__dialog-meta {
  display: flex;
  gap: 16px;
  margin-top: 12px;
  color: #64748b;
  font-size: 13px;
}
</style>
