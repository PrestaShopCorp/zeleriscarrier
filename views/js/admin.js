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
$(document).ready(function() {
		var bodyheight = $("#bloque1").height();
				$("#bloque2").height(bodyheight);
		$(window).resize(function() {
			var bodyheight = $("#bloque1").height();
				$("#bloque2").height(bodyheight);
		});
			$(".butoon_volver").click(function(){
				$("#capa_layer, #tabform, #tabList").hide()
				$("#landing").show();
			});
			
				$("#sendcontact").click(function(e){
					
						error = false;
						mes = false;
						if(!$("#txtEmpresa").val()){
							error = true;
							mes = "'.$this->l('Name company can not be empty').'";
						}
						if(!$("#txtDireccion").val()){
							error = true;
							mes = "'.$this->l('Address can not be empty').'";
						}
						if(!$("#txtTelefono").val()){
							error = true;
							mes = "'.$this->l('Phone can not be empty').'";
						}
						if(!$("#cmbProvincia").val()){
							error = true;
							mes = "'.$this->l('State can not be empty').'";
						}
						
						
						if(error){
							e.preventDefault();
							alert(mes);
						}
					
					});
					$("#boton_info").click(function(){
								$("#landing").hide();
								$("#capa_layer, #tabform").show()
							});
							$("#boton_conf").click(function(){
								$("#landing").hide();
								$("#capa_layer, #tabList").show()
							});
	
	});
