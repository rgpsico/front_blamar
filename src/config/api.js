import axios from 'axios'

const isElectron = !!window?.process?.versions?.electron


const isProd = process.env.NODE_ENV === 'production'

const api = axios.create({
  baseURL: isElectron || isProd
    ? 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api'
    : '/api'
})

export default api
