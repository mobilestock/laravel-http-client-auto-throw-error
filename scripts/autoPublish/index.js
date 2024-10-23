const fs = require('fs')
const path = require('path')
const { exec } = require('child_process')

const nativePath = path.join(__dirname, '..', '..', 'apps', 'storybook-native', 'src', 'packages')
const webPath = path.join(__dirname, '..', '..', 'apps', 'storybook-web', 'src', 'packages')

const runCommand = (command, options = {}) => {
  return new Promise((resolve, reject) => {
    exec(command, { shell: true, ...options }, (error, stdout, stderr) => {
      if (error) {
        return reject(`Erro: ${stderr || error.message}`)
      }
      resolve(stdout)
    })
  })
}

const publishPackage = async (packagePath) => {
  const packageJsonPath = path.join(packagePath, 'package.json')

  if (!fs.existsSync(packageJsonPath)) {
    console.log(`Erro: package.json não encontrado em ${packagePath}. Pulando...`)
    return
  }

  try {
    await runCommand(`npm publish --access public`, { cwd: packagePath })
    console.log(`- ${path.basename(packagePath)} foi publicado com sucesso.`)
  } catch (error) {
    if (error.includes('403') && error.includes('forbidden')) {
      console.log(`${path.basename(packagePath)} já possui a versão publicada. Ignorando...`)
    } else {
      throw error
    }
  }
}

const processPackages = async (basePath) => {
  const packageDirs = fs.readdirSync(basePath).filter((file) => fs.statSync(path.join(basePath, file)).isDirectory())

  for (const dir of packageDirs) {
    if (dir.includes('node_modules')) continue
    const packagePath = path.join(basePath, dir)
    await publishPackage(packagePath)
  }
}

const main = async () => {
  try {
    console.log('# Processando pacotes de storybook-native...')
    await processPackages(nativePath)

    console.log('# Processando pacotes de storybook-web...')
    await processPackages(webPath)

    console.log('# Processo de publicação concluído.')
  } catch (error) {
    console.error('Erro no processo de publicação:\n\n', error)
  }
}

main().catch((error) => console.error('Erro no processo principal:\n\n', error))

// npm install -g npm-cli-login
// Necessario para fazer a automação do login no npm

/*
Se o pacote foi publicado há mais de 72 horas, o npm não permite despublicar o pacote.
Para despublicar dentro desse período, execute o seguinte comando:
npm unpublish @seu-escopo/nome-do-pacote --force
*/
