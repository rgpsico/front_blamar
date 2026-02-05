const { app, BrowserWindow, ipcMain } = require('electron')
const os = require('os')
const path = require('path')

const isDev = !app.isPackaged

function createWindow () {
  const win = new BrowserWindow({
    width: 1400,
    height: 900,
    webPreferences: {
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js')
    }
  })

  if (isDev) {
    win.loadURL('http://localhost:8080')
    win.webContents.openDevTools()
  } else {
    win.loadFile(path.join(__dirname, '../dist/index.html'))
  }
}

app.whenReady().then(createWindow)

/**
 * IPC → pegar IP local
 */
ipcMain.handle('get-local-ip', () => {
  const nets = os.networkInterfaces()

  for (const name of Object.keys(nets)) {
    for (const net of nets[name]) {
      const isIpv4 = net.family === 'IPv4' || net.family === 4
      if (isIpv4 && !net.internal) {
        console.log('IP LOCAL DETECTADO:', net.address)
        return net.address
      }
    }
  }

  console.log('Nenhum IP encontrado')
  return ''
})


const win = new BrowserWindow({
  width: 1400,
  height: 900,
  icon: path.join(__dirname, '../build/icon.ico'),
  webPreferences: {
    contextIsolation: true,
    preload: path.join(__dirname, 'preload.js')
  }
})


app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit()
})
