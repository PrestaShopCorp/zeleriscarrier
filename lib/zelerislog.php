<?php
class ZelerisLog 
{	
	public static function mensaje($mensaje,$level){
    	$archivo = _PS_MODULE_DIR_.'zeleriscarrier/zeleris.log';
        $manejador = fopen($archivo,'a');
        fwrite($manejador,"[".date("r")."] PrestaShop ver:"._PS_VERSION_." -> $level: $mensaje\n\r");
        fclose($manejador);
    }
    public static function error($mensaje){
        $level='ERROR';
        self::mensaje($mensaje,$level);
    }
    public static function info($mensaje){
        $level='INFO';
        self::mensaje($mensaje,$level);
    }    
}