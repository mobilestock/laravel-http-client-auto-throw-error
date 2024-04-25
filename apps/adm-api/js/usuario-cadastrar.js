let tiposUsuario = new Vue({
  el: "#app",
  data() {
    return {
      listaTiposAdicionados: [],
      tipos: [
        { nome: "Estoquista", valor: "E" },
        { nome: "Separador", valor: "S" },
        { nome: "Conferente", valor: "C" },
        { nome: "Fotográfo", valor: "O" },
        { nome: "Fornecedor", valor: "F" },
      ],
    };
  },
  methods: {
    adicionaItemListaTipos(e) {
      if (this.tipos.find((option) => option.nome === e.target.value)) {
        this.listaTiposAdicionados.pushIfNotExist(e.target.value);
        e.target.value = "";
      }
    },
    removeItemListaTipos(item) {
      this.listaTiposAdicionados = this.listaTiposAdicionados.filter(
        (el) => el !== item
      );
    },
    mudaFormatoStringTipo(value) {
      if (value === "") {
        return [];
      }
      value = value.split(",");
      return value.map(
        (tipo) => this.tipos.find((el) => el.valor == tipo)["nome"]
      );
    },
  },
  computed: {
    listaTiposAdicionadosValores() {
      let eq = this.listaTiposAdicionados
        .map((item) => this.tipos.find((el) => el.nome == item))
        .map((item) => item.valor);
      return eq;
    },
  },
  mounted() {
    this.listaTiposAdicionados =
      this.mudaFormatoStringTipo(document.querySelector("#tiposInp").value) ||
      [];

    document.querySelector("#campoTipos").classList.remove("d-none");
  },
});

Array.prototype.pushIfNotExist = function (element) {
  if (!this.includes(element)) {
    this.push(element);
  }
};
