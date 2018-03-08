<?php
namespace Iao\Dca;

/**
 * @copyright  Sven Rhinow 2011-2018
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

use Contao\DataContainer;
use Iao\Backend\IaoBackend;

$this->loadLanguageFile('tl_iao_invoice');
$this->loadLanguageFile('tl_iao_credit');
$this->loadLanguageFile('tl_iao_offer');
$this->loadLanguageFile('tl_iao_reminder');
$this->loadLanguageFile('tl_iao_agreement');

/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['fe_iao_invoice']    = '{title_legend},type,name,headline,fe_iao_numberOfItems,perPage,invoice_status;{template_legend},fe_iao_template;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['fe_iao_offer'] = '{title_legend},type,name,headline,fe_iao_numberOfItems,perPage,offer_status;{template_legend},fe_iao_template;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['fe_iao_credit']  = '{title_legend},type,name,headline,fe_iao_numberOfItems,perPage,credit_status;{template_legend},fe_iao_template;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['fe_iao_reminder']  = '{title_legend},type,name,headline,fe_iao_numberOfItems,perPage,reminder_status;{template_legend},fe_iao_template;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['fe_iao_agreement']  = '{title_legend},type,name,headline,fe_iao_numberOfItems,perPage,agreement_status;{template_legend},fe_iao_template;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['fe_iao_projects']  = '{title_legend},type,name,headline,fe_iao_numberOfItems,perPage;{template_legend},fe_iao_template;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['fe_iao_public_project_list']  = '{title_legend},name,type;headline,fe_iao_numberOfItems,perPage;{config_legend},jumpTo;{template_legend},fe_iao_template;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['fe_iao_public_project_details']  = '{title_legend},type,name,headline;{template_legend},fe_iao_template;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['fe_iao_template'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['fe_iao_template'],
	'default'                 => 'bbk_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('Iao\Dca\Module', 'getIaoTemplates'),
	'eval'                    => array('tl_class'=>'w50'),
	'sql'					  => "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['fe_iao_numberOfItems'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['fe_iao_numberOfItems'],
	'default'                 => 3,
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50'),
	'sql'					  => "smallint(5) unsigned NOT NULL default '0'"
);


$GLOBALS['TL_DCA']['tl_module']['fields']['invoice_status'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_module']['invoice_status'],
	'exclude'               => true,
	'filter'                => true,
	'flag'                  => 1,
	'inputType'             => 'select',
	'options'				=>  &$GLOBALS['TL_LANG']['tl_iao_invoice']['status_options'],
    'eval'					=> array('doNotCopy'=>true,'includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'					=> "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_status'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_module']['offer_status'],
	'exclude'               => true,
	'filter'                => true,
	'flag'                  => 1,
	'inputType'             => 'select',
	'options'				=>  &$GLOBALS['TL_LANG']['tl_iao_offer']['status_options'],
    'eval'					=> array('doNotCopy'=>true,'includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'					=> "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['credit_status'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_module']['credit_status'],
	'exclude'               => true,
	'filter'                => true,
	'flag'                  => 1,
	'inputType'             => 'select',
	'options'				=>  &$GLOBALS['TL_LANG']['tl_iao_credit']['status_options'],
    'eval'					=> array('doNotCopy'=>true,'includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'					=> "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['reminder_status'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_module']['reminder_status'],
	'exclude'               => true,
	'filter'                => true,
	'flag'                  => 1,
	'inputType'             => 'select',
	'options'				=>  &$GLOBALS['TL_LANG']['tl_iao_reminder']['status_options'],
    'eval'					=> array('doNotCopy'=>true,'includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'					=> "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['agreement_status'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_module']['agreement_status'],
	'exclude'               => true,
	'filter'                => true,
	'flag'                  => 1,
	'inputType'             => 'select',
	'options'				=>  &$GLOBALS['TL_LANG']['tl_iao_agreement']['status_options'],
    'eval'					=> array('doNotCopy'=>true,'includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'					=> "char(1) NOT NULL default ''"
);

/**
 * Class Module
 * @package Iao\Dca
 */
class Module extends IaoBackend {

    /**
     * Return all info templates as array
     *
     * @param DataContainer $dc
     * @return array
     */
	public function getIaoTemplates(DataContainer $dc)
	{
        $arrTemplates = \Controller::getTemplateGroup('iao_');

		return $arrTemplates;
	}
}

