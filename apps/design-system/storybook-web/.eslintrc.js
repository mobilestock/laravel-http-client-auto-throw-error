// This configuration only applies to the package manager root.
/** @type {import("eslint").Linter.Config} */
const baseConfig = require('../../../eslint-config/.eslintrc.js')

module.exports = {
  ...baseConfig,
  parserOptions: {
    ...baseConfig.parserOptions,
    project: ['tsconfig.json']
  }
}
