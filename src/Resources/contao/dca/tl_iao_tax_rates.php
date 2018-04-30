<?php
namespace Iao\Dca;

use Iao\Backend\IaoBackend;

/**
 * @copyright  Sven Rhinow 2011-2018
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

/**
 * Table tl_iao_tax_rates
 */
$GLOBALS['TL_DCA']['tl_iao_tax_rates'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'onload_callback'		=> array
		(
			array('Iao\Dca\TaxRates', 'checkPermission'),
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),
	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('sorting'),
			'flag'					  => 1,
			'panelLayout'             => 'filter;search,limit'
		),
		'label' => array
		(
			'fields'                  => array('name', 'default_value'),
            'label_callback'          => array('Iao\Dca\TaxRates', 'listEntries'),
		),
		'global_operations' => array
		(
			'back' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['backBT'],
				'href'                => 'mod=&table=',
				'class'               => 'header_back',
				'attributes'          => 'onclick="Backend.getScrollOffset();"',
			),
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"',
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_tax_rates']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_tax_rates']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
				// 'button_callback'     => array('tl_iso_config', 'copyConfig'),
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_tax_rates']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
				// 'button_callback'     => array('tl_iso_config', 'deleteConfig'),
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_tax_rates']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif',
			)
		)
	),
	// Palettes
	'palettes' => array
	(
		'default' => 'name,value,sorting,default_value'
	),

	// Subpalettes
	'subpalettes' => array
	(

	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'sorting' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_tax_rates']['sorting'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>3, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_tax_rates']['name'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(125) NOT NULL default ''"
		),
		'value' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_tax_rates']['value'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true,'rgxp'=>'digit','tl_class'=>'w50'),
			'sql'                     => "float unsigned NOT NULL default '0.00'"
		),
        'default_value' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_tax_rates']['default_value'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'			          => array('tl_class'=>'clr'),
            'sql'					  => "char(1) NOT NULL default ''"
        ),
	)
);

/**
 * Class TaxRates
 * @package Iao\Dca
 */
class TaxRates extends IaoBackend
{
    /**
     * TaxRates constructor.
     */
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Check permissions to edit table tl_iao_tax_rates
	 */
	public function checkPermission()
	{
		$this->checkIaoSettingsPermission('tl_iao_tax_rates');
	}

    /**
     * List a particular record
     * @param array
     * @return string
     */
    public function listEntries($arrRow)
    {
        $return = $arrRow['name'];
        if($arrRow['default_value']) $return .= ' <span style="color:#b3b3b3; padding-left:3px;">[Standart]</span>';

        return $return;
    }
}
