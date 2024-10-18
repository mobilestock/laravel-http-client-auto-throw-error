const path = require('path')

// Gera a URL a partir do título do componente ou da documentação
function generateUrlFromTitle(title, isComponent) {
  const sanitizedTitle = title.replace(/^packages\/base\/components\//, 'componentes/')
  const urlPath = sanitizedTitle
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9\-\/\p{L}]/giu, '')
  const segments = urlPath.split('/').filter((seg, i, arr) => seg !== arr[i - 1])
  const mainSegment = segments.join('-')

  return `/?path=/docs/${mainSegment}--${segments.pop()}${isComponent ? '' : '--docs'}`
}

// Gera a URL a partir do caminho do arquivo
function generateUrlFromFilePath(filePath) {
  const relativePath = path.relative(path.join(__dirname, '../src'), filePath)
  const urlPath = relativePath
    .replace(/\\/g, '/')
    .replace(/\.mdx$/, '')
    .split('/')
    .map(section => section.toLowerCase().replace(/\s+/g, '-'))
    .join('-')

  return `/?path=/docs/${urlPath}--docs`
}

module.exports = { generateUrlFromTitle, generateUrlFromFilePath }
