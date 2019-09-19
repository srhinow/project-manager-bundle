<?php
/**
 * @copyright  Sven Rhinow 2011-2019
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

/**
 * Table tl_iao_reminder
 */
$GLOBALS['TL_DCA']['tl_iao_reminder'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'			=> 'Table',
		'ptable'				=> 'tl_iao_projects',
		'doNotCopyRecords'		=> true,
		'switchToEdit'			=> true,
		'enableVersioning'		=> false,
		'onload_callback'		=> array
		(
			array('srhinow.projectmanager.listener.dca.reminder', 'checkPDF'),
			array('srhinow.projectmanager.listener.dca.reminder', 'checkPermission'),
		),
		'onsubmit_callback'	=> array(
        	array('srhinow.projectmanager.listener.dca.reminder','setTextFinish')
		),
		'ondelete_callback'	=> array
		(
			array('srhinow.projectmanager.listener.dca.reminder', 'onDeleteReminder')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('reminder_tstamp'),
			'flag'                    => 8,
			'panelLayout'             => 'filter;search,limit'
		),
		'label' => array
		(
			'fields'                  => array('title','invoice_id'),
			'format'                  => '%s (%s)',
			'label_callback'          => array('srhinow.projectmanager.listener.dca.reminder', 'listEntries'),
		),
		'global_operations' => array
		(
			'checkReminder' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_reminder']['checkReminder'],
				'href'                => 'key=checkReminder',
				'class'               => 'check_reminder',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
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
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_reminder']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_reminder']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_reminder']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_reminder']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_reminder']['toggle'],
				'icon'                => 'ok.gif',
				#'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this, %s);"',
				'button_callback'     => array('srhinow.projectmanager.listener.dca.reminder', 'toggleIcon')
			),
			'pdf' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_reminder']['pdf'],
                'href'                => 'key=pdf',
                'icon'                => 'iconPDF.gif',
				'button_callback'     => array('srhinow.projectmanager.listener.dca.reminder', 'showPDFButton')
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array(),
		'default'                     => '{settings_legend},setting_id,pid;{invoice_legend},invoice_id,step,set_step_values,title,reminder_tstamp,periode_date,tax,postage,unpaid,sum;{address_legend},member,text_generate,address_text;{reminder_legend},text,text_finish;{status_legend},published,status,paid_on_date;{notice_legend:hide},notice'
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
		'pid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['pid'],
			'foreignKey'              => 'tl_iao_projects.title',
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>false, 'chosen'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),	
		'setting_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['setting_id'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.reminder', 'getSettingOptions'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>false, 'chosen'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255,'tl_class'=>'clr'),
			'sql'					  => "varchar(255) NOT NULL default ''"
		),
		'text' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['text'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'helpwizard'=>true,'style'=>'height:60px;', 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'					  => "mediumtext NULL"
		),
		'text_finish' => array
		(
			'label'						=> &$GLOBALS['TL_LANG']['tl_iao_reminder']['text_finish'],
			'exclude'					=> true,
			'eval'						=> array('tl_class'=>'clr'),
			'input_field_callback'		=> array('srhinow.projectmanager.listener.dca.reminder','getTextFinish'),
			'sql'					  => "mediumtext NULL"
		),
		'reminder_tstamp' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['reminder_tstamp'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true,'rgxp'=>'datim', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'clr w50 wizard'),
			'load_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.reminder', 'generateReminderTstamp')
			),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'periode_date' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['periode_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => strtotime('+14 days'),
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
		'paid_on_date' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['paid_on_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'sql'					  => "varchar(255) NOT NULL default ''"
		),
		'invoice_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['invoice_id'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.reminder', 'getInvoices'),
            'eval'			          => array('tl_class'=>'w50','includeBlankOption'=>true, 'chosen'=>true),
			'save_callback' => array
			(
//				array('srhinow.projectmanager.listener.dca.reminder', 'fillFields')
			),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
        'step' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['step'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 1,
            'inputType'               => 'select',
            'options'                 => &$GLOBALS['TL_LANG']['tl_iao_reminder']['steps'],
            'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true),
            'sql'					  => "varchar(255) NOT NULL default ''"
        ),
        'set_step_values' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['set_step_values'],
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class'=>'w50','submitOnChange'=>true),
            'save_callback' => array
            (
                array('srhinow.projectmanager.listener.dca.reminder', 'fillStepFields')
            ),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
		'unpaid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['unpaid'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50','rgxp'=>'digit', 'nospace'=>true),
			'sql'					  => "varchar(64) NOT NULL default '0'"
		),
		'tax' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['tax'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>2, 'tl_class'=>'w50'),
			'sql'					  => "varchar(2) NOT NULL default '0'"
		),
		'tax_typ' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['tax_typ'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options'                 => array('1'=>'Soll (Zins von Brutto)','2'=>'Ist (Zins von Netto)'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'					  => "varchar(25) NOT NULL default ''"
		),
		'sum' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['sum'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50','rgxp'=>'digit', 'nospace'=>true),
			'sql'					  => "varchar(64) NOT NULL default '0'"
		),
		'postage' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['postage'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>25, 'tl_class'=>'w50'),
			'sql'					  => "varchar(25) NOT NULL default '0'"
		),
        'member' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['member'],
            'filter'                  => true,
            'search'                  => true,
            'sorting'                 => true,
            'flag'                    => 11,
            'inputType'               => 'select',
            'options_callback'        => array('srhinow.projectmanager.listener.dca.reminder', 'getMemberOptions'),
            'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true, 'chosen'=>true),
            'sql'					  => "varbinary(128) NOT NULL default ''"
        ),
        'text_generate' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['text_generate'],
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class'=>'clr','submitOnChange'=>true),
            'save_callback' => array
            (
                array('srhinow.projectmanager.listener.dca.reminder', 'fillAddressText')
            ),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'address_text' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['address_text'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte'=>'tinyMCE','style'=>'height:60px;', 'tl_class'=>'clr'),
            'explanation'             => 'insertTags',
            'sql'					  => "mediumtext NULL"
        ),
        'published' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['published'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'                    => array('doNotCopy'=>true),
            'sql'					  => "char(1) NOT NULL default ''"
        ),
        'status' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['status'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 1,
            'inputType'               => 'select',
            'options'                 => &$GLOBALS['TL_LANG']['tl_iao_reminder']['status_options'],
            'eval'			  => array('tl_class'=>'w50'),
            'save_callback' => array
            (
                array('srhinow.projectmanager.listener.dca.reminder', 'updateStatus')
            ),
            'sql'					  => "char(1) NOT NULL default ''"
        ),
		'notice' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_reminder']['notice'],
			'exclude'                 => true,
			'search'		  => true,
			'filter'                  => false,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>false, 'cols'=>'10','rows'=>'10','style'=>'height:100px','rte'=>false),
			'sql'					  => "mediumtext NULL"
		),
	)
);
