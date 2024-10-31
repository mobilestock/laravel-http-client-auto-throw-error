const fs = require('fs')
const path = require('path')

const mocksDir = path.resolve(__dirname, '__mocks__')

const getMockFiles = () => {
  return fs
    .readdirSync(mocksDir)
    .filter(file => file.endsWith('.js') || file.endsWith('.tsx'))
    .map(file => path.join(mocksDir, file))
}

/** @type {import('@jest/types').Config.InitialOptions} */
module.exports = {
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['@testing-library/jest-dom'],
  setupFiles: getMockFiles(),
}
