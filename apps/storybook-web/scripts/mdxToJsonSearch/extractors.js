const fs = require('fs')
const babelParser = require('@babel/parser')
const babelTraverse = require('@babel/traverse').default
const constants = require('./constants')

module.exports = {
  extractMetaOfComponent(content) {
    const metaMatch = content.match(constants.META_OF_COMPONENT_REGEX)
    return metaMatch ? metaMatch[1].trim() : null
  },

  extractImportPath(content, componentName) {
    const componentImportRegex = new RegExp(`import\\s+\\*\\s+as\\s+${componentName}\\s+from\\s+['"]([^'"]+)['"]`, 'i')
    const match = content.match(componentImportRegex)
    return match ? match[1] : null
  },

  extractTitleFromStoriesFile(filePath) {
    const content = fs.readFileSync(`${filePath}.tsx`, 'utf-8')
    const cleanedContent = content.replace(constants.SATISFIES_META_REGEX, '').replace(constants.AS_META_REGEX, '')

    const ast = babelParser.parse(cleanedContent, {
      sourceType: 'module',
      plugins: ['typescript', 'jsx']
    })

    let title = null
    let defaultExport = null

    function findDeclaration(ast, varName) {
      let declaration = null
      babelTraverse(ast, {
        VariableDeclarator({ node }) {
          if (node.id.name === varName) {
            declaration = node.init.type === 'TSAsExpression' ? node.init.expression : node.init
          }
        }
      })
      return declaration
    }

    babelTraverse(ast, {
      ExportDefaultDeclaration({ node }) {
        const declaration = node.declaration
        defaultExport =
          declaration.type === 'Identifier'
            ? findDeclaration(ast, declaration.name)
            : declaration.expression || declaration
      }
    })

    function getTitleFromProperties(properties) {
      for (const prop of properties) {
        if (
          (prop.type === 'ObjectProperty' || prop.type === 'Property') &&
          (prop.key.name === 'title' || prop.key.value === 'title')
        ) {
          return prop.value.value
        }
      }
      return null
    }

    if (defaultExport?.properties) {
      title = getTitleFromProperties(defaultExport.properties)
    }

    return title
  },

  extractHeadings(content) {
    const headings = []
    let match

    while ((match = constants.IS_TITLE_OR_SUBTITLE_REGEX.exec(content)) !== null) {
      headings.push(match[1].trim())
    }

    return headings
  }
}
