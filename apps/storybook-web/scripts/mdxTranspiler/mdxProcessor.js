const path = require('path')
const fs = require('fs')
const { extractMetaOfComponent, extractImportPath, extractTitleFromStoriesFile, extractHeadings } = require('./extractors')
const { generateUrlFromTitle, generateUrlFromFilePath } = require('./urlGenerator')

function extractUrl(content, filePath) {
  const metaOfComponent = extractMetaOfComponent(content)
  let title = null

  if (metaOfComponent) {
    const importPath = extractImportPath(content, metaOfComponent)
    if (importPath) {
      const storiesPath = path.resolve(path.dirname(filePath), importPath)

      title = extractTitleFromStoriesFile(storiesPath)
      return { url: generateUrlFromTitle(title, true), title }
    }
  }

  const metaTitleMatch = content.match(/<Meta\s+title=['"`](.*?)['"`]\s*\/>/)
  if (metaTitleMatch) {
    title = metaTitleMatch[1]
    return { url: generateUrlFromTitle(title, false), title }
  }

  return { url: generateUrlFromFilePath(filePath), title: null }
}

function processFoundFile(fullPath, content) {
  const startIndex = content.indexOf('/>')
  if (startIndex === -1) return ''

  const processedContent = content.slice(startIndex)
    .replace(/\/\*.*?\*\//gs, '')
    .replace(/[{}]/g, '')
    .replace(/[^\p{L}\p{N}#\s-]/gu, '')

  const { url, title } = extractUrl(content, fullPath)

  const headings = extractHeadings(content)
  const globals = headings.map(heading => heading.toLowerCase().replace(/\s+/g, '-'))

  return { filePath: fullPath, content: processedContent, url, globals, title }
}

module.exports = { processFoundFile }
