<?php
namespace Iao\Backend\ImportFrom;

/**
 * @copyright  Sven Rhinow 2011-2018
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 */

use Contao\Backend;
use Contao\Files;
use Contao\Database;
use Contao\Input;

/**
 * Class InvoiceAndOffer
 * @package Iao\Backend\ImportFrom
 */
class InvoiceAndOffer
{

    /**
     * Object instance (Singleton)
     * @var InvoiceAndOffer
     */
    protected static $objInstance;


    /**
     * Prevent direct instantiation (Singleton)
     */
    protected function __construct() {}


    /**
     * Prevent cloning of the object (Singleton)
     */
    final public function __clone() {}


    /**
     * @return InvoiceAndOffer|static
     */
    public static function getInstance()
    {
        if (self::$objInstance === null)
        {
            self::$objInstance = new static();
        }

        return self::$objInstance;
    }

    /**
     * Extract the Entry files and write the data to the database
     * @param $Files
     */
	public function extractInvoiceFiles($Files)
	{
		$csv = null;
		$seperators = $GLOBALS['IAO']['csv_seperators'];
        $Database = Database::getInstance();

		// Lock the tables
		$arrLocks = array('tl_iao_invoice' => 'WRITE','tl_iao_invoice_items' => 'WRITE');
        $Database->lockTables($arrLocks);

		//get DB-Fields as arrays
		$invoice_fields = $Database->listFields('tl_iao_invoice');
		$invoice_items_fields = $Database->listFields('tl_iao_invoice_items');

		/**
		*import Invoice-File
		*/
		$handle = Files::getInstance()->fopen($Files['invoice'],'r');
		$counter = 0;
		$csvhead = $headfields = $InvoiceSet = array();

		while (($data = fgetcsv ($handle, 1000, $seperators[Input::post('separator')])) !== FALSE )
		{
			$counter ++;
			if($counter == 1 && Input::post('drop_first_row')==1)
			{
				$csvhead = $data;
				continue;
			}

			foreach($csvhead AS $headk => $headv) $headfields[$headv]=$headk;

			$lineA  = array();
			foreach($invoice_fields as  $i_field)
			{
				//exclude index Fields
				if($i_field['type']=='index') continue;
				$actkey = $headfields[$i_field['name']];
				$lineA[$i_field['name']] =  $data[$actkey]?:$i_field['default'];
			}
			$InvoiceSet = $lineA;
			
			//PDF-Datei pruefen
			$pdf_dir = Input::post('pdf_import_dir');
			$pdf_file_name = $InvoiceSet['invoice_id_str'].'.pdf';
            $InvoiceSet['invoice_pdf_file'] = (is_dir(TL_ROOT . '/' .$pdf_dir) && is_file(TL_ROOT . '/' .$pdf_dir.'/'.$pdf_file_name)) ? $pdf_file_path = $pdf_dir.'/'.$pdf_file_name : '';


			// Update the datatbase
			if(Input::post('drop_exist_entries')==1)  $Database->prepare('DELETE FROM `tl_iao_invoice` WHERE `id`=?')->execute($InvoiceSet['id']);
			$Database->prepare("INSERT INTO `tl_iao_invoice` %s")->set($InvoiceSet)->execute();
		}

		// import Invoice-Item-File
		$handle = Files::getInstance()->fopen($Files['invoice_items'],'r');
		$counter = 0;
		$csvhead = $headfields = array();
		$InvoiceItemSet = '';

		while (($data = fgetcsv ($handle, 2000, $seperators[Input::post('separator')])) !== FALSE )
		{
			$counter ++;
			if($counter == 1 && Input::post('drop_first_row')==1)
			{
				$csvhead = $data;
				continue;
			}
			foreach($csvhead AS $headk => $headv) $headfields[$headv]=$headk;

			$lineA  = array();

			foreach($invoice_items_fields as  $ii_field)
			{
				//exclude index Fields
				if($ii_field['type']=='index') continue;
				$actkey = $headfields[$ii_field['name']];

                $lineA[$ii_field['name']] =  $data[$actkey]?:$ii_field['default'];
			}

			$InvoiceItemSet = $lineA;
			if($InvoiceItemSet['id']== 5.2) {print_r($InvoiceItemSet); die();}

			// Update the datatbase
			if(Input::post('drop_exist_entries')==1)  $Database->prepare('DELETE FROM `tl_iao_invoice_items` WHERE `id`=?')->execute($InvoiceItemSet['id']);
			$Database->prepare("INSERT INTO `tl_iao_invoice_items` %s")->set($InvoiceItemSet)->execute();
		}

		// Unlock the tables
		$Database->unlockTables();

		// Notify the user
		$FilesStr = implode(', ',$Files);
		$_SESSION['TL_ERROR'] = '';
        \Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_iao_invoice']['Invoice_imported'],$FilesStr));
        setcookie('BE_PAGE_OFFSET', 0, 0, '/');

        // Redirect
		Backend::redirect(str_replace('&key=importInvoices', '', \Environment::get('request')));
	}

	/**
	 * Extract the Entry files and write the data to the database
	 * @param array
	 * @param instance
	 */
	public static function extractOfferFiles($Files)
	{
		$csv = null;
        $seperators = $GLOBALS['IAO']['csv_seperators'];
        $Database = Database::getInstance();

		// Lock the tables
		$arrLocks = array('tl_iao_offer' => 'WRITE','tl_iao_offer_items' => 'WRITE');
		$Database->lockTables($arrLocks);

		//get DB-Fields as arrays
		$offer_fields = $Database->listFields('tl_iao_offer');
		$offer_items_fields = $Database->listFields('tl_iao_offer_items');

		/**
		*import Offer-File
		*/
		$handle = Files::getInstance()->fopen($Files['offer'],'r');
		$counter = 0;
		$csvhead = $headfields = $OfferSet = array();

		while (($data = fgetcsv ($handle, 1000, $seperators[Input::post('separator')])) !== FALSE )
		{
			$counter ++;
			if($counter == 1 && Input::post('drop_first_row')==1)
			{
				$csvhead = $data;
				continue;
			}

			foreach($csvhead AS $headk => $headv) $headfields[$headv]=$headk;

			$lineA  = array();
			foreach($offer_fields as  $i_field)
			{
				//exclude index Fields
				if($i_field['type']=='index') continue;
				$actkey = $headfields[$i_field['name']];
                $lineA[$i_field['name']] =  $data[$actkey]?:$i_field['default'];
			}
			$OfferSet = $lineA;

			//PDF-Datei pruefen
			$pdf_dir = Input::post('pdf_import_dir');
			$pdf_file_name = $OfferSet['offer_id_str'].'.pdf';
			$OfferSet['offer_pdf_file'] = (is_dir(TL_ROOT . '/' .$pdf_dir) && is_file(TL_ROOT . '/' .$pdf_dir.'/'.$pdf_file_name)) ? $pdf_file_path = $pdf_dir.'/'.$pdf_file_name : '';

			// Update the datatbase
			if(Input::post('drop_exist_entries')==1)  $Database->prepare('DELETE FROM `tl_iao_offer` WHERE `id`=?')->execute($OfferSet['id']);
			$Database->prepare("INSERT INTO `tl_iao_offer` %s")->set($OfferSet)->execute();
		}

		/**
		*import Offer-Posten-File
		*/
		$handle = Files::getInstance()->fopen($Files['offer_items'],'r');
		$counter = 0;
		$csvhead = $headfields = [];
		$OfferItemSet = '';

		while (($data = fgetcsv ($handle, 1000, $seperators[Input::post('separator')])) !== FALSE )
		{
			$counter ++;
			if($counter == 1 && Input::post('drop_first_row')==1)
			{
			    $csvhead = $data;
			    continue;
			}

			foreach($csvhead AS $headk => $headv) $headfields[$headv]=$headk;

			$lineA  = array();
			foreach($offer_items_fields as  $ii_field)
			{
				//exclude index Fields
				if($ii_field['type']=='index') continue;
				$actkey = $headfields[$ii_field['name']];
                $lineA[$ii_field['name']] =  $data[$actkey]?:$ii_field['default'];
			}
			$OfferItemSet = $lineA;

			// Update the datatbase
			if(Input::post('drop_exist_entries')==1)  $Database->prepare('DELETE FROM `tl_iao_offer_items` WHERE `id`=?')->execute($OfferItemSet['id']);
			$Database->prepare("INSERT INTO `tl_iao_offer_items` %s")->set($OfferItemSet)->execute();

		}
		// Unlock the tables
		$Database->unlockTables();

		// Notify the user
		$FilesStr = implode(', ',$Files);
		$_SESSION['TL_ERROR'] = '';
        \Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_iao_offer']['Offer_imported'],$FilesStr));

		// Redirect
		setcookie('BE_PAGE_OFFSET', 0, 0, '/');
		Backend::redirect(str_replace('&key=importOffer', '', \Environment::get('request')));
	}

	public function fillAdressText($varValue)
	{
		if(strlen($varValue)<=0) return $varValue;

		$objMember = Database::getInstance()->prepare('SELECT * FROM `tl_member` WHERE `id`=?')
					    ->limit(1)
					    ->execute($varValue);

		$text = '<p>'.$objMember->company.'<br />'.($objMember->gender!='' ? $GLOBALS['TL_LANG']['tl_iao_offer']['gender'][$objMember->gender].' ':'').($objMember->title ? $objMember->title.' ':'').$objMember->firstname.' '.$objMember->lastname.'<br />'.$objMember->street.'</p>';
		$text .='<p>'.$objMember->postal.' '.$objMember->city.'</p>';

		return $text;
	}
}
