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
use Contao\StringUtil;
use Iao\Backend\IaoBackend;
use Srhinow\IaoAgreementsModel as AgreementsModel;
use Srhinow\IaoInvoiceModel as InvoiceModel;
use Srhinow\IaoProjectsModel;
use Srhinow\IaoTemplatesModel as TemplModel;

class Invoice extends IaoBackend
{
    protected $settings = array();

    /**
     * Invoice constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Check permissions to edit table tl_iao_invoice
     */
    public function checkPermission()
    {
        $this->checkIaoModulePermission('tl_iao_invoice');
    }

    /**
     * prefill eny Fields by new dataset
     * @param $table string
     * @param $id int
     * @param $set array
     * @param $obj object
     */
    public function preFillFields($table, $id, $set, $obj)
    {
        $objProject = IaoProjectsModel::findById($set['pid']);
        $settingId = ($objProject !== null && $objProject->setting_id != 0) ? $objProject->setting_id : 1;
        $settings = $this->getSettings($settingId);
        $invoiceId = $this->generateInvoiceNumber(0, $settings);
        $invoiceIdStr = $this->generateInvoiceNumberStr($invoiceId, time(), $settings);
        $set = array
        (
            'invoice_id' => $invoiceId,
            'invoice_id_str' => $invoiceIdStr
        );

        DB::getInstance()->prepare('UPDATE '.$table.' %s WHERE `id`=?')
            ->set($set)
            ->limit(1)
            ->execute($id);
    }

    /**
     * Generiert das "erstellt am" - Feld
     * @param $varValue integer
     * @param DataContainer $dc
     * @return int
     */
    public function  generateExecuteDate($varValue, DataContainer $dc)
    {
        $altdate = ($dc->activeRecord->invoice_tstamp) ? $dc->activeRecord->invoice_tstamp : time();
        return ($varValue==0) ? $altdate : $varValue;
    }

    /**
     * Falls leer wird das "zahlbar bis" - Feld generiert und befüllt
     * @param $varValue int
     * @param DataContainer $dc
     * @return int
     */
    public function  generateExpiryDate($varValue, DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);

        if(!$varValue)
        {
            // Laufzeit in Tagen
            $dur = (int) ($settings['iao_invoice_duration']) ? $settings['iao_invoice_duration'] : 14;
            $invoiceTstamp = ($dc->activeRecord->invoice_tstamp) ? $dc->activeRecord->invoice_tstamp : time();

            //auf Sonabend prüfen wenn ja dann auf Montag setzen
            if(date('N',$invoiceTstamp+($dur * 24 * 60 * 60)) == 6)  $dur = $dur+2;

            //auf Sontag prüfen wenn ja dann auf Montag setzen
            if(date('N',$invoiceTstamp+($dur * 24 * 60 * 60)) == 7)  $dur = $dur+1;

            $varValue = $invoiceTstamp+($dur * 24 * 60 * 60);

        }
        return $varValue;

    }

    /**
     * generiert den Rechnungs-Zeitstempel
     * @param $varValue int
     * @param DataContainer $dc
     * @return int
     */
    public function  generateInvoiceTstamp($varValue, DataContainer $dc)
    {
        return ((int)$varValue == 0) ? time() : $varValue;
    }

    /**
     * fill Address-Text
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

            DB::getInstance()->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
                ->set($set)
                ->limit(1)
                ->execute($dc->id);

            $this->reload();
        }
        return '';
    }

    /**
     * fill Text before if this field is empty
     * @param $varValue integer
     * @param $dc object
     * @return integer
     */
    public function fillBeforeText($varValue, DataContainer $dc)
    {
        if(strip_tags($dc->activeRecord->before_text) == '')
        {
            if(strlen($varValue) < 1) return $varValue;

            //hole das ausgewähte Template
            $objTemplate = TemplModel::findById($varValue);

            //hole den aktuellen Datensatz als DB-Object
            $objDbInvoice = InvoiceModel::findById($dc->id);

            $text = $this->changeIAOTags($objTemplate->text,'invoice',$objDbInvoice);

            // schreibe das Textfeld
            $set =['before_text' => $text];

            DB::getInstance()->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
                ->set($set)
                ->limit(1)
                ->execute($dc->id);

            $this->reload();
        }
        return $varValue;
    }

    /**
     * fill Text after if this field is empty
     * @param $varValue integer
     * @param $dc object
     * @return integer
     */
    public function fillAfterText($varValue, DataContainer $dc)
    {
        if(strip_tags($dc->activeRecord->after_text) == '')
        {
            if(strlen($varValue)<=0) return $varValue;

            //hole das ausgewähte Template
            $objTemplate = TemplModel::findById($varValue);

            //hole den aktuellen Datensatz als DB-Object
            $objDbOffer = InvoiceModel::findById($dc->id);

            $text = $this->changeIAOTags($objTemplate->text,'invoice',$objDbOffer);

            // schreibe das Textfeld
            $set =['after_text' => $text];
            DB::getInstance()->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
                ->set($set)
                ->limit(1)
                ->execute($dc->id);

            $this->reload();
        }
        return $varValue;
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     */
    public function saveBeforeTextAsTemplate($varValue, DataContainer $dc)
    {
        $text = strip_tags($dc->activeRecord->before_text);

        if($varValue == 1 && $text != '')
        {
            $set = array(
                'title' => \StringUtil::substr($text,50),
                'text' => $dc->activeRecord->before_text,
                'position' => 'invoice_before_text'
            );

            // Wenn vorher ein Template ausgewaehlt wurde wird es aktualisiert
            if((int) $dc->activeRecord->before_template > 0)
            {
                //pruefen ob es diesen Datensatz als Vorlage noch gibt
                $existObj = DB::getInstance()->prepare('SELECT * FROM `tl_iao_templates` WHERE id=?')->limit(1)->execute( (int) $dc->activeRecord->before_template);

                if($existObj->numRows > 0)
                {
                    DB::getInstance()->prepare('UPDATE `tl_iao_templates` %s WHERE id=?')->set($set)->execute( (int) $dc->activeRecord->before_template);
                }
                else
                {
                    DB::getInstance()->prepare('INSERT INTO `tl_iao_templates` %s')->set($set)->execute();
                }

                // Wenn kein Template angelegt wurde, wird ein neues angelegt
            } else {
                DB::getInstance()->prepare('INSERT INTO `tl_iao_templates` %s')->set($set)->execute();
            }

        }
        return '';
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     */
    public function saveAfterTextAsTemplate($varValue, DataContainer $dc)
    {
        $text = strip_tags($dc->activeRecord->after_text);

        if($varValue == 1 && $text != '')
        {
            $set = array(
                'title' => \StringUtil::substr($text,50),
                'text' => $dc->activeRecord->after_text,
                'position' => 'invoice_after_text'
            );

            // Wenn vorher ein Template ausgewaehlt wurde wird es aktualisiert
            if((int) $dc->activeRecord->after_template > 0)
            {
                //pruefen ob es diesen Datensatz als Vorlage noch gibt
                $existObj = TemplModel::findById((int) $dc->activeRecord->after_template);

                if(is_object($existObj))
                {
                    DB::getInstance()->prepare('UPDATE `tl_iao_templates` %s WHERE id=?')->set($set)->execute( (int) $dc->activeRecord->after_template);
                }
                else
                {
                    DB::getInstance()->prepare('INSERT INTO `tl_iao_templates` %s')->set($set)->execute();
                }

                // Wenn kein Template angelegt wurde, wird ein neues angelegt
            } else {
                DB::getInstance()->prepare('INSERT INTO `tl_iao_templates` %s')->set($set)->execute();
            }
        }
        return $varValue;
    }


    /**
     * get all Agreements to valid groups
     * @param DataContainer $dc
     * @return array
     */
    public function getAgreements(DataContainer $dc)
    {
        $varValue= array();

        $objAgr = AgreementsModel::findBy('status','1');

        if(is_object($objAgr)) while($objAgr->next())
        {
            $varValue[$objAgr->id] =  $objAgr->title.' ('.$objAgr->price.' &euro;)';
        }
        return $varValue;
    }

    /**
     * get all invoice before template
     * @param DataContainer $dc
     * @return array
     */
    public function getBeforeTemplate(DataContainer $dc)
    {
        $varValue= array();

        $objTemplates = TemplModel::findBy('position','invoice_before_text');

        if(is_object($objTemplates)) while($objTemplates->next())
        {
            $varValue[$objTemplates->id] = $objTemplates->title;
        }

        return $varValue;
    }

    /**
     * get all invoice after template
     * @param DataContainer $dc
     * @return array
     */
    public function getAfterTemplate(DataContainer $dc)
    {
        $varValue= array();

        $objTempl = TemplModel::findBy('position', 'invoice_after_text');

        if(is_object($objTempl)) while($objTempl->next())
        {
            $varValue[$objTempl->id] = $objTempl->title;
        }
        return $varValue;
    }

    /**
     * Return the edit header button
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     */
    public function editHeader($row, $href, $label, $title, $icon, $attributes)
    {
        $User = User::getInstance();
        return $User->hasAccess('css', 'themes') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';

//	    return ($User->isAdmin || count(preg_grep('/^tl_iao_invoice::/', $User->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ' : '';
    }

    /**
     * wenn GET-Parameter passen dann wird eine PDF erzeugt
     * @param DataContainer $dc
     */
    public function generateInvoicePDF(DataContainer $dc)
    {
        if(\Input::get('key') == 'pdf' && (int) \Input::get('id') > 0) $this->generatePDF((int) \Input::get('id'), 'invoice');
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

        // Wenn keine PDF-Vorlage dann kein PDF-Link
        $objPdfTemplate = 	\FilesModel::findByUuid($settings['iao_invoice_pdf']);
        if(strlen($objPdfTemplate->path) < 1 || !file_exists(TL_ROOT . '/' . $objPdfTemplate->path) ) return false;  // template file not found

        $href = 'contao/main.php?do=iao_invoice&amp;key=pdf&amp;id='.$row['id'];
        return '<a href="'.$href.'" title="'.specialchars($title).'">'.\Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * fill field invoice_id_str if it's empty
     * @param $varValue string
     * @param DataContainer $dc
     * @return string
     */

    public function setFieldInvoiceNumberStr($varValue, DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);
        $tstamp = ($dc->activeRecord->date) ?: time();

        return (strlen($varValue)>0)? $varValue : $this->generateInvoiceNumberStr($dc->activeRecord->invoice_id, $tstamp, $settings);
    }

    /**
     * fill field invoice_id if it's empty
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     */
    public function setFieldInvoiceNumber($varValue, DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);
        return $this->generateInvoiceNumber($varValue, $settings);
    }

    /**
     * List a particular record
     * @param $arrRow array
     * @return string
     */
    public function listEntries($arrRow)
    {
        $settings = $this->getSettings($arrRow['setting_id']);

        $result = DB::getInstance()->prepare("SELECT `firstname`,`lastname`,`company` FROM `tl_member`  WHERE id=?")
            ->limit(1)
            ->execute($arrRow['member']);
        $row = $result->fetchAssoc();

        return '
		<div class="cte_type status' . $arrRow['status'] . '"><strong>' . $arrRow['title'] . '</strong> '.$arrRow['invoice_id_str'].'</div>
		<div class="limit_height">
		'.$GLOBALS['TL_LANG']['tl_iao_invoice']['price_brutto'][0].': <strong>'.number_format($arrRow['price_brutto'],2,',','.').' '.$settings['iao_currency_symbol'].'</strong>
		<br>
		'.$GLOBALS['TL_LANG']['tl_iao_invoice']['remaining'][0].': <strong>'.number_format($arrRow['remaining'],2,',','.').' '.$settings['iao_currency_symbol'].'</strong>
		<br>
		'.$GLOBALS['TL_LANG']['tl_iao_invoice']['member'][0].': '.$row['firstname'].' '.$row['lastname'].' ('.$row['company'].')
		<br>
		'.(($arrRow['notice'])? $GLOBALS['TL_LANG']['tl_iao_invoice']['notice'][0].":".$arrRow['notice']: '').'
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
        if (strlen($this->Input->get('tid')))
        {
            $this->toggleVisibility($this->Input->get('tid'), ($this->Input->get('state')));
            $this->redirect($this->getReferer());
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['status']==1 ? 2 : 1);

        if ($row['status']==2)
        {
            $icon = 'logout.gif';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.$GLOBALS['TL_LANG']['tl_iao_invoice']['toggle'].'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Disable/enable a user group
     * @param integer
     * @param boolean
     */
    public function toggleVisibility($intId, $blnVisible)
    {
        // Check permissions to edit
        $this->Input->setGet('id', $intId);
        $this->Input->setGet('act', 'toggle');
        $User = User::getInstance();

        // Check permissions to publish
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_invoice::status', 'alexf'))
        {
            $logger = static::getContainer()->get('monolog.logger.contao');
            $logger->log('Not enough permissions to publish/unpublish comment ID "'.$intId.'"', 'tl_iao_invoice toggleActivity', TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new \Versions('tl_iao_invoice', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iao_invoice']['fields']['status']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_iao_invoice']['fields']['status']['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }

        // Update the database
        DB::getInstance()->prepare("UPDATE tl_iao_invoice SET status='" . ($blnVisible==1 ? '1' : '2') . "' WHERE id=?")
            ->execute($intId);

        $objVersions->create();
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return mixed
     */
    public function updateStatus($varValue, DataContainer $dc)
    {
        if($varValue == 2)
        {
            $set = array
            (
                'status' => $varValue,
                'paid_on_date' => $dc->activeRecord->paid_on_date
            );

            DB::getInstance()->prepare('UPDATE `tl_iao_reminder` %s WHERE `invoice_id`=?')
                ->set($set)
                ->execute($dc->id);
        }
        return $varValue;
    }

    /**
     * zur Aktualisierung der Datensätze aus älterer Modul-Versionen
     * @param DataContainer $dc
     */
    public function upgradeInvoices(DataContainer $dc)
    {
        $allInvObj = DB::getInstance()->prepare('SELECT * FROM `tl_iao_invoice` WHERE `remaining`=? AND `paid_on_dates` IS NULL')
            ->execute(0, '');

        if($allInvObj->numRows > 0)
        {
            while($allInvObj->next())
            {
                $paidArr = array();

                switch($allInvObj->status)
                {
                    case '1': // noch offen
                    case '3': // ruht
                        $set = array (
                            'remaining' => $allInvObj->price_brutto
                        );

                        DB::getInstance()->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
                            ->set($set)
                            ->execute($allInvObj->id);
                        break;
                    case '2': //bezahlt
                        $paidArr[] = array (
                            'paydate'=>$allInvObj->paid_on_date,
                            'payamount'=> $allInvObj->price_brutto,
                            'paynotice'=>''
                        );

                        $set = array (
                            'remaining' => 0,
                            'paid_on_dates' => serialize($paidArr)
                        );

                        DB::getInstance()->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
                            ->set($set)
                            ->execute($allInvObj->id);
                        break;
                }


                //$paid_on_date = ($allInvObj->price_brutto == $already) ? $lastPayDate : $allInvObj->paid_on_date;

            }
        }
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     */
    public function priceFormat($varValue, DataContainer $dc)
    {
        return $this->getPriceStr($varValue);
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return mixed
     */
    public function getPriceallValue($varValue, DataContainer $dc)
    {
        return $dc->activeRecord->price_brutto;
    }

    /**
     * calculate and update fields
     * @param $varValue
     * @param DataContainer $dc
     * @return mixed
     */
    public function updateRemaining($varValue, DataContainer $dc)
    {
        $paidsArr = unserialize($varValue);
        $already = 0;
        $lastPayDate = '';

        if(is_array($paidsArr) && ($paidsArr[0]['payamount'] != ''))
        {
            foreach($paidsArr as $k => $a)
            {
                $already += $a['payamount'];
                $lastPayDate = $a['paydate'];
            }
        }

        $dif = $dc->activeRecord->price_brutto - $already;
        $status = ($dc->activeRecord->price_brutto == $already && $dc->activeRecord->price_brutto > 0) ? 2 : $dc->activeRecord->status;
        $paid_on_date = ($dc->activeRecord->price_brutto == $already) ? $lastPayDate : $dc->activeRecord->paid_on_date;

        $set = [
            'remaining' => $dif,
            'status' => $status,
            'paid_on_date' => $paid_on_date
        ];

        DB::getInstance()->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
            ->set($set)
            ->execute($dc->id);

        return $varValue;
    }

}