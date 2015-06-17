{*
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
*  @author    Línea Gráfica E.C.E. S.L.- https://www.lineagrafica.es/ <soporte@lineagrafica.es>
*  @copyright 2007-2015 TELEFONICA SERVICIOS INTEGRALES DE DISTRIBUCIÓN S.A.U.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of TELEFONICA SERVICIOS INTEGRALES DE DISTRIBUCIÓN S.A.U.
*}
{literal}
	<style type="text/css">
		.table tr td {padding: 2px; color: #000000;}
		.table tr.small th {font-size:9px;text-align:center;}
		.selectedRow {background: #dadada;}
	</style>
	
	<script type="text/javascript">
		function marcarModoTransporte(id_pedido, url)
		{
			var txtModo = "";
			
			if (document.getElementById("chkModo_" + id_pedido).checked)
				txtModo = "&modotransporte=1";
				
			document.getElementById("link_etiqueta_" + id_pedido).href = url + txtModo;
		}
	</script>
{/literal}
<table style="border: 0px;">
	<tr>
		<td style="width:100px;"><img src="{$path_img_logo|escape:'html'}" /></td>
		<td><span style="color: #E10564;font-size: 24px;">{l s='Zelers carrier' mod='zeleriscarrier'}</span></td>
	</tr>
</table>

{if $pedidos}
	{* include pager template *}
	{include file="$pagerTemplate" var=$pager}
	{* include pager template *}

    	<table class="table" cellspacing="0" cellpadding="0" style="width:700px;">
	  	<thead>
	    		<tr class="small">
	      			<th>ID</th>
	      			<th><p style="width:70px;">{l s='Order' mod='zeleriscarrier'}</p></th>
	      			<th><p style="width:120px;">{l s='Customer Name' mod='zeleriscarrier'}</p></th> 
	      			<th><p style="width:60px;">{l s='Price' mod='zeleriscarrier'}</p></th>
	      			<th><p style="width:100px;">{l s='Order Date' mod='zeleriscarrier'}</p></th>
	      			<th><p style="width:100px;">{l s='Order Shipping' mod='zeleriscarrier'}</p></th>	
					<th><p style="width:50px;">{l s='Packages number' mod='zeleriscarrier'}</p></th>					
	      			<th><p style="width:170px;">{l s='Number tracking' mod='zeleriscarrier'}</p></th>
					<th><p style="width:100px;">{l s='Not generate shipping' mod='zeleriscarrier'}</p></th>
	      			<th><p style="width:20px;"> </p></th>
	      			<th><p style="width:20px;"> </p></th>
	      			<th><p style="width:20px;"> </p></th>
	    		</tr>
	  	</thead>
	  	<tbody>
	   		{foreach from=$pedidos key=o item=pedido}
	       	<tr>
               			<td>{if isset($pedido.id_envio)}{$pedido.id_envio|escape:'html'}{/if}</td>
               			<td>{if isset($pedido.num_pedido)}{$pedido.num_pedido|escape:'html'}{/if}</td>
               			<td>{if isset($pedido.firstname)}{$pedido.firstname|escape:'html'} {$pedido.lastname|escape:'html'}{/if}</td>
               			<td>{if isset($pedido.total_paid_real)}{$pedido.total_paid_real|escape:'html'}{/if}</td>
               			<td>{if isset($pedido.date_add)}{$pedido.date_add|escape:'html'}{/if}</td>
               			<td>{if isset($pedido.date)}{$pedido.date|escape:'html'}{/if}</td>               
               			{if isset($pedido.url_track) && $pedido.url_track}<td>{$pedido.packages|escape:'html'}</td>{else}<td><input style="width:40px" type="text" value="1" name="packages" class="packages" /> </td>{/if}            
               			<td>{if isset($pedido.send_code)}{$pedido.send_code|escape:'html'}{/if}</td>
						<td><input type="checkbox" id="chkModo_{if isset($pedido.id_envio)}{$pedido.id_envio|escape:'html'}{/if}" onchange="javascript:marcarModoTransporte('{if isset($pedido.id_envio)}{$pedido.id_envio|escape:'html'}{/if}', '{$pedido.link_etiqueta|escape:'html'}');" /></td>
               			<td>
               				{if $pedido.url_track}
               					<a href="{$pedido.url_track|escape:'html'}" target="_blank"><img src="{$path_img_track|escape:'html'}" title="{l s='Display Shipment tracking' mod='zeleriscarrier'}" alt="{l s='Display Shipment tracking' mod='zeleriscarrier'}" /></a>
               				{/if}
               			</td>
						<td>
               				{if $pedido.url_track}
               					<a href="{$pedido.link_envio_mail|escape:'html'}"><img src="{$path_img_email|escape:'html'}" title="{l s='Send tracking email to the customer' mod='zeleriscarrier'}" alt="{l s='Send tracking email to the customer' mod='zeleriscarrier'}" /></a>               			
               				{/if}
               			</td>
               			<td>
                   				{if $pedido.link_etiqueta}
                       				<a class="link_etiqueta" href="{$pedido.link_etiqueta|escape:'html'}" id="link_etiqueta_{if isset($pedido.id_envio)}{$pedido.id_envio|escape:'html'}{/if}">
                       					<img src="{$path_img_cod_barras|escape:'html'}" title="{l s='Display transport note' mod='zeleriscarrier'}" alt="{l s='Display transport note' mod="zeleriscarrier"}" />
                       				</a>
                   				{else}
                       				&nbsp;
                   				{/if}                   
               			</td>
					
           			</tr>
	   		{/foreach}
	  
	  	</tbody>
	</table>

	{* include pager template *}
	{include file="$pagerTemplate" var=$pager}
	{* include pager template *}
{else}
    <h3>{l s='No purchase orders for Zeleris.' mod='zeleriscarrier'}</h3>
{/if}
<script>
	$(document).ready(function(){
		$('.link_etiqueta').click(function(e){
			if($(this).closest('tr').find('input.packages').length){
				e.preventDefault();
				if(!$(this).closest('tr').find('input.packages').val())
					alert('ERROR');
				else	
					window.location.href = $(this).attr('href') + "&packages=" + $(this).closest('tr').find('input.packages').val();
			}	
		});
	})
</script>