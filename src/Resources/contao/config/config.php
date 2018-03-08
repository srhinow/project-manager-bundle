<?php
/**
 * @copyright  Sven Rhinow 2011-2018
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

/**
 * project-manager-bundle Version
 */
@define('IAO_VERSION', '1.0');
@define('IAO_BUILD', '6');
@define('IAO_PATH','vendor/srhinow/project-manager-bundle');
@define('PMB_PUBLIC_FOLDER','bundles/srhinowprojectmanager');
@define('IAO_PDFCLASS_FILE', IAO_PATH.'/classes/iaoPDF.php');

/**
 * DEFAULT IAO VALUES 
*/
$GLOBALS['IAO']['default_settings_id'] = 1;
$GLOBALS['IAO']['default_agreement_cycle'] = '+1 year';
$GLOBALS['IAO']['csv_seperators'] = ['comma'=>',', 'semicolon'=>';', 'tabulator'=>'\t', 'linebreak'=>'\n'];

/**
 * back-end modules
 */

$GLOBALS['BE_MOD']['iao'] = array
(
	'iao_projects' => array
	(
		'tables' => array('tl_iao_projects','tl_iao_agreements','tl_iao_invoice','tl_iao_invoice_items','tl_iao_offer','tl_iao_offer_items','tl_iao_credit','tl_iao_credit_items','tl_iao_reminder'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/blackboard_steps.png',
        'importInvoices'=> array('Iao\Backend\Invoice\ImportExport', 'importInvoices'),
        'exportInvoices'=> array('Iao\Backend\Invoice\ImportExport', 'exportInvoices')
	),
	'iao_offer' => array
	(
		'tables' => array('tl_iao_offer','tl_iao_offer_items'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/16-file-page.png',
		'importOffer'=> array('Iao\Backend\Offer\ImportExport', 'importOffer'),
		'exportOffer'=> array('Iao\Backend\Offer\ImportExport', 'exportOffer')
	),
	'iao_invoice' => array
	(
		'tables' => array('tl_iao_invoice','tl_iao_invoice_items'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/kontact_todo.png',
        'importInvoices'=> array('Iao\Backend\Invoice\ImportExport', 'importInvoices'),
        'exportInvoices'=> array('Iao\Backend\Invoice\ImportExport', 'exportInvoices')
	),
	'iao_credit' => array
	(
		'tables' => array('tl_iao_credit','tl_iao_credit_items'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/16-tag-pencil.png',
	),
	'iao_reminder' => array
	(
		'tables' => array('tl_iao_reminder'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/warning.png',
		'checkReminder'=> array('Iao\Backend\Reminder\Reminder', 'checkReminder'),
	),
	'iao_agreements' => array
	(
		'tables' => array('tl_iao_agreements'),
		'icon'   => 'bundles/srhinowprojectmanager/icons/clock_history_frame.png',
	),
	'iao_customer' => array
	(
		'tables'	=> array('tl_member','tl_iso_address'),
		'callback'	=> 'Iao\Modules\Be\ModuleCustomerMember',
		'icon'		=> 'bundles/srhinowprojectmanager/icons/users.png',
	),

	'iao_setup' => array
	(
		'callback'	=> 'ModuleIAOSetup',
		'tables'	=> array(),
		'icon'		=> 'bundles/srhinowprojectmanager/process.png',
	)
);

if ('BE' === TL_MODE) {
    $GLOBALS['TL_CSS'][] = PMB_PUBLIC_FOLDER.'/be.css|static';
}

$GLOBALS['TL_MODELS']['tl_iao_agreements'] = \Srhinow\IaoAgreementsModel::class;
$GLOBALS['TL_MODELS']['tl_iao_credit_items'] = \Srhinow\IaoCreditItemsModel::class;
$GLOBALS['TL_MODELS']['tl_iao_credit'] = \Srhinow\IaoCreditModel::class;
$GLOBALS['TL_MODELS']['tl_iao_invoice_items'] = \Srhinow\IaoInvoiceItemsModel::class;
$GLOBALS['TL_MODELS']['tl_iao_invoice'] = \Srhinow\IaoInvoiceModel::class;
$GLOBALS['TL_MODELS']['tl_iao_offer_items'] = \Srhinow\IaoOfferItemsModel::class;
$GLOBALS['TL_MODELS']['tl_iao_offer'] = \Srhinow\IaoOfferModel::class;
$GLOBALS['TL_MODELS']['tl_iao_projects'] = \Srhinow\IaoProjectsModel::class;
$GLOBALS['TL_MODELS']['tl_iao_reminder'] = \Srhinow\IaoReminderModel::class;
$GLOBALS['TL_MODELS']['tl_iao_settings'] = \Srhinow\IaoSettingsModel::class;
$GLOBALS['TL_MODELS']['tl_iao_template_items'] = \Srhinow\IaoTemplatesItemsModel::class;
$GLOBALS['TL_MODELS']['tl_iao_templates'] = \Srhinow\IaoTemplatesModel::class;

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
			'icon'						=> PMB_PUBLIC_FOLDER.'/icons/construction.png',
		),
		'iao_tax_rates' => array
		(
			'tables'					=> array('tl_iao_tax_rates'),
			'icon'						=> PMB_PUBLIC_FOLDER.'/icons/calculator.png',
		),
		'iao_item_units' => array
		(
			'tables'					=> array('tl_iao_item_units'),
			'icon'						=> PMB_PUBLIC_FOLDER.'/icons/category.png',
		),
	),
	'templates' => array
	(
		'iao_templates' => array
		(
			'tables' => array('tl_iao_templates'),
			'icon'   => PMB_PUBLIC_FOLDER.'/icons/text_templates_16.png'
		),
		'iao_templates_items' => array
		(
			'tables' => array('tl_iao_templates_items'),
			'icon'   => PMB_PUBLIC_FOLDER.'/icons/templates_items_16.png'
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
	'fe_iao_offer' => 'Iao\Modules\Fe\ModuleMemberOffers',
	'fe_iao_invoice' => 'Iao\Modules\Fe\ModuleMemberInvoices',
	'fe_iao_credit' => 'Iao\Modules\Fe\ModuleMemberCredits',
	'fe_iao_reminder' => 'Iao\Modules\Fe\ModuleMemberReminder',
	'fe_iao_agreement' => 'Iao\Modules\Fe\ModuleMemberAgreements',
	'fe_iao_public_project_list' => 'Iao\Modules\Fe\ModulePublicProjectList',
	'fe_iao_public_project_details' => 'Iao\Modules\Fe\ModulePublicProjectDetails'
);

/**
 * HOOKS
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('Iao\Hooks\iaoHooks', 'iaoReplaceInsertTags');

/**
 * Cron jobs
 */
$GLOBALS['TL_CRON']['daily'][] = array('Iao\Cron\iaoCrons', 'sendAgreementRemindEmail');

/**
 * Permissions are access settings for user and groups (fields in tl_user and tl_user_group)
 */
$GLOBALS['TL_PERMISSIONS'][] = 'iaomodules';
$GLOBALS['TL_PERMISSIONS'][] = 'iaomodulep';
$GLOBALS['TL_PERMISSIONS'][] = 'iaosettings';
$GLOBALS['TL_PERMISSIONS'][] = 'iaosettingp';

