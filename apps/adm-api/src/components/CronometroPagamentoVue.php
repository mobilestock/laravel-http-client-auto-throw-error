<script type="text/x-template" id="steps-pagamento">
    <div>
    <div v-if="cronometro" class="container-fluid d-flex justify-content-center align-items-center">
      <div class="d-flex flex-column ml-3">
          <span class="d-flex align-items-center">
            <h3 v-if="dias > 0" class="m-0 p-0">
                <b>Mande seus produtos para primeira página!</b><br />
                <small>Poderá usar novamente em:</small><br />
                {{dias}} dias e {{ tempoFormatado }} horas
            </h3>
            <h3 v-else class="m-0 p-0">
                <b>Mande seus produtos para primeira página!</b><br />
                <small>Você já pode utilizar esse recurso</small>
            </h3>
          </span>
      </div>
    </div>   
  </div>
</script>

<script>
    var intervalSegundos;
    Vue.component("steps-pagamento", {
        template: "#steps-pagamento",
        props: {
            cronometro: {
                required: false,
                type: Boolean,
                default: true
            },
            step: {
                type: String,
                required: true
            },
            dias:{
                type: Number,
                defautl: 0
            },
            tempoRestante:{
                type:Number,
                default:0
            }
        },
        data() {
            return {
                tempRestanteCopia : 0
            };
        },

        mounted() {
            this.tempRestanteCopia = this.tempoRestante;
            let self = this;
            intervalSegundos = setInterval(function () {
                if (!self.tempRestanteCopia) {
                    clearInterval(intervalSegundos);
                    return;
                }
                self.tempRestanteCopia -= 1;
            }, 1000);
        },
          
        
        computed: {
            tempoFormatado() {
                var horas = Math.floor(this.tempRestanteCopia / 60 / 60);
                var minutos = Math.floor((this.tempRestanteCopia / 60) % 60);
                var segundos = this.tempRestanteCopia % 60;

                return String(horas).padStart(2, '0') + ':' + String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
            }
        },
    });
</script>