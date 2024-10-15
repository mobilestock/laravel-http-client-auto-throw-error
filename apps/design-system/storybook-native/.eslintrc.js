/** @type {import("eslint").Linter.Config} */
const baseConfig = require('../../../eslint-config/.eslintrc.js')
//module.exports = require('../../../eslint-config/.eslintrc.js')

module.exports = {
  ...baseConfig,
  parserOptions: {
    ...baseConfig.parserOptions,
    project: ['tsconfig.json']
  },
  ignorePatterns: [...(baseConfig.ignorePatterns || []), 'metro.config.js', 'app.config.js']
}
