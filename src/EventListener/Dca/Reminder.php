<?php
/**
 * Created by c4.pringitzhonig.de.
 * Developer: Sven Rhinow (sven@sr-tag.de)
 * Date: 19.09.19
 */

namespace Srhinow\ProjectManagerBundle\EventListener\Dca;


use Contao\BackendUser as User;
use Contao\Database as DB;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Iao\Backend\IaoBackend;
use Srhinow\IaoInvoiceModel;
use Srhinow\IaoReminderModel;

class Reminder extends iaoBackend
{

    protected $settings = array();

    /**
     * Reminder constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Check permissions to edit table tl_iao_reminder
     */
    public function checkPermission()
    {
        $this->checkIaoModulePermission('tl_iao_reminder');
    }

    /**
     * fill date-Field if this empty
     * @param $varValue mixed
     * @param $dc object
     * @return integer
     */
    public function  generateExecuteDate($varValue, DataContainer $dc)
    {
        $altdate = ($dc->activeRecord->invoice_tstamp) ? $dc->activeRecord->invoice_tstamp : time();
        return ($varValue==0) ? $altdate : $varValue;
    }

    /**
     * fill date-Field if this empty
     * @param $varValue mixed
     * @param $dc object
     * @return integer
     */
    public function  generateReminderTstamp($varValue, DataContainer $dc)
    {
        return ($varValue == 0) ? time() : $varValue;
    }

    /**
     * get all invoices
     * @param DataContainer $dc
     * @return array
     */
    public function getInvoices(DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);
        $varValue= array();

        if($dc->activeRecord->pid > 0) {

            $all = DB::getInstance()
                ->prepare('SELECT `i`.*, `m`.`company` FROM `tl_iao_invoice` as `i` LEFT JOIN `tl_member` as `m` ON `i`.`member` = `m`.`id` WHERE `i`.`pid`=? ORDER BY `invoice_id_str` DESC')
                ->execute($dc->activeRecord->pid);

        } else {
            $all = DB::getInstance()->prepare('SELECT `i`.*, `m`.`company` FROM `tl_iao_invoice` as `i` LEFT JOIN `tl_member` as `m` ON `i`.`member` = `m`.`id` ORDER BY `invoice_id_str` DESC')
                ->execute();
        }
        while($all->next())
        {
            $varValue[$all->id] = $all->invoice_id_str.' :: '.\StringUtil::substr($all->title,20).' ('.number_format($all->price_brutto,2,',','.').' '.$settings['currency_symbol'].')';
        }

        return $varValue;
    }

    /**
     * fill Text
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     * @throws \Exception
     */

    public function fillStepFields($varValue, DataContainer $dc)
    {
        if($varValue != 1) return $varValue;

        $this->fillReminderFields($dc->activeRecord);

        return '';
    }

    /**
     * @param DataContainer $dc
     * @return string
     * @throws \Exception
     */
    public function getTextFinish(DataContainer $dc)
    {
        $obj = IaoReminderModel::findById($dc->id);
        if(!is_object($obj)) throw new \Exception('getTextFinish() ist fehlgeschlagen.');

        $text_finish = $this->changeIAOTags($obj->text,'reminder',$obj);

        return '<div class="clr widget">
                <h3><label for="ctrl_text_finish">'.$GLOBALS['TL_LANG']['tl_iao_reminder']['text_finish'][0].'</label></h3>
                <div id="ctrl_text_finish" class="preview" style="border:1px solid #ddd; padding:15px;">'.$text_finish.'</div>
                </div>';

    }

    /**
     * @param DataContainer $dc
     * @throws \Exception
     */
    public function setTextFinish(DataContainer $dc)
    {
        $objReminder = IaoReminderModel::findById($dc->id);
        if(!is_object($objReminder)) throw new \Exception('setTextFinish() ist fehlgeschlagen.');

        $text_finish = $this->changeIAOTags($objReminder->text,'reminder',$objReminder);
        $text_finish = $this->changeTags($text_finish);

        $set = ['text_finish' => $text_finish];

        DB::getInstance()->prepare('UPDATE `tl_iao_reminder` %s WHERE `id`=?')
            ->set($set)
            ->execute($dc->id);
    }

    /**
     * fill Adress-Text
     * @param $intMember int
     * @param DataContainer $dc
     * @return mixed
     */
    public function fillAddressText($varValue, DataContainer $dc)
    {
        if($varValue == 1) {

            $intMember = Input::post(member);

            $text = $this->getAddressText($intMember);

            $set = array(
                'address_text' => $text,
                'text_generate' => ''
            );

            DB::getInstance()->prepare('UPDATE `tl_iao_reminder` %s WHERE `id`=?')
                ->set($set)
                ->limit(1)
                ->execute($dc->id);
        }
        return '';
    }
    /**
     * Return the edit header button
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function editHeader($row, $href, $label, $title, $icon, $attributes)
    {
        return (User::getInstance()->isAdmin || count(preg_grep('/^tl_iao_reminder::/', $this->User->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : '';
    }

    /**
     * wenn GET-Parameter passen dann wird eine PDF erzeugt
     * @param DataContainer $dc
     */
    public function checkPDF(DataContainer $dc)
    {
        if(\Input::get('key') == 'pdf' && (int) \Input::get('id') > 0) $this->generateReminderPDF((int) \Input::get('id'), 'reminder');
    }

    /**
     * Generate a "PDF" button and return it as string
     * @param $row
     * @param $href
     * @param $label
     * @param $title
     * @param $icon
     * @return bool|string
     */
    public function showPDF($row, $href, $label, $title, $icon)
    {
        $settings = $this->getSettings($row['setting_id']);
        $styles = '';

        // wenn kein Admin dann kein PDF-Link	
        if (!User::getInstance()->isAdmin)
        {
            return false;
        }

        // Wenn keine PDF-Vorlage dann kein PDF-Link
        $objPdfTemplate = 	\FilesModel::findByUuid($settings['iao_invoice_pdf']);

        if(strlen($objPdfTemplate->path) < 1 || !file_exists(TL_ROOT . '/' . $objPdfTemplate->path) ) return false;  // template file not found

        if (\Input::get('key') == 'pdf' && \Input::get('id') == $row['id'])
        {
            $step = $row['step'];
            $pdfFile = TL_ROOT . '/' . $settings['iao_reminder_'.$step.'_pdf'];

            if(!file_exists($pdfFile)) return false;  // template file not found

            $invoiceObj = IaoInvoiceModel::findById($row['invoice_id']);

            $reminder_Str = $GLOBALS['TL_LANG']['tl_iao_reminder']['steps'][$row['step']].'-'.$invoiceObj->invoice_id_str.'-'.$row['id'];

            //-- Calculating dimensions
            $margins = unserialize($settings['iao_pdf_margins']);         // Margins as an array
            switch( $margins['unit'] )
            {
                case 'cm':      $factor = 10.0;   break;
                default:        $factor = 1.0;
            }

            $dim['top']    = !is_numeric($margins['top'])   ? PDF_MARGIN_TOP    : $margins['top'] * $factor;
            $dim['right']  = !is_numeric($margins['right']) ? PDF_MARGIN_RIGHT  : $margins['right'] * $factor;
            $dim['bottom'] = !is_numeric($margins['top'])   ? PDF_MARGIN_BOTTOM : $margins['bottom'] * $factor;
            $dim['left']   = !is_numeric($margins['left'])  ? PDF_MARGIN_LEFT   : $margins['left'] * $factor;

            // TCPDF configuration
            $l['a_meta_dir'] = 'ltr';
            $l['a_meta_charset'] = $GLOBALS['TL_CONFIG']['characterSet'];
            $l['a_meta_language'] = $GLOBALS['TL_LANGUAGE'];
            $l['w_page'] = 'page';

            // Create new PDF document with FPDI extension
            require_once(IAO_PDFCLASS_FILE);

            $pdf = new iaoPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);
            $pdf->setSourceFile($pdfFile);          // Set PDF template

            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle($reminder_Str);
            $pdf->SetSubject($reminder_Str);
            $pdf->SetKeywords($reminder_Str);

            $pdf->SetDisplayMode('fullwidth', 'OneColumn', 'UseNone');
            $pdf->SetHeaderData();

            // Remove default header/footer
            $pdf->setPrintHeader(false);

            // Set margins
            $pdf->SetMargins($dim['left'], $dim['top'], $dim['right']);

            // Set auto page breaks
            $pdf->SetAutoPageBreak(true, $dim['bottom']);

            // Set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Set some language-dependent strings
            $pdf->setLanguageArray($l);

            // Initialize document and add a page
            $pdf->AddPage();

            // Include CSS (TCPDF 5.1.000 an newer)
            $file = \FilesModel::findByUuid($settings['iao_pdf_css']);

            if(strlen($file->path) > 0 && file_exists(TL_ROOT . '/' . $file->path) )
            {
                $styles = "<style>\n" . file_get_contents(TL_ROOT . '/' . $file->path) . "\n</style>\n";
                $pdf->writeHTML($styles, true, false, true, false, '');
            }

            // write the address-data
            $pdf->drawAddress($styles.$this->changeTags($row['address_text']));

            //Mahnungsnummer
            $pdf->drawDocumentNumber($reminder_Str);

            //Datum
            $pdf->drawDate(date($GLOBALS['TL_CONFIG']['dateFormat'],$row['tstamp']));

            //ausgeführt am
            $newdate= $row['periode_date'];
            $pdf->drawInvoiceDurationDate(date($GLOBALS['TL_CONFIG']['dateFormat'],$newdate));


            //Text
            if(strip_tags($row['text_finish']))
            {
                $pdf->drawTextBefore($row['text_finish']);
            }

            // Close and output PDF document
            $pdf->lastPage();
            $pdf->Output($reminder_Str. '.pdf', 'D');

            // Stop script execution
            exit();
        }
        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'">'.image::getHtml($icon, $label).'</a> ';

    }

    /**
     * Generate a "PDF" button and return it as string
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function showPDFButton($row, $href, $label, $title, $icon)
    {
        $settings = $this->getSettings($row['setting_id']);

        // wenn kein Admin dann kein PDF-Link
        if (!User::getInstance()->isAdmin)	return '';

        $href = 'contao/main.php?do=iao_reminder&amp;key=pdf&amp;id='.$row['id'];
        return '<a href="'.$href.'" title="'.specialchars($title).'">'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Autogenerate an article alias if it has not been set yet
     * @param $varValue
     * @param DataContainer $dc
     * @return int|mixed|null|string
     * @throws \Exception
     */
    public function generateReminderNumber($varValue, DataContainer $dc)
    {
        $autoNr = false;
        $varValue = (int) $varValue;
        $settings = $this->getSettings($dc->activeRecord->setting_id);


        // Generate invoice_id if there is none
        if($varValue == 0)
        {
            $autoNr = true;
            $objNr = DB::getInstance()->prepare("SELECT `invoice_id` FROM `tl_iao_reminder` ORDER BY `invoice_id` DESC")
                ->limit(1)
                ->execute();


            if($objNr->numRows < 1 || $objNr->invoice_id == 0)  $varValue = $settings['iao_invoice_startnumber'];
            else  $varValue =  $objNr->invoice_id +1;

        }
        else
        {
            $objNr = DB::getInstance()->prepare("SELECT `invoice_id` FROM `tl_iao_reminder` WHERE `id`=? OR `invoice_id`=?")
                ->limit(1)
                ->execute($dc->id,$varValue);

            // Check whether the InvoiceNumber exists
            if ($objNr->numRows > 1 )
            {
                if (!$autoNr)
                {
                    throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
                }

                $varValue .= '-' . $dc->id;
            }
        }
        return $varValue;
    }

    /**
     * List a particular record
     * @param array
     * @return string
     */
    public function listEntries($arrRow)
    {
        $settings = $this->getSettings($arrRow['setting_id']);

        $result = DB::getInstance()->prepare("SELECT `r`.periode_date,`i`.`invoice_id_str`, `i`.`title` `invoicetitle`, `m`.`firstname`, `m`.`lastname`, `m`.`company`
		FROM `tl_iao_reminder` `r`
		LEFT JOIN `tl_member` `m` ON  `r`.`member` = `m`.`id`
		LEFT JOIN `tl_iao_invoice` `i` ON  `r`.`invoice_id` = `i`.`id`
		WHERE `r`.`id`=?")
            ->limit(1)
            ->execute($arrRow['id']);

        $row = $result->fetchAssoc();

        return '
		<div class="comment_wrap">
		<div class="cte_type status' . $arrRow['status'] . '"><strong>' . $arrRow['title'] . '</strong> '.$row['invoice_id_str'].'</div>
		<div>Rechnungs-Title: <strong>'.$row['invoicetitle'].'</strong></div>
		<div>'.$GLOBALS['TL_LANG']['tl_iao_reminder']['sum'][0].': <strong>'.number_format($arrRow['sum'],2,',','.').' '.$settings['currency_symbol'].'</strong></div>
		<div>'.$GLOBALS['TL_LANG']['tl_iao_reminder']['member'][0].': '.$row['firstname'].' '.$row['lastname'].' ('.$row['company'].')</div>
		<div>'.$GLOBALS['TL_LANG']['tl_iao_reminder']['periode_date'][0].': '.date($GLOBALS['TL_CONFIG']['dateFormat'],$row['periode_date']).'</div>
		'.(($arrRow['notice'])?"<div>".$GLOBALS['TL_LANG']['tl_iao_reminder']['notice'][0].":".$arrRow['notice']."</div>": '').'
		</div>' . "\n    ";
    }

    /**
     * Return the "toggle visibility" button
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (strlen(\Input::get('tid')))
        {
            $this->toggleVisibility(\Input::get('tid'), (\Input::get('state')));
            $this->redirect($this->getReferer());

        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['status']==1 ? 2 : 1);

        if ($row['status']==2)
        {
            $icon = 'logout.gif';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.$GLOBALS['TL_LANG']['tl_iao_reminder']['toggle'].'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * paid/not paid
     * @param integer
     * @param boolean
     */
    public function toggleVisibility($intId, $blnVisible)
    {
        // Check permissions to edit
        \Input::setGet('id', $intId);
        \Input::setGet('act', 'toggle');
        $User = User::getInstance();
        $logger = static::getContainer()->get('monolog.logger.contao');

        // Check permissions to publish
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_reminder::status', 'alexf'))
        {
            $logger->log('Not enough permissions to publish/unpublish comment ID "'.$intId.'"', 'tl_iao_reminder toggleActivity', TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $logger->create();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iao_reminder']['fields']['status']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_iao_reminder']['fields']['status']['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }

        $visibility = $blnVisible==1 ? '1' : '2';

        // Update the database
        DB::getInstance()
            ->prepare("UPDATE tl_iao_reminder SET status=? WHERE id=?")
            ->execute($visibility, $intId);

        //get reminder-Data
        $remindObj = DB::getInstance()
            ->prepare('SELECT * FROM `tl_iao_reminder` WHERE `id`=?')
            ->limit(1)
            ->execute($intId);

        if($remindObj->numRows)
        {
            $dbObj = DB::getInstance()
                ->prepare("UPDATE `tl_iao_invoice` SET `status`=?, `notice` = `notice`+?  WHERE id=?")
                ->execute($visibility, $remindObj->notice, $remindObj->invoice_id);
        }

        $logger->create();
    }

    /**
     * ondelete_callback
     * @param DataContainer $dc
     */
    public function onDeleteReminder(DataContainer $dc)
    {
        $invoiceID = $dc->activeRecord->invoice_id;

        if($invoiceID)
        {
            $otherReminderObj = DB::getInstance()->prepare('SELECT `id` FROM `tl_iao_reminder` WHERE `invoice_id`=? AND `id`!=? ORDER BY `step` DESC')
                ->limit(1)
                ->execute($invoiceID, $dc->id);

            $newReminderID = ($otherReminderObj->numRows > 0) ? $otherReminderObj->id : 0;

            $set = array(
                'reminder_id'=>$newReminderID
            );

            DB::getInstance()
                ->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
                ->set($set)
                ->execute($invoiceID);
        }
    }

    /**
     * @param DataContainer $dc
     */
    public function changeStatusReminder(DataContainer $dc)
    {
        $state = Input::get('state');
        $reminderID = Input::get('id');
        $invoiceID = $dc->activeRecord->invoice_id;

        if($state == 2)
        {
            if($invoiceID)
            {
                $set = array(
                    'reminder_id'=>$reminderID,
                    'paid_on_date'=>time(),
                    'status'=>2
                );

                DB::getInstance()
                    ->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
                    ->set($set)
                    ->execute($invoiceID);
            }
        }
        elseif($state == 1)
        {
            if($invoiceID)
            {
                $set = array(
                    'reminder_id'=>'',
                    'paid_on_date'=>'',
                    'status'=>1
                );

                DB::getInstance()
                    ->prepare('UPDATE `tl_iao_invoice` %s  WHERE `id`=?')
                    ->set($set)
                    ->execute($invoiceID);
            }
        }
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return int
     */
    public function updateStatus($varValue, DataContainer $dc)
    {
        $varValue = (int) $varValue;

        // UPDATE invoice when reminder is market as paid
        if($varValue == 2 && $dc->activeRecord->invoice_id > 0)
        {
            $set = array
            (
                'status' => $varValue,
                'paid_on_date' => $dc->activeRecord->paid_on_date
            );

            DB::getInstance()->prepare("UPDATE `tl_iao_invoice` %s  WHERE `id`=?")
                ->set($set)
                ->limit(1)
                ->execute($dc->activeRecord->invoice_id);
        }

        return $varValue;
    }
}