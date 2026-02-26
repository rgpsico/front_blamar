<template>
  <div class="dashboard">
    <v-navigation-drawer
      v-model="drawer"
      :temporary="isMobile"
      app
      class="dashboard__drawer"
    >
      <div class="dashboard__brand">
        <div class="dashboard__brand-mark">BLUMAR</div>
        <span>ERP Turismo</span>
      </div>
      <div class="dashboard__user">
        <v-avatar size="44">
          <img :src="profile.avatar" :alt="profile.name" />
        </v-avatar>
        <div>
          <div class="dashboard__user-name">Ola {{ profile.name }}</div>
          <div class="dashboard__user-mail">{{ profile.email }}</div>
        </div>
      </div>
      <v-divider></v-divider>
      <v-list nav dense class="dashboard__menu-list">
        <v-list-group
          v-for="group in filteredMenu"
          :key="group.title"
          v-model="group.open"
          :prepend-icon="group.icon"
          no-action
        >
          <template v-slot:activator>
            <v-list-item-title>{{ group.title }}</v-list-item-title>
          </template>
          <v-list-item
            v-for="item in group.items"
            :key="item.title"
            link
            :class="{'dashboard__item--active': item.active}"
            @click="setPage(item.page)"
          >
            <v-list-item-icon>
              <v-icon :color="item.active ? 'primary' : undefined">{{ item.icon }}</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title>{{ item.title }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar app flat color="transparent" class="dashboard__appbar">
      <v-app-bar-nav-icon @click="drawer = !drawer" class="dashboard__menu"></v-app-bar-nav-icon>
      <v-toolbar-title class="dashboard__title">Dashboard</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="toggleTheme">
        <v-icon>{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
      </v-btn>
      <v-btn
        text
        class="dashboard__roadmap"
        @click="openRoadmap"
      >
        <v-icon left small>mdi-rocket-launch</v-icon>
        Atualizacoes
      </v-btn>
      <v-btn class="dashboard__logout" text color="primary" @click="logout">
        <v-icon left>mdi-logout</v-icon>
        Sair
      </v-btn>
      <div class="dashboard__profile">
        <v-avatar size="36" class="mr-2 dashboard__avatar-click" @click="openProfileDialog">
          <img :src="profile.avatar" :alt="profile.name" />
        </v-avatar>
        <div class="dashboard__profile-info">
          <span class="dashboard__profile-name">{{ profile.name }}</span>
          <span class="dashboard__profile-role">{{ profile.role }}</span>
        </div>
      </div>
    </v-app-bar>

    <v-main>
      <v-container class="dashboard__content">
        <div v-if="activePage === 'dashboard'">
          <v-row>
            <v-col cols="12" md="7">
              <div class="dashboard__badge">Painel do dia</div>
              <h1 class="dashboard__headline">Operacoes turisticas em ritmo alto</h1>
              <p class="dashboard__subhead">
                Monitoramento em tempo real de reservas, acessos e performance comercial da rede.
              </p>
              <v-row>
                <v-col cols="12" sm="6">
                  <v-card elevation="4" class="dashboard__metric">
                    <v-icon color="primary" size="28">mdi-account-group</v-icon>
                    <div>
                      <span>Clientes ativos</span>
                      <strong>312</strong>
                    </div>
                  </v-card>
                </v-col>
                <v-col cols="12" sm="6">
                  <v-card elevation="4" class="dashboard__metric">
                    <v-icon color="primary" size="28">mdi-eye-outline</v-icon>
                    <div>
                      <span>Acessos do dia</span>
                      <strong>1.842</strong>
                    </div>
                  </v-card>
                </v-col>
                <v-col cols="12" sm="6">
                  <v-card elevation="4" class="dashboard__metric">
                    <v-icon color="primary" size="28">mdi-file-document-outline</v-icon>
                    <div>
                      <span>Artigos publicados</span>
                      <strong>96</strong>
                    </div>
                  </v-card>
                </v-col>
                <v-col cols="12" sm="6">
                  <v-card elevation="4" class="dashboard__metric">
                    <v-icon color="primary" size="28">mdi-cash-multiple</v-icon>
                    <div>
                      <span>Vendas do mes</span>
                      <strong>R$ 438k</strong>
                    </div>
                  </v-card>
                </v-col>
              </v-row>
            </v-col>
            <v-col cols="12" md="5">
              <v-card class="dashboard__stat-card" elevation="8">
                <div class="dashboard__stat-header">
                  <h3>Indicadores rapidos</h3>
                  <v-chip color="primary" outlined small>Mock</v-chip>
                </div>
                <div class="dashboard__stat">
                  <span>Reservas em andamento</span>
                  <strong>148</strong>
                </div>
                <div class="dashboard__stat">
                  <span>Margem media</span>
                  <strong>23.4%</strong>
                </div>
                <div class="dashboard__stat">
                  <span>Allotment ativo</span>
                  <strong>87%</strong>
                </div>
                <div class="dashboard__stat">
                  <span>NPS geral</span>
                  <strong>62</strong>
                </div>
              </v-card>
              <v-card class="dashboard__alert" elevation="6">
                <div>
                  <h4>Alertas de hoje</h4>
                  <p>3 contratos vencem em 7 dias. 2 operacoes precisam revisao.</p>
                </div>
                <v-btn color="primary" outlined small>Ver detalhes</v-btn>
              </v-card>
            </v-col>
          </v-row>
        </div>
        <ImageBankManager v-else-if="activePage === 'image'" />
        <VideoBankManager v-else-if="activePage === 'video'" />
        <AtualizacoesSistema v-else-if="activePage === 'atualizacoes'" />
        <BeachHouseManager v-else-if="activePage === 'beach-house'" />
        <AbtManager v-else-if="activePage === 'abt'" />
        <BlogManager v-else-if="activePage === 'blog'" />
        <ExpertsManager v-else-if="activePage === 'experts'" />
        <CitiesManager v-else-if="activePage === 'cidades'" />
        <DeluxeManager v-else-if="activePage === 'deluxe'" />
        <ClientsManager v-else-if="activePage === 'clientes'" />
        <ClientesTarifarioManager v-else-if="activePage === 'clientes-tarifario'" />
        <EmployeesManager v-else-if="activePage === 'funcionarios'" />
        <UsersManager v-else-if="activePage === 'usuarios'" />
        <ApiUsersManager v-else-if="activePage === 'api-usuarios'" />
        <ProfilePermissionsManager v-else-if="activePage === 'perfis'" />
        <HotelsManager v-else-if="activePage === 'hotel'" />
        <IncentivosManager v-else-if="activePage === 'incentivos'" />
        <IncentivosManager v-else-if="activePage === 'hotel-incentive'" />
        <RestaurantsManager v-else-if="activePage === 'restaurantes'" />
        <NewslettersManager v-else-if="activePage === 'newsletters'" />
      </v-container>
    </v-main>

    <v-dialog v-model="profileDialog" max-width="640px">
      <v-card>
        <v-card-title>
          Editar perfil
          <v-spacer></v-spacer>
          <v-btn icon @click="profileDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="4">
              <div class="dashboard__profile-preview">
                <v-avatar size="96">
                  <img :src="profileForm.avatarPreview || profile.avatar" :alt="profileForm.name" />
                </v-avatar>
              </div>
              <v-file-input
                v-model="profileForm.avatarFile"
                label="Imagem do perfil"
                accept="image/*"
                outlined
                dense
                show-size
                @change="onAvatarSelected"
              ></v-file-input>
            </v-col>
            <v-col cols="12" md="8">
              <v-text-field v-model="profileForm.name" label="Nome" outlined dense></v-text-field>
              <v-text-field v-model="profileForm.email" label="Email" outlined dense></v-text-field>
              <v-text-field v-model="profileForm.role" label="Cargo" outlined dense></v-text-field>
              <v-text-field
                v-model="profileForm.password"
                label="Senha"
                type="password"
                outlined
                dense
                autocomplete="new-password"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="profileDialog = false">Cancelar</v-btn>
          <v-btn color="primary" @click="saveProfile">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <RoadmapModal
      ref="roadmapModal"
      v-model="roadmapDialog"
      @error="onRoadmapError"
    />
  </div>
</template>

<script>
import BeachHouseManager from '@/components/beach/BeachHouseManager.vue'
import AbtManager from '@/components/abt/AbtManager.vue'
import BlogManager from '@/components/blog/BlogManager.vue'
import ExpertsManager from '@/components/experts/ExpertsManager.vue'
import CitiesManager from '@/components/cities/CitiesManager.vue'
import DeluxeManager from '@/components/deluxe/DeluxeManager.vue'
import ClientsManager from '@/components/clients/ClientsManager.vue'
import ClientesTarifarioManager from '@/components/clients/ClientesTarifarioManager.vue'
import EmployeesManager from '@/components/employees/EmployeesManager.vue'
import ImageBankManager from '@/components/image/ImageBankManager.vue'
import VideoBankManager from '@/components/video/VideoBankManager.vue'
import HotelsManager from '@/components/hotels/HotelsManager.vue'
import IncentivosManager from '@/components/hotels/IncentivosManager.vue'
import RestaurantsManager from '@/components/restaurants/RestaurantsManager.vue'
import UsersManager from '@/components/users/UsersManager.vue'
import ProfilePermissionsManager from '@/components/users/ProfilePermissionsManager.vue'
import NewslettersManager from '@/components/newsletters/NewslettersManager.vue'
import ApiUsersManager from '@/components/users/ApiUsersManager.vue'
import AtualizacoesSistema from '@/components/system/AtualizacoesSistema.vue'
import RoadmapModal from '@/components/system/RoadmapModal.vue'

export default {
  name: 'DashboardPage',
  components: {
    BeachHouseManager,
    AbtManager,
    BlogManager,
    ExpertsManager,
    CitiesManager,
    DeluxeManager,
    ClientsManager,
    ClientesTarifarioManager,
    EmployeesManager,
    ImageBankManager,
    VideoBankManager,
    AtualizacoesSistema,
    RoadmapModal,
    HotelsManager,
    IncentivosManager,
    RestaurantsManager,
    UsersManager,
    ProfilePermissionsManager,
    NewslettersManager,
    ApiUsersManager
  },
  data() {
    return {
      drawer: true,
      activePage: 'dashboard',
      authPermissions: [],
      authProfile: null,
      menu: [
        {
          title: 'Geral',
          icon: 'mdi-view-dashboard',
          open: true,
          items: [
            { title: 'Dashboard', icon: 'mdi-view-dashboard', page: 'dashboard', active: true }
          ]
        },
        {
          title: 'Sites e Conteudos',
          icon: 'mdi-web',
          open: true,
          items: [
            { title: 'Around Brazil Tours', icon: 'mdi-earth', page: 'abt' },     
            { title: 'Banco de Imagem', icon: 'mdi-image-multiple',  page: 'image', permissions: ['BANCO_IMAGE']},
            { title: 'Banco de Video', icon: 'mdi-video', page: 'video', permissions: ['BANCO_VIDEO'] },
            
            // { title: 'Banco de Imagem Comercial', icon: 'mdi-briefcase', page: '' },
            { title: 'Beach House', icon: 'mdi-beach', page: 'beach-house' }, 
            { title: 'Blog Receptivo', icon: 'mdi-post-outline', page: 'blog', permissions: ['MANAGE_BLOG'] },
            { title: 'Brazilian Experts', icon: 'mdi-map', page: 'experts' },
            { title: 'Newsletters', icon: 'mdi-newspaper-variant-outline', page: 'newsletters' }
          ]
        },
        {
          title: 'Cadastros',
          icon: 'mdi-account-group',
          open: false,
          items: [
            { title: 'Funcionarios', icon: 'mdi-account-tie', page: 'funcionarios' },
            { title: 'Usuarios', icon: 'mdi-account', page: 'usuarios' },
            { title: 'Usuarios API', icon: 'mdi-shield-account', page: 'api-usuarios', permissions: ['MANAGE_API_USERS'] },
            { title: 'Perfis e Permissoes', icon: 'mdi-shield-account', page: 'perfis' },
            { title: 'Cidades', icon: 'mdi-city', page: 'cidades' },
            { title: 'Clientes', icon: 'mdi-account-multiple-outline', page: 'clientes' },
            { title: 'Clientes Tarifario', icon: 'mdi-account-cash-outline', page: 'clientes-tarifario' },
            { title: 'Contatos', icon: 'mdi-card-account-phone-outline' }
          ]
        },
        {
          title: 'Produtos e File',
          icon: 'mdi-file-cabinet',
          open: false,
          items: [
            { title: 'Deluxe', icon: 'mdi-diamond-stone', page: 'deluxe' },
            { title: 'File Web', icon: 'mdi-file-link' },
            { title: 'Guias', icon: 'mdi-map-marker-path' }
         
          ]
        },
        {
          title: 'Incentives',
          icon: 'mdi-gift',
          open: false,
          items: [
            { title: 'Hotel', icon: 'mdi-bed', page: 'hotel' },
            { title: 'Hotel Incentive', icon: 'mdi-gift-outline', page: 'hotel-incentive' },
            { title: 'Venues', icon: 'mdi-office-building', page: 'incentivos' },
            { title: 'Restaurantes', icon: 'mdi-silverware-fork-knife', page: 'restaurantes' }
          ]
        },
        {
          title: 'Sistema',
          icon: 'mdi-cog',
          open: false,
          items: [
            { title: 'Atualizacoes', icon: 'mdi-history', page: 'atualizacoes' }
          ]
        }
      ],
      profile: {
        name: '',
        role: '',
        email: '',
        avatar: 'https://i.pravatar.cc/100?img=32'
      },
      profileDialog: false,
      roadmapDialog: false,
      profileForm: {
        name: '',
        role: '',
        email: '',
        password: '',
        avatarFile: null,
        avatarPreview: ''
      }
    }
  },
  computed: {
    isMobile() {
      return this.$vuetify.breakpoint.smAndDown
    },
    isDark() {
      return this.$vuetify.theme.dark
    },
    filteredMenu() {
      if (!this.authPermissions || this.authPermissions.length === 0) {
        return this.menu
      }
      return this.menu
        .map(group => {
          const items = (group.items || []).filter(item => {
            if (item.page === 'dashboard') {
              return true
            }
            if (!item.permissions || item.permissions.length === 0) {
              return false
            }
            return item.permissions.some(perm => this.authPermissions.includes(perm))
          })
          return { ...group, items }
        })
        .filter(group => group.items && group.items.length > 0)
    }
  },
  methods: {
    toggleTheme() {
      this.$vuetify.theme.dark = !this.$vuetify.theme.dark
    },
    openProfileDialog() {
      this.profileForm = {
        name: this.profile.name,
        role: this.profile.role,
        email: this.profile.email,
        password: '',
        avatarFile: null,
        avatarPreview: this.profile.avatar
      }
      this.profileDialog = true
    },
    onAvatarSelected(file) {
      if (!file) {
        this.profileForm.avatarPreview = this.profile.avatar
        return
      }
      const reader = new FileReader()
      reader.onload = () => {
        this.profileForm.avatarPreview = String(reader.result || '')
      }
      reader.readAsDataURL(file)
    },
    saveProfile() {
      this.profile = {
        ...this.profile,
        name: this.profileForm.name,
        role: this.profileForm.role,
        email: this.profileForm.email,
        avatar: this.profileForm.avatarPreview || this.profile.avatar
      }
      this.profileDialog = false
    },
    loadProfile() {
      const raw = localStorage.getItem('auth_user')
      if (!raw) {
        return
      }
      try {
        const user = JSON.parse(raw) || {}
        this.profile = {
          ...this.profile,
          name: user.nome || user.apelido || user.cod_sis || 'Usuario',
          role: user.nivel || user.departamento || 'Usuario',
          email: user.email || ''
        }
      } catch (error) {
        // mantem fallback atual
      }
    },
    loadAccessControl() {
      try {
        const permsRaw = localStorage.getItem('auth_permissions')
        const perms = permsRaw ? JSON.parse(permsRaw) : []
        this.authPermissions = Array.isArray(perms)
          ? perms
              .map(item => String(item || '').trim().toUpperCase())
              .filter(Boolean)
          : []
      } catch (error) {
        this.authPermissions = []
      }
      try {
        const profileRaw = localStorage.getItem('auth_profile')
        this.authProfile = profileRaw ? JSON.parse(profileRaw) : null
      } catch (error) {
        this.authProfile = null
      }
    },
    logout() {
      this.$emit('logout')
    },
    openRoadmap() {
      this.roadmapDialog = true
      this.loadAtualizacoes()
    },
    loadAtualizacoes() {
      this.$nextTick(() => {
        if (this.$refs.roadmapModal && this.$refs.roadmapModal.loadAtualizacoes) {
          this.$refs.roadmapModal.loadAtualizacoes()
        }
      })
    },
    onRoadmapError(error) {
      // Evita quebrar o header caso a API falhe
      console.error('Erro ao carregar atualizacoes:', error)
    },
    setPage(page) {
      if (!page) {
        return
      }

      this.activePage = page
      this.menu.forEach(group => {
        group.items.forEach(item => {
          item.active = item.page === page
        })
      })
    }
  },
  mounted() {    
    this.loadProfile()
    this.loadAccessControl()
  }
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 50%, #e2f3f7 100%);
  font-family: 'Manrope', sans-serif;
}

.dashboard__drawer {
  border-right: 1px solid rgba(148, 163, 184, 0.2);
}

.dashboard__brand {
  padding: 24px 20px 12px;
  font-weight: 600;
  letter-spacing: 0.6px;
  color: #0f172a;
}

.dashboard__brand-mark {
  font-family: 'Playfair Display', serif;
  font-size: 22px;
  color: #f9020e;
}

.dashboard__user {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
}

.dashboard__user-name {
  font-weight: 600;
  font-size: 14px;
}

.dashboard__user-mail {
  font-size: 12px;
  color: #64748b;
}

.dashboard__menu-list {
  padding-top: 4px;
}

.dashboard__menu-list .v-list-item-title {
  font-size: 16px;
}

.dashboard__menu-list .v-list-group__header .v-list-item-title {
  font-size: 16.5px;
  font-weight: 600;
}

.dashboard__item--active {
  background: rgba(249, 2, 14, 0.08);
  border-radius: 12px;
}

.dashboard__appbar {
  backdrop-filter: blur(12px);
}

.dashboard__menu {
  margin-right: 8px;
}

.dashboard__title {
  font-weight: 700;
  color: #0f172a;
}

.dashboard__profile {
  display: flex;
  align-items: center;
  gap: 8px;
}

.dashboard__avatar-click {
  cursor: pointer;
}

.dashboard__profile-preview {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}

.dashboard__logout {
  margin-right: 12px;
}

.dashboard__roadmap {
  margin-right: 8px;
  text-transform: none;
  color: #0f172a;
}

.dashboard__roadmap:hover {
  background: rgba(249, 2, 14, 0.08);
}

.dashboard__profile-info {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.dashboard__profile-name {
  font-weight: 600;
  font-size: 13px;
}

.dashboard__profile-role {
  font-size: 11px;
  color: #64748b;
}

.dashboard__content {
  padding-top: 110px;
  padding-bottom: 60px;
}

.dashboard__badge {
  display: inline-flex;
  padding: 6px 14px;
  border-radius: 999px;
  background: rgba(249, 2, 14, 0.1);
  color: #7f1d1d;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.dashboard__headline {
  font-family: 'Playfair Display', serif;
  font-size: 38px;
  margin: 16px 0 12px;
  color: #0f172a;
}

.dashboard__subhead {
  color: #475569;
  font-size: 16px;
  line-height: 1.6;
  margin-bottom: 24px;
}

.dashboard__metric {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px;
  border-radius: 18px;
}

.dashboard__metric span {
  display: block;
  font-size: 13px;
  color: #64748b;
}

.dashboard__metric strong {
  font-size: 20px;
  color: #0f172a;
}

.dashboard__stat-card {
  padding: 24px;
  border-radius: 20px;
  margin-bottom: 20px;
}

.dashboard__stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.dashboard__stat {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}

.dashboard__stat:last-child {
  border-bottom: none;
}

.dashboard__stat span {
  color: #64748b;
  font-size: 14px;
}

.dashboard__stat strong {
  font-size: 20px;
  color: #0f172a;
}

.dashboard__alert {
  padding: 20px;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(249, 2, 14, 0.08), rgba(14, 116, 144, 0.08));
}

.dashboard__alert h4 {
  margin: 0 0 6px;
}

@media (max-width: 959px) {
  .dashboard__profile-info {
    display: none;
  }

  .dashboard__content {
    padding-top: 90px;
  }
}
</style>


