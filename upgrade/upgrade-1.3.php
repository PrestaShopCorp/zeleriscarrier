<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author    Línea Gráfica E.C.E. S.L.- https://www.lineagrafica.es/ <soporte@lineagrafica.es>
* @copyright 2007-2015 TELEFONICA SERVICIOS INTEGRALES DE DISTRIBUCIÓN S.A.U.
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of TELEFONICA SERVICIOS INTEGRALES DE DISTRIBUCIÓN S.A.U.
*/

if (!defined('_PS_VERSION_'))
	exit;

function upgrade_module_1_3($module)
{
	$configs = DB::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'configuration WHERE `name` LIKE "%ZELERIS%"');
	if (is_array($configs))
		foreach($configs as $config)
			$config_installed[] = $config['name'];
	
	if (in_array('ZELERIS_DESCRIPCION_MERCANCIA',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_MERCHANDISE_DESCRIPTION" WHERE `name`="ZELERIS_DESCRIPCION_MERCANCIA"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_MANIPULACION',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_MANIPULATION" WHERE `name`="ZELERIS_MANIPULACION"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_ENVIO_GRATUITO',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_FREE_SHIPPING" WHERE `name`="ZELERIS_ENVIO_GRATUITO"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_ENVIO_GRATUITO_INTERNACI',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_FREE_SHIPPING_INTERNACI" WHERE `name`="ZELERIS_ENVIO_GRATUITO_INTERNACI"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_MOSTRAR_SERVICIOS_CASO_E',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_SHOW_SERVICES_ERROR" WHERE `name`="ZELERIS_MOSTRAR_SERVICIOS_CASO_E"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_MENSAJE_ERROR',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_ERROR_MESSAGE" WHERE `name`="ZELERIS_MENSAJE_ERROR"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_COSTE_FIJO_ENVIO',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_FIXED_COST_SHIPPING" WHERE `name`="ZELERIS_COSTE_FIJO_ENVIO"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_MARGEN_COSTE_ENVIO',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_MARGIN_SHIPPING_COST" WHERE `name`="ZELERIS_MARGEN_COSTE_ENVIO"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_IMPORTE_MINIMO_ENVIO_GRA',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_MIN_AMOUNT_FREE" WHERE `name`="ZELERIS_IMPORTE_MINIMO_ENVIO_GRA"';
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_IMPORTE_MINIMO_ENVIO_G_I',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_MIN_AMOUNT_FREE_INT" WHERE `name`="ZELERIS_IMPORTE_MINIMO_ENVIO_G_I"';		
		Db::getInstance()->execute($sql);
	}
	
	if (in_array('ZELERIS_COSTE_MANIPULACION',$config_installed))
	{
		$sql='UPDATE '._DB_PREFIX_.'configuration SET `name` = "ZELERIS_MANIPULATION_COST" WHERE `name`="ZELERIS_COSTE_MANIPULACION"';		
		Db::getInstance()->execute($sql);
	}

	
	
	$fields = DB::getInstance()->executeS('DESCRIBE '._DB_PREFIX_.'zeleris_email');
	if (is_array($fields))
		foreach($fields as $field)
			$field_installed[] = $field['Field'];
			
	if (!in_array('title',$field_installed))
	{
		$sql='ALTER TABLE '._DB_PREFIX_.'zeleris_email CHANGE `titulo` `title` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL';
		Db::getInstance()->execute($sql);
	}
	
	if (!in_array('message',$field_installed))
	{
		$sql='ALTER TABLE '._DB_PREFIX_.'zeleris_email CHANGE `mensaje` `message` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ';
		Db::getInstance()->execute($sql);
	}
	
	
	$sql='SHOW TABLES LIKE "'._DB_PREFIX_.'zeleris_envios"';
	$result = Db::getInstance()->executeS($sql);
	
	if($result)
	{
		$sql='RENAME TABLE '._DB_PREFIX_.'zeleris_envios TO '._DB_PREFIX_.'zeleris_orders';
		Db::getInstance()->execute($sql);
		
		$fields = DB::getInstance()->executeS('DESCRIBE '._DB_PREFIX_.'zeleris_orders');
		
		if (is_array($fields))
			foreach($fields as $field)
				$field_installed[] = $field['Field'];
				
				
		if (in_array('id_envio',$field_installed))
		{
			$sql='ALTER TABLE '._DB_PREFIX_.'zeleris_orders CHANGE `id_envio` `id_zeleris_orders` INT( 11 ) NOT NULL AUTO_INCREMENT';
			Db::getInstance()->execute($sql);
		}
		
		if (in_array('id_envio_order',$field_installed))
		{
			$sql='ALTER TABLE '._DB_PREFIX_.'zeleris_orders CHANGE `id_envio_order` `id_order` INT( 11 ) NOT NULL';
			Db::getInstance()->execute($sql);
		}
		
		if (in_array('codigo_envio',$field_installed))
		{
			$sql='ALTER TABLE '._DB_PREFIX_.'zeleris_orders CHANGE `codigo_envio` `send_code` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
			Db::getInstance()->execute($sql);
		}
		
		if (in_array('fecha',$field_installed))
		{
			$sql='ALTER TABLE '._DB_PREFIX_.'zeleris_orders CHANGE `fecha` `date` DATETIME NOT NULL';
			Db::getInstance()->execute($sql);
		}
		
		if (!in_array('packages',$field_installed))
		{
			$sql='ALTER TABLE '._DB_PREFIX_.'zeleris_orders ADD packages int(11) NOT NULL';
			Db::getInstance()->execute($sql);
		}
	}
	
	return $module;
}