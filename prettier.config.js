module.exports = {
  tabWidth: 2,
  useTabs: false,
  plugins: ['@prettier/plugin-php'],
  printWidth: 120,
  singleQuote: true,
  overrides: [
    {
      files: ['*.php'],
      options: {
        parser: 'php',
        tabWidth: 4
      }
    },
    {
      files: ['*.js', '*.ts', '*.tsx'],
      options: {
        semi: false,
        arrowParens: 'avoid',
        trailingComma: 'none',
        endOfLine: 'auto',
        plugins: [require.resolve('@trivago/prettier-plugin-sort-imports')],
        importOrder: ['^([a-z]|@(?!/|monorepo/ui)).*$', '^(@/|@monorepo/ui).*$', '^(../|./).*$'],
        importOrderSeparation: true,
        importOrderSortSpecifiers: true
      }
    }
  ]
}
