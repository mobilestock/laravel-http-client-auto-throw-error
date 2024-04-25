<?php
$fotosProduto = listaFotosProduto($idProdutoFoto);
$linksProduto = buscaLinkVideoProduto($idProdutoFoto);
?>

<div id="fotos-produtos-carrocel" class="carousel slide text-center" data-ride="carousel">
    <ol class="carousel-indicators">
        <?php for ($i = 0; $i < sizeof($fotosProduto) + sizeof($linksProduto); $i++) { ?>
            <li data-target="#fotos-produtos-carrocel" data-slide-to="<?= $i ?>" <?php echo ($i == 0) ? "class='active'" : "" ?>></li>
        <?php } ?>
    </ol>
    <div class="carousel-inner">
        <?php
        $tempActive = 'active';
        foreach ($fotosProduto as $foto) : ?>
            <div class="carousel-item <?= $tempActive ?>">
                <img class="d-block w-100" src="<?= $foto['caminho']; ?>" alt="<?= $foto['descricao']; ?>">
            </div>
        <?php $tempActive = '';
        endforeach; ?>
        <?php foreach ($linksProduto as $item) : ?>
            <div class="carousel-item <?= $tempActive ?>">
                <?php
                $path = substr(strrchr($item['link'], "="), 1);
                ?>
                <div class="plyr__video-embed" id="player">
                    <iframe src="https://www.youtube.com/embed/<?= $path ?>?origin=https://plyr.io&amp;iv_load_policy=3&amp;modestbranding=1&amp;playsinline=1&amp;showinfo=0&amp;rel=0&amp;enablejsapi=1" allowtransparency></iframe>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <a class="carousel-control-prev" href="#fotos-produtos-carrocel" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="sr-only">Anterior</span>
    </a>
    <a class="carousel-control-next" href="#fotos-produtos-carrocel" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only">Próximo</span>
    </a>
</div>

<script>
    const player = new Plyr('#player', {
        hideControls: true, //esconde os controles apos o video iniciar
        fullscreen: {
            enabled: false
        }
    });

    $('.carousel').carousel({
        interval: false //desabilita o carousel de trocar de tela automaticamente
    })
    $('#fotos-produtos-carrocel').on('slide.bs.carousel', function() {
        player.pause(); //pausa o video se o usuario trocar de tela do carousel
    })

    link = $(".carousel-item.active img").attr("src");

    //altera a URL do botão
    $.getJSON("http://is.gd/create.php?callback=?", {
        url: link,
        format: "json"
    }).done(function(data) {
        link = data.shorturl;
        var conteudo = encodeURIComponent(
            'Confira este produto: ' + `${$('#fotos-produtos-carrocel img').attr("alt")} - ` + link
        );

        $("#whatsapp-share-btt").click(function() {
            window.open(
                "https://wa.me/?text=" + conteudo,
                '_system', 'location=yes');
            return false;
        });
    });
</script>