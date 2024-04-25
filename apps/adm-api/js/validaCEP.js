
const $campoCep = document.querySelector('[name="cep"]');
const $campoCidade = document.querySelector('[name="cidade"]');
const $campoBairro = document.querySelector('[name="bairro"]');
const $campoRua = document.querySelector('[name="endereco"]');
const $campoUF = document.querySelector('[name="uf"]');	
const $campoComplemento = document.querySelector('[name="complemento"]');

$campoCep.addEventListener("blur", (infosDoEvento) => {
  const cep = infosDoEvento.target.value;
  fetch(`https://viacep.com.br/ws/${cep}/json/`)
  .then(respostaDoServer => {
      return respostaDoServer.json();
  })
  .then(dadosDoCep => {
    $campoCidade.value = dadosDoCep.localidade;
    $campoBairro.value = dadosDoCep.bairro;
    $campoRua.value = dadosDoCep.logradouro;
    $campoUF.value = dadosDoCep.uf;
    $campoComplemento.value = dadosDoCep.complemento;
  });
});
