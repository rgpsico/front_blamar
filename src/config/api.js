import axios from 'axios'

const isElectron = !!window?.process?.versions?.electron


const api = axios.create({
  baseURL: isElectron
    ? 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api'
    : '/api'
})

export default api
