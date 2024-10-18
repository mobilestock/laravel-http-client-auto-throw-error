const path = require('path')
const fs = require('fs')
const { loadMdxFiles } = require('./utils')

const mdxDirectory = path.join(__dirname, '../../src')
const mdxFiles = loadMdxFiles(mdxDirectory)

fs.writeFileSync(path.join(__dirname, '../../node_modules/mdxIndex.json'), JSON.stringify(mdxFiles, null, 2))

console.log('Índice de arquivos MDX gerado com sucesso!')
