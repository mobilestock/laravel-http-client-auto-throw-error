const fs = require('fs')
const simpleGit = require('simple-git')
const path = require('path')
const { exec } = require('child_process')

function incrementVersion(version, releaseType) {
  const versionParts = version.split('.').map(Number)

  switch (releaseType) {
    case 'path':
      versionParts[2]++
      break
    case 'minor':
      versionParts[1]++
      versionParts[2] = 0
      break
    case 'major':
      versionParts[0]++
      versionParts[1] = 0
      versionParts[2] = 0
      break
    default:
      console.error(`Tipo de versão inválido: ${releaseType}`)
      process.exit(1)
  }

  return versionParts.join('.')
}

const [, , platform, releaseTypeArg] = process.argv
if (!['web', 'native'].includes(platform)) {
  console.error('Uso correto: native ou web')
  process.exit(1)
}

const basePath =
  platform === 'web'
    ? path.join(__dirname, '..', 'storybook-web', 'src', 'packages', 'base')
    : path.join(__dirname, '..', 'storybook-native', 'src', 'packages', 'base')

const packageJsonPath = path.join(basePath, 'package.json')

const releaseType = releaseTypeArg || 'path'

fs.readFile(packageJsonPath, 'utf-8', (err, data) => {
  if (err) {
    console.error(`Erro ao ler o arquivo package.json: ${err}`)
    process.exit(1)
  }

  let packageJson
  try {
    packageJson = JSON.parse(data)
  } catch (e) {
    console.error(`Erro ao fazer parse do arquivo package.json: ${e}`)
    process.exit(1)
  }

  const oldVersion = packageJson.version
  const newVersion = incrementVersion(oldVersion, releaseType)
  packageJson.version = newVersion

  fs.writeFile(packageJsonPath, JSON.stringify(packageJson, null, 2), 'utf-8', (err) => {
    if (err) {
      console.error(`Erro ao escrever o arquivo package.json: ${err}`)
      process.exit(1)
    }

    console.log(`Versão alterada de ${oldVersion} para ${newVersion} no ${platform}`)

    exec('npm publish', { cwd: basePath }, (error, stdout, stderr) => {
      if (error) {
        console.error(`Erro ao executar npm publish: ${error.message}`)
        return
      }
      if (stderr) {
        console.error(`stderr: ${stderr}`)
      }
      console.log(`stdout: ${stdout}`)
    })
  })

  const git = simpleGit(basePath)
  git.add('package.json').commit(`Publicar versão ${newVersion}`).push()
})
