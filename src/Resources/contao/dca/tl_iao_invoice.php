<?php
/**
 * @copyright  Sven Rhinow 2011-2019
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

/**
 * Table tl_iao_invoice
 */
$GLOBALS['TL_DCA']['tl_iao_invoice'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_iao_projects',
		'ctable'                      => array('tl_iao_invoice_items'),
		'doNotCopyRecords'			  => true,
		'switchToEdit'                => true,
		'enableVersioning'            => false,
		'onload_callback' => array
		(
			array('srhinow.projectmanager.listener.dca.invoice', 'generateInvoicePDF'),
			array('srhinow.projectmanager.listener.dca.invoice', 'checkPermission'),
			array('srhinow.projectmanager.listener.dca.invoice','upgradeInvoices')
		),
		'oncreate_callback' => array
		(
			array('srhinow.projectmanager.listener.dca.invoice', 'preFillFields'),
			array('srhinow.projectmanager.listener.dca.invoice', 'setMemberfieldsFromProject'),
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
			'fields'                  => array('invoice_tstamp'),
			'flag'                    => 8,
			'panelLayout'             => 'filter;search,limit'
		),
		'label' => array
		(
			'fields'                  => array('title','invoice_id_str'),
			'format'                  => '%s (%s)',
			'label_callback'          => array('srhinow.projectmanager.listener.dca.invoice', 'listEntries'),
		),
		'global_operations' => array
		(
			'importInvoices' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['importInvoices'],
				'href'                => 'key=importInvoices',
				'class'               => 'global_import',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'exportInvoices' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['exportInvoices'],
				'href'                => 'key=exportInvoices',
				'class'               => 'global_export',
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
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['edit'],
				'href'                => 'table=tl_iao_invoice_items',
				'icon'                => 'edit.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif',
//				'button_callback'     => array('srhinow.projectmanager.listener.dca.invoice', 'editHeader'),
				// 'attributes'          => 'class="edit-header"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['toggle'],
				'icon'                => 'ok.gif',
				'button_callback'     => array('srhinow.projectmanager.listener.dca.invoice', 'toggleIcon')
			),
			'pdf' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_iao_invoice']['pdf'],
				'href'                => 'key=pdf',
				'icon'                => 'iconPDF.gif',
				'button_callback'     => array('srhinow.projectmanager.listener.dca.invoice', 'showPDFButton')
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('discount'),
		'default'                     => '{settings_legend},setting_id,pid;{title_legend},title;{invoice_id_legend:hide},invoice_id,invoice_id_str,invoice_tstamp,agreement_id,invoice_pdf_file,execute_date,expiry_date;{address_legend},member,text_generate,address_text;{text_before_legend},before_template,before_text,beforetext_as_template;{text_after_legend},after_template,after_text,aftertext_as_template;{status_legend},published;{paid_legend},priceall_brutto,status,paid_on_dates,remaining;{extend_legend},noVat,discount;{notice_legend:hide},notice',
	),

	// Subpalettes
	'subpalettes' => array
	(
             'discount' => ('discount_title,discount_value,discount_operator')
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['pid'],
			'foreignKey'              => 'tl_iao_projects.title',
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>false, 'chosen'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'reminder_id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),	
		'sorting' => array
		(
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),		
		'setting_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['setting_id'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.invoice', 'getSettingOptions'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>false, 'chosen'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['title'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255,'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'invoice_tstamp' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['invoice_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true,'rgxp'=>'datim', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'load_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'generateInvoiceTstamp')
			),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'execute_date' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['execute_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true,'rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'load_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'generateExecuteDate')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'expiry_date' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['expiry_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true,'rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'load_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'generateExpiryDate')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'paid_on_date' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['paid_on_date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true,'rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
			'sql'					  => "varchar(255) NOT NULL default ''"
		),
		'invoice_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['invoice_id'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'setFieldInvoiceNumber')
			),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
		'invoice_id_str' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['invoice_id_str'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'setFieldInvoiceNumberStr')
			),
			'sql'					  => "varchar(255) NOT NULL default ''"
		),
		'invoice_pdf_file' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['invoice_pdf_file'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr','extensions'=>'pdf','files'=>true, 'filesOnly'=>true, 'mandatory'=>false),
			'sql'					  => "varchar(255) NOT NULL default ''"
		),
		'member' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['member'],
			'filter'                  => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.invoice', 'getMemberOptions'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true, 'chosen'=>true),
			'sql'					  => "varbinary(128) NOT NULL default ''"
		),
        'text_generate' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['text_generate'],
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'default'                 => '',
            'eval'                    => array('tl_class'=>'clr','submitOnChange'=>true),
            'save_callback' => array
            (
                array('srhinow.projectmanager.listener.dca.invoice', 'fillAddressText')
            ),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
		'address_text' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['address_text'],
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE','style'=>'height:60px;', 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'					  => "mediumtext NULL"
		),
		'before_template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['before_template'],
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
            'default'                 => '',
			'options_callback'        => array('srhinow.projectmanager.listener.dca.invoice', 'getBeforeTemplate'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true,'submitOnChange'=>true, 'chosen'=>true),
			'save_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'fillBeforeText')
			),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
		'before_text' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['before_text'],
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'helpwizard'=>true,'style'=>'height:60px;', 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'					  => "text NULL"
		),
		'beforetext_as_template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['beforetext_as_template'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
            'default'                 => '',
			'eval'                    => array('doNotCopy'=>true),
			'sql'					  => "char(1) NOT NULL default ''",
			'save_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'saveBeforeTextAsTemplate')
			),
		),
		'after_template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['after_template'],
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'select',
            'default'                 => 0,
			'options_callback'        => array('srhinow.projectmanager.listener.dca.invoice', 'getAfterTemplate'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true,'submitOnChange'=>true, 'chosen'=>true),
			'save_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'fillAfterText')
			),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
		'after_text' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['after_text'],
			'search'                  => true,
			'inputType'               => 'textarea',
            'default'                 => '',
			'eval'                    => array('rte'=>'tinyMCE', 'helpwizard'=>true,'style'=>'height:60px;', 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'					  => "text NULL"
		),
		'aftertext_as_template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['aftertext_as_template'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
            'default'                 => '',
			'eval'                    => array('doNotCopy'=>true),
			'sql'					  => "char(1) NOT NULL default ''",
			'save_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'saveAfterTextAsTemplate')
			),

		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['published'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
            'default'                 => '',
			'eval'                    => array('doNotCopy'=>true),
			'sql'					  => "char(1) NOT NULL default ''"
		),
		'status' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_iao_invoice']['status'],
			'exclude'               => true,
			'filter'                => true,
			'flag'                  => 1,
			'inputType'             => 'select',
            'default'                 => '',
			'options'				=>  &$GLOBALS['TL_LANG']['tl_iao_invoice']['status_options'],
            'eval'					=> array('doNotCopy'=>true),
			'save_callback' => array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'updateStatus')
			),
			'sql'					=> "char(1) NOT NULL default ''"
		),
		'paid_on_dates' =>  array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['paid_on_dates'],
			'exclude'                 => true,
			'inputType'               => 'multiColumnWizard',
			'eval' => array(
				// 'style'                 => 'width:100%;',
				'doNotCopy'=>true,
				'columnFields' => array
				(
					'paydate' => array
					(
						'label'             => $GLOBALS['TL_LANG']['tl_iao_invoice']['paydate'],
						'exclude'           => true,
						'inputType'         => 'text',
						'default'			=> '',
						'eval'              => array('rgxp'=>'datim', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'wizard','style' => 'width:65%;'),
					),
					'payamount' => array
					(
						'label'             => $GLOBALS['TL_LANG']['tl_iao_invoice']['payamount'],
						'exclude'           => true,
						'search'            => true,
						'inputType'         => 'text',
						'eval'              => array('style' => 'width:80%'),
					),
					'paynotice' => array
					(
						'label'             => $GLOBALS['TL_LANG']['tl_iao_invoice']['paynotice'],
						'exclude'           => true,
						'search'            => true,
						'inputType'         => 'text',
						// 'eval'              => array('style' => 'width:60%;'),
					)
				)
			),
			'save_callback'			=> array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'updateRemaining')
			),
			'sql'				=> "blob NULL"
		),
		'remaining' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['remaining'],
			'filter'				=> true,
			'inputType'               => 'text',
            'default'                 => 0,
			'eval'                    => array('readonly'=>true,'style'=>'border:0'),
			'load_callback'			=> array
			(
				array('srhinow.projectmanager.listener.dca.invoice', 'priceFormat')
			),
			'sql'					=> "varchar(64) NOT NULL default '0'"
		),
		'priceall_brutto' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['price_brutto'],
			'inputType'               => 'text',
			'eval'                    => array('readonly'=>true,'style'=>'border:0'),
			'load_callback'			=> array
			(
				array('srhinow.projectmanager.listener.dca.invoice','getPriceallValue'),
				array('srhinow.projectmanager.listener.dca.invoice', 'priceFormat')
			)
		),
		'noVat' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['noVat'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
            'default'                 => '',
			'eval'                    => array('doNotCopy'=>true),
			'sql'					  => "char(1) NOT NULL default ''"
		),
		'discount' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['discount'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
            'default'                 => '',
			'eval'                    => array('doNotCopy'=>true,'submitOnChange'=>true),
			'sql'					  => "char(1) NOT NULL default ''"
		),
		'discount_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['discount_title'],
			'search'                  => true,
			'inputType'               => 'text',
            'default'                 => '',
			'eval'                    => array('maxlength'=>255,'tl_class'=>'w50'),
			'sql'					  => "varchar(64) NOT NULL default 'Skonto'"
		),
		'discount_value' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['discount_value'],
			'search'                  => true,
			'inputType'               => 'text',
            'default'                 => '',
			'eval'                    => array('maxlength'=>255,'tl_class'=>'w50'),
			'sql'					  => "varchar(64) NOT NULL default '3'"
		),
		'discount_operator' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['discount_operator'],
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'select',
            'default'                 => '',
			'options'                 => &$GLOBALS['TL_LANG']['tl_iao_invoice']['discount_operators'],
            'eval'			  		  => array('tl_class'=>'w50'),
            'sql'					  => "char(1) NOT NULL default '%'"
		),
		'notice' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['notice'],
			'search'		  => true,
			'filter'                  => false,
			'inputType'               => 'textarea',
            'default'                 => '',
			'eval'                    => array('mandatory'=>false, 'cols'=>'10','rows'=>'10','style'=>'height:100px','rte'=>false),
			'sql'					  => "text NULL"

		),
		'agreement_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['agreement_id'],
			'exclude'                 => false,
			'filter'                  => true,
			'search'                  => true,
			'sorting'                 => false,
			'flag'                    => 11,
			'inputType'               => 'select',
            'default'                 => 0,
			'options_callback'        => array('srhinow.projectmanager.listener.dca.invoice', 'getAgreements'),
			'eval'                    => array('tl_class'=>'w50','includeBlankOption'=>true, 'chosen'=>true),
			'sql'					  => "int(10) unsigned NOT NULL default '0'"
		),
		'price_netto' => array
		(
			'sql' 					=> "varchar(64) NOT NULL default '0'"
		),
		'price_brutto' => array
		(
			'sql' 					=> "varchar(64) NOT NULL default '0'"
		),
		'pdf_import_dir' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_offer']['pdf_import_dir'],
            'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'files'=>false, 'filesOnly'=>false, 'class'=>'mandatory'),
			'sql'					  => "binary(16) NULL"
		),
        'csv_export_dir' => array
        (
            'label'                 => &$GLOBALS['TL_LANG']['tl_iao_invoice']['csv_export_dir'],
            'inputType'               => 'fileTree',
            'eval'                  => array('mandatory'=>true, 'required'=>true, 'fieldType'=>'radio'),
            'sql'					  => "binary(16) NULL"
        ),
		'csv_source' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['csv_source'],
            'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'csv', 'class'=>'mandatory'),
			'sql'					  => "binary(16) NULL"
		),
		'csv_posten_source' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_iao_invoice']['csv_posten_source'],
            'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'csv', 'class'=>'mandatory'),
			'sql'					  => "binary(16) NULL"
		),
		// -- Backport C2 SQL-Import
		'sendEmail' => array(
				'sql' 					=> "varchar(64) NOT NULL default '0'"
		),
		'FromEmail' => array(
				'sql' 					=> "varchar(64) NOT NULL default '0'"
		),
		'ToEmail' => array(
				'sql' 					=> "varchar(64) NOT NULL default '0'"
		),
		'alias' => array(
				'sql' 					=> "varchar(64) NOT NULL default '0'"
		)
	)
);

