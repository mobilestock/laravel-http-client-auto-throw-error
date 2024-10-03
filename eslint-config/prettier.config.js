module.exports = {
  tabWidth: 2,
  semi: false,
  singleQuote: true,
  arrowParens: 'avoid',
  trailingComma: 'none',
  endOfLine: 'auto',
  printWidth: 120,
  plugins: [require.resolve('@trivago/prettier-plugin-sort-imports')],
  importOrder: ['^[a-z].*$', '^@.*$', '^[./].*$'],
  importOrderSeparation: true,
  importOrderSortSpecifiers: true
}
