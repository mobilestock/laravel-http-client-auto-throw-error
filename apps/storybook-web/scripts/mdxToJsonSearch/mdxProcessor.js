const path = require('path')
const { generateUrlFromTitle, generateUrlFromFilePath } = require('./urlGenerator')
const {
  extractMetaOfComponent,
  extractImportPath,
  extractTitleFromStoriesFile,
  extractHeadings
} = require('./extractors')
const {
  META_TITLE_REGEX,
  EMPTY_SPACE_REGEX,
  BLOCK_COMMENTS_REGEX,
  BRACES_REGEX,
  NON_ALPHANUMERIC_REGEX
} = require('./constants')

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

  const metaTitleMatch = content.match(META_TITLE_REGEX)
  if (metaTitleMatch) {
    title = metaTitleMatch[1]
    return { url: generateUrlFromTitle(title, false), title }
  }

  return { url: generateUrlFromFilePath(filePath), title: null }
}

module.exports = {
  processFoundFile(fullPath, content) {
    const startIndex = content.indexOf('/>')
    if (startIndex === -1) return ''

    const processedContent = content.slice(startIndex)
      .replace(BLOCK_COMMENTS_REGEX, '')
      .replace(BRACES_REGEX, '')
      .replace(NON_ALPHANUMERIC_REGEX, '')

    const { url, title } = extractUrl(content, fullPath)

    const headings = extractHeadings(content)
    const globals = headings.map(heading => heading.toLowerCase().replace(EMPTY_SPACE_REGEX, '-'))

    return { filePath: fullPath, content: processedContent, url, globals, title }
  }
}
