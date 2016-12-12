<?php

$pesquisancm = new \edfdans\pesquisancm\pesquisaNCM();
$retorno = $pesquisancm->pesquisarPeloCodigo('0102.29.19');

echo $retorno;

?>
