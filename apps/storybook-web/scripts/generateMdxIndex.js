const fs = require('fs')
const path = require('path')
const babelParser = require('@babel/parser')
const babelTraverse = require('@babel/traverse').default

function processContent(content) {
  const startIndex = content.indexOf('#')
  if (startIndex === -1) return ''

  let processedContent = content.slice(startIndex)

  return processedContent
    .replace(/\/\*.*?\*\//gs, '')
    .replace(/[{}]/g, '')
    .replace(/[^\p{L}\p{N}#\s-]/gu, '')
}

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
  const content = fs.readFileSync(filePath, 'utf-8')
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

  function getTitleFromProperties(properties) {
    for (const prop of properties) {
      if (
        (prop.type === 'ObjectProperty' || prop.type === 'Property') &&
        (prop.key.name === 'title' || prop.key.value === 'title')
      ) {
        return prop.value.type === 'StringLiteral' ? prop.value.value : prop.value.quasis[0].value.cooked
      }
    }
    return null
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

  if (defaultExport?.properties) {
    title = getTitleFromProperties(defaultExport.properties)
  }

  return title
}

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

function extractUrl(content, filePath) {
  const metaOfComponent = extractMetaOfComponent(content)
  let title = null

  if (metaOfComponent) {
    const importPath = extractImportPath(content, metaOfComponent)
    if (importPath) {
      const storiesPath = path.resolve(path.dirname(filePath), importPath)
      const extensions = ['.tsx', '.ts', '.jsx', '.js']

      for (const ext of extensions) {
        const fullPath = `${storiesPath}${ext}`
        if (fs.existsSync(fullPath)) {
          title = extractTitleFromStoriesFile(fullPath)
          if (title) {
            return { url: generateUrlFromTitle(title, true), title }
          }
        }
      }
    }
  }

  const metaTitleMatch = content.match(/<Meta\s+title=['"`](.*?)['"`]\s*\/>/)
  if (metaTitleMatch) {
    title = metaTitleMatch[1]
    return { url: generateUrlFromTitle(title, false), title }
  }

  return { url: generateUrlFromFilePath(filePath), title: null }
}

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

function extractHeadings(content) {
  const headings = []
  const regex = /^#{1,6}\s+(.*)$/gm
  let match

  while ((match = regex.exec(content)) !== null) {
    headings.push(match[1].trim())
  }

  return headings
}

function generateAnchor(text) {
  return text
    .toLowerCase()
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .replace(/\s+/g, '-')
}

function loadMdxFiles(directory) {
  const files = fs.readdirSync(directory, { withFileTypes: true })
  let mdxFiles = []

  for (const file of files) {
    const fullPath = path.join(directory, file.name)
    if (file.isDirectory()) {
      mdxFiles = mdxFiles.concat(loadMdxFiles(fullPath))
    } else if (file.isFile() && file.name.endsWith('.mdx')) {
      const content = fs.readFileSync(fullPath, 'utf-8')
      const processedContent = processContent(content)
      const { url, title } = extractUrl(content, fullPath)

      const headings = extractHeadings(content)
      const globals = headings.map(generateAnchor)

      mdxFiles.push({ filePath: fullPath, content: processedContent, url, globals, title })
    }
  }

  return mdxFiles
}

const mdxFiles = loadMdxFiles(path.join(__dirname, '../src'))

fs.writeFileSync(path.join(__dirname, '../node_modules/mdxIndex.json'), JSON.stringify(mdxFiles, null, 2))

console.log('Índice de arquivos MDX gerado com sucesso!')
