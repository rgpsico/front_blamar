import axios from 'axios'

const isElectron =
  typeof window !== 'undefined' &&
  window.process &&
  window.process.type

const isFileProtocol =
  typeof window !== 'undefined' &&
  window.location &&
  window.location.protocol === 'file:'

const isProd = process.env.NODE_ENV === 'production'

const api = axios.create({
  baseURL: isElectron || isFileProtocol || isProd
    ? 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api'
    : '/api'
})

export default api
