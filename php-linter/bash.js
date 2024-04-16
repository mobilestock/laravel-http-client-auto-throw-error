const { exec } = require('child_process')
let comando
if (process.platform === 'win32') {
  comando = '"C:\\Program Files\\Git\\bin\\sh.exe"'
} else if (process.platform === 'darwin') {
  comando = '/bin/bash'
} else {
  comando = '/usr/bin/bash'
}
comando += ` -c 'php-linter/fix "${process.argv[2]}"'`

console.log('Cmd:', comando)
exec(comando, (error, stdout, stderr) => {
  console.log({ error, stdout, stderr })
})
