<?php
/**
 * @copyright  Sven Rhinow 2011-2019
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

/**
 * Table tl_iao_agreements
 */
$GLOBALS['TL_DCA']['tl_iao_agreements'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_iao_projects',
		'switchToEdit'                => true,
		'enableVersioning'            => false,
		'onload_callback' => array
		(
			array('srhinow.projectmanager.listener.dca.agreement','IAOSettings'),
			array('srhinow.projectmanager.listener.dca.agreement', 'checkPermission'),
		),
		'onsubmit_callback'	    => array
		(
		    array('srhinow.projectmanager.listener.dca.agreement','saveNettoAndBrutto')
		),
        'oncreate_callback' => array
        (
            array('srhinow.projectmanager.listener.dca.agreement', 'setMemberfieldsFromProject'),
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
			'mode'                    => 2,
			'fields'                  => array('end_date'),
			'flag'                    => 8,
			'panelLayout'             => 'filter;search,limit'
		),
		'label' => array
		(
			'fields'                  => array('title','beginn_date','end_date','price_brutto'),
			'format'                  => '%s (aktuelle Laufzeit: %s - %s)',
			'label_callback'          => array('srhinow.projectmanager.listener.dca.agreement', 'listEntries'),
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
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_agreements']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_agreements']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'invoice' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_agreements']['invoice'],
				'href'                => 'key=addInvoice',
				'icon'                => 'bundles/srhinowprojectmanager/icons/kontact_todo.png',
				'button_callback'     => array('srhinow.projectmanager.listener.dca.agreement', 'addInvoice')
			),
			'pdf' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_agreements']['pdf'],
				'href'                => 'key=pdf',
				'icon'                => 'iconPDF.gif',
				'button_callback'     => array('srhinow.projectmanager.listener.dca.agreement', 'showPDF')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_agreements']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('sendEmail'),
		'default'                     => '{settings_legend},setting_id,pid;
										  {title_legend},title;
										  {agreement_legend:hide},agreement_pdf_file;
										  {address_legend},member,text_generate,address_text;
										  {other_legend},price,vat,vat_incl,count,amountStr;
										  {status_legend},agreement_date,periode,beginn_date,end_date,status,terminated_date,new_generate;
										  {email_legend},sendEmail;
										  {invoice_generate_legend},before_template,after_template,posten_template;
										  {notice_legend:hide},notice'
	),
	// Subpalettes
	'subpalettes' => array
	(
             'sendEmail' => ('remind_before,email_from,email_to,email_subject,email_text')
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['pid'],
			'foreignKey'              => 'tl_iao_projects.title',
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>false, 'chosen'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default '1'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'setting_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['setting_id'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.agreement', 'getSettingOptions'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>false, 'chosen'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255,'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'agreement_date' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['agreement_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'load_callback'			=> array (
				array('srhinow.projectmanager.listener.dca.agreement','getAgreementValue')
			),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'periode' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['periode'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255,'tl_class'=>'w50'),
			'load_callback'			=> array (
				array('srhinow.projectmanager.listener.dca.agreement','getPeriodeValue')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'beginn_date' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['beginn_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'load_callback'				=> array
			(
				array('srhinow.projectmanager.listener.dca.agreement','getBeginnDateValue')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'end_date' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['end_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'load_callback'				=> array
			(
				array('srhinow.projectmanager.listener.dca.agreement','getEndDateValue')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'new_generate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['new_generate'],
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr'),
			'save_callback'				=> array
			(
				array('srhinow.projectmanager.listener.dca.agreement','setNewCycle')
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'terminated_date' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['terminated_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'price' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['price'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'clr'),
			'sql'					  => "varchar(64) NOT NULL default '0'"
		),
		'price_netto' => array
		(
			'sql'					  => "varchar(64) NOT NULL default '0'"
		),
		'price_brutto' => array
		(
			'sql'					  => "varchar(64) NOT NULL default '0'"
		),
		'vat' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['vat'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.agreement', 'getTaxRatesOptions'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'					  => "int(10) unsigned NOT NULL default '19'"
		),
		'vat_incl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['vat_incl'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options'                 => &$GLOBALS['TL_LANG']['tl_iao_agreements']['vat_incl_percents'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'					  => "int(10) unsigned NOT NULL default '1'"
		),
		'count' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['count'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'default'				  => '1',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'					  => "varchar(64) NOT NULL default '0'"
		),
		'amountStr' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['amountStr'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.agreement', 'getItemUnitsOptions'),
            'eval'                    => array('tl_class'=>'w50','submitOnChange'=>false),
			'sql'					  => "varchar(64) NOT NULL default ''"
		),
		'member' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['member'],
			'exclude'                 => true,
			'filter'                  => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.agreement', 'getMemberOptions'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true, 'chosen'=>true),
			'sql'                     => "varbinary(128) NOT NULL default ''"
		),
        'text_generate' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['text_generate'],
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class'=>'clr','submitOnChange'=>true),
            'save_callback' => array
            (
                array('srhinow.projectmanager.listener.dca.agreement', 'fillAddressText')
            ),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
		'address_text' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['address_text'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE','style'=>'height:60px;', 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'                     => "mediumtext NULL"
		),
		'status' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_iao_agreements']['status'],
			'exclude'               => true,
			'filter'                => true,
			'flag'                  => 1,
			'inputType'             => 'select',
			'options'               => &$GLOBALS['TL_LANG']['tl_iao_agreements']['status_options'],
            'eval'			  		=> array('tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		'agreement_pdf_file' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['agreement_pdf_file'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'mandatory'=>false,'extensions'=>'pdf'),
			'sql'                     => "binary(16) NULL"
		),
		'sendEmail' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['sendEmail'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true,'submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'remind_before' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['remind_before'],
			'exclude'                 => true,
			'default'                 => '-3 weeks',
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255,'tl_class'=>'clr'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'email_from' => array
	    (
		    'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['email_from'],
		    'exclude'                 => true,
		    'inputType'               => 'text',
		    'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'maxlength'=>32, 'decodeEntities'=>true, 'tl_class'=>'clr w50'),
		    'sql'                     => "varchar(32) NOT NULL default ''"
	    ),
	    'email_to' => array
	    (
		    'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['email_to'],
		    'exclude'                 => true,
		    'flag'                    => 11,
		    'inputType'               => 'text',
		    'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
		    'sql'                     => "varchar(32) NOT NULL default ''"
	    ),
	    'email_subject' => array
	    (
		    'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['email_subject'],
		    'exclude'                 => true,
		    'flag'                    => 11,
		    'inputType'               => 'text',
		    // 'default'		  		=> &$GLOBALS['TL_LANG']['tl_iao_agreements']['email_subject_default'],
		    'eval'                    => array('mandatory'=>false, 'maxlength'=>255, 'tl_class'=>'clr long'),
		    'sql'                     => "varchar(255) NOT NULL default ''"
	    ),
	    'email_text' => array
	    (
		    'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['email_text'],
		    'exclude'                 => true,
		    'inputType'               => 'textarea',
		    'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true),
		    'sql'                     => "text NULL"
	    ),
		'before_template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['before_template'],
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.agreement', 'getBeforeTemplate'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true,'submitOnChange'=>false, 'chosen'=>true),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
		'after_template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['after_template'],
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.agreement', 'getAfterTemplate'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true, 'submitOnChange'=>false, 'chosen'=>true),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
   		'posten_template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['posten_template'],
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.agreement', 'getPostenTemplate'),
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption'=>true, 'submitOnChange'=>false, 'chosen'=>true),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
		'notice' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_agreements']['notice'],
			'exclude'                 => true,
			'search'		  		=> true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>false, 'cols'=>'10','rows'=>'10','style'=>'height:100px','rte'=>false),
			'sql'                     => "text NULL"
		),
	)
);

