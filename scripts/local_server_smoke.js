const axios = require('axios')

async function run() {
  process.env.API_TOKEN = ''
  process.env.LOCAL_UPLOAD_PORT = '4546'

  const { startLocalServer } = require('../electron/local_server')
  const server = startLocalServer()
  const baseUrl = 'http://127.0.0.1:4546'

  try {
    const hotelUrl = `${baseUrl}/api/upload_from_erp_enviar_para_hotel`
    const cidadeUrl = `${baseUrl}/api/upload_from_erp_enviar_para_cidade`

    let hotelStatus = null
    let cidadeStatus = null

    try {
      await axios.post(hotelUrl, null, { timeout: 5000 })
    } catch (err) {
      hotelStatus = err?.response?.status || null
    }

    try {
      await axios.post(cidadeUrl, null, { timeout: 5000 })
    } catch (err) {
      cidadeStatus = err?.response?.status || null
    }

    if (hotelStatus !== 400 || cidadeStatus !== 400) {
      throw new Error(`Unexpected status: hotel=${hotelStatus} cidade=${cidadeStatus}`)
    }

    console.log('local_server_smoke: OK')
  } finally {
    server.close()
  }
}

run().catch(err => {
  console.error('local_server_smoke: FAIL', err.message)
  process.exit(1)
})
