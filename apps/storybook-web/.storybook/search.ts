import Fuse from 'fuse.js';

const mdxFiles = require('../node_modules/mdxIndex.json')

export function searchMdx(query: string) {
  const fuse = new Fuse(mdxFiles, {
    keys: ['content', 'title', 'globals'],
    threshold: 0.2,
    minMatchCharLength: 4,
    distance: 20,
    ignoreLocation: true
  })

  const result = fuse.search(query).map(result => result.item) as Array<{
    filePath: string
    content: string
    url: string
    globals: string[]
    title: string
  }>

  return result
}

export function searchForGlobalAnchors(query: string, globals: string[]): string | null {
  const fuse = new Fuse(globals, {
    threshold: 0.4,
    minMatchCharLength: 4,
    distance: 20,
    ignoreLocation: true
  })

  const result = fuse.search(query)[0]
  return result ? result.item : null
}
