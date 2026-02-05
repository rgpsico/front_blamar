import axios from 'axios'

const isElectron =
  typeof window !== 'undefined' &&
  window.process &&
  window.process.type

const isFileProtocol =
  typeof window !== 'undefined' &&
  window.location &&
  window.location.protocol === 'file:'

const api = axios.create({
  baseURL: isElectron || isFileProtocol
    ? 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api'
    : '/api'
})

export default api
