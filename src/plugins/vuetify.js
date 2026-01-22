import Vue from 'vue'
import Vuetify from 'vuetify'

Vue.use(Vuetify)

export default new Vuetify({
  theme: {
    themes: {
      light: {
        primary: '#F9020E',
        secondary: '#0E7490',
        accent: '#F59E0B',
        background: '#F8FAFC',
        info: '#0F172A'
      }
    }
  },
  icons: {
    iconfont: 'mdi'
  }
})
