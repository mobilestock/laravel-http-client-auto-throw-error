const path = require('path')
const fs = require('fs')
const { processFoundFile } = require('./mdxProcessor')

const mdxDirectory = path.join(__dirname, '../../src')

function loadMdxFiles(directory) {
  const files = fs.readdirSync(directory, { withFileTypes: true })
  let mdxFiles = []

  files.forEach(file => {
    const fullPath = path.join(directory, file.name)
    if (file.isDirectory()) {
      mdxFiles = mdxFiles.concat(loadMdxFiles(fullPath))
    } else if (file.isFile() && file.name.endsWith('.mdx')) {
      const content = fs.readFileSync(fullPath, 'utf-8')
      mdxFiles.push(processFoundFile(fullPath, content))
    }
  })

  return mdxFiles
}

const mdxFiles = loadMdxFiles(mdxDirectory)

fs.writeFileSync(path.join(__dirname, '../../node_modules/mdxIndex.json'), JSON.stringify(mdxFiles, null, 2))

console.log('Índice de arquivos MDX gerado com sucesso!')
