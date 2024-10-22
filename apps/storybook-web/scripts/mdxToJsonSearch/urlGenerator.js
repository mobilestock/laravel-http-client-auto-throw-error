const path = require('path')
const constants = require('./constants')

module.exports = {
  generateUrlFromTitle(title, isComponent) {
    const sanitizedTitle = title.replace(constants.FILEPATH_STRUCTURE_REGEX, 'componentes/')
    const urlPath = sanitizedTitle
      .toLowerCase()
      .replace(constants.EMPTY_SPACE_REGEX, '-')
      .replace(constants.UNICODE_SENSITIVE_REGEX, '')
    const segments = urlPath.split('/').filter((seg, i, arr) => seg !== arr[i - 1])
    const mainSegment = segments.join('-')

    return `/?path=/docs/${mainSegment}--${segments.pop()}${isComponent ? '' : '--docs'}`
  },

  generateUrlFromFilePath(filePath) {
    const relativePath = path.relative(path.join(__dirname, '../../src'), filePath)
    const urlPath = relativePath
      .replace(constants.MDX_EXTENSION_REGEX, '')
      .split(path.sep)
      .map(section => section.toLowerCase().replace(constants.EMPTY_SPACE_REGEX, '-'))
      .join('-')

    return `/?path=/docs/${urlPath}--docs`
  }
}

