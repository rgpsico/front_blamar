const { contextBridge, ipcRenderer } = require('electron')

contextBridge.exposeInMainWorld('electronAPI', {
  getLocalIP: () => ipcRenderer.invoke('get-local-ip')
})
console.log('Preload carregado, API exposta:', window.electronAPI)