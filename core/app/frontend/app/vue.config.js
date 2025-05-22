module.exports = {
  configureWebpack: {
    mode: 'production',
    output: {
      filename: 'app.js'
    },
    optimization: {
      splitChunks: false
    },
  },
  filenameHashing: false,
}
