<?php
namespace iao\ExportImport;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Widget;
use Contao\Database as DB;
use Contao\Files;
use Contao\FileTree;
use Contao\Input;

/**
 *
 * @copyright  Sven Rhinow 2011-2014
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */


/**
 * Class iao_invoice
 * Provide methods to handle project-manager-bundle-module.
 */
class Invoice extends Backend
{
	/**
	 * Export Invoices
	 */
	public function exportInvoices()
	{
        $formId = 'tl_iao_export';
        $seperators = $GLOBALS['IAO']['csv_seperators'];

		if (Input::post('FORM_SUBMIT') == $formId)
		{
		    $ObjCsvExportFolder = \FilesModel::findByUuid(\StringUtil::uuidToBin(Input::post('csv_export_dir', true)));

			// Check the file names
			if (!$ObjCsvExportFolder === null || strlen($ObjCsvExportFolder->path) < 1)
			{
                \Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
				$this->reload();
			}

            $csv_export_dir = $ObjCsvExportFolder->path;

			// Skip invalid entries
			if (!is_dir(TL_ROOT . '/' . $csv_export_dir))
			{
                \Message::addError($GLOBALS['TL_LANG']['ERR']['importFolder'], $csv_export_dir);
				$this->reload();
			}

			// check if the directory writeable
			if (!is_writable(TL_ROOT . '/' . $csv_export_dir))
			{
//				$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['ERR']['PermissionDenied'],TL_ROOT . '/' . $csv_export_dir);
                \Message::addError($GLOBALS['TL_LANG']['ERR']['PermissionDenied'],TL_ROOT . '/' . $csv_export_dir);
				$this->reload();
			}

			// get DB-Fields as arrays
            $DB = DB::getInstance();
			$invoice_fields = $DB->listFields('tl_iao_invoice');
			$invoice_items_fields = $DB->listFields('tl_iao_invoice_items');

			$invoice_export_csv = Input::post('export_invoice_filename').'.csv';
			$invoice_items_export_csv = Input::post('export_invoice_item_filename').'.csv';

			// work on tl_iao_invoice
			$dbObj = $DB->prepare('SELECT * FROM `tl_iao_invoice`')->execute();

			$isOneLine = true;
			$oneLine = [];
			$linesArr = [];

			while($dbObj->next())
			{
				$lineA  = [];

				foreach($invoice_fields as $i_field)
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
			$fp = $File->fopen($csv_export_dir.'/'.$invoice_export_csv,'w');


			foreach ($linesArr as $line)
			{
				fputcsv($fp,  $line, $seperators[Input::post('separator')]);
			}

			$File->fclose($fp);

			// work on tl_iao_invoice_items
			$dbObj = $DB->prepare('SELECT * FROM `tl_iao_invoice_items`')->execute();

			$isOneLine = true;
			$oneLine = [];
			$linesArr = [];

			while($dbObj->next())
			{
				$lineA  = [];

				foreach($invoice_items_fields as $i_field)
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
			$fp = $File->fopen($csv_export_dir.'/'.$invoice_items_export_csv,'w');

			foreach ($linesArr as $line)
			{
				fputcsv($fp,  $line, $seperators[Input::post('separator')]);
			}

			$File->fclose($fp);

			//after ready export
			$_SESSION['TL_ERROR'] = '';
            \Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_iao_invoice']['Invoice_exported']));
			setcookie('BE_PAGE_OFFSET', 0, 0, '/');
			$this->redirect(str_replace('&key=exportInvoices', '', $this->Environment->request));
		}

		$Template = new BackendTemplate('be_iao_export_csv');
		$Template->headline = $GLOBALS['TL_LANG']['tl_iao_invoice']['exportInvoices'][1];
		$Template->message = \Message::generate();
		$Template->csv_seperators = $GLOBALS['IAO']['csv_seperators'];
		$Template->lang_array = $GLOBALS['TL_LANG']['tl_iao_invoice'];
        $Template->objTree4Export = new FileTree(FileTree::getAttributesFromDca($GLOBALS['TL_DCA']['tl_iao_invoice']['fields']['csv_export_dir'], 'csv_export_dir', null, 'csv_export_dir', 'tl_iao_invoice'));
        $Template->formId = $formId;

		// Return the form
		return $Template->parse();
	}

	/**
	 * Import invoices
	 */
	public function importInvoices()
	{
		$formId = 'tl_iao_import';
        $seperators = $GLOBALS['IAO']['csv_seperators'];

	    if (Input::post('FORM_SUBMIT') == $formId)
		{
            $ObjCsvInvoiceFile = \FilesModel::findByUuid(\StringUtil::uuidToBin(Input::post('csv_source', true)));

            // Check the file names
            if (!$ObjCsvInvoiceFile === null || strlen($ObjCsvInvoiceFile->path) < 1)
            {
                \Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
                $this->reload();
            }
            $csv_source = $ObjCsvInvoiceFile->path;

            $ObjCsvInvoiceItemFile = \FilesModel::findByUuid(\StringUtil::uuidToBin(Input::post('csv_posten_source', true)));
            // Check the file names
            if (!$ObjCsvInvoiceItemFile === null || strlen($ObjCsvInvoiceItemFile->path) < 1)
            {
                \Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
                $this->reload();
            }
            $csv_posten_source = $ObjCsvInvoiceItemFile->path;


			// Skip invalid invoice-entries
			if (is_dir(TL_ROOT . '/' . $csv_source))
			{
                \Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['importFolder'], basename($csv_source)));
                $this->reload();
			}

			// Skip invalid posten-entries
			if (is_dir(TL_ROOT . '/' . $csv_posten_source))
			{
                \Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['importFolder'], basename($csv_source)));
                $this->reload();
			}

			// Skip anything but .cto files
			if ($ObjCsvInvoiceFile->extension != 'csv')
			{
                \Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $ObjCsvInvoiceFile->extension));
                $this->reload();
			}

			// Skip anything but .cto files
			if ($ObjCsvInvoiceItemFile->extension != 'csv')
			{
                \Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $ObjCsvInvoiceItemFile->extension));
                $this->reload();
			}

			$csv_files = array
			(
				'invoice'=>$csv_source,
				'invoice_items'=>$csv_posten_source
			);

			//TODO 'Als library-Klassen anlegen'
			// get right libraries
			$lib = $this->Input->post('import_lib');
			$import_path =  __DIR__.'/../libs_import/import_'.$lib.'.php';

			if(is_file($import_path))
			{
				include_once($import_path);
				$ClassName = 'import_'.$lib;
				$importlib = new $ClassName();
				return $importlib->extractInvoiceFiles($csv_files, $this);
			}
			else
			{
				$_SESSION['TL_ERROR'][] = sprintf('lib %s gibt es nicht', $import_path);
				$this->reload();
			}

		}

        $Template = new BackendTemplate('be_iao_import_csv');
        $Template->headline = $GLOBALS['TL_LANG']['tl_iao_invoice']['importInvoices'][1];
        $Template->message = \Message::generate();
        $Template->csv_seperators = $GLOBALS['IAO']['csv_seperators'];
        $Template->lang_array = $GLOBALS['TL_LANG']['tl_iao_invoice'];
        $Template->objTree4PDF = new FileTree(FileTree::getAttributesFromDca($GLOBALS['TL_DCA']['tl_iao_invoice']['fields']['pdf_import_dir'], 'pdf_import_dir', null, 'pdf_import_dir', 'tl_iao_invoice'));
        $Template->objTree4Source = new FileTree(FileTree::getAttributesFromDca($GLOBALS['TL_DCA']['tl_iao_invoice']['fields']['csv_source'], 'csv_source', null, 'csv_source', 'tl_iao_invoice'));
        $Template->objTree4Posten = new FileTree(FileTree::getAttributesFromDca($GLOBALS['TL_DCA']['tl_iao_invoice']['fields']['csv_posten_source'], 'csv_posten_source', null, 'csv_posten_source', 'tl_iao_invoice'));
        $Template->formId = $formId;

        // Return the form
        return $Template->parse();
	}

}