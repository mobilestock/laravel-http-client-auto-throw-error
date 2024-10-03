module.exports = {
  semi: false,
  singleQuote: true,
  arrowParens: 'avoid',
  trailingComma: 'none',
  endOfLine: 'auto',
  printWidth: 120,
  plugins: ['@trivago/prettier-plugin-sort-imports'],
  importOrder: ['^[a-z].*$', '^@.*$', '^[./].*$'],
  importOrderSeparation: true,
  importOrderSortSpecifiers: true
}
