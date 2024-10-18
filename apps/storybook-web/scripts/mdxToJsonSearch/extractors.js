const fs = require('fs')
const path = require('path')
const babelParser = require('@babel/parser')
const babelTraverse = require('@babel/traverse').default

function extractMetaOfComponent(content) {
  const metaMatch = content.match(/<Meta[^>]*of=\{([^}]+)\}[^>]*\/>/)
  return metaMatch ? metaMatch[1].trim() : null
}

function extractImportPath(content, componentName) {
  const regex = new RegExp(`import\\s+\\*\\s+as\\s+${componentName}\\s+from\\s+['"]([^'"]+)['"]`, 'i')
  const match = content.match(regex)
  return match ? match[1] : null
}

function extractTitleFromStoriesFile(filePath) {
  const content = fs.readFileSync(`${filePath}.tsx`, 'utf-8')
  const cleanedContent = content.replace(/satisfies\s+Meta<[^>]+>/g, '').replace(/as\s+Meta[^\n]*/g, '')

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
}

function extractHeadings(content) {
  const headings = []
  const regex = /^#{1,6}\s+(.*)$/gm
  let match

  while ((match = regex.exec(content)) !== null) {
    headings.push(match[1].trim())
  }

  return headings
}

module.exports = { extractMetaOfComponent, extractImportPath, extractTitleFromStoriesFile, extractHeadings }
