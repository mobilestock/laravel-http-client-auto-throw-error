function formataCep(cep) {
    cep = cep.toString()
    const cepLimpo = cep.replace(/[^0-9]/g, "");
    let cepFormatado = cep.replace(/[^0-9]/g, "");
    if (cepLimpo.length > 5) {
        cepFormatado = cepFormatado.substring(0, 5) + "-" + cepFormatado.substring(5);
    }
    if (cepLimpo.length > 8 || cepFormatado.length > 9) {
        return cepFormatado.substring(0, 9);
    }

    return cepFormatado;
}
