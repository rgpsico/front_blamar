<template>
  <div class="incentivos-manager">
    <div class="incentivos-manager__header">
      <div>
        <h2>Incentivos</h2>
        <p>Listagem e manutencao completa do modulo de incentivos.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn color="primary" class="mr-2" @click="openCreate">Novo Incentivo</v-btn>
      <v-btn outlined color="primary" @click="fetchIncentives">Atualizar</v-btn>
    </div>

    <v-card class="incentivos-manager__filters" elevation="4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.nome"
            label="Buscar por nome"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.cidade"
            label="Cidade"
            dense
            outlined
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.status"
            :items="statusFilterOptions"
            label="Status"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.ativo"
            :items="activeFilterOptions"
            label="Ativo"
            dense
            outlined
            clearable
          ></v-select>
        </v-col>
      </v-row>
      <div class="incentivos-manager__filter-actions">
        <v-btn outlined color="primary" @click="applyFilters">Aplicar</v-btn>
        <v-btn text @click="resetFilters">Limpar</v-btn>
      </div>
    </v-card>

    <v-card elevation="6">
      <div class="incentivos-manager__table-tools">
        <span class="incentivos-manager__table-label">Ordenar por</span>
        <v-btn text small color="primary" @click="toggleIdSort">
          <v-icon left>{{ sortDesc ? 'mdi-arrow-down' : 'mdi-arrow-up' }}</v-icon>
          ID
        </v-btn>
      </div>
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        item-key="inc_id"
        class="elevation-0"
        :sort-by.sync="sortBy"
        :sort-desc.sync="sortDesc"
      >
        <template slot="item.thumnail" slot-scope="{ item }">
          <v-avatar v-if="item.thumnail" size="42" tile>
            <v-img :src="item.thumnail" :alt="item.inc_name || 'Thumbnail'"></v-img>
          </v-avatar>
          <span v-else>-</span>
        </template>
        <template slot="item.inc_is_active" slot-scope="{ item }">
          <v-chip :color="item.inc_is_active ? 'success' : 'grey'" small>
            {{ item.inc_is_active ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn small outlined color="info" class="mr-2" @click="openShowPage(item)">
            Ver
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
        <v-card-title class="incentivos-manager__dialog-title">
          <div class="incentivos-manager__dialog-heading">
            <div>{{ dialogTitle }}</div>
            <div v-if="dialogHotelLabel" class="incentivos-manager__dialog-subtitle">
              {{ dialogHotelLabel }}
            </div>
          </div>
          <v-spacer></v-spacer>
          <v-btn icon @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
      <v-card-text>
        <v-progress-linear
          v-if="loadingDetail"
          indeterminate
          color="primary"
          class="mb-4"
        ></v-progress-linear>
        <v-form>
          <v-tabs v-model="activeTab" background-color="transparent" grow>
              <v-tab>Banners</v-tab>
              <v-tab>Sidebar</v-tab>
              <v-tab>Programa</v-tab>
              <v-tab>Convention</v-tab>
              <v-tab>Contato</v-tab>
              <v-tab>Midias</v-tab>
              <v-tab>Imagens</v-tab>
              <v-tab>Quartos</v-tab>
              <v-tab>Amenities</v-tab>
              <v-tab>Dining</v-tab>
              <v-tab>Facilities</v-tab>
              <v-tab>Layouts de Sala</v-tab>
              <v-tab>Salas</v-tab>
              <v-tab>Notas</v-tab>
            </v-tabs>

            <v-tabs-items v-model="activeTab" class="mt-4">
              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Banners do hotel</div>
                </div>
                <div class="incentivos-manager__banner-grid">
                  <div>
                    <div class="text-subtitle-2 mb-2">Imagem principal ou video</div>
                    <v-row>
                      <v-col cols="12" md="4">
                        <v-select
                          v-model="editedItem.banner_main_type"
                          :items="bannerMainTypeOptions"
                          label="Tipo principal"
                          outlined
                          dense
                        ></v-select>
                      </v-col>
                      <v-col cols="12" md="8">
                        <v-text-field
                          v-model="editedItem.banner_main_url"
                          label="URL principal"
                          outlined
                          dense
                        ></v-text-field>
                      </v-col>
                    </v-row>
                    <div class="incentivos-manager__banner-preview incentivos-manager__banner-preview--main">
                      <template v-if="editedItem.banner_main_type === 'video' && editedItem.banner_main_url">
                        <iframe
                          v-if="isYoutubeUrl(editedItem.banner_main_url)"
                          :src="youtubeEmbedUrl(editedItem.banner_main_url)"
                          title="Video principal"
                          frameborder="0"
                          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                          allowfullscreen
                        ></iframe>
                        <video v-else :src="editedItem.banner_main_url" controls></video>
                      </template>
                      <v-img
                        v-else-if="editedItem.banner_main_url"
                        :src="editedItem.banner_main_url"
                        height="220"
                        cover
                      ></v-img>
                      <div v-else class="incentivos-manager__banner-placeholder">
                        <v-icon size="36">
                          {{ editedItem.banner_main_type === 'video' ? 'mdi-video' : 'mdi-image' }}
                        </v-icon>
                        <div class="mt-1">
                          {{ editedItem.banner_main_type === 'video' ? 'Video principal' : 'Sem imagem' }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div>
                    <div class="text-subtitle-2 mb-2">Tres outras imagens</div>
                    <div v-for="(url, index) in editedItem.banner_images" :key="`banner-img-${index}`" class="mb-3">
                      <v-text-field
                        v-model="editedItem.banner_images[index]"
                        :label="`Imagem ${index + 1} (URL)`"
                        outlined
                        dense
                      ></v-text-field>
                      <div class="incentivos-manager__banner-preview">
                        <v-img v-if="url" :src="url" height="100" cover></v-img>
                        <div v-else class="incentivos-manager__banner-placeholder">
                          <v-icon size="28">mdi-image</v-icon>
                          <div class="mt-1">Sem imagem</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Sidebar</div>
                </div>
                <v-row>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model.number="editedItem.star_rating"
                      label="Estrelas"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model.number="editedItem.total_rooms"
                      label="Total de quartos"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.hotel_contact.google_maps_url"
                      label="Google Maps URL"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      :value="sidebarNoteLanguage"
                      label="Idioma nota"
                      outlined
                      dense
                      @input="updateSidebarNoteLanguage"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="10">
                    <v-textarea
                      :value="sidebarNote"
                      label="Personal note"
                      outlined
                      rows="3"
                      @input="updateSidebarNote"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12" md="8">
                    <div class="incentivos-manager__map-embed">
                      <iframe
                        v-if="mapEmbedUrl"
                        :src="mapEmbedUrl"
                        width="100%"
                        height="160"
                        frameborder="0"
                        style="border:0;"
                        allowfullscreen
                        loading="lazy"
                      ></iframe>
                      <div v-else class="incentivos-manager__map-placeholder">
                        Mapa nao disponivel
                      </div>
                    </div>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <v-row>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="editedItem.inc_name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="editedItem.inc_status"
                      :items="statusOptions"
                      label="Status"
                      outlined
                      dense
                    ></v-select>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="editedItem.inc_description"
                      label="Descricao"
                      outlined
                      rows="3"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="editedItem.hotel_ref_id"
                      label="Hotel ref ID"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field
                      v-model="editedItem.hotel_name_snapshot"
                      label="Hotel snapshot"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="editedItem.thumnail"
                      label="Thumnail"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" class="pt-0">
                    <div class="text-caption text--secondary">
                      Esta imagem aparecera na listagem dos hoteis.
                    </div>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="editedItem.city_name" label="Cidade" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="editedItem.country_code"
                      label="Pais (ISO)"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="editedItem.inc_is_active" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <v-row>
                  <v-col cols="12">
                    <v-textarea
                      v-model="editedItem.convention.description"
                      label="Descricao"
                      outlined
                      rows="3"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="editedItem.floor_plan_url"
                      label="Floor plan of the area (URL)"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="editedItem.convention.imagem_planta_hotel"
                      label="Imagem planta do hotel (URL)"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="editedItem.convention.url360_hotel"
                      label="URL Tour 360 do hotel"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="editedItem.convention.total_rooms"
                      label="Total de salas"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="editedItem.convention.has_360" label="Tour 360" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <v-row>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="editedItem.hotel_contact.address" label="Endereco" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model="editedItem.hotel_contact.postal_code" label="CEP" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model="editedItem.hotel_contact.state_code" label="UF" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.hotel_contact.phone" label="Telefone" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.hotel_contact.email" label="Email" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="editedItem.hotel_contact.website_url" label="Website" outlined dense></v-text-field>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Midias</div>
                  <v-btn small outlined color="primary" @click="addMedia">Adicionar</v-btn>
                </div>
                <v-row v-for="(media, index) in editedItem.media" :key="`media-${index}`">
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="media.media_type"
                      :items="mediaTypeOptions"
                      label="Tipo"
                      outlined
                      dense
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="7">
                    <v-text-field v-model="media.media_url" label="URL" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="1">
                    <v-text-field
                      v-model.number="media.position"
                      label="#"
                      type="number"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="6" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeMedia(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="media.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Imagens do hotel</div>
                </div>
                <v-row class="incentivos-manager__image-inputs">
                  <v-col cols="12">
                    <div class="d-flex align-center justify-space-between">
                      <div class="text-subtitle-2">Adicionar imagem por URL</div>
                      <v-btn small outlined color="primary" @click="addImageDraft">Adicionar</v-btn>
                    </div>
                  </v-col>
                  <v-col
                    v-for="(draft, index) in imageDrafts"
                    :key="`image-draft-${index}`"
                    cols="12"
                  >
                    <v-row>
                      <v-col cols="12" md="7">
                        <v-text-field
                          v-model="draft.url"
                          label="URL da imagem"
                          outlined
                          dense
                        ></v-text-field>
                      </v-col>
                      <v-col cols="12" md="2">
                        <v-text-field
                          v-model.number="draft.order"
                          label="Ordem"
                          type="number"
                          outlined
                          dense
                        ></v-text-field>
                      </v-col>
                      <v-col cols="12" md="3" class="d-flex align-center">
                        <v-btn small color="primary" class="mr-2" @click="saveImageDraft(index)">
                          Salvar
                        </v-btn>
                        <v-btn small text color="error" @click="removeImageDraft(index)">
                          Remover
                        </v-btn>
                      </v-col>
                    </v-row>
                  </v-col>
                </v-row>
                <div v-if="imageGalleryItems.length === 0" class="incentivos-manager__empty">
                  Nenhuma imagem cadastrada.
                </div>
                <v-row v-else>
                  <v-col
                    v-for="(image, index) in imageGalleryItems"
                    :key="`image-${index}`"
                    cols="12"
                    md="4"
                  >
                    <v-card class="incentivos-manager__image-card" elevation="2">
                      <v-img :src="image.url" height="160" cover></v-img>
                      <v-card-text class="py-2">
                        <div class="text-caption text--secondary">{{ image.label }}</div>
                        <v-text-field
                          :value="image.order"
                          label="Ordem"
                          type="number"
                          dense
                          outlined
                          class="mt-2"
                          :disabled="!image.editable"
                          @input="updateImageOrder(image, $event)"
                        ></v-text-field>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Categoria quarto</div>
                </div>
                <v-row>
                  <v-col cols="12" md="5">
                    <v-text-field
                      v-model="editedItem.room_description"
                      label="Categoria quarto"
                      outlined
                      dense
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="7">
                    <v-textarea
                      v-model="editedItem.rooms_categories_text"
                      label="Descricao da categoria"
                      outlined
                      rows="2"
                    ></v-textarea>
                  </v-col>
                </v-row>
                <div class="d-flex justify-end">
                  <v-btn small outlined color="primary" @click="addRoomCategory">Adicionar</v-btn>
                </div>
                <v-divider class="my-4"></v-divider>
                <v-row v-for="(room, index) in editedItem.room_categories" :key="`room-${index}`">
                  <v-col cols="12" md="5">
                    <v-text-field v-model="room.room_name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model.number="room.quantity" label="Qtd" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model.number="room.area_m2" label="Area m2" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="room.view_type" label="Vista" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="room.room_type" label="Tipo" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="room.notes" label="Notas" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="1">
                    <v-text-field v-model.number="room.position" label="#" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeRoomCategory(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="room.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Room amenities</div>
                  <v-btn small outlined color="primary" @click="addRoomAmenity">Adicionar</v-btn>
                </div>
                <v-row v-for="(amenity, index) in editedItem.room_amenities" :key="`amenity-${index}`">
                  <v-col cols="12" md="6">
                    <v-text-field v-model="amenity.name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="amenity.icon" label="Icon" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="1">
                    <v-switch v-model="amenity.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeRoomAmenity(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Dining</div>
                  <v-btn small outlined color="primary" @click="addDining">Adicionar</v-btn>
                </div>
                <v-row v-for="(dining, index) in editedItem.dining" :key="`dining-${index}`">
                  <v-col cols="12" md="4">
                    <v-text-field v-model="dining.name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="dining.cuisine" label="Cozinha" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model.number="dining.capacity" label="Cap." type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model.number="dining.seating_capacity" label="Lugares" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model="dining.schedule" label="Horario" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="dining.description" label="Descricao" outlined rows="2"></v-textarea>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="dining.image_url" label="Imagem" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-switch v-model="dining.is_michelin" label="Michelin" inset></v-switch>
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-switch v-model="dining.can_be_private" label="Privativo" inset></v-switch>
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-text-field v-model.number="dining.position" label="#" type="number" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="6" md="2" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeDining(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-switch v-model="dining.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Facilities</div>
                  <v-btn small outlined color="primary" @click="addFacility">Adicionar</v-btn>
                </div>
                <v-row v-for="(facility, index) in editedItem.facilities" :key="`facility-${index}`">
                  <v-col cols="12" md="5">
                    <v-text-field v-model="facility.name" label="Nome" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="facility.icon" label="Icon" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-switch v-model="facility.is_active" label="Ativo" inset></v-switch>
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeFacility(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                </v-row>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Layouts por sala</div>
                </div>
                <div v-if="!editedItem.convention_rooms.length" class="text-caption text--secondary">
                  Cadastre as salas primeiro para definir os layouts.
                </div>
                <div
                  v-for="(room, index) in editedItem.convention_rooms"
                  :key="`layout-room-${index}`"
                  class="incentivos-manager__room-group"
                >
                  <div class="text-subtitle-2 mb-2">
                    {{ room.name || `Sala ${index + 1}` }}
                  </div>
                  <div class="incentivos-manager__tab-head">
                    <div>Layouts</div>
                    <v-btn small outlined color="primary" @click="addRoomLayout(index)">Adicionar layout</v-btn>
                  </div>
                  <v-row
                    v-for="(layout, lIndex) in room.layouts"
                    :key="`layout-${index}-${lIndex}`"
                  >
                    <v-col cols="12" md="6">
                      <v-text-field v-model="layout.layout_type" label="Tipo" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="3">
                      <v-text-field v-model.number="layout.capacity" label="Capacidade" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="1" class="d-flex align-center">
                      <v-btn icon color="error" @click="removeRoomLayout(index, lIndex)">
                        <v-icon>mdi-delete</v-icon>
                      </v-btn>
                    </v-col>
                  </v-row>
                  <v-divider v-if="index < editedItem.convention_rooms.length - 1" class="my-2"></v-divider>
                </div>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Salas de evento</div>
                  <v-btn small outlined color="primary" @click="addConventionRoom">Adicionar</v-btn>
                </div>
                <div
                  v-for="(room, index) in editedItem.convention_rooms"
                  :key="`croom-${index}`"
                  class="incentivos-manager__room-group"
                >
                  <v-row class="incentivos-manager__room-row">
                    <v-col cols="12" md="4">
                      <v-text-field v-model="room.name" label="Nome" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.area_m2" label="Area m2" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.height_m" label="Altura m" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="1" class="d-flex align-center">
                      <v-btn icon color="error" @click="removeConventionRoom(index)">
                        <v-icon>mdi-delete</v-icon>
                      </v-btn>
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_theater" label="Theater" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_classroom" label="School" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_u_shape" label="U-Shape" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_banquet" label="Banquet" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model.number="room.capacity_cocktail" label="Cocktail" type="number" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field v-model="room.notes" label="Notas" outlined dense></v-text-field>
                    </v-col>
                  </v-row>
                  <v-divider v-if="index < editedItem.convention_rooms.length - 1" class="my-2"></v-divider>
                </div>
              </v-tab-item>

              <v-tab-item>
                <div class="incentivos-manager__tab-head">
                  <div>Notas</div>
                  <v-btn small outlined color="primary" @click="addNote">Adicionar</v-btn>
                </div>
                <v-row v-for="(note, index) in editedItem.notes" :key="`note-${index}`">
                  <v-col cols="12" md="2">
                    <v-text-field v-model="note.language" label="Idioma" outlined dense></v-text-field>
                  </v-col>
                  <v-col cols="12" md="9">
                    <v-textarea v-model="note.note" label="Nota" outlined rows="2"></v-textarea>
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex align-center">
                    <v-btn icon color="error" @click="removeNote(index)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-col>
                </v-row>
              </v-tab-item>
            </v-tabs-items>
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
            Tem certeza que deseja excluir o incentivo
            <strong>{{ editedItem.inc_name || editedItem.inc_id }}</strong>?
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
const LAYOUT_TAB = 11

const blankItem = () => ({
  inc_id: null,
  inc_name: '',
  inc_description: '',
  hotel_ref_id: null,
  hotel_name_snapshot: '',
  thumnail: '',
  city_name: '',
  country_code: '',
  inc_status: 'active',
  inc_is_active: true,
  star_rating: null,
  total_rooms: null,
  floor_plan_url: '',
  hotel_contact: {
    address: '',
    postal_code: '',
    state_code: '',
    phone: '',
    email: '',
    website_url: '',
    google_maps_url: '',
    latitude: null,
    longitude: null
  },
  banner_main_type: 'image',
  banner_main_url: '',
  banner_images: ['', '', ''],
  media: [],
  room_description: '',
  rooms_categories_text: '',
  room_categories: [],
  room_amenities: [],
  dining: [],
  facilities: [],
  convention: {
    description: '',
    total_rooms: null,
    has_360: false,
    imagem_planta_hotel: '',
    url360_hotel: ''
  },
  convention_rooms: [],
  notes: []
})

export default {
  name: 'IncentivosManager',
  data() {
    return {
      loading: false,
      saving: false,
      loadingDetail: false,
      dialog: false,
      dialogDelete: false,
      activeTab: 0,
      items: [],
      sortBy: 'inc_id',
      sortDesc: false,
      filters: {
        nome: '',
        cidade: '',
        status: '',
        ativo: 'all'
      },
      editedIndex: -1,
      editedItem: blankItem(),
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      headers: [
        { text: 'ID', value: 'inc_id' },
        { text: 'Thumb', value: 'thumnail', sortable: false },
        { text: 'Nome', value: 'inc_name' },
        { text: 'Status', value: 'inc_status' },
        { text: 'Cidade', value: 'city_name' },
        { text: 'Ativo', value: 'inc_is_active', sortable: false },
        { text: 'Acoes', value: 'actions', sortable: false, align: 'end' }
      ],
      statusOptions: ['active', 'inactive', 'draft'],
      statusFilterOptions: [
        { text: 'Ativo', value: 'active' },
        { text: 'Inativo', value: 'inactive' },
        { text: 'Rascunho', value: 'draft' },
        { text: 'Arquivado', value: 'archived' }
      ],
      activeFilterOptions: [
        { text: 'Todos', value: 'all' },
        { text: 'Sim', value: 'true' },
        { text: 'Nao', value: 'false' }
      ],
      mediaTypeOptions: ['banner', 'gallery', 'video', 'map'],
      bannerMainTypeOptions: [
        { text: 'Imagem', value: 'image' },
        { text: 'Video', value: 'video' }
      ],
      imageDrafts: [{ url: '', order: null }]
    }
  },
  watch: {
    activeTab(val) {
      if (val === LAYOUT_TAB) {
        this.fetchRoomLayouts()
      }
    }
  },
  computed: {
    dialogTitle() {
      return this.editedIndex === -1 ? 'Novo Incentivo' : 'Editar Incentivo'
    },
    dialogHotelLabel() {
      if (this.editedIndex === -1) return ''
      const id = this.editedItem?.inc_id
      const name = (this.editedItem?.inc_name || '').trim()
      if (!id && !name) return ''
      if (id && name) return `Incentivo: ${id} - ${name}`
      if (id) return `Incentivo: ${id}`
      return `Incentivo: ${name}`
    },
    mapEmbedUrl() {
      const url = this.editedItem?.hotel_contact?.google_maps_url || ''
      if (!url) return ''
      const lower = url.toLowerCase().trim()
      if (lower.includes('google.com/maps')) {
        return lower.includes('output=embed') ? url : `${url}${url.includes('?') ? '&' : '?'}output=embed`
      }
      if (lower.includes('maps.app.goo.gl')) {
        return ''
      }
      return ''
    },
    imageGalleryItems() {
      const items = []
      const main = (this.editedItem?.banner_main_url || '').trim()
      if (main) {
        items.push({ url: main, label: 'Banner principal', order: 0, editable: false })
      }
      const banners = Array.isArray(this.editedItem?.banner_images) ? this.editedItem.banner_images : []
      banners.forEach((url, index) => {
        const clean = (url || '').trim()
        if (!clean) return
        items.push({ url: clean, label: `Banner ${index + 1}`, order: index + 1, editable: false })
      })
      const media = Array.isArray(this.editedItem?.media) ? this.editedItem.media : []
      media.forEach((entry, index) => {
        const url = (entry?.media_url || '').trim()
        if (!url) return
        const type = String(entry?.media_type || '').toLowerCase()
        if (type === 'video' || type === 'map') return
        const suffix = entry?.position !== null && entry?.position !== undefined ? ` #${entry.position}` : ''
        items.push({
          url,
          label: `${entry.media_type || 'media'}${suffix}`,
          order: entry?.position ?? null,
          editable: true,
          mediaIndex: index
        })
      })
      return [...items].sort((a, b) => {
        const ao = Number.isFinite(a.order) ? a.order : Number.MAX_SAFE_INTEGER
        const bo = Number.isFinite(b.order) ? b.order : Number.MAX_SAFE_INTEGER
        if (ao !== bo) return ao - bo
        return String(a.label || '').localeCompare(String(b.label || ''))
      })
    },
    sidebarNote() {
      return this.editedItem?.notes?.[0]?.note || ''
    },
    sidebarNoteLanguage() {
      return this.editedItem?.notes?.[0]?.language || ''
    }
  },
  mounted() {
    this.fetchIncentives()
  },
  methods: {
    isYoutubeUrl(value) {
      return /(?:youtube\.com|youtu\.be)/i.test(value || '')
    },
    youtubeEmbedUrl(value) {
      const raw = (value || '').trim()
      if (!raw) return ''
      if (/youtube\.com\/embed\//i.test(raw)) return raw
      try {
        const parsed = new URL(raw)
        const host = parsed.hostname.replace(/^www\./i, '')
        if (host === 'youtu.be') {
          const id = parsed.pathname.replace('/', '')
          return id ? `https://www.youtube.com/embed/${id}` : raw
        }
        if (host.endsWith('youtube.com')) {
          const v = parsed.searchParams.get('v')
          if (v) return `https://www.youtube.com/embed/${v}`
          const parts = parsed.pathname.split('/').filter(Boolean)
          const idx = parts.findIndex((part) => part === 'embed' || part === 'shorts')
          if (idx >= 0 && parts[idx + 1]) {
            return `https://www.youtube.com/embed/${parts[idx + 1]}`
          }
        }
      } catch (error) {
        return raw
      }
      return raw
    },
    splitMedia(mediaList) {
      const list = Array.isArray(mediaList) ? mediaList : []
      const isBannerSlot = (media) => {
        const type = media?.media_type
        const pos = Number(media?.position ?? 0)
        return (type === 'banner' || type === 'video') && pos >= 0 && pos <= 3
      }
      const bannerEntries = list.filter((media) => isBannerSlot(media))
      const otherEntries = list.filter((media) => !isBannerSlot(media))
      const sorted = [...bannerEntries].sort((a, b) => Number(a.position ?? 0) - Number(b.position ?? 0))
      const main =
        sorted.find((media) => media.media_type === 'video') ||
        sorted.find((media) => Number(media.position ?? 0) === 0) ||
        sorted[0]
      const mainType = main && main.media_type === 'video' ? 'video' : 'image'
      const mainUrl = main?.media_url || ''
      const images = sorted
        .filter((media) => media !== main && media.media_type === 'banner')
        .map((media) => media.media_url || '')
      while (images.length < 3) {
        images.push('')
      }
      return {
        banner: {
          mainType,
          mainUrl,
          images: images.slice(0, 3)
        },
        otherMedia: otherEntries
      }
    },
    buildBannerMediaPayload() {
      const items = []
      const mainUrl = (this.editedItem.banner_main_url || '').trim()
      if (mainUrl) {
        items.push({
          inc_media_id: null,
          media_type: this.editedItem.banner_main_type === 'video' ? 'video' : 'banner',
          media_url: mainUrl,
          position: 0,
          is_active: true
        })
      }
      const images = Array.isArray(this.editedItem.banner_images) ? this.editedItem.banner_images : []
      images.slice(0, 3).forEach((url, index) => {
        const clean = (url || '').trim()
        if (!clean) return
        items.push({
          inc_media_id: null,
          media_type: 'banner',
          media_url: clean,
          position: index + 1,
          is_active: true
        })
      })
      return items
    },
    authHeaders() {
      const token = localStorage.getItem('auth_token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    normalizeItem(item) {
      const program = item.program || item
      const relations = item.relations || item
      const normalizedRooms = Array.isArray(relations.convention_rooms)
        ? relations.convention_rooms.map((room) => ({
            ...room,
            capacity_theater:
              room.capacity_theater !== undefined && room.capacity_theater !== null && `${room.capacity_theater}` !== ''
                ? Number(room.capacity_theater)
                : null,
            capacity_classroom:
              room.capacity_classroom !== undefined && room.capacity_classroom !== null && `${room.capacity_classroom}` !== ''
                ? Number(room.capacity_classroom)
                : null,
            capacity_u_shape:
              room.capacity_u_shape !== undefined && room.capacity_u_shape !== null && `${room.capacity_u_shape}` !== ''
                ? Number(room.capacity_u_shape)
                : null,
            capacity_banquet:
              room.capacity_banquet !== undefined && room.capacity_banquet !== null && `${room.capacity_banquet}` !== ''
                ? Number(room.capacity_banquet)
                : null,
            capacity_cocktail:
              room.capacity_cocktail !== undefined && room.capacity_cocktail !== null && `${room.capacity_cocktail}` !== ''
                ? Number(room.capacity_cocktail)
                : null,
            layouts: Array.isArray(room.layouts) ? room.layouts : []
          }))
        : []

      const mediaSplit = this.splitMedia(relations.media)

      return {
        inc_id: program.inc_id || program.id || null,
        inc_name: program.inc_name || program.name || '',
        inc_description: program.inc_description || program.description || '',
        hotel_ref_id: program.hotel_ref_id || null,
        hotel_name_snapshot: program.hotel_name_snapshot || '',
        thumnail: program.thumnail || '',
        city_name: program.city_name || '',
        country_code: program.country_code || '',
        inc_status: program.inc_status || 'active',
        inc_is_active: program.inc_is_active !== undefined ? program.inc_is_active : true,
        star_rating:
          program.star_rating !== undefined && program.star_rating !== null && `${program.star_rating}` !== ''
            ? Number(program.star_rating)
            : null,
        total_rooms:
          program.total_rooms !== undefined && program.total_rooms !== null && `${program.total_rooms}` !== ''
            ? Number(program.total_rooms)
            : null,
        floor_plan_url: program.floor_plan_url || '',
        room_description: program.room_description || '',
        rooms_categories_text: program.rooms_categories_text || '',
        hotel_contact: relations.hotel_contact || {
          address: '',
          postal_code: '',
          state_code: '',
          phone: '',
          email: '',
          website_url: '',
          google_maps_url: '',
          latitude: null,
          longitude: null
        },
        banner_main_type: mediaSplit.banner.mainType,
        banner_main_url: mediaSplit.banner.mainUrl,
        banner_images: mediaSplit.banner.images,
        media: mediaSplit.otherMedia,
        room_categories: Array.isArray(relations.room_categories) ? relations.room_categories : [],
        room_amenities: Array.isArray(relations.room_amenities) ? relations.room_amenities : [],
        dining: Array.isArray(relations.dining) ? relations.dining : [],
        facilities: Array.isArray(relations.facilities) ? relations.facilities : [],
        convention: (() => {
          const baseConvention = relations.convention || {}
          return {
            inc_convention_id: baseConvention.inc_convention_id || null,
            description: baseConvention.description || '',
            total_rooms:
              baseConvention.total_rooms !== undefined &&
              baseConvention.total_rooms !== null &&
              `${baseConvention.total_rooms}` !== ''
                ? Number(baseConvention.total_rooms)
                : null,
            has_360: baseConvention.has_360 !== undefined ? baseConvention.has_360 : false,
            imagem_planta_hotel:
              baseConvention.imagem_planta_hotel || baseConvention.url_planta_image || '',
            url360_hotel: baseConvention.url360_hotel || ''
          }
        })(),
        convention_rooms: normalizedRooms,
        notes: Array.isArray(relations.notes) ? relations.notes : []
      }
    },
    buildQuery() {
      const params = new URLSearchParams()
      params.append('request', 'listar_incentives_simples')
      if (this.filters.nome) params.append('filtro_nome', this.filters.nome)
      if (this.filters.cidade) params.append('filtro_cidade', this.filters.cidade)
      if (this.filters.status) params.append('filtro_status', this.filters.status)
      if (this.filters.ativo) params.append('filtro_ativo', this.filters.ativo)
      return params.toString()
    },
    async fetchIncentives() {
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}api_incentives.php?${this.buildQuery()}`, {
          headers: this.authHeaders()
        })
        const data = await response.json()
        const list = Array.isArray(data) ? data : data.data
        this.items = Array.isArray(list) ? list.map(this.normalizeItem) : []
      } catch (error) {
        this.showMessage(`Erro ao carregar: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      this.fetchIncentives()
    },
    toggleIdSort() {
      this.sortBy = 'inc_id'
      this.sortDesc = !this.sortDesc
    },
    resetFilters() {
      this.filters = {
        nome: '',
        cidade: '',
        status: '',
        ativo: 'all'
      }
      this.fetchIncentives()
    },
    updateImageOrder(item, value) {
      if (!item || !item.editable) return
      const index = item.mediaIndex
      if (index === null || index === undefined) return
      const parsed = Number(value)
      this.editedItem.media[index].position = Number.isFinite(parsed) ? parsed : null
    },
    addImageDraft() {
      this.imageDrafts.push({ url: '', order: null })
    },
    removeImageDraft(index) {
      this.imageDrafts.splice(index, 1)
      if (this.imageDrafts.length === 0) {
        this.imageDrafts.push({ url: '', order: null })
      }
    },
    saveImageDraft(index) {
      const draft = this.imageDrafts[index]
      const url = (draft?.url || '').trim()
      if (!url) {
        this.showMessage('Informe a URL da imagem.', 'warning')
        return
      }
      const order = Number.isFinite(Number(draft.order)) ? Number(draft.order) : null
      this.editedItem.media.push({
        inc_media_id: null,
        media_type: 'gallery',
        media_url: url,
        position: order,
        is_active: true
      })
      this.imageDrafts[index].url = ''
      this.imageDrafts[index].order = null
      this.showMessage('Imagem adicionada.', 'success')
    },
    ensureSidebarNote() {
      if (!Array.isArray(this.editedItem.notes)) {
        this.editedItem.notes = []
      }
      if (!this.editedItem.notes[0]) {
        this.editedItem.notes.push({ inc_note_id: null, language: '', note: '' })
      }
    },
    updateSidebarNote(value) {
      this.ensureSidebarNote()
      this.editedItem.notes[0].note = value
    },
    updateSidebarNoteLanguage(value) {
      this.ensureSidebarNote()
      this.editedItem.notes[0].language = value
    },
    openCreate() {
      this.editedIndex = -1
      this.editedItem = blankItem()
      this.activeTab = 0
      this.dialog = true
    },
    async openEdit(item) {
      this.editedIndex = this.items.indexOf(item)
      this.editedItem = this.normalizeItem(item)
      this.activeTab = 0
      this.dialog = true

      if (item && item.inc_id) {
        await this.fetchIncentiveDetail(item.inc_id)
      }
    },
    openShowPage(item) {
      const id = item?.inc_id
      if (!id) {
        this.showMessage('ID do incentivo nao encontrado.', 'warning')
        return
      }
      const url = `https://webdeveloper.blumar.com.br/desenv/roger/client_area_incentive/hotel/hotel_show_section.php?id=${encodeURIComponent(
        id
      )}`
      window.open(url, '_blank')
    },
    openDelete(item) {
      this.editedItem = this.normalizeItem(item)
      this.dialogDelete = true
    },
    closeDialog() {
      this.dialog = false
    },
    addMedia() {
      this.editedItem.media.push({
        inc_media_id: null,
        media_type: 'gallery',
        media_url: '',
        position: 0,
        is_active: true
      })
    },
    removeMedia(index) {
      this.editedItem.media.splice(index, 1)
    },
    addRoomCategory() {
      this.editedItem.room_categories.push({
        inc_room_id: null,
        room_name: '',
        quantity: null,
        area_m2: null,
        view_type: '',
        room_type: '',
        notes: '',
        position: 0,
        is_active: true
      })
    },
    removeRoomCategory(index) {
      this.editedItem.room_categories.splice(index, 1)
    },
    addDining() {
      this.editedItem.dining.push({
        inc_dining_id: null,
        name: '',
        description: '',
        cuisine: '',
        capacity: null,
        seating_capacity: null,
        schedule: '',
        is_michelin: false,
        can_be_private: false,
        image_url: '',
        position: 0,
        is_active: true
      })
    },
    removeDining(index) {
      this.editedItem.dining.splice(index, 1)
    },
    addFacility() {
      this.editedItem.facilities.push({
        inc_facility_id: null,
        name: '',
        icon: '',
        is_active: true
      })
    },
    removeFacility(index) {
      this.editedItem.facilities.splice(index, 1)
    },
    addRoomAmenity() {
      this.editedItem.room_amenities.push({
        inc_room_amenity_id: null,
        name: '',
        icon: '',
        is_active: true
      })
    },
    removeRoomAmenity(index) {
      this.editedItem.room_amenities.splice(index, 1)
    },
    addConventionRoom() {
      this.editedItem.convention_rooms.push({
        inc_room_id: null,
        name: '',
        area_m2: null,
        height_m: null,
        capacity_theater: null,
        capacity_classroom: null,
        capacity_u_shape: null,
        capacity_banquet: null,
        capacity_cocktail: null,
        notes: '',
        layouts: []
      })
    },
    removeConventionRoom(index) {
      this.editedItem.convention_rooms.splice(index, 1)
    },
    addRoomLayout(roomIndex) {
      const room = this.editedItem.convention_rooms[roomIndex]
      if (!room.layouts) {
        this.$set(room, 'layouts', [])
      }
      room.layouts.push({
        inc_layout_id: null,
        layout_type: '',
        capacity: null
      })
    },
    removeRoomLayout(roomIndex, layoutIndex) {
      const room = this.editedItem.convention_rooms[roomIndex]
      if (room && Array.isArray(room.layouts)) {
        room.layouts.splice(layoutIndex, 1)
      }
    },
    addNote() {
      this.editedItem.notes.push({
        inc_note_id: null,
        language: '',
        note: ''
      })
    },
    removeNote(index) {
      this.editedItem.notes.splice(index, 1)
    },
    async fetchIncentiveDetail(id) {
      this.loadingDetail = true
      try {
        const response = await fetch(
          `${API_BASE}api_incentives.php?request=buscar_incentive&id=${id}`,
          { headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result && (result.error || result.success === false)) {
          throw new Error(result.error || result.message || 'Erro ao carregar incentivo')
        }
        const data = result && result.data ? result.data : result
        this.editedItem = this.normalizeItem(data || {})
      } catch (error) {
        this.showMessage(`Erro ao carregar incentivo: ${error.message}`, 'error')
      } finally {
        this.loadingDetail = false
      }
    },
    async fetchRoomLayouts() {
      if (!this.editedItem || !this.editedItem.inc_id) {
        return
      }
      try {
        const response = await fetch(`${API_BASE}api_layouts.php?id=${this.editedItem.inc_id}`, {
          headers: this.authHeaders()
        })
        const result = await response.json()
        if (result && (result.error || result.success === false)) {
          throw new Error(result.error || result.message || 'Erro ao carregar layouts')
        }
        const data = Array.isArray(result.data) ? result.data : []
        const layoutMap = new Map(
          data.map((room) => [Number(room.inc_room_id), Array.isArray(room.layouts) ? room.layouts : []])
        )
        this.editedItem.convention_rooms = (this.editedItem.convention_rooms || []).map((room) => {
          const roomId = room.inc_room_id !== undefined && room.inc_room_id !== null ? Number(room.inc_room_id) : null
          const layouts = roomId !== null && layoutMap.has(roomId) ? layoutMap.get(roomId) : room.layouts
          return {
            ...room,
            layouts: Array.isArray(layouts) ? layouts : []
          }
        })
      } catch (error) {
        this.showMessage(`Erro ao carregar layouts: ${error.message}`, 'error')
      }
    },
    async saveRoomLayouts() {
      if (!this.editedItem || !this.editedItem.inc_id) {
        this.showMessage('Salve o incentivo antes de cadastrar layouts.', 'warning')
        return
      }
      const rooms = Array.isArray(this.editedItem.convention_rooms) ? this.editedItem.convention_rooms : []
      if (!rooms.length) {
        this.showMessage('Cadastre as salas antes de salvar layouts.', 'warning')
        return
      }
      this.saving = true
      try {
        const payload = {
          convention_rooms: rooms.map((room) => ({
            inc_room_id: room.inc_room_id,
            layouts: Array.isArray(room.layouts) ? room.layouts : []
          }))
        }
        const response = await fetch(`${API_BASE}api_layouts.php?id=${this.editedItem.inc_id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            ...this.authHeaders()
          },
          body: JSON.stringify(payload)
        })
        const result = await response.json()
        if (result && (result.error || result.success === false)) {
          throw new Error(result.error || result.message || 'Erro ao salvar layouts')
        }
        this.showMessage('Layouts salvos.')
      } catch (error) {
        this.showMessage(`Erro ao salvar layouts: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async save() {
      if (this.activeTab === LAYOUT_TAB) {
        await this.saveRoomLayouts()
        return
      }
      if (!this.editedItem.inc_name) {
        this.showMessage('Informe o nome do incentivo.', 'warning')
        return
      }
      this.saving = true
      try {
        const isEdit = this.editedIndex > -1
        const request = isEdit ? 'atualizar_incentive' : 'criar_incentive'
        const method = isEdit ? 'PUT' : 'POST'
        const url = isEdit
          ? `${API_BASE}api_incentives.php?request=${request}&id=${this.editedItem.inc_id}`
          : `${API_BASE}api_incentives.php?request=${request}`

        const mediaPayload = [...this.buildBannerMediaPayload(), ...this.editedItem.media]
        const conventionPayload = (() => {
          const base = this.editedItem.convention || {}
          return {
            inc_convention_id: base.inc_convention_id || null,
            description: base.description || '',
            total_rooms:
              base.total_rooms !== undefined && base.total_rooms !== null && `${base.total_rooms}` !== ''
                ? Number(base.total_rooms)
                : null,
            has_360: base.has_360 !== undefined ? base.has_360 : false,
            imagem_planta_hotel: base.imagem_planta_hotel || base.url_planta_image || '',
            url360_hotel: base.url360_hotel || ''
          }
        })()

        const payload = {
          inc_id: this.editedItem.inc_id,
          inc_name: this.editedItem.inc_name,
          inc_description: this.editedItem.inc_description,
          hotel_ref_id: this.editedItem.hotel_ref_id,
          hotel_name_snapshot: this.editedItem.hotel_name_snapshot,
          thumnail: this.editedItem.thumnail,
          city_name: this.editedItem.city_name,
          country_code: this.editedItem.country_code,
          inc_status: this.editedItem.inc_status,
          inc_is_active: this.editedItem.inc_is_active,
          star_rating: this.editedItem.star_rating,
          total_rooms: this.editedItem.total_rooms,
          floor_plan_url: this.editedItem.floor_plan_url,
          room_description: this.editedItem.room_description,
          rooms_categories_text: this.editedItem.rooms_categories_text,
          media: mediaPayload,
          room_categories: this.editedItem.room_categories,
          room_amenities: this.editedItem.room_amenities,
          dining: this.editedItem.dining,
          facilities: this.editedItem.facilities,
          convention: conventionPayload,
          convention_rooms: this.editedItem.convention_rooms,
          notes: this.editedItem.notes
        }

        const contact = this.editedItem.hotel_contact || {}
        const hasContact =
          (contact.address && String(contact.address).trim() !== '') ||
          (contact.postal_code && String(contact.postal_code).trim() !== '') ||
          (contact.state_code && String(contact.state_code).trim() !== '') ||
          (contact.phone && String(contact.phone).trim() !== '') ||
          (contact.email && String(contact.email).trim() !== '') ||
          (contact.website_url && String(contact.website_url).trim() !== '') ||
          (contact.google_maps_url && String(contact.google_maps_url).trim() !== '') ||
          (contact.latitude !== null && contact.latitude !== undefined && String(contact.latitude).trim() !== '') ||
          (contact.longitude !== null && contact.longitude !== undefined && String(contact.longitude).trim() !== '')

        if (hasContact) {
          payload.hotel_contact = contact
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
        this.showMessage(isEdit ? 'Incentivo atualizado.' : 'Incentivo criado.')
        this.dialog = false
        await this.fetchIncentives()
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.saving = false
      }
    },
    async confirmDelete() {
      if (!this.editedItem.inc_id) {
        return
      }
      this.saving = true
      try {
        const response = await fetch(
          `${API_BASE}api_incentives.php?request=excluir_incentive&id=${this.editedItem.inc_id}`,
          { method: 'DELETE', headers: this.authHeaders() }
        )
        const result = await response.json()
        if (result.error) {
          throw new Error(result.error)
        }
        this.showMessage('Incentivo excluido.')
        this.dialogDelete = false
        await this.fetchIncentives()
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
.incentivos-manager__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.incentivos-manager__header h2 {
  margin: 0;
  font-size: 24px;
}

.incentivos-manager__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.incentivos-manager__filters {
  padding: 16px;
  margin-bottom: 16px;
}

.incentivos-manager__filter-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.incentivos-manager__table-tools {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px 0;
}

.incentivos-manager__table-label {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.6);
}

.incentivos-manager__dialog-title {
  display: flex;
  align-items: center;
}

.incentivos-manager__dialog-heading {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.incentivos-manager__dialog-subtitle {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.6);
}

.incentivos-manager__empty {
  padding: 12px;
  color: rgba(0, 0, 0, 0.6);
  font-size: 13px;
}

.incentivos-manager__image-card {
  overflow: hidden;
}

.incentivos-manager__image-inputs {
  margin-bottom: 8px;
}

.incentivos-manager__tab-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.incentivos-manager__room-group {
  padding: 8px 4px;
}

.incentivos-manager__room-row {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px;
  margin: 0;
  background: #f8fafc;
}

.incentivos-manager__banner-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
  gap: 16px;
}

.incentivos-manager__banner-preview {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  background: #f1f5f9;
}

.incentivos-manager__banner-preview iframe,
.incentivos-manager__banner-preview video {
  width: 100%;
  height: 100%;
  display: block;
  border: 0;
  object-fit: cover;
}

.incentivos-manager__banner-preview--main {
  min-height: 220px;
  height: 220px;
}

.incentivos-manager__banner-placeholder {
  height: 100%;
  min-height: 100px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.incentivos-manager__banner-preview--main .incentivos-manager__banner-placeholder {
  min-height: 220px;
}

.incentivos-manager__map-embed {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  background: #f8fafc;
}

.incentivos-manager__map-placeholder {
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-size: 12px;
}

@media (max-width: 960px) {
  .incentivos-manager__banner-grid {
    grid-template-columns: 1fr;
  }
}
</style>
