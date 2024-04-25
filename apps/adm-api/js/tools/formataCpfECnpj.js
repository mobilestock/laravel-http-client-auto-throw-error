function formataCnpj(valor) {
    if (!valor) return "";
    const valorRaw = valor.replace(/[^0-9]/g, "");
    let valorFormatado = "";
    for (let i = 0; i < valorRaw.length; i++) {
      switch (true) {
        case [2, 5].includes(i):
          valorFormatado += ".";
          break;
        case i === 8:
          valorFormatado += "/";
          break;
        case i === 12:
          valorFormatado += "-";
          break;
      }
      valorFormatado += valorRaw[i];
    }
    return valorFormatado.substr(0, 18);
}

function formataCpf(valor) {
    if (!valor) return "";
    const valorRaw = valor.replace(/[^0-9]/g, "");
    let valorFormatado = '';
    for (let i = 0; i < valorRaw.length; i++) {
        switch (true) {
            case [3, 6].includes(i):
                valorFormatado += '.';
                break;
            case i === 9:
                valorFormatado += '-';
                break;
        }
        valorFormatado += valorRaw[i];
    }
    return valorFormatado.substr(0, 14);
}