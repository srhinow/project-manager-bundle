<?php
namespace Iao\Backend\Offer;

/**
 * @copyright  Sven Rhinow 2011-2019
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Database as DB;
use Contao\Files;
use Contao\FilesModel;
use Contao\FileTree;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Iao\Backend\ImportFrom\InvoiceAndOffer;
use Srhinow\IaoOfferItemsModel;
use Srhinow\IaoOfferModel;

/**
 * Class ImportExport
 * @package Iao\Backend\Invoice
 */
class ImportExport extends Backend
{

	/**
	 * Export offer
	 */
	public function exportOffer()
	{
        $formId = 'tl_iao_export';
        $seperators = $GLOBALS['IAO']['csv_seperators'];

		if (Input::post('FORM_SUBMIT') == $formId)
		{
            $ObjCsvExportFolder = \FilesModel::findByUuid(StringUtil::uuidToBin(Input::post('csv_export_dir', true)));

            // Check the file names
            if (!$ObjCsvExportFolder === null || strlen($ObjCsvExportFolder->path) < 1)
            {
                Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
                $this->reload();
            }

            $csv_export_dir = $ObjCsvExportFolder->path;

			// Skip invalid entries
			if (!is_dir(TL_ROOT . '/' . $csv_export_dir))
			{
                Message::addError($GLOBALS['TL_LANG']['ERR']['importFolder'], $csv_export_dir);
                $this->reload();
			}

			// check if the directory writeable
			if (!is_writable(TL_ROOT . '/' . $csv_export_dir))
			{
                Message::addError($GLOBALS['TL_LANG']['ERR']['PermissionDenied'],TL_ROOT . '/' . $csv_export_dir);
                $this->reload();
			}

			// get DB-Fields as arrays
            $DB = DB::getInstance();
			$offer_fields = $DB->listFields('tl_iao_offer');
			$offer_items_fields = $DB->listFields('tl_iao_offer_items');

			$offer_export_csv = Input::post('export_filename').'.csv';
			$offer_items_export_csv = Input::post('export_item_filename').'.csv';

			// work on tl_iao_offer
            $dbObj = IaoOfferModel::findAll();

			$isOneLine = true;
            $oneLine = [];
            $linesArr = [];

			if(null !== $dbObj) while($dbObj->next())
			{
                $lineA  = [];

				foreach($offer_fields as $i_field)
				{
					//exclude index Fields
					if($i_field['type']=='index') continue;

                    if($isOneLine)  $oneLine[] = $i_field['name'];
                    $lineA[] = $dbObj->{$i_field['name']};
				}

				if($isOneLine) $linesArr[] = $oneLine;
				$linesArr[] = $lineA;
				$isOneLine = false;
			}

			//set handle from file
            $File = Files::getInstance();
			$fp = $File->fopen($csv_export_dir.'/'.$offer_export_csv,'w');


			foreach ($linesArr as $line)
			{
				fputcsv($fp,  $line, $seperators[Input::post('separator')]);
			}

            $File->fclose($fp);
            $dbObj = null;

			// work on tl_iao_offer_items
            $dbObj = IaoOfferItemsModel::findAll();

			$isOneLine = true;
			$oneLine = array();
			$linesArr = array();

			while($dbObj->next())
			{
				$lineA  = array();

				foreach($offer_items_fields as $i_field)
				{
					//exclude index Fields
					if($i_field['type']=='index') continue;

					if($isOneLine)  $oneLine[] = $i_field['name'];
                    $lineA[] = $dbObj->{$i_field['name']};
				}

				if($isOneLine) $linesArr[] = $oneLine;
				$linesArr[] = $lineA;
				$isOneLine = false;
			}

			//set handle from file
            $File = Files::getInstance();
			$fp = $File->fopen($csv_export_dir.'/'.$offer_items_export_csv,'w');

			foreach ($linesArr as $line)
			{
				fputcsv($fp,$line,$seperators[Input::post('separator')]);
			}

			$File->fclose($fp);

			//after ready export
			$_SESSION['TL_ERROR'] = '';
            Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_iao_offer']['Offer_exported']));
            setcookie('BE_PAGE_OFFSET', 0, 0, '/');
			$this->redirect(str_replace('&key=exportOffer', '', \Environment::get('request')));
		}

        $Template = new BackendTemplate('be_iao_export_csv');
        $Template->headline = $GLOBALS['TL_LANG']['tl_iao_offer']['exportOffer'][1];
        $Template->backlink = ampersand(str_replace('&key=exportOffer', '', $this->Environment->request));
        $Template->message = Message::generate();
        $Template->csv_seperators = $GLOBALS['IAO']['csv_seperators'];
        $Template->lang_array = $GLOBALS['TL_LANG']['tl_iao_offer'];
        $Template->default_name = 'tl_iao_offer_'.date('Y-m-d');
        $Template->default_item_name = 'tl_iao_offer_items_'.date('Y-m-d');
        $Template->objTree4Export = new FileTree(FileTree::getAttributesFromDca($GLOBALS['TL_DCA']['tl_iao_offer']['fields']['csv_export_dir'], 'csv_export_dir', null, 'csv_export_dir', 'tl_iao_offer'));
        $Template->formId = $formId;

        // Return the form
        return $Template->parse();
	}

	/**
	 * Import offer
	 */
	public function importOffer()
	{
        $formId = 'tl_iao_import';
        $seperators = $GLOBALS['IAO']['csv_seperators'];

		if (Input::post('FORM_SUBMIT') == $formId)
		{
            $ObjCsvOfferFile = FilesModel::findByUuid(StringUtil::uuidToBin(Input::post('csv_source', true)));

            // Check the file names
            if (!$ObjCsvOfferFile === null || strlen($ObjCsvOfferFile->path) < 1)
            {
                Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
                $this->reload();
            }
            $csv_source = $ObjCsvOfferFile->path;

            $ObjCsvOfferItemFile = FilesModel::findByUuid(StringUtil::uuidToBin(Input::post('csv_posten_source', true)));

            // Check the file names
            if (!$ObjCsvOfferItemFile === null || strlen($ObjCsvOfferItemFile->path) < 1)
            {
                Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
                $this->reload();
            }
            $csv_posten_source = $ObjCsvOfferItemFile->path;

			// Skip invalid offer-entries
			if (is_dir(TL_ROOT . '/' . $csv_source))
			{
                Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['importFolder'], basename($csv_source)));
                $this->reload();
			}

			// Skip invalid posten-entries
			if (is_dir(TL_ROOT . '/' . $csv_posten_source))
			{
                Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['importFolder'], basename($csv_source)));
                $this->reload();
			}


			// Skip anything but .csv files
			if ($ObjCsvOfferFile->extension != 'csv')
			{
                Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $ObjCsvOfferFile->extension));
				$this->reload();
			}

			// Skip anything but .csv files
			if ($ObjCsvOfferItemFile->extension != 'csv')
			{
                Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $ObjCsvOfferItemFile->extension));
				$this->reload();
			}

            $csv_files = array
            (
				'offer'=>$csv_source,
				'offer_items'=>$csv_posten_source
			);


            InvoiceAndOffer::getInstance()->extractOfferFiles($csv_files);
		}

        $Template = new BackendTemplate('be_iao_import_csv');
        $Template->headline = $GLOBALS['TL_LANG']['tl_iao_offer']['importInvoices'][1];
        $Template->backlink = ampersand(str_replace('&key=importOffer', '', $this->Environment->request));
        $Template->message = Message::generate();
        $Template->csv_seperators = $GLOBALS['IAO']['csv_seperators'];
        $Template->lang_array = $GLOBALS['TL_LANG']['tl_iao_offer'];
        $Template->objTree4PDF = new FileTree(FileTree::getAttributesFromDca($GLOBALS['TL_DCA']['tl_iao_offer']['fields']['pdf_import_dir'], 'pdf_import_dir', null, 'pdf_import_dir', 'tl_iao_offer'));
        $Template->objTree4Source = new FileTree(FileTree::getAttributesFromDca($GLOBALS['TL_DCA']['tl_iao_offer']['fields']['csv_source'], 'csv_source', null, 'csv_source', 'tl_iao_offer'));
        $Template->objTree4Posten = new FileTree(FileTree::getAttributesFromDca($GLOBALS['TL_DCA']['tl_iao_offer']['fields']['csv_posten_source'], 'csv_posten_source', null, 'csv_posten_source', 'tl_iao_offer'));
        $Template->formId = $formId;

        // Return the form
        return $Template->parse();
	}

}
