<?php

/**
 * PHP version 5
 * @copyright  sr-tag.de 2011-2017
 * @author     Sven Rhinow
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

/**
 * project-manager-bundle Version
 */
@define('IAO_PATH','vendor/srhinow/project-manager-bundle');
@define('PMB_PUBLIC_FOLDER','bundles/srhinowprojectmanager');
@define('IAO_PDFCLASS_FILE', IAO_PATH.'/classes/iaoPDF.php');

/**
 * DEFAULT IAO VALUES 
*/
$GLOBALS['IAO']['default_settings_id'] = 1;
$GLOBALS['IAO']['default_agreement_cycle'] = '+1 year';

/**
 * back-end modules
 */

$GLOBALS['BE_MOD']['iao'] = array
(
	'iao_projects' => array
	(
		'tables' => array('tl_iao_projects','tl_iao_agreements','tl_iao_invoice','tl_iao_invoice_items','tl_iao_offer','tl_iao_offer_items','tl_iao_credit','tl_iao_credit_items','tl_iao_reminder'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/blackboard_steps.png',
		'stylesheet' => PMB_PUBLIC_FOLDER.'/be.css',
	),
	'iao_offer' => array
	(
		'tables' => array('tl_iao_offer','tl_iao_offer_items'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/16-file-page.png',
        'stylesheet' => PMB_PUBLIC_FOLDER.'/be.css',
		'importOffer'=> array('iao_offer', 'importOffer'),
		'exportOffer'=> array('iao_offer', 'exportOffer')
	),
	'iao_invoice' => array
	(
		'tables' => array('tl_iao_invoice','tl_iao_invoice_items'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/kontact_todo.png',
        'stylesheet' => PMB_PUBLIC_FOLDER.'/be.css',
		'importInvoices'=> array('iao_invoice', 'importInvoices'),
		'exportInvoices'=> array('iao_invoice', 'exportInvoices')
	),
	'iao_credit' => array
	(
		'tables' => array('tl_iao_credit','tl_iao_credit_items'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/16-tag-pencil.png',
        'stylesheet' => PMB_PUBLIC_FOLDER.'/be.css',
	),
	'iao_reminder' => array
	(
		'tables' => array('tl_iao_reminder'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/warning.png',
        'stylesheet' => PMB_PUBLIC_FOLDER.'/be.css',
		'checkReminder'=> array('iao_reminder', 'checkReminder'),
	),
	'iao_agreements' => array
	(
		'tables' => array('tl_iao_agreements'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/clock_history_frame.png',
        'stylesheet' => PMB_PUBLIC_FOLDER.'/be.css',
	),
	'iao_customer' => array
	(
		'tables'	=> array('tl_member'),
		'callback'	=> 'ModuleCustomerMember',
		'icon'		=> 'bundles/srhinowprojectmanager/icons/users.png',
        'stylesheet' => PMB_PUBLIC_FOLDER.'/be.css',
	),

	'iao_setup' => array
	(
		'callback'	=> 'ModuleIAOSetup',
		'tables'	=> array(),
		'icon'		=> 'bundles/srhinowprojectmanager/process.png',
        'stylesheet' => PMB_PUBLIC_FOLDER.'/be.css',
	)
);

/**
 * Setup Modules
 */
$GLOBALS['IAO_MOD'] = array
(
	'config' => array
	(
		'iao_settings' => array
		(
			'tables'					=> array('tl_iao_settings'),
			'icon'						=> IAO_PATH.'/html/icons/construction.png',
		),
		'iao_tax_rates' => array
		(
			'tables'					=> array('tl_iao_tax_rates'),
			'icon'						=> IAO_PATH.'/html/icons/calculator.png',
		),
		'iao_item_units' => array
		(
			'tables'					=> array('tl_iao_item_units'),
			'icon'						=> IAO_PATH.'/html/icons/category.png',
		),
	),
	'templates' => array
	(
		'iao_templates' => array
		(
			'tables' => array('tl_iao_templates'),
			'icon'   => IAO_PATH.'/html/icons/text_templates_16.png'
		),
		'iao_templates_items' => array
		(
			'tables' => array('tl_iao_templates_items'),
			'icon'   => IAO_PATH.'/html/icons/templates_items_16.png'
		)
	)
);

// Enable tables in iao_setup
if ($_GET['do'] == 'iao_setup')
{
	foreach ($GLOBALS['IAO_MOD'] as $strGroup=>$arrModules)
	{
		foreach ($arrModules as $strModule => $arrConfig)
		{
			if (is_array($arrConfig['tables']))
			{

				$GLOBALS['BE_MOD']['iao']['iao_setup']['tables'] = array_merge($GLOBALS['BE_MOD']['iao']['iao_setup']['tables'], $arrConfig['tables']);

			}
		}
	}
}

/**
 * Frontend modules
 */
$GLOBALS['FE_MOD']['iao_fe'] = array
(
	'fe_iao_offer' => 'ModuleMemberOffers',
	'fe_iao_invoice' => 'ModuleMemberInvoices',
	'fe_iao_credit' => 'ModuleMemberCredits',
	'fe_iao_reminder' => 'ModuleMemberReminder',
	'fe_iao_agreement' => 'ModuleMemberAgreements',
	'fe_iao_public_project_list' => 'ModulePublicProjectList',
	'fe_iao_public_project_details' => 'ModulePublicProjectDetails'
);

/**
 * HOOKS
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('iaoHooks', 'iaoReplaceInsertTags');

/**
 * Cron jobs
 */
$GLOBALS['TL_CRON']['daily'][] = array('iaoCrons', 'sendAgreementRemindEmail');

/**
 * Permissions are access settings for user and groups (fields in tl_user and tl_user_group)
 */
$GLOBALS['TL_PERMISSIONS'][] = 'iaomodules';
$GLOBALS['TL_PERMISSIONS'][] = 'iaomodulep';
$GLOBALS['TL_PERMISSIONS'][] = 'iaosettings';
$GLOBALS['TL_PERMISSIONS'][] = 'iaosettingp';

