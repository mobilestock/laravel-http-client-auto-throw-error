module.exports = {
  semi: false,
  singleQuote: true,
  useTabs: false,
  tabWidth: 2,
  arrowParens: 'avoid',
  trailingComma: 'none',
  endOfLine: 'auto',
  printWidth: 120,
  plugins: [require.resolve('@trivago/prettier-plugin-sort-imports'), '@prettier/plugin-php'],
  importOrder: ['^[a-z].*$', '^@.*$', '^[./].*$'],
  importOrderSeparation: true,
  importOrderSortSpecifiers: true,
  overrides: [
    {
      files: ['*.php'],
      options: {
        parser: 'php',
        tabWidth: 4
      }
    },
    {
      files: ['*.js'],
      options: {
        semi: false
      }
    }
  ]
}
