const path = require('path')
const urlGenerator = require('./urlGenerator')
const extractors = require('./extractors')
const constants = require('./constants')

function extractUrl(content, filePath) {
  const metaOfComponent = extractors.extractMetaOfComponent(content)
  let title = null

  if (metaOfComponent) {
    const importPath = extractors.extractImportPath(content, metaOfComponent)
    if (importPath) {
      const storiesPath = path.resolve(path.dirname(filePath), importPath)

      title = extractors.extractTitleFromStoriesFile(storiesPath)
      return { url: urlGenerator.generateUrlFromTitle(title, true), title }
    }
  }

  const metaTitleMatch = content.match(constants.META_TITLE_REGEX)
  if (metaTitleMatch) {
    title = metaTitleMatch[1]
    return { url: urlGenerator.generateUrlFromTitle(title, false), title }
  }

  return { url: urlGenerator.generateUrlFromFilePath(filePath), title: null }
}

module.exports = {
  processFoundFile(fullPath, content) {
    const startIndex = content.indexOf('/>')
    if (startIndex === -1) return ''

    const processedContent = content.slice(startIndex)
      .replace(constants.BLOCK_COMMENTS_REGEX, '')
      .replace(constants.BRACES_REGEX, '')

    const { url, title } = extractUrl(content, fullPath)

    const headings = extractors.extractHeadings(content)
    const globals = headings.map(heading => heading.toLowerCase().replace(constants.EMPTY_SPACE_REGEX, '-'))

    return { filePath: fullPath, content: processedContent, url, globals, title }
  }
}
