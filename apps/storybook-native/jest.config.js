const fs = require('fs')
const path = require('path')

const mocksDir = path.resolve(__dirname, '__mocks__')

const getMockFiles = () => {
  return fs
    .readdirSync(mocksDir)
    .filter((file) => file.endsWith('.tsx'))
    .map((file) => path.join(mocksDir, file))
}

module.exports = {
  preset: 'react-native',
  setupFilesAfterEnv: ['@testing-library/jest-native/extend-expect'],
  transformIgnorePatterns: ['node_modules/(?!(react-native|@react-native|@testing-library|styled-components)/)'],
  setupFiles: getMockFiles(),
}
