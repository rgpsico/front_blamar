const express = require('express')
const multer = require('multer')
const archiver = require('archiver')
const sharp = require('sharp')
const os = require('os')
const fs = require('fs')
const path = require('path')

function getConfig() {
  return {
    basePath: process.env.BASE_PATH || 'Z:\\\\wwwinternet\\\\bancodeimagemfotos',
    basePathCidade: process.env.BASE_PATH_CIDADE || 'Z:\\\\wwwinternet\\\\bancoimagemfotos\\\\cidade',
    basePathHotel: process.env.BASE_PATH_HOTEL || 'Z:\\\\wwwinternet\\\\bancoimagemfotos\\\\hotel',
    token: process.env.API_TOKEN || '',
    port: Number(process.env.LOCAL_UPLOAD_PORT || 4545)
  }
}

const SIZES = {
  tbn: [135, 90],
  small: [300, 200],
  med: [450, 300],
  grd: [840, 560]
}

const EXTENSOES_IMAGEM = new Set(['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'])

const upload = multer({ dest: os.tmpdir() })

function normalizeName(value) {
  if (!value) return ''
  const raw = String(value).trim().toLowerCase()
  const cleaned = raw.replace(/[^a-z0-9-_]+/g, '_')
  return cleaned.replace(/_+/g, '_').replace(/^_+|_+$/g, '')
}

function sanitizeRelPath(input) {
  if (!input) return null
  const raw = String(input).trim().replace(/\\/g, '/')
  if (raw.includes('..') || raw.startsWith('/')) return null
  const parts = raw.split('/').filter(Boolean)
  for (const part of parts) {
    if (!/^[a-zA-Z0-9-_]+$/.test(part)) return null
  }
  return parts.join('/')
}

function ensureDir(targetDir) {
  fs.mkdirSync(targetDir, { recursive: true })
}

function moveFile(src, dest) {
  try {
    fs.renameSync(src, dest)
  } catch (error) {
    fs.copyFileSync(src, dest)
    fs.unlinkSync(src)
  }
}

function createZip(zipPath, files) {
  return new Promise((resolve, reject) => {
    const output = fs.createWriteStream(zipPath)
    const archive = archiver('zip', { zlib: { level: 9 } })

    output.on('close', resolve)
    archive.on('error', reject)

    archive.pipe(output)
    files.forEach(filePath => {
      if (fs.existsSync(filePath)) {
        archive.file(filePath, { name: path.basename(filePath) })
      }
    })
    archive.finalize()
  })
}

async function gerarTamanhos(caminhoOriginal, pasta, nomeBase, ext) {
  const paths = {}
  for (const [key, [w, h]] of Object.entries(SIZES)) {
    const novoNome = `${nomeBase}_${key}${ext}`
    const destino = path.join(pasta, novoNome)
    const pipeline = sharp(caminhoOriginal).resize(w, h, { fit: 'inside' })
    if (ext === '.jpg' || ext === '.jpeg') {
      await pipeline.jpeg({ quality: 90 }).toFile(destino)
    } else if (ext === '.png') {
      await pipeline.png({ quality: 90 }).toFile(destino)
    } else if (ext === '.webp') {
      await pipeline.webp({ quality: 90 }).toFile(destino)
    } else {
      await pipeline.toFile(destino)
    }
    paths[key] = destino
  }
  return paths
}

function validateToken(req, res, expectedToken) {
  if (!expectedToken) return true
  const authHeader = req.headers.authorization || ''
  const queryToken = req.query.token || ''
  const raw = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : authHeader
  const token = raw || queryToken
  if (token && token === expectedToken) return true
  res.status(401).json({ success: false, error: 'Token invalido' })
  return false
}

function setupCors(app) {
  app.use((req, res, next) => {
    res.setHeader('Access-Control-Allow-Origin', '*')
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
    if (req.method === 'OPTIONS') {
      res.status(204).end()
      return
    }
    next()
  })
}

function startLocalServer() {
  const config = getConfig()
  const app = express()
  setupCors(app)

  app.post('/api/upload_from_erp_enviar_para_hotel', upload.single('file'), async (req, res) => {
    console.log('[HOTEL] upload request received')
    if (!validateToken(req, res, config.token)) return

    const startedAt = Date.now()
    const body = req.body || {}
    const file = req.file
    const cidadeNome = String(body.cidade_nome || '').trim()
    const hotelNome = String(body.hotel_nome || '').trim()
    const skipZip = String(req.query.skip_zip || '').toLowerCase() === '1'
    const skipSizes = String(req.query.skip_sizes || '').toLowerCase() === '1'

    if (!file) return res.status(400).json({ success: false, error: 'Arquivo nao enviado' })
    if (!cidadeNome || !hotelNome) {
      return res.status(400).json({ success: false, error: 'cidade_nome e hotel_nome sao obrigatorios' })
    }

    const cidadeSlug = normalizeName(cidadeNome)
    const hotelSlug = normalizeName(hotelNome)
    const safePath = sanitizeRelPath(`${cidadeSlug}/${hotelSlug}`)
    if (!safePath) return res.status(400).json({ success: false, error: 'Pasta invalida' })

    const ext = path.extname(file.originalname || '').toLowerCase()
    if (!EXTENSOES_IMAGEM.has(ext)) {
      return res.status(400).json({ success: false, error: 'Extensao nao permitida' })
    }

    const destinoDir = path.join(config.basePathHotel, safePath.replace(/\//g, path.sep))
    ensureDir(destinoDir)

    const nomeBase = normalizeName(path.parse(file.originalname).name) || 'arquivo'
    const filename = `${nomeBase}_${Math.floor(Date.now() / 1000)}${ext}`
    const caminhoFinal = path.join(destinoDir, filename)

    try {
      console.log('[HOTEL] save start', { destinoDir, filename })
      moveFile(file.path, caminhoFinal)
      console.log('[HOTEL] save done', { ms: Date.now() - startedAt })
      const sizes = skipSizes ? {} : await gerarTamanhos(caminhoFinal, destinoDir, nomeBase, ext)
      if (!skipSizes) console.log('[HOTEL] sizes done', { ms: Date.now() - startedAt })

      const zipFilename = `${nomeBase}_${Math.floor(Date.now() / 1000)}_all.zip`
      const zipPath = path.join(destinoDir, zipFilename)
      if (!skipZip) {
        await createZip(zipPath, [caminhoFinal, ...Object.values(sizes)])
        console.log('[HOTEL] zip done', { ms: Date.now() - startedAt })
      }

      return res.json({
        success: true,
        cidade: cidadeSlug,
        hotel: hotelSlug,
        original: `bancoimagemfotos/hotel/${safePath}/${filename}`,
        sizes: Object.fromEntries(
          Object.entries(sizes).map(([key, value]) => [
            key,
            `bancoimagemfotos/hotel/${safePath}/${path.basename(value)}`
          ])
        ),
        zip: skipZip ? null : `bancoimagemfotos/hotel/${safePath}/${zipFilename}`,
        full_path: caminhoFinal
      })
    } catch (error) {
      console.error('[HOTEL] error', error)
      return res.status(500).json({ success: false, error: String(error) })
    }
  })

  app.post('/api/upload_from_erp_enviar_para_cidade', upload.single('file'), async (req, res) => {
    if (!validateToken(req, res, config.token)) return

    const startedAt = Date.now()
    const body = req.body || {}
    const file = req.file
    const cidadeNome = String(body.cidade_nome || '').trim()
    const skipZip = String(req.query.skip_zip || '').toLowerCase() === '1'
    const skipSizes = String(req.query.skip_sizes || '').toLowerCase() === '1'

    if (!file) return res.status(400).json({ success: false, error: 'Arquivo nao enviado' })
    if (!cidadeNome) return res.status(400).json({ success: false, error: 'cidade_nome e obrigatorio' })

    const safeCidade = sanitizeRelPath(cidadeNome)
    if (!safeCidade) return res.status(400).json({ success: false, error: 'Nome da cidade invalido' })

    const ext = path.extname(file.originalname || '').toLowerCase()
    if (!EXTENSOES_IMAGEM.has(ext)) {
      return res.status(400).json({ success: false, error: 'Extensao nao permitida' })
    }

    const destinoDir = path.join(config.basePathCidade, safeCidade.replace(/\//g, path.sep))
    ensureDir(destinoDir)

    const nomeBase = normalizeName(path.parse(file.originalname).name) || 'arquivo'
    const filename = `${nomeBase}_${Math.floor(Date.now() / 1000)}${ext}`
    const caminhoFinal = path.join(destinoDir, filename)

    try {
      console.log('[CIDADE] save start', { destinoDir, filename })
      moveFile(file.path, caminhoFinal)
      console.log('[CIDADE] save done', { ms: Date.now() - startedAt })
      const sizes = skipSizes ? {} : await gerarTamanhos(caminhoFinal, destinoDir, nomeBase, ext)
      if (!skipSizes) console.log('[CIDADE] sizes done', { ms: Date.now() - startedAt })

      const zipFilename = `${nomeBase}_${Math.floor(Date.now() / 1000)}_all.zip`
      const zipPath = path.join(destinoDir, zipFilename)
      if (!skipZip) {
        await createZip(zipPath, [caminhoFinal, ...Object.values(sizes)])
        console.log('[CIDADE] zip done', { ms: Date.now() - startedAt })
      }

      return res.json({
        success: true,
        cidade: safeCidade,
        original: `bancoimagemfotos/cidade/${safeCidade}/${filename}`,
        sizes: Object.fromEntries(
          Object.entries(sizes).map(([key, value]) => [
            key,
            `bancoimagemfotos/cidade/${safeCidade}/${path.basename(value)}`
          ])
        ),
        zip: skipZip ? null : `bancoimagemfotos/cidade/${safeCidade}/${zipFilename}`,
        full_path: caminhoFinal
      })
    } catch (error) {
      console.error('[CIDADE] error', error)
      return res.status(500).json({ success: false, error: String(error) })
    }
  })

  const server = app.listen(config.port, '127.0.0.1', () => {
    console.log(`[LOCAL SERVER] upload server on http://127.0.0.1:${config.port}`)
  })

  return server
}

module.exports = { startLocalServer }
