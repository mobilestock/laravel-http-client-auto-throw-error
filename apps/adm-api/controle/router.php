<?php
$pagina = isset($_GET['page']) ? $_GET['page'] : 1;

?>

<script>
    $(function() {
        $("#conteudo").load('<?php echo isset($_GET['page']) ? $_GET['page'] : 'index.php' ?>');
    })
</script>