<?php

namespace api_cliente\Cript;

/**
 * @deprecated NÃO USAR
 */
class Cript{
    public function servernamber($valor){
        $valor = substr($valor, -3, 1);
        switch($valor){
            case 0:
                $valor = 3546415;
                break;
            case 1:
                $valor = 2628742;
                break;
            case 2:
                $valor = 6321577;
                break;
            case 3:
                $valor = 2154755;
                break;
            case 4:
                $valor = 3195452;
                break;
            case 5:
                $valor = 4795718;
                break;
            case 6:
                $valor = 2357456;
                break;
            case 7:
                $valor = 2459329;
                break;
            case 8:
                $valor = 3351486;
                break;
            case 9:
                $valor = 4489212;
                break;
    
            default:
                echo "Erro";
    
        }
        return $valor;
    
    }
    
    public function invertnum($valor){
        $strin = array(9=>"a",8=>"c",7=>"m",6=>"j",5=>"x",4=>"p",3=>"q",2=>"t",1=>"e",0=>"r");
        $valor = $strin[$valor];
    return $valor;
    }
    public function desinvertnum($valor){
        $strin = array("a"=>9,"c"=>8,"m"=>7,"j"=>6,"x"=>5,"p"=>4,"q"=>3,"t"=>2,"e"=>1,"r"=>0);
        $valor = $strin[$valor];
    return $valor;
    }
    
    public static function criptInt($valor){  
        $cript = new Cript;
        if(strlen($valor)>18){return 'erro - Tamanho limite int(11)'; exit;} 
        $vl = substr(date("s"),1,1) . $cript->invertnum(str_replace(".","2", substr(gettimeofday("s"),-1))) . $cript->invertnum(str_replace(".","2", substr(gettimeofday("s"),-2, -1)));
    
        if($valor < 1000){
            $valor = $valor * $cript->servernamber($vl);
            $tip   = "4";
        }
        else if($valor > 999999999){
            $valor = $valor;
            $tip   = "7";
        }
        else{
            $valor = $valor + $cript->servernamber($vl);
            $tip   = "9";
        }
    
        $valor = "$valor";
    
        
        if(isset($valor[1]))$valor[1]  =  $cript->invertnum($valor[1]);
        if(isset($valor[3]))$valor[3]  =  $cript->invertnum($valor[3]);
        if(isset($valor[7]))$valor[7]  =  $cript->invertnum($valor[7]);
        if(isset($valor[4]))$valor[4]  =  $cript->invertnum($valor[4]);
        if(isset($valor[6]))$valor[6]  =  $cript->invertnum($valor[6]);
        if(isset($valor[10]))$valor[10] =  $cript->invertnum($valor[10]);
        if(isset($valor[11]))$valor[11] =  $cript->invertnum($valor[11]);
        if(isset($valor[13]))$valor[13] =  $cript->invertnum($valor[13]);
        if(isset($valor[15]))$valor[15] =  $cript->invertnum($valor[15]);
        if(isset($valor[17]))$valor[17] =  $cript->invertnum($valor[17]);
        if(isset($valor[21]))$valor[21] =  $cript->invertnum($valor[21]);
        if(isset($valor[22]))$valor[22] =  $cript->invertnum($valor[22]);
        if(isset($valor[23]))$valor[23] =  $cript->invertnum($valor[23]);
        if(isset($valor[27]))$valor[27] =  $cript->invertnum($valor[27]);
        if(isset($valor[30]))$valor[30] =  $cript->invertnum($valor[30]);
        if(isset($valor[32]))$valor[32] =  $cript->invertnum($valor[32]);
        if(isset($valor[33]))$valor[33] =  $cript->invertnum($valor[33]);
    
    
        $valor =  "$valor$tip$vl";
        $valor =  $valor  = str_replace(" ","",$valor );
        return $valor;
    }
    public static function dcriptInt($valor, $erro){
        if($valor == null){echo $erro; exit;}
        $val = substr($valor, -3, 3);
        $va = substr($valor, -4, 1);
        $valor = substr($valor, 0, -4);
        $valor = "$valor";
        if(isset($valor[1]))$valor[1] =  self::desinvertnum($valor[1]);
        if(isset($valor[3]))$valor[3] =  self::desinvertnum($valor[3]);
        if(isset($valor[7]))$valor[7] =  self::desinvertnum($valor[7]);
        if(isset($valor[4]))$valor[4] =  self::desinvertnum($valor[4]);
        if(isset($valor[6]))$valor[6] =  self::desinvertnum($valor[6]);
        if(isset($valor[10]))$valor[10] =  self::desinvertnum($valor[10]);
        if(isset($valor[11]))$valor[11] =  self::desinvertnum($valor[11]);
        if(isset($valor[13]))$valor[13] =  self::desinvertnum($valor[13]);
        if(isset($valor[15]))$valor[15] =  self::desinvertnum($valor[15]);
        if(isset($valor[17]))$valor[17] =  self::desinvertnum($valor[17]);
        if(isset($valor[21]))$valor[21] =  self::desinvertnum($valor[21]);
        if(isset($valor[22]))$valor[22] =  self::desinvertnum($valor[22]);
        if(isset($valor[23]))$valor[23] =  self::desinvertnum($valor[23]);
        if(isset($valor[27]))$valor[27] =  self::desinvertnum($valor[27]);
        if(isset($valor[30]))$valor[30] =  self::desinvertnum($valor[30]);
        if(isset($valor[32]))$valor[32] =  self::desinvertnum($valor[32]);
        if(isset($valor[33]))$valor[33] =  self::desinvertnum($valor[33]);
        
        if($va == 4){
            $valor = $valor / self::servernamber($val);
        }
        else if($va == 7){
            $valor = $valor;
        }
        else{
            $valor = $valor - self::servernamber($val);        
        }
        $valor = str_ireplace(",","",number_format($valor));
    
    return $valor;
    } 
}