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
<table style="border: 0px;">
	<tr>
		<td style="width:100px;"><img src="{$path_img_logo|escape:'html'}" /></td>
		<td><span style="color: #E10564;font-size: 24px;">{l s='Zeleris Carrier ' mod='zeleriscarrier'}</span></td>
	</tr>
</table>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

{if $formulario}
	<h2>{l s='Edit the message to be sent to the client, along with the number tracking.' mod='zeleriscarrier'}</h2>
	<form method="post" action="{$url_formulario|escape:'html'}">
		<fieldset style="width: 600px;">
			<legend style="cursor: pointer;"><img src="../img/admin/email_edit.gif"> {l s='Body of the message, informing the client sending your purchase.' mod="zeleriscarrier"}</legend>
			<div style="" id="message">
				<br><br>
				<textarea  rows="12" cols="79" name="message" id="txt_msg">{$message|escape:'html'}</textarea>
				<br><br>
				<input type="submit" value="{l s='Send email' mod='zeleriscarrier'}" name="submitMessage" class="button">
			</div>
		</fieldset>
	</form>
{else}
	{if $error}
		{$error|escape:'utf-8'}
	{else}
		{$resultado|escape:'utf-8'}
	{/if}
{/if}

<p>&nbsp;</p>
<p>&nbsp;</p>
<p>{$volver|escape:'utf-8'}</p>