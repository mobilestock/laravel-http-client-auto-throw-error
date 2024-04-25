let btnSubmit = document.querySelector("#btn-submit-escrever-uma-duvida");
let listaDuvidas = JSON.parse(document.querySelector('#duvidas-json').value);

document
  .querySelector("#form-escrever-uma-duvida")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    let botaoPadrao = btnSubmit.innerHTML;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btnSubmit.disabled = true;

    let form = new FormData();
    form.append("conteudo", document.querySelector("#textarea-duvida").value);

    let json = await fetch(
      "src/controller/Colaborador/ClienteSalvaDuvida.php",
      {
        method: "POST",
        body: form,
      }
    ).then((r) => r.json());

    if (!json.status) {
      btnSubmit.innerHTML = botaoPadrao;
      $.alert(json.message);
      return;
    }

    btnSubmit.innerHTML = '<i class="fas fa-check"></i>';
  });

document.querySelector('#buscar').addEventListener('input', function (e) {

        let exp = new RegExp(e.target.value.trim(), 'i');
        // retorna apenas as fotos que condizem com a expressão
        let listaDuvidasFiltrada = listaDuvidas.filter(duvida => exp.test(duvida.titulo))
        document.querySelector('#lista-duvidas').innerHTML = listaDuvidasFiltrada.map(duvida => `
            <li class="mt-3" style="list-style: none;margin: 0;padding: 0;margin-top: 10;">
                <details>
                    <summary class="h5" style="outline: 0">${duvida.titulo}</summary>
                    ${duvida.conteudo}
                </details>
            </li>
        `).join('');
});