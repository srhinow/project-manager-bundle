<?php
/**
 * @copyright  Sven Rhinow 2011-2019
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

/**
 * Table tl_iao_projects
 */
$GLOBALS['TL_DCA']['tl_iao_projects'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_iao_offer','tl_iao_invoice','tl_iao_credit'),
		'switchToEdit'                => false,
		'enableVersioning'            => true,

		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('title'),
			'flag'                    => 1,
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s',
			// 'label_callback'          => array('tl_iao_projects', 'listEntries'),
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		'operations' => array
		(
			'offer' => array
			(
				'label'  => &$GLOBALS['TL_LANG']['tl_iao_projects']['offer'],
				'href'   => 'table=tl_iao_offer&onlyproj=1',
                'icon'   => 'bundles/srhinowprojectmanager/icons/16-file-page.png',
			),
			'invoice' => array
			(
				'label'  => &$GLOBALS['TL_LANG']['tl_iao_projects']['invoice'],
				'href'   => 'table=tl_iao_invoice&onlyproj=1',
                'icon'   => 'bundles/srhinowprojectmanager/icons/kontact_todo.png',
			),
			'credit' => array
			(
				'label'  => &$GLOBALS['TL_LANG']['tl_iao_projects']['credit'],
				'href'   => 'table=tl_iao_credit&onlyproj=1',
                'icon'   => 'bundles/srhinowprojectmanager/icons/16-tag-pencil.png',
			),
			'reminder' => array
			(
				'label'  => &$GLOBALS['TL_LANG']['tl_iao_projects']['reminder'],
				'href'   => 'table=tl_iao_reminder&onlyproj=1',
                'icon'   => 'bundles/srhinowprojectmanager/icons/warning.png',

			),
			'agreements' => array
			(
				'label'  => &$GLOBALS['TL_LANG']['tl_iao_projects']['agreements'],
				'href'   => 'table=tl_iao_agreements&onlyproj=1',
                'icon'   => 'bundles/srhinowprojectmanager/icons/clock_history_frame.png',
			),
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_projects']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_projects']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_projects']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_projects']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			),
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('in_reference','finished'),
		'default'                     => '{settings_legend},setting_id;{project_legend},title,member,url;notice;{finshed_legend},finished;{reference_legend},in_reference'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'in_reference' => 'reference_title,reference_alias,reference_short_title,reference_subtitle,reference_customer,reference_todo,reference_desription,tags,singleSRC,multiSRC,orderSRC',
		'finished' => 'finished_date',
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
			'sql'					  => "int(10) unsigned NOT NULL default '0'",
			'sorting'                 => true,
		),
		'setting_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['setting_id'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.project', 'getSettingOptions'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>false, 'chosen'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'member' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['member'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.project', 'getMemberOptions'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true,'submitOnChange'=>true, 'chosen'=>true),
			'sql'					  => "varbinary(128) NOT NULL default ''"
		),
		'in_reference' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['in_reference'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true,'submitOnChange'=>true),
			'sql'					  => "char(1) NOT NULL default ''"
		),
		'reference_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['reference_title'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
        'reference_alias' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['reference_alias'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
            'save_callback' => array
            (
                array('srhinow.projectmanager.listener.dca.project', 'generateReferenceAlias')
            ),
            'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
        ),

        'reference_short_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['reference_short_title'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255, 'tl_class'=>'clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'reference_subtitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['reference_subtitle'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'reference_customer' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['reference_customer'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE','style'=>'height:60px;', 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'					  => "mediumtext NULL"
		),
		'reference_todo' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['reference_todo'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE','style'=>'height:60px;', 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'					  => "mediumtext NULL"
		),
		'reference_desription' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['reference_desription'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE','style'=>'height:60px;', 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'					  => "mediumtext NULL"
		),
		'tags' => array(
			'label'     => &$GLOBALS['TL_LANG']['MSC']['tags'],
    		'inputType' => 'tag',
    		'sql'					  => "mediumtext NULL"
  		),

		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['url'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255,'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'finished' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['finished'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true,'submitOnChange'=>true),
			'sql'					  => "char(1) NOT NULL default ''"
		),
		'finished_date' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['finished_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'notice' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['notice'],
			'exclude'                 => true,
			'search'		  => true,
			'filter'                  => false,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>false, 'cols'=>'10','rows'=>'10','style'=>'height:100px','rte'=>false,'tl_css'=>'clr m12 long'),
			'sql'					  => "text NULL"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'mandatory'=>false, 'tl_class'=>'clr','extensions'=>Config::get('validImageTypes')),
			'sql'                     => "binary(16) NULL"
		),
		'multiSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['multiSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'orderSRC', 'files'=>true, 'mandatory'=>false,'extensions'=>Config::get('validImageTypes')),
			'sql'                     => "blob NULL",
		),
		'orderSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_projects']['orderSRC'],
			'sql'                     => "blob NULL"
		),
	)
);
