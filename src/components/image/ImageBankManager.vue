<template>
  <div class="image-bank">
    <div class="image-bank__header">
      <div>
        <h2>Banco de Imagem</h2>
        <p>Selecione cidade e hotel para listar imagens e use a busca por termo para nome, legenda, autor ou cidade.</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn outlined color="secondary" class="mr-2" @click="openCreate">Cadastrar</v-btn>
      <v-btn outlined color="primary" class="mr-2" @click="clearFilters">Limpar</v-btn>
      <v-btn color="primary" :loading="loading" @click="runSearch">Buscar</v-btn>
    </div>

    <v-card class="image-bank__filters" elevation="6">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="searchTerm"
            label="Buscar por nome, legenda, autor ou cidade"
            dense
            outlined
            @keyup.enter="runSearch"
          ></v-text-field>
        </v-col>

        <v-col cols="12" md="4">
          <v-autocomplete
            v-model="selectedCity"
            :items="cityOptions"
            item-text="nome_en"
            return-object
            label="Cidade"
            dense
            outlined
            @change="fetchHotelsByCity"
          ></v-autocomplete>
        </v-col>

        <v-col cols="12" md="4">
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
            @change="fetchImagesForHotel"
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
        <span>Cidade</span>
        <strong>{{ selectedCity?.nome_en || 'Sem cidade' }}</strong>
      </div>
      <div class="image-bank__summary-item">
        <span>Hotel</span>
        <strong>{{ selectedHotel?.nome_for || 'Sem hotel' }}</strong>
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
              <span v-if="image.nome_hotel">Hotel: {{ image.nome_hotel }}</span>
              <span v-else-if="image.mneu_for">Hotel: {{ image.mneu_for }}</span>
              <span v-if="image.nome_cidade">Cidade: {{ image.nome_cidade }}</span>
              <span v-else-if="image.fk_cidcod">Cidade: {{ image.fk_cidcod }}</span>
              <span v-if="image.autor">Autor: {{ image.autor }}</span>
            </div>
            <div class="image-bank__actions">
              <v-btn text small color="primary" @click="openPreview(image)">Preview</v-btn>
              <v-btn text small @click="copyUrl(image)">Copiar URL</v-btn>
              <v-btn text small color="secondary" @click="openEdit(image)">Editar</v-btn>
              <v-btn text small color="error" @click="openDelete(image)">Excluir</v-btn>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-card v-else class="image-bank__empty" elevation="0">
      <v-icon size="36" color="primary">mdi-image-off</v-icon>
      <p>Nenhuma imagem encontrada. Ajuste filtros e tente novamente.</p>
    </v-card>

    <v-dialog v-model="previewDialog" max-width="1040px">
      <v-card>
        <v-card-title class="image-bank__dialog-title">
          <span>Preview</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="previewDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-img :src="previewUrl" height="520"></v-img>
          <div class="image-bank__dialog-meta">
            <span>PK: {{ previewImage?.pk_bco_img }}</span>
            <span>Tipo: {{ typeLabel(previewImage?.tp_produto) }}</span>
            <span v-if="previewImage?.legenda">Legenda: {{ previewImage.legenda }}</span>
            <span v-if="previewImage?.autor">Autor: {{ previewImage.autor }}</span>
            <span v-if="previewImage?.nome_hotel">Hotel: {{ previewImage.nome_hotel }}</span>
            <span v-else-if="previewImage?.mneu_for">Hotel: {{ previewImage.mneu_for }}</span>
            <span v-if="previewImage?.nome_cidade">Cidade: {{ previewImage.nome_cidade }}</span>
            <span v-else-if="previewImage?.fk_cidcod">Cidade: {{ previewImage.fk_cidcod }}</span>
          </div>

          <div class="image-bank__dialog-sections">
            <div class="image-bank__dialog-section">
              <div class="image-bank__dialog-section-title">Descrições</div>
              <div class="image-bank__dialog-section-grid">
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Legenda PT</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.legenda_pt || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Legenda ESP</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.legenda_esp || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Palavras-chave</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.palavras_chave || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Nome do produto</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.nome_produto || 'Não informado' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="image-bank__dialog-section">
              <div class="image-bank__dialog-section-title">Direitos da imagem</div>
              <div class="image-bank__dialog-section-grid">
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Autorização</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.autorizacao || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Origem</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.origem || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Nacional</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.nacional ? 'Sim' : 'Não' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Fachada</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.fachada ? 'Sim' : 'Não' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="image-bank__dialog-section">
              <div class="image-bank__dialog-section-title">Status e datas</div>
              <div class="image-bank__dialog-section-grid">
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Ativo para cliente</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.ativo_cli ? 'Sim' : 'Não' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">AV</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.av ? 'Sim' : 'Não' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">AV3</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.av3 ? 'Sim' : 'Não' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Data cadastro</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.data_cadastro || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">Data validade</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.dt_validade || 'Não informado' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="image-bank__dialog-section">
              <div class="image-bank__dialog-section-title">Relacionamentos</div>
              <div class="image-bank__dialog-section-grid">
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">FK Cidade</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.fk_cidcod || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">ID Hotel</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.id_hotel || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">ID Service</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.id_service || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">ID City</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.id_city || 'Não informado' }}
                  </div>
                </div>
                <div class="image-bank__dialog-field">
                  <div class="image-bank__dialog-field-label">ID Special Destination</div>
                  <div class="image-bank__dialog-field-value">
                    {{ previewImage?.id_special_destination || 'Não informado' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="image-bank__dialog-section" v-if="previewImageUrls.length">
              <div class="image-bank__dialog-section-title">Links da imagem</div>
              <div class="image-bank__dialog-links-grid">
                <div
                  class="image-bank__dialog-link"
                  v-for="item in previewImageUrls"
                  :key="item.key"
                >
                  <div class="image-bank__dialog-link-label">{{ item.label }}</div>
                  <a :href="item.url" target="_blank" rel="noopener">{{ item.url }}</a>
                </div>
              </div>
            </div>

            <div class="image-bank__dialog-section">
              <div class="image-bank__dialog-section-title">Dados completos (JSON)</div>
              <pre class="image-bank__dialog-json">{{ previewImage }}</pre>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog v-model="editDialog" max-width="860px">
      <v-card>
        <v-card-title class="image-bank__dialog-title">
          <span>Editar imagem</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="editDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12">
              <v-radio-group
                v-model="createForm.destino_tipo"
                row
                label="Destino da imagem"
                @change="onCreateDestinoChange"
              >
                <v-radio label="Hotel" value="hotel"></v-radio>
                <v-radio label="Cidade" value="cidade"></v-radio>
              </v-radio-group>
            </v-col>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="createForm.cidade"
                :items="cityOptions"
                item-text="nome_en"
                return-object
                label="Cidade"
                dense
                outlined
                @change="onCreateCidadeChange"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="6" v-if="createForm.destino_tipo === 'hotel'">
              <v-select
                v-model="createForm.hotel"
                :items="createHotelOptions"
                item-text="nome_for"
                item-value="mneu_for"
                return-object
                label="Hotel"
                dense
                outlined
                :disabled="!createForm.cidade"
                @change="updateCreateFolder"
              ></v-select>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.legenda" label="Legenda" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.autor" label="Autor" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.legenda_pt" label="Legenda PT" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.legenda_esp" label="Legenda ESP" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="12">
              <v-text-field
                v-model="editForm.palavras_chave"
                label="Palavras-chave"
                dense
                outlined
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.nome_produto" label="Nome do produto" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="editForm.ordem" label="Ordem" dense outlined type="number"></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="editForm.tp_produto"
                :items="typeOptions.filter(item => item.value !== 'all' && item.value !== 'custom')"
                item-text="text"
                item-value="value"
                label="Tipo"
                dense
                outlined
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="editForm.id_hotel"
                label="Hotel (mneu_for)"
                dense
                outlined
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="editForm.fk_cidcod"
                label="FK Cidade"
                dense
                outlined
                type="number"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="editForm.id_hotel_ref"
                label="ID Hotel"
                dense
                outlined
                type="number"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="editForm.id_service"
                label="ID Service"
                dense
                outlined
                type="number"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="editForm.id_city"
                label="ID City"
                dense
                outlined
                type="number"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="editForm.id_special_destination"
                label="ID Special Destination"
                dense
                outlined
                type="number"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.origem" label="Origem" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="editForm.autorizacao"
                label="Autorização"
                dense
                outlined
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="editForm.data_cadastro"
                label="Data cadastro"
                dense
                outlined
                type="date"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="editForm.dt_validade"
                label="Data validade"
                dense
                outlined
                type="date"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="editForm.novo_caminho"
                label="Novo caminho (opcional)"
                dense
                outlined
                placeholder="hotel/pasta/arquivo.jpg"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-switch v-model="editForm.fachada" label="Fachada"></v-switch>
            </v-col>
            <v-col cols="12" md="3">
              <v-switch v-model="editForm.nacional" label="Nacional"></v-switch>
            </v-col>
            <v-col cols="12" md="3">
              <v-switch v-model="editForm.ativo_cli" label="Ativo cliente"></v-switch>
            </v-col>
            <v-col cols="12" md="3">
              <v-switch v-model="editForm.av" label="AV"></v-switch>
            </v-col>
            <v-col cols="12" md="3">
              <v-switch v-model="editForm.av3" label="AV3"></v-switch>
            </v-col>
            <v-col cols="12">
              <div class="image-bank__dialog-section-title">Links atuais</div>
              <div class="image-bank__dialog-links-grid">
                <div
                  class="image-bank__dialog-link"
                  v-for="item in editImageUrls"
                  :key="item.key"
                >
                  <div class="image-bank__dialog-link-label">{{ item.label }}</div>
                  <a :href="item.url" target="_blank" rel="noopener">{{ item.url }}</a>
                </div>
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.tam_1" label="tam_1" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.tam_2" label="tam_2" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.tam_3" label="tam_3" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.tam_4" label="tam_4" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.tam_5" label="tam_5" dense outlined></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="editForm.zip" label="zip" dense outlined></v-text-field>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="editDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="editLoading" @click="saveEdit">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="createDialog" max-width="720px">
      <v-card>
        <v-card-title class="image-bank__dialog-title">
          <span>Cadastrar imagem</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="createDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12">
              <v-radio-group
                v-model="createForm.destino_tipo"
                row
                label="Destino da imagem"
                @change="onCreateDestinoChange"
              >
                <v-radio label="Hotel" value="hotel"></v-radio>
                <v-radio label="Cidade" value="cidade"></v-radio>
              </v-radio-group>
            </v-col>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="createForm.cidade"
                :items="cityOptions"
                item-text="nome_en"
                return-object
                label="Cidade"
                dense
                outlined
                @change="onCreateCidadeChange"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="6" v-if="createForm.destino_tipo === 'hotel'">
              <v-select
                v-model="createForm.hotel"
                :items="createHotelOptions"
                item-text="nome_for"
                item-value="mneu_for"
                return-object
                label="Hotel"
                dense
                outlined
                :disabled="!createForm.cidade"
                @change="updateCreateFolder"
              ></v-select>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="createForm.titulo"
                label="Título da imagem"
                dense
                outlined
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="createForm.descricao"
                label="Descrição"
                dense
                outlined
              ></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-file-input
                v-model="createForm.arquivo"
                label="Arquivo"
                dense
                outlined
                accept="image/*"
                show-size
              ></v-file-input>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="createDialog = false">Cancelar</v-btn>
          <v-btn
            color="primary"
            :loading="createLoading"
            :disabled="!createForm.titulo || !createForm.arquivo"
            @click="submitCreate"
          >
            Salvar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDialog" max-width="520px">
      <v-card>
        <v-card-title class="image-bank__dialog-title">
          <span>Excluir imagem</span>
          <v-spacer></v-spacer>
          <v-btn icon @click="deleteDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          Tem certeza que deseja excluir esta imagem?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="deleteDialog = false">Cancelar</v-btn>
          <v-btn color="error" :loading="deleteLoading" @click="confirmDelete">Excluir</v-btn>
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

const API_BASE = 'api_banco_de_imagem.php'

export default {
  name: 'ImageBankManager',
  data() {
    return {
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
      editDialog: false,
      editLoading: false,
      deleteDialog: false,
      deleteLoading: false,
      deleteTarget: null,
      createDialog: false,
      createLoading: false,
      createHotelOptions: [],
      createForm: {
        destino_tipo: 'hotel',
        cidade: null,
        hotel: null,
        titulo: '',
        descricao: '',
        arquivo: null,
        pasta: ''
      },
      editForm: {
        pk_bco_img: null,
        legenda: '',
        legenda_pt: '',
        legenda_esp: '',
        palavras_chave: '',
        nome_produto: '',
        autor: '',
        origem: '',
        autorizacao: '',
        ordem: '',
        tp_produto: 1,
        id_hotel: '',
        fk_cidcod: '',
        id_hotel_ref: '',
        id_service: '',
        id_city: '',
        id_special_destination: '',
        data_cadastro: '',
        dt_validade: '',
        fachada: false,
        nacional: false,
        ativo_cli: false,
        av: false,
        av3: false,
        tam_1: '',
        tam_2: '',
        tam_3: '',
        tam_4: '',
        tam_5: '',
        zip: '',
        novo_caminho: ''
      },
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
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
    previewUrl() {
      if (!this.previewImage) {
        return ''
      }
      return this.imagePreview(this.previewImage)
    },
    previewImageUrls() {
      if (!this.previewImage) {
        return []
      }
      const baseUrl = 'https://www.blumar.com.br/'
      const toUrl = value => {
        if (!value) return ''
        if (value.startsWith('http://') || value.startsWith('https://')) return value
        return baseUrl + value.replace(/^\/+/, '').replace(/ /g, '%20')
      }
      const order = ['tam_4', 'tam_3', 'tam_2', 'tam_1', 'tam_5']
      const items = []
      const urls = this.previewImage.urls || {}
      order.forEach(key => {
        const url = urls[key] || toUrl(this.previewImage[key])
        if (url) {
          items.push({ key, label: key.toUpperCase(), url })
        }
      })
      if (this.previewImage.zip) {
        const url = toUrl(this.previewImage.zip)
        if (url) {
          items.push({ key: 'zip', label: 'ZIP', url })
        }
      }
      return items
    },
    editImageUrls() {
      if (!this.editForm) {
        return []
      }
      const baseUrl = 'https://www.blumar.com.br/'
      const toUrl = value => {
        if (!value) return ''
        if (value.startsWith('http://') || value.startsWith('https://')) return value
        return baseUrl + value.replace(/^\/+/, '').replace(/ /g, '%20')
      }
      const order = ['tam_4', 'tam_3', 'tam_2', 'tam_1', 'tam_5']
      const items = []
      order.forEach(key => {
        const url = toUrl(this.editForm[key])
        if (url) {
          items.push({ key, label: key.toUpperCase(), url })
        }
      })
      if (this.editForm.zip) {
        const url = toUrl(this.editForm.zip)
        if (url) {
          items.push({ key: 'zip', label: 'ZIP', url })
        }
      }
      return items
    }
  },
  
  mounted() {
    if (window.electronAPI?.getLocalIP) {
      window.electronAPI.getLocalIP().then(ip => {
        console.log('Banco de imagem: IP', ip)
      })
    }
    this.fetchCities()
  },
  methods: {
    async getLocalIp() {
      try {
        if (typeof window !== 'undefined' && window.electronAPI?.getLocalIP) {
          return await window.electronAPI.getLocalIP()
        }
      } catch (error) {
        return ''
      }
      return ''
    },
    showMessage(text, color) {
      this.snackbar.text = text
      this.snackbar.color = color || 'success'
      this.snackbar.show = true
    },
    openCreate() {
      this.createDialog = true
    },
    async fetchCities() {
      try {
        const response = await api.get(`${API_BASE}?action=list_cities`)
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
          this.images = []
          return
        }
        const cityName = this.selectedCity?.nome_en || ''
        const response = await api.get(
          `${API_BASE}?action=hotels_by_city&city=${encodeURIComponent(cityName)}`
        )
        const data = response.data
        this.hotelOptions = Array.isArray(data?.hotels) ? data.hotels : []
        this.selectedHotel = null
        this.images = []
      } catch (error) {
        this.showMessage(`Erro ao buscar hoteis: ${error.message}`, 'error')
      }
    },
    async fetchCreateHotelsByCity() {
      try {
        if (!this.createForm.cidade || this.createForm.destino_tipo !== 'hotel') {
          this.createHotelOptions = []
          this.createForm.hotel = null
          return
        }
        const cityName = this.createForm.cidade?.nome_en || ''
        const response = await api.get(
          `${API_BASE}?action=hotels_by_city&city=${encodeURIComponent(cityName)}`
        )
        const data = response.data
        this.createHotelOptions = Array.isArray(data?.hotels) ? data.hotels : []
        this.createForm.hotel = null
      } catch (error) {
        this.showMessage(`Erro ao buscar hoteis: ${error.message}`, 'error')
      }
    },
    onCreateCidadeChange() {
      this.createForm.hotel = null
      if (this.createForm.destino_tipo === 'hotel') {
        this.fetchCreateHotelsByCity()
      } else {
        this.createHotelOptions = []
      }
      this.updateCreateFolder()
    },
    onCreateDestinoChange() {
      this.createForm.hotel = null
      if (this.createForm.destino_tipo !== 'hotel') {
        this.createHotelOptions = []
      }
      this.updateCreateFolder()
    },
    updateCreateFolder() {
      const tipo = this.createForm.destino_tipo
      if (tipo === 'cidade' && this.createForm.cidade?.nome_en) {
        const slug = this.normalizeFolderName(this.createForm.cidade.nome_en)
        this.createForm.pasta = `cidade/${slug}`
        return
      }
      if (tipo === 'hotel' && this.createForm.hotel?.nome_for) {
        const slug = this.normalizeFolderName(this.createForm.hotel.nome_for)
        this.createForm.pasta = `hotel/${slug}`
        return
      }
    },
    normalizeFolderName(value) {
      if (!value) return ''
      return String(value)
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '_')
        .replace(/[^a-z0-9_/-]/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_+|_+$/g, '')
    },
    async runSearch() {
      this.loading = true
      try {
        const hasSearch = !!this.searchTerm
        const hasHotel = !!this.selectedHotel?.mneu_for
        const hasCity = !!this.selectedCity

        if (hasSearch) {
          const response = await api.get(
            `${API_BASE}?action=search_by_name&termo=${encodeURIComponent(this.searchTerm)}`
          )
          let results = Array.isArray(response.data?.images) ? response.data.images : []

          if (hasCity && this.selectedCity?.cidade_cod) {
            results = results.filter(
              img => String(img.fk_cidcod) === String(this.selectedCity.cidade_cod)
            )
          }
          if (hasHotel) {
            results = results.filter(img => String(img.mneu_for) === String(this.selectedHotel.mneu_for))
          }

          this.images = results
          return
        }

        if (hasHotel) {
          await this.fetchImagesForHotel()
          return
        }

        if (hasCity) {
          await this.fetchImagesForCity()
          return
        }

        this.showMessage('Informe um termo de busca ou selecione cidade e hotel.', 'warning')
      } catch (error) {
        this.showMessage(`Erro ao buscar imagens: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchImagesForHotel() {
      if (!this.selectedHotel?.mneu_for) {
        this.images = []
        return
      }
      this.loading = true
      try {
        const response = await api.get(
          `${API_BASE}?action=hotel_images&hotel_id=${encodeURIComponent(this.selectedHotel.mneu_for)}`
        )
        this.images = Array.isArray(response.data?.images) ? response.data.images : []
      } catch (error) {
        this.showMessage(`Erro ao buscar imagens: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    async fetchImagesForCity() {
      if (!this.selectedCity?.cidade_cod) {
        this.images = []
        return
      }
      this.loading = true
      try {
        const response = await api.get(
          `${API_BASE}?action=city_generic_images&cidade_cod=${encodeURIComponent(
            this.selectedCity.cidade_cod
          )}`
        )
        this.images = Array.isArray(response.data?.images) ? response.data.images : []
      } catch (error) {
        this.showMessage(`Erro ao buscar imagens da cidade: ${error.message}`, 'error')
      } finally {
        this.loading = false
      }
    },
    clearFilters() {
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
    async openPreview(image) {
      this.previewImage = image
      this.previewDialog = true
      if (!image?.pk_bco_img) {
        return
      }
      try {
        const response = await api.get(
          `${API_BASE}?action=get_image_data&pk_bco_img=${encodeURIComponent(image.pk_bco_img)}`
        )
        if (response.data?.success && response.data?.data) {
          this.previewImage = {
            ...this.previewImage,
            ...response.data.data
          }
        }
      } catch (error) {
        this.showMessage(`Erro ao carregar detalhes da imagem: ${error.message}`, 'error')
      }
    },
    async openEdit(image) {
      if (!image?.pk_bco_img) {
        return
      }
      this.editDialog = true
      this.editLoading = true
      try {
        const response = await api.get(
          `${API_BASE}?action=get_image_data&pk_bco_img=${encodeURIComponent(image.pk_bco_img)}`
        )
        const data = response.data?.data || {}
        this.editForm = {
          pk_bco_img: image.pk_bco_img,
          legenda: data.legenda || image.legenda || '',
          legenda_pt: data.legenda_pt || '',
          legenda_esp: data.legenda_esp || '',
          palavras_chave: data.palavras_chave || '',
          nome_produto: data.nome_produto || '',
          autor: data.autor || image.autor || '',
          origem: data.origem || '',
          autorizacao: data.autorizacao || '',
          ordem: data.ordem ?? '',
          tp_produto: Number(data.tp_produto ?? image.tp_produto ?? 1),
          id_hotel: data.mneu_for || image.mneu_for || '',
          fk_cidcod: data.fk_cidcod ?? '',
          id_hotel_ref: data.id_hotel ?? '',
          id_service: data.id_service ?? '',
          id_city: data.id_city ?? '',
          id_special_destination: data.id_special_destination ?? '',
          data_cadastro: data.data_cadastro || '',
          dt_validade: data.dt_validade || '',
          fachada: data.fachada === true || data.fachada === 't',
          nacional: data.nacional === true || data.nacional === 't',
          ativo_cli: data.ativo_cli === true || data.ativo_cli === 't',
          av: data.av === true || data.av === 't',
          av3: data.av3 === true || data.av3 === 't',
          tam_1: data.tam_1 || '',
          tam_2: data.tam_2 || '',
          tam_3: data.tam_3 || '',
          tam_4: data.tam_4 || '',
          tam_5: data.tam_5 || '',
          zip: data.zip || '',
          novo_caminho: ''
        }
      } catch (error) {
        this.showMessage(`Erro ao carregar dados para edição: ${error.message}`, 'error')
      } finally {
        this.editLoading = false
      }
    },
    async saveEdit() {
      if (!this.editForm?.pk_bco_img) {
        return
      }
      this.editLoading = true
      try {
        const toNumberOrNull = value => {
          if (value === null || value === undefined || value === '') {
            return undefined
          }
          const n = Number(value)
          return Number.isNaN(n) ? undefined : n
        }

          const payload = {
            pk_bco_img: this.editForm.pk_bco_img,
            tp_produto: this.editForm.tp_produto,
            legenda: this.editForm.legenda,
            legenda_pt: this.editForm.legenda_pt,
            legenda_esp: this.editForm.legenda_esp,
            palavras_chave: this.editForm.palavras_chave,
            nome_produto: this.editForm.nome_produto,
            autor: this.editForm.autor,
            origem: this.editForm.origem,
            autorizacao: this.editForm.autorizacao,
            id_hotel: this.editForm.id_hotel,
            fk_cidcod: toNumberOrNull(this.editForm.fk_cidcod),
            id_hotel_ref: toNumberOrNull(this.editForm.id_hotel_ref),
            id_service: toNumberOrNull(this.editForm.id_service),
            id_city: toNumberOrNull(this.editForm.id_city),
            id_special_destination: toNumberOrNull(this.editForm.id_special_destination),
            ordem: Number(this.editForm.ordem || 0),
          fachada: this.editForm.fachada ? 't' : 'f',
          nacional: this.editForm.nacional ? 't' : 'f',
          ativo_cli: this.editForm.ativo_cli ? 't' : 'f',
          av: this.editForm.av ? 't' : 'f',
          av3: this.editForm.av3 ? 't' : 'f',
            data_cadastro: this.editForm.data_cadastro,
            dt_validade: this.editForm.dt_validade,
            tam_1: this.editForm.tam_1,
          tam_2: this.editForm.tam_2,
          tam_3: this.editForm.tam_3,
          tam_4: this.editForm.tam_4,
          tam_5: this.editForm.tam_5,
          zip: this.editForm.zip,
          novo_caminho: this.editForm.novo_caminho || undefined
        }
        const response = await api.post(`${API_BASE}?action=update_metadata`, payload)
        if (response.data?.success) {
          this.showMessage('Imagem atualizada com sucesso.')
          this.editDialog = false
          this.updateImageInList(payload)
        } else {
          this.showMessage(response.data?.error || 'Erro ao atualizar.', 'error')
        }
      } catch (error) {
        this.showMessage(`Erro ao salvar: ${error.message}`, 'error')
      } finally {
        this.editLoading = false
      }
    },
    updateImageInList(payload) {
      const index = this.images.findIndex(img => img.pk_bco_img === payload.pk_bco_img)
      if (index === -1) {
        return
      }
      const updated = {
        ...this.images[index],
        legenda: payload.legenda,
        legenda_pt: payload.legenda_pt,
        legenda_esp: payload.legenda_esp,
        palavras_chave: payload.palavras_chave,
        nome_produto: payload.nome_produto,
        autor: payload.autor,
        ordem: payload.ordem,
        tp_produto: payload.tp_produto,
        mneu_for: payload.id_hotel,
        fk_cidcod: payload.fk_cidcod || this.images[index].fk_cidcod,
        fachada: payload.fachada,
        nacional: payload.nacional,
        ativo_cli: payload.ativo_cli,
        av: payload.av,
        av3: payload.av3,
        data_cadastro: payload.data_cadastro,
        dt_validade: payload.dt_validade,
        origem: payload.origem,
        autorizacao: payload.autorizacao,
        tam_1: payload.tam_1,
        tam_2: payload.tam_2,
        tam_3: payload.tam_3,
        tam_4: payload.tam_4,
        tam_5: payload.tam_5,
        zip: payload.zip
      }
      this.$set(this.images, index, updated)
      if (this.previewImage?.pk_bco_img === payload.pk_bco_img) {
        this.previewImage = { ...this.previewImage, ...updated }
      }
    },
    async submitCreate() {
      if (!this.createForm.titulo || !this.createForm.arquivo) {
        return
      }
      this.createLoading = true
      try {
        const formData = new FormData()
        formData.append('titulo', this.createForm.titulo)
        formData.append('descricao', this.createForm.descricao || '')
        const pastaNormalizada = this.normalizeFolderName(this.createForm.pasta || '')
        formData.append('pasta', pastaNormalizada)
        if (this.createForm.cidade?.nome_en) {
          formData.append('cidade_nome', this.normalizeFolderName(this.createForm.cidade.nome_en))
        }
        if (this.createForm.cidade?.cidade_cod) {
          formData.append('fk_cidcod', this.createForm.cidade.cidade_cod)
        }
        formData.append('arquivo', this.createForm.arquivo)

        const action =
          this.createForm.destino_tipo === 'hotel' ? 'upload_image_hotel' : 'upload_image'
        if (this.createForm.destino_tipo === 'hotel' && this.createForm.hotel?.nome_for) {
          formData.append('hotel_nome', this.normalizeFolderName(this.createForm.hotel.nome_for))
        }
        if (this.createForm.destino_tipo === 'hotel' && this.createForm.hotel?.mneu_for) {
          formData.append('mneu_for', this.createForm.hotel.mneu_for)
        }
        if (this.createForm.destino_tipo === 'hotel') {
          const userIp = await this.getLocalIp()
          
          if (userIp) {
            formData.append('user_ip', userIp)
          }
        }
        const response = await api.post(`${API_BASE}?action=${action}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        if (response.data?.success) {
          const newImage = response.data?.image
          if (newImage) {
            this.images = [newImage, ...this.images]
          }
          this.showMessage('Imagem cadastrada com sucesso.')
          this.createDialog = false
          this.createForm = {
            destino_tipo: 'hotel',
            cidade: null,
            hotel: null,
            titulo: '',
            descricao: '',
            arquivo: null,
            pasta: ''
          }
          return
        }

        this.showMessage(response.data?.error || 'Erro ao cadastrar.', 'error')
      } catch (error) {
        this.showMessage(`Erro ao cadastrar: ${error.message}`, 'error')
      } finally {
        this.createLoading = false
      }
    },
    async copyUrl(image) {
      const url = this.imagePreview(image)
      try {
        await navigator.clipboard.writeText(url)
        this.showMessage('URL copiada.')
      } catch (error) {
        this.showMessage('Nao foi possivel copiar.', 'error')
      }
    },
    openDelete(image) {
      this.deleteTarget = image || null
      this.deleteDialog = true
    },
    async confirmDelete() {
      if (!this.deleteTarget?.pk_bco_img) {
        return
      }
      this.deleteLoading = true
      try {
        const response = await api.post(`${API_BASE}?action=delete_image`, {
          pk_bco_img: this.deleteTarget.pk_bco_img
        })
        if (response.data?.success) {
          this.images = this.images.filter(img => img.pk_bco_img !== this.deleteTarget.pk_bco_img)
          this.showMessage('Imagem excluida com sucesso.')
          this.deleteDialog = false
          this.deleteTarget = null
          return
        }
        this.showMessage(response.data?.error || 'Erro ao excluir.', 'error')
      } catch (error) {
        this.showMessage(`Erro ao excluir: ${error.message}`, 'error')
      } finally {
        this.deleteLoading = false
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
  row-gap: 4px;
  margin-top: 10px;
  flex-wrap: wrap;
  align-items: center;
}

::v-deep .image-bank__actions .v-btn {
  min-width: 0;
  padding: 0 6px;
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
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 8px 16px;
  margin-top: 12px;
  color: #64748b;
  font-size: 13px;
}

.image-bank__dialog-links {
  margin-top: 16px;
}

.image-bank__dialog-sections {
  margin-top: 16px;
  display: grid;
  gap: 16px;
}

.image-bank__dialog-section {
  background: #ffffff;
  border-radius: 14px;
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
}

.image-bank__dialog-section-title {
  font-size: 13px;
  color: #0f172a;
  font-weight: 600;
  margin-bottom: 10px;
}

.image-bank__dialog-section-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px 16px;
}

.image-bank__dialog-field {
  display: grid;
  gap: 4px;
}

.image-bank__dialog-field-label {
  font-size: 12px;
  color: #64748b;
}

.image-bank__dialog-field-value {
  font-size: 13px;
  color: #0f172a;
  font-weight: 600;
}

.image-bank__dialog-links-title {
  font-size: 13px;
  color: #0f172a;
  font-weight: 600;
  margin-bottom: 8px;
}

.image-bank__dialog-links-grid {
  display: grid;
  gap: 10px;
}

.image-bank__dialog-link {
  background: #f8fafc;
  border-radius: 10px;
  padding: 8px 10px;
  display: grid;
  gap: 6px;
}

.image-bank__dialog-link-label {
  font-size: 12px;
  color: #0f172a;
  font-weight: 600;
}

.image-bank__dialog-link a {
  font-size: 12px;
  color: #2563eb;
  word-break: break-all;
}

.image-bank__dialog-json {
  margin: 0;
  background: #0f172a;
  color: #e2e8f0;
  padding: 12px;
  border-radius: 10px;
  font-size: 12px;
  white-space: pre-wrap;
  word-break: break-word;
}

</style>
