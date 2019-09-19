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
use Contao\FilesModel;
use Contao\Image;
use Contao\Input;
use Iao\Backend\IaoBackend;
use Srhinow\IaoReminderModel;

class Agreement extends IaoBackend
{
    protected $settings = array();

    /**
     * Agreements constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * get all default iao-Settings
     */
    public function IAOSettings(DataContainer $dc)
    {
        $this->settings = $this->getSettings($GLOBALS['IAO']['default_settings_id']);
    }

    /**
     * Check permissions to edit table tl_iao_agreements
     */
    public function checkPermission()
    {
        $this->checkIaoModulePermission('tl_iao_agreements');
    }

    /**
     * List a particular record
     * @param array
     * @return string
     */
    public function listEntries($arrRow)
    {
        $return = '
		<div class="comment_wrap">
		<div class="cte_type agreement_status' . $arrRow['status'] . '"><strong>' . $arrRow['title'].'</strong></div>
		<div>Vertragszeit: '.date($GLOBALS['TL_CONFIG']['dateFormat'], $arrRow['beginn_date']).' - '.date($GLOBALS['TL_CONFIG']['dateFormat'], $arrRow['end_date']).'</div>';
        if($arrRow['status'] == 2) $return .= '<div>gekündigt am: '.date($GLOBALS['TL_CONFIG']['dateFormat'], $arrRow['terminated_date']).'</div>';
        if($arrRow['price'] != '') $return .= '<div>Betrag: '.$this->getPriceStr($arrRow['price_brutto']).'</div>';
        $return .= '</div>' . "\n    ";

        return $return;
    }

    /**
     * fill date-Field if this empty
     * @param mixed
     * @param object
     * @return integer
     */
    public function  generateExecuteDate($varValue, DataContainer $dc)
    {
        $altdate = ($dc->activeRecord->invoice_tstamp) ? $dc->activeRecord->invoice_tstamp : time();
        return ($varValue==0) ? $altdate : $varValue;
    }

    /**
     * fill date-Field if this empty
     * @param mixed
     * @param object
     * @return string
     */
    public function  getPeriodeValue($varValue, DataContainer $dc)
    {
        return ($varValue == '') ? '+1 year' : $varValue;
    }

    /**
     * fill Adress-Text
     * @param $intMember integer
     * @param $dc object
     * @return integer
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

            $text = $this->getAddressText($varValue);

            DB::getInstance()->prepare('UPDATE `tl_iao_agreements` %s WHERE `id`=?')
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
        $User = User::getInstance();
        return ($User->isAdmin || count(preg_grep('/^tl_iao_agreements::/', $User->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : '';
    }


    /**
     * Generate a button and return it as string
     * @param $row array
     * @param $href string
     * @param $label string
     * @param $title string
     * @param $icon string
     * @throws \Exception
     * @return string
     */
    public function addInvoice($row, $href, $label, $title, $icon)
    {
        if (!User::getInstance()->isAdmin) return '';

        if (\Input::get('key') == 'addInvoice' && \Input::get('id') == $row['id'])
        {
            //zuerst die neue range setzen damit fuer die Rechnung auch gleich der richtige Zeitraum steht
            $data['id'] = $row['id'];
            $data['beginn'] = $row['beginn_date'];
            $data['end'] = $row['end_date'];
            $data['periode'] = strlen($row['periode'] > 0)?:$GLOBALS['IAO']['default_agreement_cycle'];
            $data['today'] = time();

            $this->generateNewCycle($data);

            $beforeTemplObj = $this->getTemplateObject('tl_iao_templates',$row['before_template']);
            $afterTemplObj = $this->getTemplateObject('tl_iao_templates',$row['after_template']);
            $invoiceId = $this->generateInvoiceNumber(0,$this->settings);
            $invoiceIdStr = $this->generateInvoiceNumberStr($invoiceId, time(), $this->settings);

            //Insert Invoice-Entry
            $set = array
            (
                'pid' => $row['pid'],
                'tstamp' => time(),
                'invoice_tstamp' => time(),
                'invoice_id' => $invoiceId,
                'invoice_id_str' => $invoiceIdStr,
                'title' => $row['title'],
                'address_text' => $row['address_text'],
                'member' => $row['member'],
                'price_netto' => $row['price_netto'],
                'price_brutto' => $row['price_brutto'],
                'before_template' => $row['before_template'],
                'before_text' => $this->changeIAOTags($beforeTemplObj->text,'agreement',(object) $row),
                'after_template' => $row['after_template'],
                'after_text' => $this->changeIAOTags($afterTemplObj->text,'agreement',(object) $row),
                'agreement_id' => $row['id'],

            );

            $result = DB::getInstance()->prepare('INSERT INTO `tl_iao_invoice` %s')
                ->set($set)
                ->execute();

            $newInvoiceID = (int) $result->insertId;

            //Insert Postions for this Entry
            if($newInvoiceID > 0)
            {
                //Posten-Template holen
                $postenTemplObj = $this->getTemplateObject('tl_iao_templates_items',$row['posten_template']);

                if($postenTemplObj->numRows > 0)
                {
                    $headline = $this->changeIAOTags($postenTemplObj->headline,'agreement',(object) $row);
                    $date = $postenTemplObj->date;
                    $time = $postenTemplObj->time;
                    $text = $this->changeIAOTags($postenTemplObj->text,'agreement',(object) $row);
                } else {
                    $headline = $text = '';
                    $time = $date = 0;
                }

                //Insert Invoice-Entry
                $postenset = array
                (
                    'pid' => $newInvoiceID,
                    'tstamp' => time(),
                    'headline' => $headline,
                    'headline_to_pdf' => '1',
                    'date' => $date,
                    'time' => $time,
                    'text' => $text,
                    'count' => $row['count'],
                    'amountStr' => $row['amountStr'],
                    'price' => $row['price'],
                    'price_netto' => $row['price_netto'],
                    'price_brutto' => $row['price_brutto'],
                    'published' => '1',
                    'vat' => $row['vat'],
                    'vat_incl' => $row['vat_incl'],
                    'posten_template' => 0
                );

                $newposten = DB::getInstance()->prepare('INSERT INTO `tl_iao_invoice_items` %s')
                    ->set($postenset)
                    ->execute();

                if($newposten->insertId < 1)
                {
                    throw new \Exception('Es konnte kein Rechnungs-Element angelegt werden.');
                }

                $redirectUrl = $this->addToUrl('do=iao_invoice&mode=2&table=tl_iao_invoice&s2e=1&id='.$newInvoiceID.'&act=edit&rt='.REQUEST_TOKEN);
                $redirectUrl = str_replace('key=addInvoice&amp;','', $redirectUrl);
                $this->redirect($redirectUrl);
            }

        }

        //Button erzeugen
        $link = (\Input::get('onlyproj') == 1) ? 'do=iao_agreements&amp;id='.$row['id'].'&amp;projId='.\Input::get('id') : 'do=iao_agreements&amp;id='.$row['id'].'';
        $link = $this->addToUrl($href.'&amp;'.$link.'&rt='.REQUEST_TOKEN);
        $link = str_replace('table=tl_iao_agreements&amp;','',$link);
        return '<a href="'.$link.'" title="'.specialchars($title).'">'.\Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * save the price_netto and price_brutto from actuell item
     * @param object
     * @return string
     */
    public function saveNettoAndBrutto(DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $englprice = str_replace(',','.',$dc->activeRecord->price);

        $Netto = $nettoSum = $Brutto = $bruttoSum = 0;

        if($dc->activeRecord->vat_incl == 1)
        {
            $Netto = $englprice;
            $Brutto = $this->getBruttoPrice($englprice,$dc->activeRecord->vat);
        }
        else
        {
            $Netto = $this->getNettoPrice($englprice,$dc->activeRecord->vat);
            $Brutto = $englprice;
        }

        $nettoSum = round($Netto,2) * $dc->activeRecord->count;
        $bruttoSum = round($Brutto,2) * $dc->activeRecord->count;

        DB::getInstance()->prepare('UPDATE `tl_iao_agreements` SET `price_netto`=?, `price_brutto`=? WHERE `id`=?')
            ->limit(1)
            ->execute($nettoSum, $bruttoSum, $dc->id);
    }

    /**
     * Return the "toggle visibility" button
     * @param $row array
     * @param $href string
     * @param $label string
     * @param $title string
     * @param $icon string
     * @param $attributes string
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        $this->import('BackendUser', 'User');

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

        return '<a href="'.$this->addToUrl($href).'" title="'.$GLOBALS['TL_LANG']['tl_iao_agreements']['toggle'].'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
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

        $logger = static::getContainer()->get('monolog.logger.contao');

        // Check permissions to publish
        $User = User::getInstance();
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_agreements::status', 'alexf'))
        {
            $logger->log('Not enough permissions to publish/unpublish comment ID "'.$intId.'"', 'tl_iao_agreements toggleActivity', TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new \Versions('tl_iao_agreements', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iao_agreements']['fields']['status']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_iao_agreements']['fields']['status']['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }

        $visibility = $blnVisible==1 ? '1' : '2';

        // Update the database
        DB::getInstance()->prepare("UPDATE tl_iao_agreements SET status=? WHERE id=?")
            ->execute($visibility, $intId);

        //get reminder-Data
        $remindObj = IaoReminderModel::findById($intId);

        if(is_object($remindObj))
        {
            DB::getInstance()->prepare("UPDATE `tl_iao_invoice` SET `status`=?, `notice` = `notice`+?  WHERE id=?")
                ->execute($visibility, $remindObj->notice, $remindObj->invoice_id);
        }

        $objVersions->create();
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return int
     */
    public function getAgreementValue($varValue, DataContainer $dc)
    {
        return ($varValue == '0') ? time() : $varValue ;
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return int
     */
    public function getBeginnDateValue($varValue, DataContainer $dc)
    {
        $agreement_date = ($dc->activeRecord->agreement_date) ? $dc->activeRecord->agreement_date : time() ;
        $beginn_date = ($varValue == '') ? $agreement_date : $varValue ;
        $end_date = $this->getEndDateValue($dc->activeRecord->end_date, $dc);

        $set = array
        (
            'beginn_date' => $beginn_date,
            'end_date' => $end_date
        );

        DB::getInstance()->prepare('UPDATE `tl_iao_agreements` %s WHERE `id`=?')
            ->set($set)
            ->execute($dc->id);

        return $beginn_date;
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return false|int
     */
    public function getEndDateValue($varValue, DataContainer $dc)
    {
        if($varValue != '') return $varValue;

        $agreement_date = ($dc->activeRecord->agreement_date) ? $dc->activeRecord->agreement_date : time() ;
        $beginn_date = ($dc->activeRecord->beginn_date) ? $dc->activeRecord->beginn_date : $agreement_date;
        $periode = ($dc->activeRecord->periode) ? $dc->activeRecord->periode : $GLOBALS['IAO']['default_agreement_cycle'];

        // wenn der Wert nicht manuell verändert wurde die Periode berechnen
        return ($varValue == $dc->activeRecord->end_date) ? strtotime($periode.' -1 day', $beginn_date) : $varValue ;
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
    public function showPDF($row, $href, $label, $title, $icon)
    {
        if (!User::getInstance()->isAdmin || strlen($row['agreement_pdf_file']) < 1 ) return '';

        // Wenn keine PDF-Vorlage dann kein PDF-Link
        $objPdf = 	FilesModel::findByUuid($row['agreement_pdf_file']);
        if(strlen($objPdf->path) < 1 || !file_exists(TL_ROOT . '/' . $objPdf->path) ) return false;  // template file not found

        $pdfFile = TL_ROOT . '/' . $objPdf->path;

        if (\Input::get('key') == 'pdf' && \Input::get('id') == $row['id'])
        {

            if(!empty($row['agreement_pdf_file']) && file_exists($pdfFile))
            {
                header("Content-type: application/pdf");
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                header('Content-Length: '.filesize($pdfFile));
                header('Content-Disposition: inline; filename="'.basename($pdfFile).'";');
                ob_clean();
                flush();
                readfile($pdfFile);
                exit();
            }
        }
        $href = $this->addToUrl($href.'&amp;id='.$row['id']);
        $href = str_replace('&amp;onlyproj=1','',$href);
        $href = str_replace('do=iao_projects&amp;','do=iao_agreements&amp;',$href);
        $href = str_replace('table=tl_iao_agreements&amp;','',$href);
        $button = (!empty($row['agreement_pdf_file']) && file_exists($pdfFile)) ? '<a href="'.$href.'" title="'.specialchars($title).'">'.Image::getHtml($icon, $label).'</a> ' : '';
        return $button;
    }

    public function generateNewCycle($data) {

        if($data['periode'] == '') $data['periode'] = $GLOBALS['IAO']['default_agreement_cycle'];

        if($data['end'] && $data['beginn'])
        {
            $new_beginn = strtotime($data['periode'], $data['beginn']);
            $set = array
            (
                'beginn_date' => $new_beginn,
                'end_date' => strtotime($data['periode'].' -1 day', $new_beginn),
                'new_generate' => ''
            );

            DB::getInstance()->prepare('UPDATE `tl_iao_agreements` %s WHERE `id`=?')
                ->set($set)
                ->execute($data['id']);
        }
    }
    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     */
    public function setNewCycle($varValue, DataContainer $dc)
    {
        if($varValue == 1)
        {
            $data['id'] = $dc->id;
            $data['beginn'] = $dc->activeRecord->beginn_date;
            $data['end'] = $dc->activeRecord->end_date;
            $data['periode'] = $dc->activeRecord->periode;
            $data['today'] = time();

            $this->generateNewCycle($data);
        }
        return '';
    }

    /**
     * @param \DataContainer $dc
     * @return array
     */
    public function getPostenTemplate(\DataContainer $dc)
    {
        $varValue= array();

        $all = DB::getInstance()->prepare('SELECT `id`,`headline` FROM `tl_iao_templates_items` WHERE `position`=?')
            ->execute('invoice');

        while($all->next())
        {
            $varValue[$all->id] = $all->headline;
        }
        return $varValue;
    }

    /**
     * get all invoice before template
     * @param object
     * @return array
     */
    public function getBeforeTemplate(DataContainer $dc)
    {
        $varValue= array();

        $all = DB::getInstance()->prepare('SELECT `id`,`title` FROM `tl_iao_templates` WHERE `position`=?')
            ->execute('invoice_before_text');

        while($all->next())
        {
            $varValue[$all->id] = $all->title;
        }

        return $varValue;
    }

    /**
     * get all invoice after template
     * @param object
     * @return array
     */
    public function getAfterTemplate(DataContainer $dc)
    {
        $varValue= array();

        $all = DB::getInstance()->prepare('SELECT `id`,`title` FROM `tl_iao_templates` WHERE `position`=?')
            ->execute('invoice_after_text');

        while($all->next())
        {
            $varValue[$all->id] = $all->title;
        }

        return $varValue;
    }

}