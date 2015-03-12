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
<p style="text-align:center;"><span>{l s='Pagination' mod='zeleriscarrier'}:</span>
	<a href="index.php?tab=AdminZeleris&token={$token|escape:'html'}&p=1"><img src="../img/admin/list-prev2.gif" /></a>
	{foreach from=$pager.before key=p item=page}
		<a href="index.php?tab=AdminZeleris&token={$token|escape:'html'}&p={$page|escape:'html'}" class="action_module" style="margin-right:5px;">{$page}</a>
	{/foreach}
	<span style="font-weight:bold; margin-right:5px;">{$pager.actual|escape:'html'}</span>
	{foreach from=$pager.after key=p item=page}
		<a href="index.php?tab=AdminZeleris&token={$token|escape:'html'}&p={$page|escape:'html'}" class="action_module" style="margin-right:5px;">{$p}</a>
	{/foreach}
		<a href="index.php?tab=AdminZeleris&token={$token|escape:'html'}&p={$pager.last|escape:'html'}"><img src="../img/admin/list-next2.gif" /></a>
</p>