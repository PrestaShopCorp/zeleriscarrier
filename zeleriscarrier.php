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
	require_once(_PS_MODULE_DIR_.'zeleriscarrier/lib/zelerislog.php');

	class ZelerisCarrier extends CarrierModule
	{
		public $id_carrier;
		private $_html = '';
		private $_postErrors = array();
		private $_moduleName = 'zeleriscarrier';


		public function __construct()
		{
			$this->id_carrier = '';
			$this->name = 'zeleriscarrier';
			$this->tab = 'shipping_logistics';
			$this->version = '1.2.5';
			$this->author = 'Línea Gráfica';

			parent::__construct();

			$this->displayName = $this->l('Zeleris carrier');
			$this->description = $this->l('Integrates sending zeleris');

			if (Module::isInstalled($this->name))
			{
				/*************** Get Carriers ***************/
				$cookie = $this->context->cookie;
				$carriers = Carrier::getCarriers($cookie->id_lang, true, false, false, null, Carrier::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);

				/*************** check if isset id carrier ***************/
				$warning = array();
				if (!Configuration::get('ZELERIS_URL'))
					$warning[] = $this->l('"URL Gateway"').' ';
				if (!Configuration::get('ZELERIS_GUID'))
					$warning[] = $this->l('"Customer`s identificator (GUID)"').' ';
				if (!Configuration::get('ZELERIS_MERCHANDISE_DESCRIPTION'))
					$warning[] = $this->l('"Merchandise description"').' ';
				if (count($warning))
					$this->warning .= implode(' , ', $warning).$this->l('You must configure before use.').' ';
			}
		}

		public function tablesRollback()
		{
		}

		public function install()
		{
			if (!extension_loaded('soap'))
			{
				ZelerisLog::error($this->l('Not soap extension loaded, can\' install module '));
				$this->errors[] = $this->l('Can\'t install module because the Soap Extension is not loaded');
				return false;
			}
			$query = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'zeleris_orders (
				id_zeleris_orders int(11) NOT NULL AUTO_INCREMENT,
				id_order int(11) NOT NULL,
				send_code varchar(50) NOT NULL,
				url_track varchar(255) NOT NULL,
				date datetime NOT NULL,
				packages int(11) NOT NULL,
				PRIMARY KEY (`id_zeleris_orders`)
				) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ';

			if (!Db::getInstance()->execute($query))
			{
				ZelerisLog::error($this->l('Unable to create table ')._DB_PREFIX_.'zeleris_orders '.$this->l('Using ENGINE =')._MYSQL_ENGINE_);
				$this->tablesRollback();
				return false;
			}

			$query = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'zeleris_email (
			id int(11) NOT NULL AUTO_INCREMENT,
			title varchar(128),
			message text,
			PRIMARY KEY (`id`)
		  ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ';

			if (!Db::getInstance()->execute($query))
			{
				ZelerisLog::error($this->l('Unable to create table ')._DB_PREFIX_.'zeleris_email '.$this->l('Using ENGINE =')._MYSQL_ENGINE_);
				$this->tablesRollback();
				return false;
			}

			$query = 'INSERT INTO '._DB_PREFIX_.'zeleris_email (title,message) VALUES ("example","Write your message here...")';
			if (!Db::getInstance()->execute($query))
			{
				ZelerisLog::error($this->l('Unable to create table ')._DB_PREFIX_.'zeleris_email '.$this->l('Using ENGINE =')._MYSQL_ENGINE_);
				$this->tablesRollback();
				return false;
			}

			$carrierConfig = array(
				0 => array('name' => 'eCommerce',
					'id_tax_rules_group' => 0,
					'active' => true,
					'deleted' => 0,
					'shipping_handling' => false,
					'range_behavior' => 0,
					'delay' => array(Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => $this->l('Next day delivery')),
					'id_zone' => 1,
					'is_module' => true,
					'shipping_external' => true,
					'external_module_name' => $this->name,
					'need_range' => true
				),);

			$id_carrier1 = $this->installExternalCarrier($carrierConfig[0]);
			Configuration::updateValue('ZELERIS_CARRIER_ID', (int)$id_carrier1);

			if (!parent::install() || !$this->registerHook('updateCarrier'))
				return false;

			// Create Tab  
			$tab = new Tab();
			$tab->class_name = 'AdminZeleris';
			$tab->id_parent = 10;
			$tab->module = $this->name;
			$tab->name[(int)(Configuration::get('PS_LANG_DEFAULT'))] = 'Zeleris';

			if (!$tab->add())
			{
				$this->tablesRollback();
				return false;
			}
			return true;
		}

		public function uninstall()
		{
			if (!parent::uninstall() || !$this->unregisterHook('updateCarrier'))
				return false;

			/*************** Delete Carrier ***************/
			$Carrier1 = new Carrier((int)(Configuration::get('ZELERIS_CARRIER_ID')));
			if (Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier1->id))
			{
				$cookie = $this->context->cookie;
				$carriersD = Carrier::getCarriers($cookie->id_lang, true, false, false, null, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
				foreach ($carriersD as $carrierD)
					if ($carrierD['active'] && !$carrierD['deleted'] && ($carrierD['name'] != $this->_config['name']))
						Configuration::updateValue('PS_CARRIER_DEFAULT', $carrierD['id_carrier']);
			}

			$Carrier1->deleted = 1;
			if (!$Carrier1->update())
				return false;
			/*************** Detele Tab and tables ***************/
			Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'tab WHERE module = "'.$this->_moduleName.'"');
			Db::getInstance()->execute('DROP TABLE '._DB_PREFIX_.'zeleris_orders');
			Db::getInstance()->execute('DROP TABLE '._DB_PREFIX_.'zeleris_email');
			return true;
		}


		public static function installExternalCarrier($config)
		{
			$carrier = new Carrier();
			$carrier->name = $config['name'];
			$carrier->id_tax_rules_group = $config['id_tax_rules_group'];
			$carrier->id_zone = $config['id_zone'];
			$carrier->active = $config['active'];
			$carrier->deleted = $config['deleted'];
			$carrier->delay = $config['delay'];
			$carrier->shipping_handling = $config['shipping_handling'];
			$carrier->range_behavior = $config['range_behavior'];
			$carrier->is_module = $config['is_module'];
			$carrier->shipping_external = $config['shipping_external'];
			$carrier->external_module_name = $config['external_module_name'];
			$carrier->need_range = $config['need_range'];

			$languages = Language::getLanguages(true);
			foreach ($languages as $language)
			{
				if ($language['iso_code'] == 'fr')
					$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
				if ($language['iso_code'] == 'en')
					$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
				if ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')))
					$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			}

			if ($carrier->add())
			{
				$groups = Group::getGroups(true);
				foreach ($groups as $group)
					Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_group', array('id_carrier' => (int)($carrier->id), 'id_group' => (int)($group['id_group'])), 'INSERT');

				$rangePrice = new RangePrice();
				$rangePrice->id_carrier = $carrier->id;
				$rangePrice->delimiter1 = '0';
				$rangePrice->delimiter2 = '1000000000';
				$rangePrice->add();

				$rangeWeight = new RangeWeight();
				$rangeWeight->id_carrier = $carrier->id;
				$rangeWeight->delimiter1 = '0';
				$rangeWeight->delimiter2 = '1000000000';
				$rangeWeight->add();

				$zones = Zone::getZones(true);

				foreach ($zones as $zone)
					$carrier->addZone($zone['id_zone']);

				if ($config['name'] == 'ZELERIS-24H')
				{
					if (!Tools::copy(dirname(__FILE__).'/zeleris_ecomm_24_pshop.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'))
						return false;
				}

				/*************** Return carrier id ***************/
				return (int)($carrier->id);
			}
			return false;
		}


		public function getContent()
		{
			if (!Configuration::get('ZELERIS_URL') || Configuration::get('ZELERIS_URL') == '' || !Configuration::get('ZELERIS_GUID') || Configuration::get('ZELERIS_GUID') == '')
				$this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/landing.js');
			else
				$this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/tablist.js');

			$this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/admin.css');
			$this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/admin.js');

			$this->_displayLanding();
			$this->_displayForm();
			return $this->_html;
		}

		private function _displayLanding()
		{
			$this->_html .= '
		<div id="landing" style="display:none">
			<div class="title" style=><img src="/modules/zeleriscarrier/views/img/logo.png" alt="" /><span>'.$this->l('Logistics solutions for your online business').'</span></div>
			<div class="landing_content">
				<div class="bloque" id="bloque1" style="">
					
					<div id="content1" style=""> 
						<div >
							<img src="/modules/zeleriscarrier/views/img/entrega.png" alt="" style="float:left;padding-right: 10px;" />'.$this->l('The Zeleris ecommerce service is designed to meet the logistics and transport companies that market their products through Internet needs.').'</br></br>'.$this->l('Trust the logistics of your online store to one of the leading companies in delivery to individuals and with the support of Telefonica Group.').'<br/><br/>'.$this->l('This module allows quick and easy integration of the online store with the transport system Zeleris.').'
						</div>
						<div class="mt20 partner">
							<div class="title_c">'.$this->l('Features of this module:').'</div>
								<ul>
									<li>'.$this->l('- Flexible Parameterization of standard and express services.').'</li>
									<li>'.$this->l('- National and international services.').'</li>
									<li>'.$this->l('- Configuration of shipping prices.').'</li>
									<li>'.$this->l('- Allocation of costs manipulated.').'</li>
									<li>'.$this->l('- Fixing purchase amounts from which transportation is free.').'</li>
									<li>'.$this->l('- Management of COD payments.').'</li>
									<li>'.$this->l('- Integration with pickup service').'</li>
								</ul>
						</div>
					
						<div class="botones">
							<div id="boton_info">'.$this->l('I want to be customer').'</div>
							<div id="boton_conf">'.$this->l('I am a customer').'</div>
						</div>
					</div>
				</div>
				<div class="bloque" id="bloque2">
						<div class="mt30 transporte">
							<div class="title_t">'.$this->l('National transport').'</div>
							<ul>
								<li>'.$this->l('- Notice and coordination of deliveries (SMS, call, email)').'</li>
								<li>'.$this->l('- Online troubleshooting').'</li>
								<li>'.$this->l('- Delivery to return for exchange of product').'</li>
								<li>'.$this->l('- Choice of delivery collection point').'</li>
							</ul>
						</div>
						
						<div class="mt30 transporte">
							<div class="title_t">'.$this->l('International transport').'</div>
							<ul>
								<li>'.$this->l('- International Air Service').'</li>
								<li>'.$this->l('- Ground Service International').'</li>
							</ul>
						</div>
						
						<div class="mt30 transporte">
							<div class="title_t">'.$this->l('Logistics').'</div>
							<ul>
								<li>'.$this->l('- Storage').'</li>
								<li>'.$this->l('- Picking').'</li>
								<li>'.$this->l('- Dropshipping').'</li>
								<li>'.$this->l('- Integration with shuttle').'</li>
							</ul>
						</div>
					
						<div class="enlace">'.$this->l('More information at:').' <a href="http://www.zeleris.com" target="_blank">www.zeleris.com</a></div>
					</div>
					<div class="clear"></div>
				</div>	
				</div>
		
		';
		}

		private function _displayForm()
		{
			$this->_html .= '<div id="capa_layer" style="display:none">
		<div class="clear">&nbsp;</div>
		<fieldset style="border: 0px;">	';
			$this->_html .= '<h2>'.$this->l('Zeleris').'</h2>';
			if (!empty($_POST) && Tools::isSubmit('submitContact'))
				$this->contact();
			$this->_html .= '<div id="tabs">
		
		';

			if (!Configuration::get('ZELERIS_URL') || Configuration::get('ZELERIS_URL') == '' || !Configuration::get('ZELERIS_GUID') || Configuration::get('ZELERIS_GUID') == '')
				$this->_html .= '<div class="butoon_volver">'.$this->l('Back').'</div>';
			$this->_html .= '
			<div id="tabform" style="display:none">
				<form action="index.php?tab='.Tools::getValue('tab').'&configure='.Tools::getValue('configure').'&token='.Tools::getValue('token').'&tab_module='.Tools::getValue('tab_module').'&module_name='.Tools::getValue('module_name').'&id_tab=2&section=request" method="post" class="form" id="requestForm">
						<h2 style="font-family: Verdana;">'.$this->l('Request commercial visit').'</h2>
						<table cellspacing="4" class="table">
						    <tbody>
								<tr>
									<td class="label">* '.$this->l('Company name').'</td>
									<td>
										<input name="txtEmpresa" type="text" maxlength="80" id="txtEmpresa" class="textbox" style="width:221px;">
									</td>
								</tr>
								<tr>
									<td class="label">'.$this->l('NIF').'</td>
						        <td>
                                    <input name="txtNIF" type="text" maxlength="20" id="txtNIF" class="textbox" style="width:221px;">
                                </td>
						    </tr>
						    <tr>
						        <td class="label">* '.$this->l('Address').'</td>
						        <td>
                                    <input name="txtDireccion" type="text" maxlength="80" id="txtDireccion" class="textbox" style="width:221px;">
                                </td>
						    </tr>
						    <tr>
						        <td class="label">* '.$this->l('State').'</td>
						        <td>
                                    <select name="cmbProvincia" id="cmbProvincia" class="select" style="width: 231px;">
										<option selected="selected" value=""></option>
										<option value="Alava">Alava</option>
										<option value="Albacete">Albacete</option>
										<option value="Alicante">Alicante</option>
										<option value="Almeria">Almeria</option>
										<option value="Asturias">Asturias</option>
										<option value="Avila">Avila</option>
										<option value="Badajoz">Badajoz</option>
										<option value="Baleares">Baleares</option>
										<option value="Barcelona">Barcelona</option>
										<option value="Burgos">Burgos</option>
										<option value="Caceres">Caceres</option>
										<option value="Cadiz">Cadiz</option>
										<option value="Cantabria">Cantabria</option>
										<option value="Castellon">Castellon</option>
										<option value="Ceuta">Ceuta</option>
										<option value="Ciudad Real">Ciudad Real</option>
										<option value="Cordoba">Cordoba</option>
										<option value="Cuenca">Cuenca</option>
										<option value="Girona">Girona</option>
										<option value="Granada">Granada</option>
										<option value="Guadalajara">Guadalajara</option>
										<option value="Guipuzcoa">Guipuzcoa</option>
										<option value="Huelva">Huelva</option>
										<option value="Huesca">Huesca</option>
										<option value="Jaen">Jaen</option>
										<option value="La Coru&ntilde;a">La Coru&ntilde;a</option>
										<option value="La Rioja">La Rioja</option>
										<option value="Las Palmas">Las Palmas</option>
										<option value="Leon">Leon</option>
										<option value="Lleida">Lleida</option>
										<option value="Lugo">Lugo</option>
										<option value="Madrid">Madrid</option>
										<option value="Malaga">Malaga</option>
										<option value="Melilla">Melilla</option>
										<option value="Murcia">Murcia</option>
										<option value="Navarra">Navarra</option>
										<option value="Ourense">Ourense</option>
										<option value="Palencia">Palencia</option>
										<option value="Pontevedra">Pontevedra</option>
										<option value="Salamanca">Salamanca</option>
										<option value="Santa Cruz de Tenerife">Santa Cruz de Tenerife</option>
										<option value="Segovia">Segovia</option>
										<option value="Sevilla">Sevilla</option>
										<option value="Soria">Soria</option>
										<option value="Tarragona">Tarragona</option>
										<option value="Teruel">Teruel</option>
										<option value="Toledo">Toledo</option>
										<option value="Valencia">Valencia</option>
										<option value="Valladolid">Valladolid</option>
										<option value="Vizcaya">Vizcaya</option>
										<option value="Zamora">Zamora</option>
										<option value="Zaragoza">Zaragoza</option>
									</select>
						        </td>
						    </tr>
						    <tr>
						        <td class="label">'.$this->l('Contact Person').'</td>
						        <td>
                                    <input name="txtContacto" type="text" maxlength="40" id="txtContacto" class="textbox" style="width:221px;">
                                </td>
						    </tr>
						    <tr>
						        <td class="label">* '.$this->l('Phone').'</td>
						        <td>
                                    <input name="txtTelefono" type="text" maxlength="15" id="txtTelefono" class="textbox" style="width:221px;">
                                </td>
						    </tr>
						    <tr>
						        <td class="label">'.$this->l('Email').'</td>
						        <td>
                                    <input name="txtEMail" type="text" maxlength="60" id="txtEMail" class="textbox" style="width:221px;">
                                </td>
						    </tr>
						    <tr>
						        <td class="label">'.$this->l('Services of interest').'</td>
						        <td>
                                    <table id="chkServicios" class="checkbox" border="0">
										<tbody>
											<tr>
												<td><input id="chkServicios_0" type="checkbox" name="chkServicios[]" value="'.$this->l('Carrier').'"><label for="chkServicios_0">'.$this->l('Carrier').'</label></td>
											</tr>
											<tr>
												<td><input id="chkServicios_1" type="checkbox" name="chkServicios[]" value="'.$this->l('Logistic').'"><label for="chkServicios_1">'.$this->l('Logistic').'</label></td>
											</tr>
										</tbody>
									</table>
                                </td>
						    </tr>
						    <tr>
						        <td class="label">'.$this->l('Current number of weekly shipments').'</td>
						        <td>
                                    <input name="txtEnviosActual" type="text" maxlength="5" id="txtEnviosActual" class="textbox" style="width:60px;">
                                </td>
						    </tr>
						    <tr>
						        <td class="label">'.$this->l('Expected number of weekly shipments').'</td>
						        <td>
                                    <input name="txtEnviosPrevisto" type="text" maxlength="5" id="txtEnviosPrevisto" class="textbox" style="width:60px;">
                                </td>
						    </tr>
						    <tr>
						        <td class="label">'.$this->l('Comments').'</td>
						        <td>
                                    <textarea name="txtObservaciones" rows="2" cols="20" id="txtObservaciones" class="textarea" style="width:221px;"></textarea>
                                </td>
						    </tr>
						    <tr>
						        <td colspan="2"></td>
						    </tr>

						    <tr>
						        <td>
                                    <input type="hidden" name="source_page" id="source_page">
                                    <input type="hidden" name="id_procedencia" id="id_procedencia">
						        </td>
						        <td>
								    <div class="boton">
									    <div class="izq"></div>
                                        <input type="submit" value="'.$this->l('Send').'" name="submitContact" id="sendcontact"/>
									    <div class="der"></div>
								    </div>
								    
						        </td>
						    </tr>
						</tbody></table>
						
				</form>
			</div>
		
			
		
			<div id="tabList" style="display:none">';

			$this->_html .= '<fieldset><legend><img src="'.$this->_path.'views/img/logotipo.png" alt="" /> '.$this->l('Status Zeleris carrier module for PrestaShop:').'</legend>';
			$alert = array();
			if (!Configuration::get('ZELERIS_URL') || Configuration::get('ZELERIS_URL') == '')
				$alert['zeleris_url'] = 1;
			if (!Configuration::get('ZELERIS_GUID') || Configuration::get('ZELERIS_GUID') == '')
				$alert['zeleris_guid'] = 1;
			if (!Configuration::get('ZELERIS_MERCHANDISE_DESCRIPTION') || Configuration::get('ZELERIS_MERCHANDISE_DESCRIPTION') == '')
			{
				Configuration::updateValue('ZELERIS_MERCHANDISE_DESCRIPTION', $this->l('Not indicated'));
				$alert['ZELERIS_MERCHANDISE_DESCRIPTION'] = $this->l('Not indicated');
			}

			if (!count($alert))
				$this->_html .= '<img src="'._PS_IMG_.'admin/module_install.png" /><strong>'.$this->l('ZELERIS is configured and online!').'</strong>';
			else
			{
				$this->_html .= '<img src="'._PS_IMG_.'admin/warn2.png" /><strong>'.$this->l('ZELERIS is not yet configured. Please correct the errors indicated:').'</strong>';
				$this->_html .= '<br />'.((isset($alert['zeleris_url'])) ? '<img src="'._PS_IMG_.'admin/warn2.png" />' : '<img src="'._PS_IMG_.'admin/module_install.png" />').' 1- '.$this->l('Configuring the Gateway URL.');
				$this->_html .= '<br />'.((isset($alert['zeleris_guid'])) ? '<img src="'._PS_IMG_.'admin/warn2.png" />' : '<img src="'._PS_IMG_.'admin/module_install.png" />').' 2- '.$this->l('Configuring the client identifier (GUID).');
				$this->_html .= '<br />'.((isset($alert['ZELERIS_MERCHANDISE_DESCRIPTION'])) ? '<img src="'._PS_IMG_.'admin/warn2.png" />' : '<img src="'._PS_IMG_.'admin/module_install.png" />').' 3- '.$this->l('Configuring the merchandise description.');
			}
			if (!empty($_POST) && Tools::isSubmit('submitSave'))
			{
				$this->_html .= '<script>$(document).ready(function(){$("#landing").hide(); $("#capa_layer, #tabList").show()})</script>';

				$this->_postValidation();
				if (!count($this->_postErrors))
					$this->_postProcess();
				else
					foreach ($this->_postErrors as $err)
						$this->_html .= '<div class="alert error"><img src="'._PS_IMG_.'admin/forbbiden.gif" alt="nok" />&nbsp;'.$err.'</div>';
			}


			$zeleris_manipulation_fixed = '';
			$zeleris_manipulation_percentage = '';
			if (Tools::getValue('zeleris_manipulation', Configuration::get('ZELERIS_MANIPULATION')) == 'F')
				$zeleris_manipulation_fixed = 'selected="selected"';
			if (Tools::getValue('zeleris_manipulation', Configuration::get('ZELERIS_MANIPULATION')) == 'P')
				$zeleris_manipulation_percentage = 'selected="selected"';

			$free_shipping_no = '';
			$free_shipping_yes = '';
			if (Tools::getValue('zeleris_free_shipping', Configuration::get('ZELERIS_FREE_SHIPPING')) == '0')
				$free_shipping_no = 'checked="checked"';
			if (Tools::getValue('zeleris_free_shipping', Configuration::get('ZELERIS_FREE_SHIPPING')) == '1')
				$free_shipping_yes = 'checked="checked"';

			$free_shipping_international_no = '';
			$free_shipping_international_yes = '';
			if (Tools::getValue('zeleris_free_shipping_internacional', Configuration::get('ZELERIS_FREE_SHIPPING_INTERNACI')) == '0')
				$free_shipping_international_no = 'checked="checked"';
			if (Tools::getValue('zeleris_free_shipping_internacional', Configuration::get('ZELERIS_FREE_SHIPPING_INTERNACI')) == '1')
				$free_shipping_international_yes = 'checked="checked"';

			$show_services_error_no = '';
			$show_services_error_yes = '';
			if (Tools::getValue('zeleris_show_services_error', Configuration::get('ZELERIS_SHOW_SERVICES_ERROR')) == '0')
				$show_services_error_no = 'checked="checked"';
			if (Tools::getValue('zeleris_show_services_error', Configuration::get('ZELERIS_SHOW_SERVICES_ERROR')) == '1')
				$show_services_error_yes = 'checked="checked"';

			$this->_html .= '</fieldset>
				<div class="tabItem">
					
					<form action="index.php?tab='.Tools::getValue('tab').'&configure='.Tools::getValue('configure').'&token='.Tools::getValue('token').'&tab_module='.Tools::getValue('tab_module').'&module_name='.Tools::getValue('module_name').'&id_tab=1&section=general" method="post" class="form" id="configForm">
						
							<h4>'.$this->l('Configuration data carrier module for PrestaShop Zeleris').' :</h4>
								<table style="border: 0px;">
									<tr>
										<td class="columna1">'.$this->l('Version').' : </td>
										<td class="columna2">
											<p>PT-1.2.0</p>
											<p class="tip"></p>
										</td>
									</tr>
									<tr>
										<td class="columna1">'.$this->l('URL Gateway').' : </td>
										<td class="columna2">
												<input type="text" size="99" name="zeleris_url" value="'.Tools::getValue('zeleris_url', Configuration::get('ZELERIS_URL')).'" />
												<p class="tip">'.$this->l('Zeleris data connection. No change except Zeleris indication.').'</p>
										</td>
									</tr>
									<tr>
										<td class="columna1">'.$this->l('Client identifier (GUID)').' : </td>
										<td class="columna2">
												<input type="text" size="50" name="zeleris_guid" value="'.Tools::getValue('zeleris_guid', Configuration::get('ZELERIS_GUID')).'" />
												<p class="tip">'.$this->l('Data provided by Zeleris. No change except Zeleris indication.').'</p>
										</td>
									</tr>
									<tr>
										<td class="columna1">'.$this->l('Merchandise description').' : </td>
										<td class="columna2">
												<input type="text" size="99" name="zeleris_merchandise_decription" value="'.Tools::getValue('zeleris_merchandise_decription', Configuration::get('ZELERIS_MERCHANDISE_DESCRIPTION')).'" />
												<p class="tip">'.$this->l('Generic description merchandise on shipments requiring customs clearance. (max. 20 characters)').'</p>
										</td>
									</tr>
									
									<tr>
										<td colspan="2"><hr/></td>
									</tr>
									
									<tr>
										<td class="columna1">'.$this->l('Type handling cost').' : </td>
										<td class="columna2">
												<select name="zeleris_manipulation">
													<option '.$zeleris_manipulation_fixed.' value="F">'.$this->l('Fixed').'</option>
													<option '.$zeleris_manipulation_percentage.' value="P">'.$this->l('Percentage').'</option>
												</select>
												<p class="tip">'.$this->l('Define a fixed cost or  variable cost ').'</p>
										</td>
									</tr>
									<tr>
										<td class="columna1">'.$this->l('Fixed cost or percentage of manipulated').' : </td>
										<td class="columna2">
												<input type="text" size="15" name="zeleris_manipulation_cost" value="'.Tools::getValue('zeleris_manipulation_cost', Configuration::get('ZELERIS_MANIPULATION_COST')).'" />
												<p class="tip">'.$this->l('Specifies the fixed cost or a percentage of the taxable amount of your order to charge the buyer for expenses handling. The percentage is calculated on the taxable amount of the purchase, not shipping.').'</p>
										</td>
									</tr>
									<tr>
										<td colspan="2"><hr/></td>
									</tr>
									
									<tr>
										<td class="columna1">'.$this->l('Free National shipping').' :</td>
										<td class="columna2">
												<input type="radio" name="zeleris_free_shipping" value="0" '.$free_shipping_no.'/>'.$this->l('No').'&nbsp;&nbsp;&nbsp;
												<input type="radio" name="zeleris_free_shipping" value="1" '.$free_shipping_yes.'/>'.$this->l('Yes').'
												<p class="tip">'.$this->l('Select "Yes" to enable the free shippingin on national purchases that meet or exceed the amount specified in "Threshold for Free national shipping" Select "No" if the customer should always pay the cost of transportation across national purchase, whatever the amount of the same.').'</p>
										</td>
									</tr>
										<tr>
										<td class="columna1">'.$this->l('Max price for free shipping ').' : </td>
										<td class="columna2">
												<input type="text" size="6" name="zeleris_min_amount_free" value="'.Tools::getValue('zeleris_min_amount_free', Configuration::get('ZELERIS_MIN_AMOUNT_FREE')).'" />
												<p class="tip">'.$this->l('Order Amount of national purchases, excluding tax, from which transport becomes free for the buyer, provided that the "Free national shipping" option is set to "Yes".').'</p>
										</td>
									</tr>
									<tr>
										<td colspan="2"><hr/></td>
									</tr>
									
									<tr>
										<td class="columna1">'.$this->l('Free International shipping').' :</td>
										<td class="columna2">
												<input type="radio" name="zeleris_free_shipping_internacional" value="0" '.$free_shipping_international_no.'/>'.$this->l('No').'&nbsp;&nbsp;&nbsp;
												<input type="radio" name="zeleris_free_shipping_internacional" value="1" '.$free_shipping_international_yes.'/>'.$this->l('Yes').'
												<p class="tip">'.$this->l('Select "Yes" to enable the free shipping on international purchases that meet or exceed the amount specified in "Threshold for free international shipping." Select "No" if the customer should always pay the cost of transportation across international purchase, whatever the amount of the same.').'</p>
										</td>
									</tr>
										<tr>
										<td class="columna1">'.$this->l('Max price for free shipping ').' :</td>
										<td class="columna2">
												<input type="text" size="6" name="zeleris_min_amount_free_int" value="'.Tools::getValue('zeleris_min_amount_free_int', Configuration::get('ZELERIS_MIN_AMOUNT_FREE_INT')).'" />
												<p class="tip">'.$this->l('Order Amount international shopping, no taxes, from which transport becomes free for the buyer, provided that the "Free International Shipping" option is set to "Yes".').'</p>
										</td>
									</tr>
									<tr>
										<td colspan="2"><hr/></td>
									</tr>
			
									<tr>
										<td class="columna1">'.$this->l('Show services in case of error').' :</td>
										<td class="columna2">
												<input type="radio" name="zeleris_show_services_error" value="0" '.$show_services_error_no.'/>No&nbsp;&nbsp;&nbsp;
												<input type="radio" name="zeleris_show_services_error" value="1" '.$show_services_error_yes.'/>Si
												<p class="tip">'.$this->l('Indicates whether or not the services show the buyer should not be available for the selected destination or not having money, etc.').'</p>
										</td>
									</tr>
									<tr>
										<td class="columna1">'.$this->l('Error message').' : </td>
										<td class="columna2">
												<input type="text" size="99" name="zeleris_error_message" value="'.Tools::getValue('zeleris_error_message', Configuration::get('ZELERIS_ERROR_MESSAGE')).'" />
												<p class="tip">'.$this->l('Text displayed to the purchaser, in case of failure of communications when see prices').'</p>
										</td>
									</tr>
									<tr>
										<td colspan="2"><hr/></td>
									</tr>
									
									<tr>
										<td class="columna1">'.$this->l('Fixed Shipping Cost').' : </td>
										<td class="columna2">
												<input type="text" size="5" name="zeleris_fixed_cost_shipping" value="'.Tools::getValue('zeleris_fixed_cost_shipping', Configuration::get('ZELERIS_FIXED_COST_SHIPPING')).'" />
												<p class="tip">'.$this->l('Fixed price for shipping. If greater than zero, is not taken into account neither the price nor the transportation cost plus shipping. In this case, it will be taxable transportation. If it is zero, the taxable amount is the cost of transport plus the cost plus shipping.').'</p>
										</td>
									</tr>
									<tr>
										<td class="columna1">'.$this->l('Margin on shipping cost').' : </td>
										<td class="columna2">
												<input type="text" size="15" name="zeleris_margin_shipping_cost" value="'.Tools::getValue('zeleris_margin_shipping_cost', Configuration::get('ZELERIS_MARGIN_SHIPPING_COST')).'" />
												<p class="tip">'.$this->l('Margin rate increase on shipping cost. This amount will be added to the transport.').'</p>
										</td>
									</tr>								
								</table>
								<br /><br />
								
						<div class="margin-form"><input class="button" name="submitSave" type="submit" value="Save"></div>
					</form>
				</div>
			</div>
		</div>
		</fieldset>';
			$this->_html .= '</div>';
		}

		private function _postValidation()
		{
			if (Tools::getValue('zeleris_customer_code', '') == '' && Tools::getValue('zeleris_customer_pass', '') == '' && Tools::getValue('zeleris_url', '') == '')
				$this->_postErrors[] = $this->l('You need to configure correctly: URL Gateway, Client Identifier (GUID) and merchandise description');
		}

		private function _postProcess()
		{
			if (Configuration::updateValue('ZELERIS_URL', Tools::getValue('zeleris_url')) && Configuration::updateValue('ZELERIS_GUID', Tools::getValue('zeleris_guid')) && Configuration::updateValue('ZELERIS_MERCHANDISE_DESCRIPTION', Tools::getValue('zeleris_merchandise_decription')) && Configuration::updateValue('ZELERIS_MODE', Tools::getValue('zeleris_mode')) && Configuration::updateValue('ZELERIS_MANIPULATION', Tools::getValue('zeleris_manipulation')) && Configuration::updateValue('ZELERIS_MANIPULATION_COST', Tools::getValue('zeleris_manipulation_cost')) && Configuration::updateValue('ZELERIS_FREE_SHIPPING', Tools::getValue('zeleris_free_shipping')) && Configuration::updateValue('ZELERIS_MIN_AMOUNT_FREE', Tools::getValue('zeleris_min_amount_free')) && Configuration::updateValue('ZELERIS_FREE_SHIPPING_INTERNACI', Tools::getValue('zeleris_min_amount_free_int')) && Configuration::updateValue('ZELERIS_MIN_AMOUNT_FREE_INT', Tools::getValue('zeleris_min_amount_free_int')) && Configuration::updateValue('ZELERIS_SHOW_SERVICES_ERROR', Tools::getValue('zeleris_show_services_error')) && Configuration::updateValue('ZELERIS_ERROR_MESSAGE', Tools::getValue('zeleris_error_message')) && Configuration::updateValue('ZELERIS_FIXED_COST_SHIPPING', Tools::getValue('zeleris_fixed_cost_shipping')) && Configuration::updateValue('ZELERIS_MARGIN_SHIPPING_COST', Tools::getValue('zeleris_margin_shipping_cost')))
				$this->_html .= $this->displayConfirmation($this->l('The configuration has been successfully updated.'));
			else
				$this->_html .= $this->displayErrors($this->l('ERROR. The configuration has not been updated.'));
		}


		public function getOrderShippingCost($params, $shipping_cost)
		{
			$country_user = 'es';
			$cp_user = '-';

			$total = $params->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
			$weight = $params->getTotalWeight();
			if ($weight == 0)
				$weight = 1;

			$address_user_id = $params->id_address_delivery;
			$query = 'SELECT * FROM '._DB_PREFIX_.'address where id_address = "'.(int)$address_user_id.'"';
			$data_user = Db::getInstance()->executeS($query);

			if (count($data_user) > 0)
			{
				$query = 'SELECT iso_code FROM '._DB_PREFIX_.'country where id_country = "'.(int)$data_user[0]['id_country'].'"';
				$country_user_id = Db::getInstance()->executeS($query);
				$country_user = $country_user_id[0]['iso_code'];
				$cp_user = $data_user[0]['postcode'];
			}

			$manipulation_cost = 0;
			if (Configuration::get('ZELERIS_FIXED_COST_SHIPPING'))
				$shipping_cost_fixed = (float)(Configuration::get('ZELERIS_FIXED_COST_SHIPPING'));
			if (Configuration::get('ZELERIS_MANIPULACION') == 'F')
				$manipulation_cost = (float)(Configuration::get('ZELERIS_MANIPULATION_COST'));
			if (Configuration::get('ZELERIS_MANIPULACION') == 'V')
				$manipulation_cost = (float)($total * (Configuration::get('ZELERIS_MANIPULATION_COST') / 100));


			/*************** Calculate shipping ***************/

			/*************** National and free ***************/
			if ($country_user == 'ES' && Configuration::get('ZELERIS_FREE_SHIPPING') && $total >= Configuration::get('ZELERIS_MIN_AMOUNT_FREE'))
				return 0;

			/*************** International and free ***************/
			if ($country_user !== 'ES' && Configuration::get('ZELERIS_FREE_SHIPPING_INTERNACI') && $total >= Configuration::get('ZELERIS_MIN_AMOUNT_FREE_INT'))
				return 0;

			/*************** Shipping isn´t free ***************/
			if (Configuration::get('ZELERIS_FIXED_COST_SHIPPING'))
			{
				/*************** fixed cost ***************/
				$shipping_cost = $shipping_cost_fixed;
				$amount = (float)($shipping_cost + $manipulation_cost);
			}
			else
			{
				/*************** Variable cost ***************/
				$send = array();
				$send['uidCliente'] = Tools::substr(trim($this->clean(Configuration::get('ZELERIS_GUID'))), 0, 32);
				$send['codPaisDst'] = Tools::substr(trim($this->clean($country_user)), 0, 2);
				$send['cpDst'] = Tools::substr(trim($this->clean($cp_user)), 0, 7);
				$send['bultos'] = 1;
				$send['weight'] = $weight;
				if ($country_user == 'ES')
				{
					// no decimals national shippings
					$send['weight'] = Tools::substr(trim($this->clean(ceil($send['weight']))), 0, 6);
				}
				else
					$send['weight'] = Tools::substr(trim($this->clean(round($send['weight'], 2))), 0, 6);

				$send['servicio'] = '0';
				$send['URL'] = trim(Configuration::get('ZELERIS_URL'));
				$shipping_cost = $this->givePrice($send);
				/*************** Call web service to get shipping ***************/
				$shipping_cost = $shipping_cost + (float)(Configuration::get('ZELERIS_MARGIN_SHIPPING_COST'));
				$amount = (float)($shipping_cost + $manipulation_cost);
				if ($amount == 0)
					return false;
			}

			$amount = number_format($amount, (int)Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', '');
			return (float)$amount;

		}


		public function getOrderShippingCostExternal($params)
		{
		}


		public function ordersTable()
		{
			$cookie = $this->context->cookie;
			$smarty = $this->smarty;


			$this->initZelerisSend();


			$smarty->assign('tokenOrder', Tools::getAdminToken('AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)$cookie->id_employee));


			$countQuery = Db::getInstance()->executeS('SELECT COUNT(o.id_order) AS allCmd
												   FROM '._DB_PREFIX_.'orders o
												   JOIN '._DB_PREFIX_.'carrier c ON c.id_carrier = o.id_carrier
												   WHERE LOWER(c.external_module_name) = "zeleriscarrier"');
			$perPage = 20;
			$allReg = $countQuery[0]['allCmd'];
			$p = Tools::getIsset('p');
			if ($p != '')
			{
				if (is_nan($p))
					$p = 1;
			}
			else
				$p = 1;

			require_once(_PS_MODULE_DIR_.'zeleriscarrier/lib/Pager.php');
			$pager = new Pager(array('before' => 5, 'after' => 5, 'all' => $allReg, 'page' => $p, 'perPage' => $perPage));
			$start = ((int)$p - 1) * $perPage;
			$smarty->assign('pager', $pager->setPages());
			$smarty->assign('page', (int)$p);


			$orders = Db::getInstance()->executeS('SELECT
													o.id_order, o.module, o.total_paid_real, o.valid, o.date_add, c.name, e.*,
													u.firstname,u.lastname
												FROM '._DB_PREFIX_.'orders o 
												JOIN '._DB_PREFIX_.'carrier c ON c.id_carrier = o.id_carrier 
												JOIN '._DB_PREFIX_.'zeleris_orders e ON e.id_order = o.id_order 
												JOIN '._DB_PREFIX_.'customer u ON u.id_customer = o.id_customer 
												WHERE c.external_module_name = "zeleriscarrier" 
												ORDER BY o.id_order DESC 
												LIMIT '.(int)$start.', '.(int)$perPage);


			$i = 0;
			foreach ($orders as $order)
			{
				if ($order['valid'])
				{
					$orders[$i]['link_etiqueta'] = 'index.php?tab=AdminZeleris&id_order_envio='.(int)$order['id_order'].'&option=etiqueta&token='.Tools::getValue('token');
					if ($order['send_code'])
					{
						$orders[$i]['link_cancelar'] = 'index.php?tab=AdminZeleris&id_order_envio='.(int)$order['id_order'].'&option=cancelar&token='.Tools::getValue('token');
						$orders[$i]['link_envio_mail'] = 'index.php?tab=AdminZeleris&id_order_envio='.(int)$order['id_order'].'&option=envio&token='.Tools::getValue('token');
					}
					else
					{
						$orders[$i]['link_cancelar'] = '';
						$orders[$i]['link_envio_mail'] = '';
					}
				}
				else
				{
					$orders[$i]['link_etiqueta'] = '';
					$orders[$i]['link_cancelar'] = '';
				}
				$orders[$i]['num_pedido'] = sprintf('%06d', (int)$order['id_order']);
				$i++;
			}


			$smarty->assign('path_img_logo', $this->_path.'views/img/logotipo.png');
			$smarty->assign('path_img_track', $this->_path.'views/img/track.png');
			$smarty->assign('path_img_email', $this->_path.'views/img/email.jpg');
			$smarty->assign('path_img_cod_barras', $this->_path.'views/img/cod_barras.png');
			$smarty->assign('path_img_cancelar', $this->_path.'views/img/cancelar.gif');
			$smarty->assign('token', Tools::getValue('token'));
			$smarty->assign('pedidos', $orders);
			$smarty->assign('pagerTemplate', _PS_MODULE_DIR_.'zeleriscarrier/views/templates/admin/pager_template.tpl');
			return $this->display(__FILE__, 'views/templates/admin/pedidos.tpl');
		}


		public function printLabels($id_order = 0, $packages = 1)
		{
			$smarty = $this->smarty;

			$errores = false;

			if ($id_order)
			{
				$result = Db::getInstance()->executeS('SELECT send_code
													  FROM '._DB_PREFIX_.'zeleris_orders
													  WHERE id_order = "'.(int)$id_order.'"');
				$id_track = $result[0]['send_code'];
			}
			else
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true).'&amp;configure=zeleriscarrier&amp;tab_module=shipping_logistics&amp;module_name=zeleriscarrier');

			if ($id_track == '')
			{

				$data = Db::getInstance()->executeS('SELECT
													o.id_order, o.module, o.total_paid_real,
													c.name, u.email,
													a.firstname, a.lastname, a.address1, a.address2,
													a.other, a.postcode, a.city,
													a.phone, a.phone_mobile, z.iso_code
												  FROM '._DB_PREFIX_.'orders AS o
												  JOIN '._DB_PREFIX_.'carrier AS c ON c.id_carrier = o.id_carrier
												  JOIN '._DB_PREFIX_.'customer AS u ON u.id_customer = o.id_customer
												  JOIN '._DB_PREFIX_.'address a ON a.id_address = o.id_address_delivery
												  JOIN '._DB_PREFIX_.'country AS z ON z.id_country = a.id_country
												  WHERE
													o.id_order = "'.(int)$id_order.'"');


				$products = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference
													  FROM '._DB_PREFIX_.'order_detail
													  WHERE id_order = "'.(int)$id_order.'"');


				$country_zeleris = $data[0]['iso_code'];


				$send = array();
				$expedition_weight = 0;
				$send['detalle'] = '';
				foreach ($products as $product)
				{
					$unitweight = $product['product_weight'];
					$units = $product['product_quantity'];
					$expedition_weight += $unitweight * $units;

					if ($country_zeleris == 'ES')
						$unitweight = ceil($unitweight);
					else
						$unitweight = round($unitweight, 2);

					for ($i = 1; $i <= $units; $i++)
					{
						$send['detalle'] .= '<InfoBulto';
						$send['detalle'] .= ' Referencia="'.$product['product_reference'];
						$send['detalle'] .= '" Bulto="1';
						$send['detalle'] .= '" Kilos="'.$unitweight;
						$send['detalle'] .= '" Volumen= "0"';
						$send['detalle'] .= '/>';
					}
				}

				if ($country_zeleris == 'ES')
				{
					if ($expedition_weight < 1)
						$expedition_weight = 1;
				}
				else
				{
					if ($expedition_weight == 0)
						$expedition_weight = 1;
				}


				$payment_method = $data[0]['module'];
				if ($payment_method == 'cashondelivery')
					$zeleris_reembolso = (float)($data[0]['total_paid_real']);
				else
					$zeleris_reembolso = 0;

				$send['URL'] = trim(Configuration::get('ZELERIS_URL'));
				$send['uidCliente'] = Tools::substr(trim($this->clean(Configuration::get('ZELERIS_GUID'))), 0, 32);
				$send['nombreDst'] = Tools::substr(trim($this->clean($data[0]['firstname'])).' '.trim($this->clean($data[0]['lastname'])), 0, 40);
				$send['direccionDst'] = Tools::substr(trim($this->clean($data[0]['address1'])).' '.trim($this->clean($data[0]['address2'])), 0, 80);
				$send['cpDst'] = Tools::substr(trim($this->clean($data[0]['postcode'])), 0, 7);
				$send['poblacionDst'] = Tools::substr(trim($this->clean($data[0]['city'])), 0, 40);
				$send['codPaisDst'] = Tools::substr(trim($this->clean($country_zeleris)), 0, 2);
				$send['telefono1Dst'] = Tools::substr(trim($this->clean($data[0]['phone'])), 0, 15);
				$send['telefono2Dst'] = Tools::substr(trim($this->clean($data[0]['phone_mobile'])), 0, 15);
				$send['faxDst'] = '';
				$send['emailDst'] = Tools::substr(trim($this->clean($data[0]['email'])), 0, 60);
				$send['RefC'] = Tools::substr(sprintf('%06d', trim($this->clean($data[0]['id_order']))), 0, 20);
				$send['bultos'] = $packages;
				$send['servicio'] = '0';
				$send['importeReembolso'] = Tools::substr($zeleris_reembolso, 0, 13);
				$send['nombreProducto'] = Tools::substr(trim($this->clean(Configuration::get('ZELERIS_MERCHANDISE_DESCRIPTION'))), 0, 20);

				$send['observaciones'] = Tools::substr(trim($this->clean($data[0]['other'])), 0, 40);
				$send['weight'] = (float)($expedition_weight);
				if ($country_zeleris == 'ES')
				{
					$send['codPaisDst'] = '';
					$send['weight'] = Tools::substr(trim($this->clean(ceil($send['weight']))), 0, 6);
				}
				else
				{
					$send['codPaisDst'] = $country_zeleris;
					$send['weight'] = Tools::substr(trim($this->clean(round($send['weight'], 2))), 0, 6);
				}

				if (!Tools::getValue('modotransporte'))
					$_GET['modotransporte'] = '';


				$send['modoTransporte'] = Tools::getValue('modotransporte');

				$id_track = $this->save($send, $errores);


				if ($id_track != '')
				{

					$baseurl = Tools::substr(trim(Configuration::get('ZELERIS_URL')), 0, Tools::strlen(trim(Configuration::get('ZELERIS_URL'))) - Tools::strlen(basename(trim(Configuration::get('ZELERIS_URL')))));
					$url = $baseurl.'Etiqueta.aspx?uid='.Configuration::get('ZELERIS_GUID').'&nseg='.$id_track;

					$client = new SoapClient(trim(Configuration::get('ZELERIS_URL')));
					$response = $client->TrackingURL()->TrackingURLResult;
					$urltrack = $response.'?id_seguimiento='.$id_track;

					Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'zeleris_orders
											SET
												send_code = "'.pSQL($id_track).'",
												url_track = "'.pSQL($urltrack).'",
												packages ="'.pSQL($packages).'",
												date = "'.date('Y-m-d H:i:s').'"
											WHERE id_order = "'.(int)$id_order.'"');
					Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'orders
											SET shipping_number="'.pSQL($id_track).'",
											
											WHERE id_order = "'.(int)$id_order.'"');
					$smarty->assign('download_pdf', $url);
				}
			}
			else
			{
				$baseurl = Tools::substr(trim(Configuration::get('ZELERIS_URL')), 0, Tools::strlen(trim(Configuration::get('ZELERIS_URL'))) - Tools::strlen(basename(trim(Configuration::get('ZELERIS_URL')))));
				$url = $baseurl.'Etiqueta.aspx?uid='.trim(Configuration::get('ZELERIS_GUID')).'&nseg='.$id_track;

				$smarty->assign('download_pdf', $url);
			}

			$smarty->assign('volver', '<a href="index.php?tab=AdminZeleris&token='.Tools::getAdminTokenLite('AdminZeleris').'"><strong>'.$this->l('Back').'</strong></a>');
			$smarty->assign('errores', $errores);
			$smarty->assign('path_img_logo', $this->_path.'views/img/logotipo.png');
			return $this->display(__FILE__, 'views/templates/admin/etiqueta.tpl');
		}


		public function initZelerisSend()
		{
			$sends = Db::getInstance()->executeS('SELECT o.id_order
											   FROM '._DB_PREFIX_.'orders o
											   JOIN '._DB_PREFIX_.'carrier c ON c.id_carrier = o.id_carrier
											   WHERE LOWER(c.external_module_name) = "zeleriscarrier"');
			if (!$sends)
				return false;

			foreach ($sends as $send)
				if (!Db::getInstance()->executeS('SELECT id_order FROM '._DB_PREFIX_.'zeleris_orders WHERE id_order = "'.(int)$send['id_order'].'"'))
					Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'zeleris_orders(id_order, send_code, url_track) VALUES ("'.(int)$send['id_order'].'","","")');
			
			return true;
		}


		public function sendEmailTrack($id_order = false)
		{
			$cookie = $this->context->cookie;
			$smarty = $this->smarty;

			$error = false;
			$result = false;
			if (!Tools::getValue('message'))
			{
				$datos = Db::getInstance()->executeS('SELECT message FROM '._DB_PREFIX_.'zeleris_email');
				$message = $datos[0]['message'];
				$url_form = 'index.php?tab=AdminZeleris&id_order_envio='.$id_order.'&option=envio&token='.Tools::getAdminTokenLite('AdminZeleris');
				$smarty->assign('message', $message);
				$smarty->assign('formulario', true);
				$smarty->assign('url_formulario', $url_form);
			}
			else
			{

				if ($id_order)
				{
					$datos = Db::getInstance()->executeS('SELECT
														o.id_order, o.reference, u.firstname, u.lastname, u.email, e.url_track
													  FROM '._DB_PREFIX_.'orders AS o
													  JOIN '._DB_PREFIX_.'customer AS u ON (u.id_customer = o.id_customer)
													  JOIN '._DB_PREFIX_.'zeleris_orders AS e ON (e.id_order = o.id_order)
													  WHERE
														o.id_order = "'.(int)$id_order.'"');

					$name = (string)$datos[0]['firstname'];
					$lastname = (string)$datos[0]['lastname'];
					$user_email = (string)$datos[0]['email'];
					$order_id = sprintf('%06d', (int)$id_order);
					$order_reference = (string)$datos[0]['reference'];
					$subject = $this->l('Track code for order. ').(string)$order_id;
					$link = '<p><a href="'.(string)$datos[0]['url_track'].'">'.$this->l('Track').'</a></p>';
					$message = Tools::getValue('message', '').'<p>'.$link.'</p>';

					if (Validate::isEmail($user_email) && Mail::Send((int)($cookie->id_lang), 'order_customer_comment', $subject, array('{firstname}' => $name, '{lastname}' => $lastname, '{id_order}' => $order_id, '{order_name}' => $order_reference, '{email}' => $user_email, '{message}' => $message), $user_email))
					{
						Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'zeleris_email
												SET message="'.Tools::getValue('message').'"
												WHERE id = "1"');
						$result = '<p>'.$this->l('Tracking URL request').'<b>'.$order_id.'</b> '.$this->l('was sent sucesfully').' <b>'.$name.' '.$lastname.'</b> '.$this->l('to user').' <b>'.$user_email.'</b></p>';
					}
					else
						$error = Tools::displayError($this->l('There was an error while trying to send the message to the email: ').$user_email);

					$smarty->assign('formulario', false);
				}
			}

			$smarty->assign('volver', '<a href="index.php?tab=AdminZeleris&token='.Tools::getAdminTokenLite('AdminZeleris').'"><strong>'.$this->l('Back').'</strong></a>');
			$smarty->assign('error', $error);
			$smarty->assign('resultado', $result);
			$smarty->assign('path_img_logo', $this->_path.'views/img/logotipo.png');

			return $this->display(__FILE__, 'views/templates/admin/seguimiento.tpl');
		}


		protected function givePrice($sending)
		{
			try
			{
				$xml = array('docIn' => '<?xml version="1.0" encoding="UTF-8" ?>'.
					'<Body>'.
					'<InfoCuenta>'.
					'<UIDCliente>'.$sending['uidCliente'].'</UIDCliente>'.
					'<Usuario>'.''.'</Usuario>'.
					'<Clave>'.''.'</Clave>'.
					'<CodRemitente>'.''.'</CodRemitente>'.
					'</InfoCuenta>'.

					'<DatosDestino>'.
					'<Pais>'.$sending['codPaisDst'].'</Pais>'.
					'<Codpos>'.$sending['cpDst'].'</Codpos>'.
					'</DatosDestino>'.

					'<DatosServicio>'.
					'<Bultos>'.$sending['bultos'].'</Bultos>'.
					'<Kilos>'.$sending['weight'].'</Kilos>'.
					'<Volumen>'.'0'.'</Volumen>'.
					'<Servicio>'.$sending['servicio'].'</Servicio>'.
					'<Reembolso>'.'0'.'</Reembolso>'.
					'<ValorSeguro>'.'0'.'</ValorSeguro>'.
					'</DatosServicio>'.
					'</Body>');

				$client = new SoapClient($sending['URL']);
				$response = $client->Valora($xml)->ValoraResult;
				$initial_position = strpos($response, '<Status>') + 8;
				$end_position = strpos($response, '</Status>');
				$error = Tools::substr($response, $initial_position, $end_position - $initial_position);
				if ($error == 'OK')
					$value = Tools::substr($response, $initial_position, $end_position - $initial_position);
				else
					$value = 0;

			}
			catch (Exception $e)
			{
				$value = 0;
			}

			return $value;
		}


		protected function save($sending, &$errores)
		{
			try
			{
				$xml = array('docIn' => '<?xml version="1.0" encoding="UTF-8" ?>'.
					'<Body>'.
					'<InfoCuenta>'.
					'<UIDCliente>'.$sending['uidCliente'].'</UIDCliente>'.
					'<Usuario>'.''.'</Usuario>'.
					'<Clave>'.''.'</Clave>'.
					'<CodRemitente>'.''.'</CodRemitente>'.
					'</InfoCuenta>'.

					'<DatosDestinatario>'.
					'<NifCons>'.'-'.'</NifCons>'.
					'<Nombre>'.$sending['nombreDst'].'</Nombre>'.
					'<Direccion>'.$sending['direccionDst'].'</Direccion>'.
					'<Pais>'.$sending['codPaisDst'].'</Pais>'.
					'<Codpos>'.$sending['cpDst'].'</Codpos>'.
					'<Poblacion>'.$sending['poblacionDst'].'</Poblacion>'.
					'<Contacto>'.''.'</Contacto>'.
					'<Telefono1>'.$sending['telefono1Dst'].'</Telefono1>'.
					'<Telefono2>'.$sending['telefono2Dst'].'</Telefono2>'.
					'<Email>'.$sending['emailDst'].'</Email>'.
					'</DatosDestinatario>'.

					'<DatosServicio>'.
					'<Referencia>'.$sending['RefC'].'</Referencia>'.
					'<Bultos>'.$sending['bultos'].'</Bultos>'.
					'<Kilos>'.$sending['weight'].'</Kilos>'.
					'<Volumen>'.'0'.'</Volumen>'.
					'<Servicio>'.$sending['servicio'].'</Servicio>'.
					'<Reembolso>'.$sending['importeReembolso'].'</Reembolso>'.
					'<ValorSeguro>'.'0'.'</ValorSeguro>'.
					'<ValoraAduana>'.'0'.'</ValoraAduana>'.
					'<Mercancia>'.$sending['nombreProducto'].'</Mercancia>'.
					'<TipoGastosAduana>'.'0'.'</TipoGastosAduana>'.
					'<TipoAvisoEntrega>'.'0'.'</TipoAvisoEntrega>'.
					'<TipoPortes>'.'P'.'</TipoPortes>'.
					'<TipoReembolso>'.'P'.'</TipoReembolso>'.
					'<DAS>'.''.'</DAS>'.
					'<GS>'.''.'</GS>'.
					'<Identicket>'.''.'</Identicket>'.
					'<FechaEA>'.''.'</FechaEA>'.
					'<Observaciones>'.$sending['observaciones'].'</Observaciones>'.
					'<ModoTransporte>'.$sending['modoTransporte'].'</ModoTransporte>'.
					'<InfoBultos>'.$sending['detalle'].'</InfoBultos>'.
					'</DatosServicio>'.
					'</Body>');

				$client = new SoapClient($sending['URL']);
				$response = $client->GrabaServicios($xml)->GrabaServiciosResult;

				$initial_position = strpos($response, '<resultado>') + 11;
				$end_position = strpos($response, '</resultado>');
				$result = Tools::substr($response, $initial_position, $end_position - $initial_position);

				if ($result == 'OK')
				{
					$initial_position = strpos($response, '<nseg>') + 6;
					$end_position = strpos($response, '</nseg>');
					$id_track = Tools::substr($response, $initial_position, $end_position - $initial_position);
					$sending['error'] = 'OK';
					$errores = false;
				}
				else
				{
					$id_track = '';
					$initial_position = strpos($response, '<mensaje>') + 9;
					$end_position = strpos($response, '</mensaje>');
					$MensajeError = Tools::substr($response, $initial_position, $end_position - $initial_position);
					$MensajeError = Tools::substr($MensajeError, 0, 150);
					$errores = Tools::displayError($this->l('ERROR: Unable to record the order in Zeleris. reason: ').$MensajeError);
				}
			}
			catch (Exception $e)
			{
				$id_track = '';

				$MensajeError = Tools::substr($e, 0, 150);
				$errores = Tools::displayError($this->l('ERROR: Unable to record the order in Zeleris. reason: ').$MensajeError);
			}

			return $id_track;
		}


		public function clean($value)
		{
			$trans = array('&' => 'y', '¿' => '', '?' => '', '<' => '', '>' => '');
			return utf8_encode(strtr(utf8_decode($value), $trans));
		}


		protected function contact()
		{
			$cookie = $this->context->cookie;
			$smarty = $this->smarty;
			$language = $this->context->language->iso_code;
			if (Tools::getValue('chkServicios'))
				$chkServicios = implode(',', Tools::getValue('chkServicios'));
			else
				$chkServicios = '';
			$subject = $this->l('New request from Prestashop module');

			if (Mail::Send((int)($cookie->id_lang), 'zeleris_contact', $subject, 
			array('{txtCompany}' => Tools::getValue('txtEmpresa'), 
				'{txtVatNumber}' => Tools::getValue('txtNIF'), 
				'{txtAddress}' => Tools::getValue('txtDireccion'), 
				'{cmbState}' => Tools::getValue('cmbProvincia'), 
				'{txtContact}' => Tools::getValue('txtContacto'), 
				'{txtPhone}' => Tools::getValue('txtTelefono'), 
				'{txtEMail}' => Tools::getValue('txtEMail'), 
				'{chkServices}' => $chkServicios, 
				'{txtCurrentSendings}' => Tools::getValue('txtEnviosActual'), 
				'{txtNextSendings}' => Tools::getValue('txtEnviosPrevisto'), 
				'{txtObservations}' => Tools::getValue('txtObservaciones'), 
				'{cmbSource}' => $this->l('Prestashop'), 
				'{txtSource}' => Tools::getValue('txtProcedencia')
			), 
			array('diana.aguilerasantos@telefonica.com', 'alicia.sanchezmartin@telefonica.com', 'daniel.pastranapina@telefonica.com'), null, null, null, null, null, dirname(__FILE__).'/mails/'))
				$this->_html .= $this->displayConfirmation($this->l('The mail has been delivered sucessfully.'));
			$this->_html .= '<script>$(document).ready(function(){$("#landing").hide(); $("#capa_layer, #tabform").show()})</script>';
		}
	}