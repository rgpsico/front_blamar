const { defineConfig } = require('@vue/cli-service')

module.exports = defineConfig({
  transpileDependencies: true,
  devServer: {
    proxy: {
      '/api': {
        target: 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api',
        changeOrigin: true,
        secure: false,
        pathRewrite: { '^/api': '' }
      }
    }
  }
})
