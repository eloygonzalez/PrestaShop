<?php
/*
* 2007-2013 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class Dashtrends extends Module
{
	public function __construct()
	{
		$this->name = 'dashtrends';
		$this->displayName = 'Dashboard Trends';
		$this->tab = '';
		$this->version = '0.1';
		$this->author = 'PrestaShop';
		
		parent::__construct();
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('dashboardZoneTwo') || !$this->registerHook('dashboardDatas'))
			return false;
		return true;
	}

	public function hookDashboardZoneTwo($params)
	{
		return $this->display(__FILE__, 'dashboard_zone_two.tpl');
	}
	
	public function hookDashboardDatas($params)
	{
		$gapi = Module::isInstalled('gapi') ? Module::getInstanceByName('gapi') : false;
		if (Validate::isLoadedObject($gapi))
		{
			$visits_score = 0;
			if ($result = $gapi->requestReportData('', 'ga:visits', $this->context->employee->stats_date_from, $this->context->employee->stats_date_to, null, null, 1, 1))
				$visits_score = $result[0]['metrics']['visits'];
		}
		else
		{
			$visits_score = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT COUNT(c.`id_connections`)
			FROM `'._DB_PREFIX_.'connections` c
			WHERE c.`date_add` BETWEEN '.ModuleGraph::getDateBetween().'
			'.Shop::addSqlRestriction(false, 'c'));
		}
		$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
		SELECT COUNT(o.`id_order`) as orders_score, SUM(o.`total_paid_tax_excl` / o.conversion_rate) as sales_score
		FROM `'._DB_PREFIX_.'orders` o
		WHERE o.`invoice_date` BETWEEN '.ModuleGraph::getDateBetween().'
		'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o'));
		extract($row);

		return array(
			'data_value' => array(
				'sales_score' => Tools::displayPrice((float)$sales_score),
				'orders_score' => $orders_score,
				'cart_value_score' => Tools::displayPrice($orders_score ? $sales_score / $orders_score : 0),
				'visits_score' => $visits_score,
				'convertion_rate_score' => $visits_score ? 100 * $orders_score / $visits_score : 0,
				'net_profits_score' => Tools::displayPrice(0),
			),
			'data_trends' => array(
				'sales_score_trends' => array('way' => 'up', 'value' => 0.42),
				'orders_score_trends' => array('way' => 'down', 'value' => 0.42),
				'cart_value_score_trends' => array('way' => 'up', 'value' => 0.42),
				'visits_score_trends' => array('way' => 'down', 'value' => 0.42),
				'convertion_rate_score_trends' => array('way' => 'up', 'value' => 0.42),
				'net_profits_score_trends' => array('way' => 'up', 'value' => 0.42)
			)
		);
	}
}