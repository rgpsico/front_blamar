<template>
  <div>
    <div class="d-flex align-center mb-4">
      <div>
        <h2>Restaurants Incentive</h2>
        <p>CRUD com abas de dados, imagens e menus.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Restaurante</v-btn>
      <v-btn outlined color="primary" @click="fetchRestaurants">Atualizar</v-btn>
    </div>

    <v-card class="pa-4" elevation="4">
      <v-row>
        <v-col cols="12" md="5"><v-text-field v-model="filters.nome" label="Nome" dense outlined clearable /></v-col>
        <v-col cols="12" md="4">
          <v-autocomplete v-model="filters.city_code" :items="cityItems" item-text="text" item-value="value" label="Cidade" dense outlined clearable />
        </v-col>
        <v-col cols="12" md="3">
          <v-select v-model="filters.is_active" :items="statusOptions" item-text="text" item-value="value" label="Status" dense outlined clearable />
        </v-col>
      </v-row>
      <div class="d-flex justify-end" style="gap:12px">
        <v-btn outlined color="primary" @click="applyFilters">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6" class="mt-4">
      <v-data-table :headers="headers" :items="items" :loading="loading" item-key="id" disable-sort>
        <template slot="item.city_code" slot-scope="{ item }">{{ cityLabel(item.city_code) }}</template>
        <template slot="item.is_active" slot-scope="{ item }"><v-chip small :color="item.is_active ? 'success' : 'grey'">{{ item.is_active ? 'Ativo' : 'Inativo' }}</v-chip></template>
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn icon small color="primary" @click="openEdit(item)"><v-icon>mdi-pencil</v-icon></v-btn>
          <v-btn icon small color="error" @click="openDelete(item)"><v-icon>mdi-delete</v-icon></v-btn>
        </template>
      </v-data-table>
      <v-divider />
      <v-card-actions>
        <v-select v-model="pagination.perPage" :items="pagination.perPageOptions" label="Por pagina" dense outlined hide-details style="max-width:140px" @change="changePerPage" />
        <v-spacer />
        <v-pagination v-model="pagination.page" :length="pagination.lastPage" total-visible="7" @input="fetchRestaurants" />
      </v-card-actions>
    </v-card>

    <v-dialog v-model="dialog" max-width="1180px" persistent>
      <v-card>
        <v-card-title>
          <span>{{ dialogTitle }}</span>
          <v-spacer />
          <v-btn icon @click="closeDialog"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-card-text>
          <v-tabs v-model="activeTab" grow><v-tab>Dados</v-tab><v-tab>Imagens</v-tab><v-tab>Menus</v-tab></v-tabs>
          <v-tabs-items v-model="activeTab" class="mt-4">
            <v-tab-item>
              <v-row>
                <v-col cols="12" md="8"><v-text-field v-model="editedItem.name" label="Nome *" outlined dense /></v-col>
                <v-col cols="12" md="4"><v-switch v-model="editedItem.is_active" label="Ativo" inset /></v-col>
                <v-col cols="12" md="6"><v-text-field v-model="editedItem.slug" label="Slug *" outlined dense /></v-col>
                <v-col cols="12" md="6"><v-autocomplete v-model="editedItem.city_code" :items="cityItems" item-text="text" item-value="value" label="Cidade *" outlined dense clearable /></v-col>
                <v-col cols="12" md="4"><v-text-field v-model.number="editedItem.capacity" label="Capacidade" type="number" outlined dense /></v-col>
                <v-col cols="12" md="4"><v-switch v-model="editedItem.has_private_area" label="Area privativa" inset /></v-col>
                <v-col cols="12" md="4"><v-switch v-model="editedItem.has_view" label="Tem vista" inset /></v-col>
                <v-col cols="12"><v-text-field v-model="editedItem.address" label="Endereco" outlined dense /></v-col>
                <v-col cols="12" md="6"><v-text-field v-model="editedItem.latitude" label="Latitude" outlined dense /></v-col>
                <v-col cols="12" md="6"><v-text-field v-model="editedItem.longitude" label="Longitude" outlined dense /></v-col>
                <v-col cols="12"><v-text-field v-model="editedItem.short_description" label="Descricao curta" outlined dense /></v-col>
                <v-col cols="12"><v-textarea v-model="editedItem.description" label="Descricao" outlined rows="4" /></v-col>
              </v-row>
            </v-tab-item>

            <v-tab-item>
              <v-alert v-if="!editedItem.id" type="info" outlined>Salve o restaurante primeiro para usar imagens.</v-alert>
              <template v-else>
                <v-card outlined class="pa-4 mb-4">
                  <v-row>
                    <v-col cols="12" md="7"><v-text-field v-model="imageDraft.image_url" label="URL da imagem *" outlined dense /></v-col>
                    <v-col cols="12" md="2"><v-text-field v-model.number="imageDraft.position" label="Posicao" type="number" outlined dense /></v-col>
                    <v-col cols="12" md="3"><v-switch v-model="imageDraft.is_cover" label="Capa" inset /></v-col>
                  </v-row>
                  <div class="d-flex justify-end" style="gap:12px">
                    <v-btn text @click="resetImageDraft">Limpar</v-btn>
                    <v-btn color="primary" :loading="imageSaving" @click="saveImage">{{ imageDraft.id ? 'Salvar imagem' : 'Adicionar imagem' }}</v-btn>
                  </div>
                </v-card>
                <v-row>
                  <v-col v-for="img in editedItem.images" :key="img.id" cols="12" sm="6" md="4">
                    <v-card outlined>
                      <v-img :src="img.image_url" height="160" cover />
                      <v-card-text>
                        <div>{{ img.is_cover ? 'Capa' : 'Galeria' }} | Posicao {{ img.position || 0 }}</div>
                        <div class="text-caption" style="word-break:break-word">{{ img.image_url }}</div>
                      </v-card-text>
                      <v-card-actions>
                        <v-btn text small color="primary" @click="editImage(img)">Editar</v-btn>
                        <v-spacer />
                        <v-btn text small color="error" @click="deleteImage(img)">Excluir</v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-col>
                </v-row>
              </template>
            </v-tab-item>

            <v-tab-item>
              <v-alert v-if="!editedItem.id" type="info" outlined>Salve o restaurante primeiro para usar menus.</v-alert>
              <template v-else>
                <div class="menu-grid">
                  <v-card outlined>
                    <v-card-title class="py-3">Menus<v-spacer /><v-btn icon small color="primary" @click="openNewMenu"><v-icon>mdi-plus</v-icon></v-btn></v-card-title>
                    <v-divider />
                    <v-list dense>
                      <v-list-item v-for="menu in menus" :key="menu.id" :class="{ 'blue lighten-5': menuDraft.id === menu.id }" @click="selectMenu(menu)">
                        <v-list-item-content><v-list-item-title>{{ menu.title || `Menu ${menu.id}` }}</v-list-item-title></v-list-item-content>
                        <v-list-item-action><v-btn icon x-small color="error" @click.stop="deleteMenu(menu)"><v-icon small>mdi-delete</v-icon></v-btn></v-list-item-action>
                      </v-list-item>
                    </v-list>
                  </v-card>
                  <v-card outlined>
                    <v-card-title class="py-3">{{ menuDraft.id ? 'Editar menu' : 'Novo menu' }}<v-spacer /><v-btn text small @click="openNewMenu">Novo</v-btn></v-card-title>
                    <v-divider />
                    <v-card-text>
                      <v-text-field v-model="menuDraft.title" label="Titulo do menu *" outlined dense />
                      <div class="d-flex justify-space-between align-center mb-3">
                        <div class="subtitle-2">Secoes</div>
                        <v-btn small outlined color="primary" @click="addSection">Adicionar secao</v-btn>
                      </div>
                      <v-expansion-panels multiple flat>
                        <v-expansion-panel v-for="(section, s) in menuDraft.sections" :key="section.uid">
                          <v-expansion-panel-header>{{ section.name || `Secao ${s + 1}` }}</v-expansion-panel-header>
                          <v-expansion-panel-content>
                            <v-row>
                              <v-col cols="12" md="8"><v-text-field v-model="section.name" label="Nome da secao *" outlined dense /></v-col>
                              <v-col cols="12" md="2"><v-text-field v-model.number="section.position" label="Posicao" type="number" outlined dense /></v-col>
                              <v-col cols="12" md="2" class="d-flex align-center justify-end"><v-btn text small color="error" @click="removeSection(s)">Excluir</v-btn></v-col>
                            </v-row>
                            <div class="d-flex justify-space-between align-center mb-2">
                              <div class="text-caption">Itens</div>
                              <v-btn text small color="primary" @click="addItem(s)">Adicionar item</v-btn>
                            </div>
                            <v-card v-for="(item, i) in section.items" :key="item.uid" outlined class="pa-3 mb-3">
                              <v-row>
                                <v-col cols="12" md="5"><v-text-field v-model="item.name" label="Nome do item *" outlined dense /></v-col>
                                <v-col cols="12" md="2"><v-text-field v-model.number="item.position" label="Posicao" type="number" outlined dense /></v-col>
                                <v-col cols="12" md="5" class="d-flex align-center justify-end"><v-btn text small color="error" @click="removeItem(s, i)">Excluir item</v-btn></v-col>
                                <v-col cols="12"><v-textarea v-model="item.description" label="Descricao" outlined rows="2" /></v-col>
                              </v-row>
                            </v-card>
                          </v-expansion-panel-content>
                        </v-expansion-panel>
                      </v-expansion-panels>
                    </v-card-text>
                    <v-card-actions><v-spacer /><v-btn text @click="openNewMenu">Limpar</v-btn><v-btn color="primary" :loading="menuSaving" @click="saveMenu">Salvar menu</v-btn></v-card-actions>
                  </v-card>
                </div>
              </template>
            </v-tab-item>
          </v-tabs-items>
        </v-card-text>
        <v-card-actions><v-spacer /><v-btn text @click="closeDialog">Cancelar</v-btn><v-btn color="primary" :loading="saving" @click="saveRestaurant">Salvar restaurante</v-btn></v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDelete" max-width="420px">
      <v-card>
        <v-card-title>Confirmar exclusao</v-card-title>
        <v-card-text><v-alert type="warning" border="left" colored-border>Tem certeza que deseja excluir <strong>{{ editedItem.name }}</strong>?</v-alert></v-card-text>
        <v-card-actions><v-spacer /><v-btn text @click="dialogDelete = false">Cancelar</v-btn><v-btn color="error" :loading="saving" @click="confirmDelete">Excluir</v-btn></v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000">
      {{ snackbar.text }}
      <template v-slot:action="{ attrs }"><v-btn text v-bind="attrs" @click="snackbar.show = false">Fechar</v-btn></template>
    </v-snackbar>
  </div>
</template>

<script>
import api from '@/services/api'
const API_BASE = `${api.defaults.baseURL}/`
const blankRestaurant = () => ({ id: null, name: '', slug: '', city_code: '', short_description: '', description: '', capacity: null, has_private_area: false, has_view: false, address: '', latitude: '', longitude: '', is_active: true, images: [] })
const blankImage = () => ({ id: null, image_url: '', is_cover: false, position: 0 })
const blankMenu = () => ({ id: null, title: '', sections: [] })
let uid = 0
const nextUid = (p) => `${p}-${++uid}`

export default {
  name: 'IncentiveRestaurantsManager',
  data: () => ({
    loading: false, saving: false, dialog: false, dialogDelete: false, activeTab: 0, editedIndex: -1,
    items: [], cities: [], filters: { nome: '', city_code: null, is_active: 'all' }, editedItem: blankRestaurant(),
    imageDraft: blankImage(), imageSaving: false, menus: [], menuDraft: blankMenu(), menuSaving: false,
    pagination: { page: 1, perPage: 20, total: 0, lastPage: 1, perPageOptions: [10, 20, 30, 50, 100] },
    snackbar: { show: false, text: '', color: 'success' },
    headers: [{ text: 'ID', value: 'id' }, { text: 'Nome', value: 'name' }, { text: 'Cidade', value: 'city_code' }, { text: 'Capacidade', value: 'capacity' }, { text: 'Status', value: 'is_active' }, { text: 'Acoes', value: 'actions', align: 'end' }],
    statusOptions: [{ text: 'Todos', value: 'all' }, { text: 'Ativo', value: 'true' }, { text: 'Inativo', value: 'false' }]
  }),
  computed: {
    dialogTitle() { return this.editedIndex === -1 ? 'Novo Restaurante Incentive' : 'Editar Restaurante Incentive' },
    cityItems() { return (this.cities || []).map((c) => ({ value: String(c.tpocidcod), text: c.nome_en ? `${c.nome_pt} (${c.nome_en})` : c.nome_pt })) }
  },
  watch: {
    'editedItem.name'(v) { if (!(this.editedIndex !== -1 && this.editedItem.slug)) this.editedItem.slug = this.slugify(v) }
  },
  mounted() { this.fetchCities(); this.fetchRestaurants() },
  methods: {
    authHeaders() { const t = localStorage.getItem('auth_token'); return t ? { Authorization: `Bearer ${t}` } : {} },
    showMessage(text, color = 'success') { this.snackbar = { show: true, text, color } },
    slugify(v) { return String(v || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') },
    n(v) { const x = Number(v); return Number.isFinite(x) ? x : null },
    b(v) { return v === true || v === 't' || v === 'true' || v === 1 || v === '1' },
    cityLabel(code) { const m = this.cityItems.find((i) => i.value === String(code)); return m ? m.text : code || '-' },
    q() { const p = new URLSearchParams({ request: 'listar_restaurantes_paginate', page: String(this.pagination.page), per_page: String(this.pagination.perPage) }); if (this.filters.nome) p.append('filtro_nome', this.filters.nome); if (this.filters.city_code) p.append('filtro_city_code', this.filters.city_code); if (this.filters.is_active !== 'all') p.append('filtro_active', this.filters.is_active); return p.toString() },
    normRestaurant(x) { return { ...blankRestaurant(), ...x, id: this.n(x?.id), capacity: this.n(x?.capacity), has_private_area: this.b(x?.has_private_area), has_view: this.b(x?.has_view), is_active: x?.is_active === undefined ? true : this.b(x?.is_active), city_code: x?.city_code ? String(x.city_code) : '', images: Array.isArray(x?.images) ? x.images.map((i) => ({ id: this.n(i.id), image_url: i.image_url || '', is_cover: this.b(i.is_cover), position: this.n(i.position) || 0 })) : [] } },
    normMenu(x) { return { id: this.n(x?.id), title: x?.title || '', sections: Array.isArray(x?.sections) ? x.sections.map((s) => ({ id: this.n(s.id), uid: nextUid('s'), name: s.name || '', position: this.n(s.position) || 0, items: Array.isArray(s.items) ? s.items.map((i) => ({ id: this.n(i.id), uid: nextUid('i'), name: i.name || '', description: i.description || '', position: this.n(i.position) || 0 })) : [] })) : [] } },
    async fetchCities() { try { const r = await fetch(`${API_BASE}api_restaurante_incentive.php?request=listar_cidades_tpo`, { headers: this.authHeaders() }); const d = await r.json(); this.cities = Array.isArray(d?.data) ? d.data : [] } catch (e) { this.showMessage(`Erro ao carregar cidades: ${e.message}`, 'error') } },
    async fetchRestaurants() { this.loading = true; try { const r = await fetch(`${API_BASE}api_restaurante_incentive.php?${this.q()}`, { headers: this.authHeaders() }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); this.items = Array.isArray(d?.data) ? d.data.map(this.normRestaurant) : []; this.pagination.total = Number(d?.total || 0); this.pagination.lastPage = Math.max(1, Number(d?.last_page || 1)); this.pagination.page = Number(d?.current_page || this.pagination.page); this.pagination.perPage = Number(d?.per_page || this.pagination.perPage) } catch (e) { this.showMessage(`Erro ao carregar restaurantes: ${e.message}`, 'error') } finally { this.loading = false } },
    async fetchRestaurantDetail(id) { const r = await fetch(`${API_BASE}api_restaurante_incentive.php?request=buscar_restaurante&id=${id}`, { headers: this.authHeaders() }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); this.editedItem = this.normRestaurant(d.data || {}) },
    async fetchMenus(id) { const r = await fetch(`${API_BASE}api_restaurante_incentive.php?request=listar_menus&restaurant_id=${id}`, { headers: this.authHeaders() }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); this.menus = Array.isArray(d?.data) ? d.data : [] },
    applyFilters() { this.pagination.page = 1; this.fetchRestaurants() },
    resetFilters() { this.filters = { nome: '', city_code: null, is_active: 'all' }; this.pagination.page = 1; this.fetchRestaurants() },
    changePerPage() { this.pagination.page = 1; this.fetchRestaurants() },
    resetEditors() { this.activeTab = 0; this.imageDraft = blankImage(); this.menus = []; this.menuDraft = blankMenu() },
    openCreate() { this.editedIndex = -1; this.editedItem = blankRestaurant(); this.resetEditors(); this.dialog = true },
    async openEdit(item) { this.loading = true; try { this.editedIndex = this.items.findIndex((x) => x.id === item.id); this.resetEditors(); await this.fetchRestaurantDetail(item.id); await this.fetchMenus(item.id); this.dialog = true } catch (e) { this.showMessage(`Erro ao abrir restaurante: ${e.message}`, 'error') } finally { this.loading = false } },
    openDelete(item) { this.editedItem = this.normRestaurant(item); this.dialogDelete = true },
    closeDialog() { this.dialog = false; this.editedItem = blankRestaurant(); this.resetEditors() },
    restaurantPayload() { return { name: this.editedItem.name, slug: this.editedItem.slug || this.slugify(this.editedItem.name), city_code: this.editedItem.city_code, short_description: this.editedItem.short_description, description: this.editedItem.description, capacity: this.n(this.editedItem.capacity), has_private_area: !!this.editedItem.has_private_area, has_view: !!this.editedItem.has_view, address: this.editedItem.address, latitude: this.editedItem.latitude, longitude: this.editedItem.longitude, is_active: !!this.editedItem.is_active } },
    async saveRestaurant() { const p = this.restaurantPayload(); if (!p.name || !p.slug || !p.city_code) return this.showMessage('Informe nome, slug e cidade.', 'warning'); this.saving = true; try { const edit = !!this.editedItem.id; const url = edit ? `${API_BASE}api_restaurante_incentive.php?request=atualizar_restaurante&id=${this.editedItem.id}` : `${API_BASE}api_restaurante_incentive.php?request=criar_restaurante`; const r = await fetch(url, { method: edit ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', ...this.authHeaders() }, body: JSON.stringify(p) }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); await this.fetchRestaurants(); if (!edit && d?.id) await this.fetchRestaurantDetail(d.id); this.showMessage(edit ? 'Restaurante atualizado.' : 'Restaurante criado.') } catch (e) { this.showMessage(`Erro ao salvar restaurante: ${e.message}`, 'error') } finally { this.saving = false } },
    resetImageDraft() { this.imageDraft = blankImage() },
    editImage(img) { this.imageDraft = { id: img.id, image_url: img.image_url, is_cover: !!img.is_cover, position: this.n(img.position) || 0 } },
    async saveImage() { if (!this.editedItem.id) return this.showMessage('Salve o restaurante antes.', 'warning'); if (!this.imageDraft.image_url) return this.showMessage('Informe a URL da imagem.', 'warning'); this.imageSaving = true; try { const edit = !!this.imageDraft.id; const url = edit ? `${API_BASE}api_restaurante_incentive.php?request=atualizar_imagem&id=${this.imageDraft.id}` : `${API_BASE}api_restaurante_incentive.php?request=criar_imagem`; const r = await fetch(url, { method: edit ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', ...this.authHeaders() }, body: JSON.stringify({ restaurant_id: this.editedItem.id, image_url: this.imageDraft.image_url, is_cover: !!this.imageDraft.is_cover, position: this.n(this.imageDraft.position) || 0 }) }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); await this.fetchRestaurantDetail(this.editedItem.id); this.resetImageDraft(); this.showMessage(edit ? 'Imagem atualizada.' : 'Imagem criada.') } catch (e) { this.showMessage(`Erro ao salvar imagem: ${e.message}`, 'error') } finally { this.imageSaving = false } },
    async deleteImage(img) { this.imageSaving = true; try { const r = await fetch(`${API_BASE}api_restaurante_incentive.php?request=excluir_imagem&id=${img.id}`, { method: 'DELETE', headers: this.authHeaders() }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); await this.fetchRestaurantDetail(this.editedItem.id); if (this.imageDraft.id === img.id) this.resetImageDraft(); this.showMessage('Imagem excluida.') } catch (e) { this.showMessage(`Erro ao excluir imagem: ${e.message}`, 'error') } finally { this.imageSaving = false } },
    openNewMenu() { this.menuDraft = blankMenu() },
    async selectMenu(menu) { try { const r = await fetch(`${API_BASE}api_restaurante_incentive.php?request=buscar_menu&id=${menu.id}`, { headers: this.authHeaders() }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); this.menuDraft = this.normMenu(d.data || {}) } catch (e) { this.showMessage(`Erro ao carregar menu: ${e.message}`, 'error') } },
    addSection() { this.menuDraft.sections.push({ id: null, uid: nextUid('s'), name: '', position: this.menuDraft.sections.length, items: [] }) },
    removeSection(s) { this.menuDraft.sections.splice(s, 1) },
    addItem(s) { this.menuDraft.sections[s].items.push({ id: null, uid: nextUid('i'), name: '', description: '', position: this.menuDraft.sections[s].items.length }) },
    removeItem(s, i) { this.menuDraft.sections[s].items.splice(i, 1) },
    menuPayload() { return { restaurant_id: this.editedItem.id, title: this.menuDraft.title, sections: this.menuDraft.sections.filter((s) => String(s.name || '').trim()).map((s, si) => ({ name: s.name, position: this.n(s.position) ?? si, items: (s.items || []).filter((i) => String(i.name || '').trim()).map((i, ii) => ({ name: i.name, description: i.description || '', position: this.n(i.position) ?? ii })) })) } },
    async saveMenu() { if (!this.editedItem.id) return this.showMessage('Salve o restaurante antes.', 'warning'); if (!this.menuDraft.title) return this.showMessage('Informe o titulo do menu.', 'warning'); this.menuSaving = true; try { if (this.menuDraft.id) { const del = await fetch(`${API_BASE}api_restaurante_incentive.php?request=excluir_menu&id=${this.menuDraft.id}`, { method: 'DELETE', headers: this.authHeaders() }); const dd = await del.json(); if (dd?.error || dd?.success === false) throw new Error(dd.error || dd.message) } const r = await fetch(`${API_BASE}api_restaurante_incentive.php?request=criar_menu`, { method: 'POST', headers: { 'Content-Type': 'application/json', ...this.authHeaders() }, body: JSON.stringify(this.menuPayload()) }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); await this.fetchMenus(this.editedItem.id); this.showMessage('Menu salvo.') } catch (e) { this.showMessage(`Erro ao salvar menu: ${e.message}`, 'error') } finally { this.menuSaving = false } },
    async deleteMenu(menu) { this.menuSaving = true; try { const r = await fetch(`${API_BASE}api_restaurante_incentive.php?request=excluir_menu&id=${menu.id}`, { method: 'DELETE', headers: this.authHeaders() }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); await this.fetchMenus(this.editedItem.id); if (this.menuDraft.id === menu.id) this.openNewMenu(); this.showMessage('Menu excluido.') } catch (e) { this.showMessage(`Erro ao excluir menu: ${e.message}`, 'error') } finally { this.menuSaving = false } },
    async confirmDelete() { this.saving = true; try { const r = await fetch(`${API_BASE}api_restaurante_incentive.php?request=excluir_restaurante&id=${this.editedItem.id}`, { method: 'DELETE', headers: this.authHeaders() }); const d = await r.json(); if (d?.error || d?.success === false) throw new Error(d.error || d.message); this.dialogDelete = false; await this.fetchRestaurants(); this.showMessage('Restaurante excluido.') } catch (e) { this.showMessage(`Erro ao excluir restaurante: ${e.message}`, 'error') } finally { this.saving = false } }
  }
}
</script>

<style scoped>
.menu-grid { display:grid; grid-template-columns:300px minmax(0,1fr); gap:16px; }
@media (max-width:960px) { .menu-grid { grid-template-columns:1fr; } }
</style>
