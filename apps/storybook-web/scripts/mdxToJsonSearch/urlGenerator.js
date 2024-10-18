const path = require('path')
const {
  FILEPATH_STRUCTURE_REGEX,
  EMPTY_SPACE_REGEX,
  REVERSE_SLASH_REGEX,
  UNICODE_SENSITIVE_REGEX,
  MDX_EXTENSION_REGEX
} = require('./constants')

module.exports = {
  generateUrlFromTitle(title, isComponent) {
    const sanitizedTitle = title.replace(FILEPATH_STRUCTURE_REGEX, 'componentes/')
    const urlPath = sanitizedTitle
      .toLowerCase()
      .replace(EMPTY_SPACE_REGEX, '-')
      .replace(UNICODE_SENSITIVE_REGEX, '')
    const segments = urlPath.split('/').filter((seg, i, arr) => seg !== arr[i - 1])
    const mainSegment = segments.join('-')

    return `/?path=/docs/${mainSegment}--${segments.pop()}${isComponent ? '' : '--docs'}`
  },

  generateUrlFromFilePath(filePath) {
    const relativePath = path.relative(path.join(__dirname, '../../src'), filePath)
    const urlPath = relativePath
      .replace(REVERSE_SLASH_REGEX, '/')
      .replace(MDX_EXTENSION_REGEX, '')
      .split('/')
      .map(section => section.toLowerCase().replace(EMPTY_SPACE_REGEX, '-'))
      .join('-')

    return `/?path=/docs/${urlPath}--docs`
  }
}

