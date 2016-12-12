<?php

namespace edfdans\pesquisancm;

use Exception;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class pesquisaNCM {
    private $url        = 'http://www4.receita.fazenda.gov.br/simulador/PesquisarNCM.jsp';
    private $urlCaptcha = 'http://www4.receita.fazenda.gov.br/simulador/captcha.jpg?operacao=pesquisarNCM';
    
    public function pesquisarPeloCodigo($codigo = ''){
        $codigo = preg_replace('/[^0-9]/', '', $codigo);

        if (strlen($codigo) != 8){
            return 'Formado do NCM deve ser 0000.00.00 ou 00000000';
        }else{
            $capitulo    = substr($codigo, 0, 2);
            $posicao     = substr($codigo, 0, 4);
            $subPosicao1 = substr($codigo, 0, 5);
            $subPosicao2 = substr($codigo, 0, 6);
            $codigoItem  = substr($codigo, 0, 7);

            $url = $this->url . 
                    '?codigo='            . $codigoItem .
                    '&codigoCapitulo='    . $capitulo .
                    '&codigoPosicao='     . $posicao .
                    '&codigoSubPosicao1=' . $subPosicao1 .
                    '&codigoSubPosicao2=' . $subPosicao2 .
                    '&codigoItem='        . $codigoItem .
                    '&button=Exibir+NCMs';


            try {
                $cliente = new Client();        
                $retorno = $cliente->request('GET', $url);

                if ($retorno->filter('#listaNCM > font')->count() > 0){

                    $ncms    = $retorno->filter('#listaNCM > font');
                    $retorno = '';
                    foreach ($ncms->filter('font') as $ncm){
                        $ncm       = new Crawler($ncm);
                        $descricao = $ncm->getNode(0)->textContent;
                        
                        if (!empty($descricao)){
                            if (strlen($descricao) > 8){
                                if (substr($descricao, 0, 8) == $codigo){
                                    $descricao = str_replace($codigo, '', $descricao);
                                    
                                    if ( (strlen($descricao) >= 3) && (substr($descricao, 0, 3) == ' - ') ){
                                        $retorno = substr($descricao, 3);
                                    }
                                    
                                    break;
                                }                                
                            }
                        }                    
                    }
                    
                    if (!empty($retorno)){
                        return $retorno;
                    }else{
                        return 'NCM não encontrado';
                    }

                }else{
                    return 'NCM não encontrado';
                }
            } catch (Exception $e){
                return $e->getMessage();
            }
        }
    }
    
    public function carregarSessao(){
        $cliente = new Client();        
        $cliente->request('GET', $this->url);

        $cookie    = $cliente->getResponse()->getHeaders()['Set-Cookie'][0];        
        $curl      = curl_init($this->urlCaptcha);

        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0',
                'Referer: ' . $this->url,
                'Cookie: ' . $cookie,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_BINARYTRANSFER => true
        ]);
        
        $imagem = curl_exec($curl);
        curl_close($curl);
        
        if (@imagecreatefromstring($imagem) == false){
            throw new Exception('Não foi possível carregar o captcha');
        }

        return [
            'cookie'        => $cookie,
            'imagemCaptcha' => 'data:image/png;base64,' . base64_encode($imagem)
        ];
    }
    
    public function pesquisarPelaDescricao($descricao = '', $captcha = '', $cookie = '', $formatarNCM = false){
        if (empty($descricao)){
            throw new Exception('Descrição não informada!');
        }

        $cliente = new Client();
        $cliente->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0');
        $cliente->setHeader('Referer', $this->url);
        $cliente->setHeader('Cookie', explode(';', $cookie)[0]);

        $resposta = $cliente->request('POST', $this->url, [
                        'descricaoNCM'      => $descricao,
                        'codigoVerificacao' => $captcha,
                    ]);
        
        $retorno  = [];
        if ($resposta->filter('.linktext')->count() > 0){
            
            $ncms = $resposta->filter('.linktext');
            
            foreach ($ncms as $ncm){
                $ncm       = new Crawler($ncm);
                $descricao = $ncm->getNode(0)->textContent;

                if (!empty($descricao)){
                    $partes = explode(' - ', $descricao);
                    
                    if (count($partes) >= 2){
                        if (strlen($partes[0]) == 8){
                            
                            if ($formatarNCM){
                                $partes[0] = substr($partes[0], 0, 4) . '.' . substr($partes[0], 4, 2) . '.' . substr($partes[0], 6, 2);
                            }
                            
                            $retorno[$partes[0]] = $partes[1];
                        }
                    }
                }
            }
        }else{
            $retorno = [
                '' => 'Nenhum NCM encontrado!'
            ];
        }
        
        return $retorno;
    }
}
