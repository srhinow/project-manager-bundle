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
use Contao\Input;
use Iao\Backend\IaoBackend;
use Srhinow\IaoOfferModel;
use Srhinow\IaoProjectsModel;
use Srhinow\IaoTemplatesModel;

class Offer extends IaoBackend
{

    protected $settings = array();

    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Check permissions to edit table tl_iao_offer
     */
    public function checkPermission()
    {
        $this->checkIaoModulePermission('tl_iao_offer');
    }

    /**
     * prefill eny Fields by new dataset
     * @param string
     * @param integer
     * @param array
     */
    public function preFillFields($table, $id, $set)
    {
        $objProject = IaoProjectsModel::findById($set['pid']);
        $settingId = ($objProject !== null && $objProject->setting_id != 0) ? $objProject->setting_id : 1;
        $settings = $this->getSettings($settingId);

        $offerId = $this->generateOfferNumber(0, $settings);
        $offerIdStr = $this->generateOfferNumberStr('', $offerId, time(), $settings);

        $set = array
        (
            'offer_id' => $offerId,
            'offer_id_str' => $offerIdStr
        );

        DB::getInstance()->prepare('UPDATE '.$table.' %s WHERE `id`=?')
            ->set($set)
            ->limit(1)
            ->execute($id);
    }

    /**
     * fill date-Field if this empty
     * @param mixed
     * @param object
     * @return int
     */
    public function  generateExpiryDate($varValue, \DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);

        if($varValue == 0)
        {
            $format = ( $settings['iao_offer_expiry_date'] ) ? $settings['iao_offer_expiry_date'] : '+3 month';
            $tstamp = ($dc->activeRecord->offer_tstamp) ? $dc->activeRecord->offer_tstamp : time();
            $varValue = strtotime($format,$tstamp);
        }
        return  $varValue;
    }

    /**
     * @param \DataContainer $dc
     */
    public function updateExpiryToTstmp(\DataContainer $dc)
    {
        $offerObj = IaoOfferModel::findAll();

        if(is_object($offerObj)) while($offerObj->next())
        {
            if(!stripos($offerObj->expiry_date,'-')) continue;

            $set = array('expiry_date' => strtotime($offerObj->expiry_date));
            DB::getInstance()->prepare('UPDATE `tl_iao_offer` %s WHERE `id`=?')
                ->set($set)
                ->execute($offerObj->id);
        }
    }

    /**
     * fill date-Field if this empty
     * @param mixed
     * @param object
     * @return integer
     */
    public function  generateOfferTstamp($varValue, \DataContainer $dc)
    {
        return ((int)$varValue == 0) ? time() : $varValue;
    }

    /**
     * fill Member And Address-Text
     * @param $varValue integer
     * @param $dc object
     * @return $value string
     */
    public function fillMemberAndAddressFields($varValue, \DataContainer $dc)
    {
        if((strlen($varValue) < 1)) return $varValue;

        $objProj = IaoProjectsModel::findById($varValue);
        if(is_object($objProj))
        {
            if((int) $objProj->member > 0)
            {
                $addressText = $this->getAddressText($objProj->member);

                $set = [
                    'member' => $objProj->member,
                    'address_text' => $addressText
                ];

            } else {
                $set = [
                    'member' => '',
                    'address_text' => ''
                ];
            }

            DB::getInstance()->prepare("UPDATE `tl_iao_offer` %s WHERE `id`=?")
                ->limit(1)
                ->set($set)
                ->execute($dc->id);

        }

        return $varValue;
    }
    /**
     * fill Address-Text
     * @param $varValue mixed
     * @param $dc object
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

            $text = $this->getAddressText($varValue);

            DB::getInstance()->prepare('UPDATE `tl_iao_offer` %s WHERE `id`=?')
                ->set($set)
                ->limit(1)
                ->execute($dc->id);
        }
        //leere checkbox zurueck geben
        return '';
    }


    /**
     * fill Text before if this field is empty
     * @param $varValue integer
     * @param $dc object
     * @return integer
     */
    public function fillBeforeText($varValue, \DataContainer $dc)
    {
        if(strip_tags($dc->activeRecord->before_text) == '')
        {
            if(strlen($varValue)<=0) return $varValue;

            //hole das ausgewähte Template
            $objTemplate = IaoTemplatesModel::findById($varValue);

            //hole den aktuellen Datensatz als DB-Object
            $objDbOffer = IaoOfferModel::findById($dc->id);

            $text = $this->changeIAOTags($objTemplate->text, 'offer', $objDbOffer);

            // schreibe das Textfeld
            $set =['before_text' => $text];
            DB::getInstance()->prepare('UPDATE `tl_iao_offer` %s WHERE `id`=?')
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
    public function fillAfterText($varValue, \DataContainer $dc)
    {
        if(strip_tags($dc->activeRecord->after_text) == '')
        {
            if(strlen($varValue) < 1) return $varValue;

            //hole das ausgewähte Template
            $objTemplate = IaoTemplatesModel::findById($varValue);

            //hole den aktuellen Datensatz als DB-Object
            $objDbOffer = IaoOfferModel::findById($dc->id);

            // ersetzte evtl. Platzhalter
            $text = $this->changeIAOTags($objTemplate->text,'offer', $objDbOffer);

            // schreibe das Textfeld
            $set =['after_text' => $text];
            DB::getInstance()->prepare('UPDATE `tl_iao_offer` SET `after_text`=? WHERE `id`=?')
                ->set($set)
                ->limit(1)
                ->execute($text,$dc->id);

            $this->reload();
        }
        return $varValue;
    }

    /**
     * get all template with position = 'offer_before_text'
     * @param object
     * @return array
     */
    public function getBeforeTemplate(\DataContainer $dc)
    {
        $varValue= array();

        $all = DB::getInstance()->prepare('SELECT `id`,`title` FROM `tl_iao_templates` WHERE `position`=?')
            ->execute('offer_before_text');

        while($all->next())
        {
            $varValue[$all->id] = $all->title;
        }

        return $varValue;
    }

    /**
     * get all template with position = 'offer_after_text'
     * @param object
     * @return array
     */
    public function getAfterTemplate(\DataContainer $dc)
    {
        $varValue= array();

        $all = DB::getInstance()->prepare('SELECT `id`,`title` FROM `tl_iao_templates` WHERE `position`=?')
            ->execute('offer_after_text');

        while($all->next())
        {
            $varValue[$all->id] = $all->title;
        }

        return $varValue;
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
        return ($User->isAdmin || count(preg_grep('/^tl_iao_offer::/', $User->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ' : '';
    }

    /**
     * generate invoice from this offer
     * @param $row array
     * @param $href string
     * @param $label string
     * @param $title string
     * @param $icon string
     * @return string
     */
    public function addInvoice($row, $href, $label, $title, $icon)
    {
        $settings = $this->getSettings($row['setting_id']);
        $User = User::getInstance();
        if (!$User->isAdmin) return false;

        if (\Input::get('key') == 'addInvoice' && \Input::get('id') == $row['id'])
        {
            //Insert Invoice-Entry
            $set = array
            (
                'pid' => (\Input::get('projId')) ? : $row['pid'],
                'tstamp' => time(),
                'invoice_tstamp' => time(),
                'title' => $row['title'],
                'address_text' => $row['address_text'],
                'member' => $row['member'],
                'price_netto' => $row['price_netto'],
                'price_brutto' => $row['price_brutto'],
                'noVat' => $row['noVat'],
                'notice' => $row['notice'],
            );

            $result = DB::getInstance()->prepare('INSERT INTO `tl_iao_invoice` %s')
                ->set($set)
                ->execute();

            $newInvoiceID = $result->insertId;

            //Insert Postions for this Entry
            if($newInvoiceID)
            {
                $posten = DB::getInstance()->prepare('SELECT * FROM `tl_iao_offer_items` WHERE `pid`=? ')
                    ->execute($row['id']);

                if(is_object($posten)) while($posten->next())
                {
                    //Insert Invoice-Entry
                    $postenset = [
                        'pid' => $newInvoiceID,
                        'tstamp' => $posten->tstamp,
                        'type' => $posten->type,
                        'headline' => $posten->headline,
                        'headline_to_pdf' => $posten->headline_to_pdf,
                        'sorting' => $posten->sorting,
                        'date' => $posten->date,
                        'time' => $posten->time,
                        'text' => $posten->text,
                        'count' => $posten->count,
                        'amountStr' => $posten->amountStr,
                        'operator' => $posten->operator,
                        'price' => $posten->price,
                        'price_netto' => $posten->price_netto,
                        'price_brutto' => $posten->price_brutto,
                        'published' => $posten->published,
                        'vat' => $posten->vat,
                        'vat_incl' => $posten->vat_incl
                    ];

                    DB::getInstance()->prepare('INSERT INTO `tl_iao_invoice_items` %s')
                        ->set($postenset)
                        ->execute();
                }

                // Update the database
                $set = ['status'=>'2'];

                DB::getInstance()->prepare("UPDATE tl_iao_offer %s WHERE id=?")
                    ->set($set)
                    ->execute($row['id']);

                $redirectUrl = $this->addToUrl('do=iao_invoice&mode=2&table=tl_iao_invoice&s2e=1&id='.$newInvoiceID.'&act=edit&rt='.REQUEST_TOKEN);
                $redirectUrl = str_replace('key=addInvoice&amp;','', $redirectUrl);
                $this->redirect($redirectUrl);

//				$this->redirect($this->addToUrl('do=iao_invoice&table=tl_iao_invoice&id='.$newInvoiceID.'&act=edit') );
            }
        }

        $link = (\Input::get('onlyproj') == 1) ? 'do=iao_offer&amp;id='.$row['id'].'&amp;projId='.\Input::get('id') : 'do=iao_offer&amp;id='.$row['id'].'';
        $link = $this->addToUrl($href.'&amp;'.$link.'&rt='.REQUEST_TOKEN);
        $link = str_replace('table=tl_iao_offer&amp;','',$link);
        return '<a href="'.$link.'" title="'.specialchars($title).'">'.\Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * wenn GET-Parameter passen dann wird eine PDF erzeugt
     * @param \DataContainer $dc
     */
    public function generateOfferPDF(\DataContainer $dc)
    {
        if(\Input::get('key') == 'pdf' && (int) \Input::get('id') > 0) $this->generatePDF((int) \Input::get('id'), 'offer');
    }

    /**
     * Generate a "PDF" button and return it as pdf-document on Browser
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
        $User = User::getInstance();

        // wenn kein Admin dann kein PDF-Link	
        if (!$User->isAdmin || count(preg_grep('/^tl_iao_offer::/', $User->alexf)) > 0) return '';

        // Wenn keine PDF-Vorlage dann kein PDF-Link
        $objPdfTemplate = 	\FilesModel::findByUuid($settings['iao_offer_pdf']);
        if(strlen($objPdfTemplate->path) < 1 || !file_exists(TL_ROOT . '/' . $objPdfTemplate->path) ) return false;  // template file not found

        $href = 'contao/main.php?do=iao_offer&amp;key=pdf&amp;id='.$row['id'];
        return '<a href="'.$href.'" title="'.specialchars($title).'">'.\Image::getHtml($icon, $label).'</a> ';

    }

    /**
     * fill field offer_id_str if it's empty
     * @param string
     * @param object
     * @return string
     */
    public function setFieldOfferNumberStr($varValue, \DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);
        $tstamp = ($dc->activeRecord->tstamp)?: time();

        return $this->generateOfferNumberStr($varValue, $dc->activeRecord->offer_id, $tstamp, $settings);
    }

    /**
     * create an offer-number-string and replace placeholder
     * @param string
     * @param integer
     * @param integer
     * @param array
     * @return string
     */
    public function generateOfferNumberStr($varValue, $offerId, $tstamp, $settings)
    {
        if(strlen($varValue) < 1)
        {
            $format = 		$settings['iao_offer_number_format'];
            $format =  str_replace('{date}',date('Ymd',$tstamp), $format);
            $format =  str_replace('{nr}',$offerId, $format);
            $varValue = $format;
        }
        return $varValue;
    }

    /**
     * fill field offer_id if it's empty
     * @param string
     * @param object
     * @return string
     */
    public function setFieldOfferNumber($varValue, \DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);
        return $this->generateOfferNumber($varValue, $settings);
    }

    /**
     * generate a offer-number if not set
     * @param mixed
     * @param object
     * @return string
     */
    public function generateOfferNumber($varValue, $settings)
    {
        $autoNr = false;
        $varValue = (int) $varValue;

        // Generate offer_id if there is none
        if($varValue == 0)
        {
            $autoNr = true;
            $objNr = DB::getInstance()->prepare("SELECT `offer_id` FROM `tl_iao_offer` ORDER BY `offer_id` DESC")
                ->limit(1)
                ->execute();

            if($objNr->numRows < 1 || $objNr->offer_id == 0)  $varValue = $settings['iao_offer_startnumber'];
            else  $varValue =  $objNr->offer_id +1;
        }
        else
        {
            $objNr = DB::getInstance()->prepare("SELECT `offer_id` FROM `tl_iao_offer` WHERE `id`=? OR `offer_id`=?")
                ->limit(1)
                ->execute(\Input::get('id'),$varValue);

            // Check whether the OfferNumber exists
            if ($objNr->numRows > 1 )
            {
                if (!$autoNr)
                {
                    throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
                }

                $varValue .= '-' . \Input::get('id');
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
        $settings = $this->getSettings($arrRow['settings_id']);

        $result = DB::getInstance()->prepare("SELECT `firstname`,`lastname`,`company` FROM `tl_member`  WHERE id=?")
            ->limit(1)
            ->execute($arrRow['member']);

        $row = $result->fetchAssoc();

        return '
		<div class="cte_type status' . $arrRow['status'] . '"><strong>' . $arrRow['title'] . '</strong> '.$arrRow['offer_id_str'].'</div>
		<div class="limit_height">
		'.$GLOBALS['TL_LANG']['tl_iao_offer']['price_brutto'][0].': <strong>'.number_format($arrRow['price_brutto'],2,',','.').' '.$settings['iao_currency_symbol'].'</strong>
		<br>
		'.$GLOBALS['TL_LANG']['tl_iao_offer']['member'][0].': '.$row['firstname'].' '.$row['lastname'].' ('.$row['company'].')
		<br>
		'.(($arrRow['notice'])? $GLOBALS['TL_LANG']['tl_iao_offer']['notice'][0].":".$arrRow['notice'] : '').'
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

        return '<a href="'.$this->addToUrl($href).'" title="'.$GLOBALS['TL_LANG']['tl_iao_offer']['toggle'].'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Disable/enable a offer
     * @param integer
     * @param boolean
     */
    public function toggleVisibility($intId, $blnVisible)
    {
        // Check permissions to edit
        $this->Input->setGet('id', $intId);
        $this->Input->setGet('act', 'toggle');

        // Check permissions to publish
        $User = User::getInstance();
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_offer::status', 'alexf'))
        {
            $logger = static::getContainer()->get('monolog.logger.contao');
            $logger->log('Not enough permissions to publish/unpublish comment ID "'.$intId.'"', 'tl_iao_offer toggleActivity', TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new \Versions('tl_iao_offer', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iao_offer']['fields']['status']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_iao_offer']['fields']['status']['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }

        // Update the database
        $set = ['status'=>($blnVisible==1 ? '1' : '2')];

        DB::getInstance()->prepare("UPDATE tl_iao_offer %s WHERE id=?")
            ->set($set)
            ->execute($intId);

        $objVersions->create();
    }
}