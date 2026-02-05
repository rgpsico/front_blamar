module.exports = {
  publicPath: './',

  lintOnSave: false,

  devServer: {
    proxy: {
      '^/api': {
        target: 'https://webdeveloper.blumar.com.br',
        changeOrigin: true,
        secure: true,
        pathRewrite: { '^/api': '/desenv/roger/conteudo/api' }
      }
    }
  }
}
