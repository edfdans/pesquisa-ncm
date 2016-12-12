<?php

$pesquisancm = new \edfdans\pesquisancm\pesquisaNCM();

if (isset($_POST['descricao']) && isset($_POST['captcha']) && isset($_POST['cookie'])){
    $retorno = $pesquisancm->pesquisarPelaDescricao($_POST['descricao'], $_POST['captcha'], $_POST['cookie']);
    echo implode('<br />', $retorno);
    die;
}

$sessao = $pesquisancm->carregarSessao();

?>

<form action="" method="POST">
    Descrição: <input type="text" name="descricao" value="" /><br />
    <img src="<?php echo $sessao['imagem-captcha']; ?>" /><br />
    Captcha: <input type="text" name="captcha" /><br />
    <input type="hidden" name="cookie" value="<?php echo $sessao['cookie']; ?>" />
    <input type="submit" />
</form>
