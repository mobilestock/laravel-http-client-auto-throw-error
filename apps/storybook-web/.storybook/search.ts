import Fuse from 'fuse.js';

const mdxFiles = require('../node_modules/mdxIndex.json')

const fuse = new Fuse(mdxFiles, {
  keys: ['content', 'title', 'globals'],
  threshold: 0.2,
  minMatchCharLength: 4,
  distance: 20,
  ignoreLocation: true
})

export function searchMdx(query: string): Array<{ filePath: string; content: string }> {
  return fuse.search(query).map(result => result.item) as Array<{
    filePath: string
    content: string
    url: string
    globals: string[]
    title: string
  }>
}
