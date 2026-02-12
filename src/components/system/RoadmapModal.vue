<template>
  <v-dialog
    v-model="internalOpen"
    max-width="980px"
    transition="scale-transition"
    scrollable
  >
    <v-card class="roadmap-modal__card">
      <v-card-title class="roadmap-modal__title">
        <div>
          <h2>Roadmap &amp; Historico do Sistema</h2>
          <p>Acompanhe todas as melhorias e evolucoes do ERP</p>
        </div>
        <v-spacer></v-spacer>
        <v-btn icon @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text class="roadmap-modal__body">
        <div class="roadmap-modal__filters">
          <v-select
            v-model="filters.tipo"
            :items="tipoOptions"
            label="Tipo"
            dense
            outlined
            clearable
          ></v-select>
          <v-select
            v-model="filters.modulo"
            :items="moduloOptions"
            label="Modulo"
            dense
            outlined
            clearable
          ></v-select>
          <v-switch
            v-model="filters.publicoOnly"
            inset
            label="Mostrar apenas publicas"
          ></v-switch>
        </div>

        <div v-if="loading" class="roadmap-modal__loading">
          <v-skeleton-loader type="list-item-two-line"></v-skeleton-loader>
          <v-skeleton-loader type="list-item-two-line"></v-skeleton-loader>
          <v-skeleton-loader type="list-item-two-line"></v-skeleton-loader>
          <v-skeleton-loader type="list-item-two-line"></v-skeleton-loader>
        </div>

        <div v-else class="roadmap-modal__timeline">
          <div v-if="groupedUpdates.length === 0" class="roadmap-modal__empty">
            Nenhuma atualizacao encontrada.
          </div>
          <div v-for="group in groupedUpdates" :key="group.date" class="roadmap-modal__group">
            <div class="roadmap-modal__date">
              <v-icon small class="mr-1">mdi-calendar</v-icon>
              {{ group.date }}
            </div>
            <div
              v-for="item in group.items"
              :key="item.id"
              class="roadmap-modal__item"
            >
              <div class="roadmap-modal__dot"></div>
              <div class="roadmap-modal__content">
                <v-chip :color="tipoColor(item.tipo)" small text-color="white">
                  {{ (item.tipo || '').toUpperCase() }}
                </v-chip>
                <h3>{{ item.titulo }}</h3>
                <p>{{ item.descricao }}</p>
                <div class="roadmap-modal__meta">
                  <span><strong>Modulo:</strong> {{ item.modulo || '-' }}</span>
                  <span><strong>Por:</strong> {{ item.created_by || '-' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script>
import api from '@/services/api'

const API_BASE = 'api_sistema_atualizacoes.php'

export default {
  name: 'RoadmapModal',
  props: {
    value: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      internalOpen: false,
      loading: false,
      updates: [],
      filters: {
        tipo: '',
        modulo: '',
        publicoOnly: false
      }
    }
  },
  computed: {
    tipoOptions() {
      return ['feature', 'bugfix', 'improvement', 'refactor', 'security']
    },
    moduloOptions() {
      const set = new Set()
      this.updates.forEach(item => {
        if (item.modulo) {
          set.add(item.modulo)
        }
      })
      return Array.from(set).sort()
    },
    filteredUpdates() {
      return this.updates.filter(item => {
        if (this.filters.tipo && item.tipo !== this.filters.tipo) return false
        if (this.filters.modulo && item.modulo !== this.filters.modulo) return false
        if (this.filters.publicoOnly && !item.publico) return false
        return true
      })
    },
    groupedUpdates() {
      const groups = []
      let lastDate = null
      this.filteredUpdates.forEach(item => {
        const formatted = this.formatDate(item.created_at)
        if (formatted !== lastDate) {
          groups.push({ date: formatted, items: [item] })
          lastDate = formatted
        } else {
          groups[groups.length - 1].items.push(item)
        }
      })
      return groups
    }
  },
  watch: {
    value: {
      immediate: true,
      handler(val) {
        this.internalOpen = val
        if (val) {
          this.loadAtualizacoes()
        }
      }
    },
    internalOpen(val) {
      if (!val) {
        this.$emit('input', false)
      } else {
        this.$emit('input', true)
      }
    }
  },
  methods: {
    close() {
      this.internalOpen = false
    },
    normalizeItem(item) {
      return {
        id: item.id,
        titulo: item.titulo || '',
        descricao: item.descricao || '',
        modulo: item.modulo || '',
        tipo: item.tipo || '',
        publico: item.publico === true || item.publico === 't',
        created_by: item.created_by || '',
        created_at: item.created_at || ''
      }
    },
    async loadAtualizacoes() {
      this.loading = true
      try {
        const response = await api.get(`${API_BASE}?request=listar_atualizacoes&limit=50`)
        const data = response?.data || []
        const list = Array.isArray(data) ? data : data.data
        const normalized = Array.isArray(list) ? list.map(this.normalizeItem) : []
        this.updates = normalized.sort((a, b) => {
          const dateA = new Date(a.created_at || 0).getTime()
          const dateB = new Date(b.created_at || 0).getTime()
          return dateB - dateA
        })
      } catch (error) {
        this.$emit('error', error)
      } finally {
        this.loading = false
      }
    },
    tipoColor(tipo) {
      switch (String(tipo || '').toLowerCase()) {
        case 'feature':
          return 'primary'
        case 'bugfix':
          return 'error'
        case 'improvement':
          return 'success'
        case 'refactor':
          return 'orange'
        case 'security':
          return 'purple'
        default:
          return 'grey'
      }
    },
    formatDate(value) {
      if (!value) return '-'
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) {
        return value
      }
      return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.roadmap-modal__card {
  border-radius: 20px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
  overflow: hidden;
}

.roadmap-modal__title {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 24px 8px;
}

.roadmap-modal__title h2 {
  margin: 0;
  font-size: 22px;
}

.roadmap-modal__title p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.roadmap-modal__body {
  max-height: 70vh;
  overflow-y: auto;
  padding: 12px 24px 24px;
}

.roadmap-modal__filters {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 12px;
  align-items: center;
  margin-bottom: 16px;
}

.roadmap-modal__timeline {
  position: relative;
  padding-left: 32px;
}

.roadmap-modal__timeline::before {
  content: '';
  position: absolute;
  left: 10px;
  top: 8px;
  bottom: 8px;
  width: 2px;
  background: linear-gradient(180deg, rgba(248, 2, 14, 0.15), rgba(15, 23, 42, 0.15));
}

.roadmap-modal__group {
  margin-bottom: 18px;
}

.roadmap-modal__date {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  margin: 8px 0 12px;
  padding: 4px 10px;
  background: rgba(148, 163, 184, 0.12);
  border-radius: 999px;
}

.roadmap-modal__item {
  position: relative;
  display: flex;
  gap: 16px;
  padding: 14px 16px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
  margin-bottom: 12px;
}

.roadmap-modal__dot {
  position: absolute;
  left: -25px;
  top: 20px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #f9020e;
  box-shadow: 0 0 0 6px rgba(249, 2, 14, 0.12);
}

.roadmap-modal__content h3 {
  margin: 8px 0 6px;
  font-size: 16px;
  color: #0f172a;
}

.roadmap-modal__content p {
  margin: 0 0 10px;
  color: #475569;
  font-size: 14px;
  line-height: 1.5;
}

.roadmap-modal__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 12px;
  color: #64748b;
}

.roadmap-modal__empty {
  padding: 24px 0;
  color: #64748b;
}

.roadmap-modal__loading {
  display: grid;
  gap: 12px;
}

@media (max-width: 768px) {
  .roadmap-modal__filters {
    grid-template-columns: 1fr;
  }
}
</style>
