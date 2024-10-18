const fs = require('fs')
const path = require('path')
const { processFoundFile } = require('./mdxProcessor')

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

module.exports = { loadMdxFiles }
