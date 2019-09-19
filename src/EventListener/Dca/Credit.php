<?php
/**
 * Created by c4.pringitzhonig.de.
 * Developer: Sven Rhinow (sven@sr-tag.de)
 * Date: 19.09.19
 */

namespace Srhinow\ProjectManagerBundle\EventListener\Dca;


use Contao\BackendUser as User;
use Contao\Database as DB;
use Contao\Image;
use Contao\Input;
use Iao\Backend\IaoBackend;
use Srhinow\IaoCreditModel;
use Srhinow\IaoProjectsModel;
use Srhinow\IaoTemplatesModel;

class Credit  extends IaoBackend
{

    protected $settings = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check permissions to edit table tl_iao_credit
     */
    public function checkPermission()
    {
        $this->checkIaoModulePermission('tl_iao_credit');
    }

    /**
     * prefill eny Fields by new dataset
     */
    public function preFillFields($table, $id, $set, $obj)
    {
        $objProject = IaoProjectsModel::findById($set['pid']);
        $settingId = ($objProject !== null && $objProject->setting_id != 0) ? $objProject->setting_id : 1;
        $settings = $this->getSettings($settingId);
        $creditId = $this->generateCreditNumber(0, $settings);
        $creditIdStr = $this->createCreditNumberStr('', $creditId, time(), $settings);

        $set = array
        (
            'credit_id' => $creditId,
            'credit_id_str' => $creditIdStr
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
     * @return integer
     */
    public function  generateCreditDate($varValue, \DataContainer $dc)
    {
        return ($varValue==0) ? date($GLOBALS['TL_CONFIG']['dateFormat']) : $varValue;
    }

    /**
     * fill date-Field if this empty
     * @param $varValue mixed
     * @param $dc object
     * @return mixed
     */
    public function  generateExpiryDate($varValue, \DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);

        if($varValue==0)
        {
            $format = ( $settings['iao_credit_expiry_date'] ) ? $settings['iao_credit_expiry_date'] : '+3 month';
            $tstamp = ($dc->activeRecord->credit_tstamp) ? $dc->activeRecord->credit_tstamp : time();
            $varValue = strtotime($format,$tstamp);
        }
        return  $varValue;
    }

    /**
     * @param \DataContainer $dc
     */
    public function updateExpiryToTstmp(\DataContainer $dc)
    {
        $objCredits = IaoCreditModel::findAll();

        if(is_object($objCredits)) while($objCredits->next())
        {
            if(!stripos($objCredits->expiry_date,'-')) continue;

            $set = array('expiry_date' => strtotime($objCredits->expiry_date));
            DB::getInstance()->prepare('UPDATE `tl_iao_credit` %s WHERE `id`=?')
                ->set($set)
                ->execute($objCredits->id);
        }
    }

    /**
     * fill date-Field if this empty
     * @param $varValue mixed
     * @param $dc object
     * @return integer
     */
    public function  generateCreditTstamp($varValue, \DataContainer $dc)
    {
        return ((int)$varValue == 0) ? time() : $varValue;
    }

    /**
     * fill Adress-Text
     * @param $intMember integer
     * @param $dc object
     * @return integer
     */
    public function fillAddressText($varValue, \DataContainer $dc)
    {
        if($varValue == 1) {

            $intMember = Input::post(member);
            $text = $this->getAddressText($intMember);

            $set = array(
                'address_text' => $text,
                'text_generate' => ''
            );

            $text = $this->getAddressText($intMember);

            DB::getInstance()->prepare('UPDATE `tl_iao_credit` %s WHERE `id`=?')
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
    public function fillBeforeTextFromTemplate($varValue, \DataContainer $dc)
    {
        if(strip_tags($dc->activeRecord->before_text) == '')
        {
            if(strlen($varValue)<=0) return $varValue;

            //hole das ausgewähte Template
            $objTemplate = IaoTemplatesModel::findById($varValue);

            //hole den aktuellen Datensatz als DB-Object
            $objDbCredit = IaoCreditModel::findById($dc->id);

            // ersetzte evtl. Platzhalter
            $text = $this->changeIAOTags($objTemplate->text, 'credit' , $objDbCredit);

            // schreibe das Textfeld
            DB::getInstance()->prepare('UPDATE `tl_iao_credit` SET `before_text`=? WHERE `id`=?')
                ->limit(1)
                ->execute($text, $dc->id);

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
    public function fillAfterTextFromTemplate($varValue, \DataContainer $dc)
    {

        if(strip_tags($dc->activeRecord->after_text)=='')
        {
            if(strlen($varValue)<=0) return $varValue;

            //hole das ausgewähte Template
            $objTemplate = IaoTemplatesModel::findById($varValue);

            //hole den aktuellen Datensatz als DB-Object
            $objDbCredit = IaoCreditModel::findById($dc->id);

            // ersetzte evtl. Platzhalter
            $text = $this->changeIAOTags($objTemplate->text, 'credit' , $objDbCredit);

            DB::getInstance()->prepare('UPDATE `tl_iao_credit` SET `after_text`=? WHERE `id`=?')
                ->limit(1)
                ->execute($objTemplate->text,$dc->id);

            $this->reload();
        }
        return $varValue;
    }


    /**
     * get all template with position = 'credit_before_text'
     * @param object
     * @return array
     */
    public function getBeforeTemplate(\DataContainer $dc)
    {
        $varValue= array();

        $all = DB::getInstance()->prepare('SELECT `id`,`title` FROM `tl_iao_templates` WHERE `position`=?')
            ->execute('credit_before_text');

        while($all->next())
        {
            $varValue[$all->id] = $all->title;
        }

        return $varValue;
    }

    /**
     * get all credit after template
     * @param object
     * @return array
     */
    public function getAfterTemplate(\DataContainer $dc)
    {
        $varValue= array();

        $all = DB::getInstance()->prepare('SELECT `id`,`title` FROM `tl_iao_templates` WHERE `position`=?')
            ->execute('credit_after_text');

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
        return ($User->isAdmin || count(preg_grep('/^tl_iao_credit::/', $User->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : '';
    }

    /**
     * wenn GET-Parameter passen dann wird eine PDF erzeugt
     *
     */
    public function generateCreditPDF(\DataContainer $dc)
    {
        if(\Input::get('key') == 'pdf' && (int) \Input::get('id') > 0) $this->generatePDF((int) \Input::get('id'), 'credit');
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
        $User = User::getInstance();

        if (!$User->isAdmin || count(preg_grep('/^tl_iao_credit::/', $User->alexf)))	return '';

        $objPdfTemplate = 	\FilesModel::findByUuid($settings['iao_credit_pdf']);
        if(strlen($objPdfTemplate->path) < 1 || !file_exists(TL_ROOT . '/' . $objPdfTemplate->path) ) return '';  // template file not found

        $href = 'contao/main.php?do=iao_credit&amp;key=pdf&amp;id='.$row['id'];
        return '<a href="'.$href.'" title="'.specialchars($title).'">'.\Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * fill field invoice_id_str if it's empty
     * @param string
     * @param object
     * @return string
     */
    public function setFieldCreditNumberStr($varValue, \DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);
        $tstamp = ($dc->activeRecord->date) ?: time();

        return $this->createCreditNumberStr($varValue, $dc->activeRecord->credit_id, $tstamp, $settings);
    }

    /**
     * generate a Credit-number-string if not set
     * @param string
     * @param integer
     * @param integer
     * @param array
     * @return string
     */
    public function createCreditNumberStr($varValue, $creditId, $tstamp, $settings)
    {

        if(strlen($varValue) < 1)
        {
            $format = $settings['iao_credit_number_format'];
            $format =  str_replace('{date}',date('Ymd', $tstamp), $format);
            $format =  str_replace('{nr}', $creditId, $format);
            $varValue = $format;
        }
        return $varValue;
    }

    /**
     * fill field invoice_id if it's empty
     * @param string
     * @param object
     * @return string
     */
    public function setFieldCreditNumber($varValue, \DataContainer $dc)
    {
        $settings = $this->getSettings($dc->activeRecord->setting_id);
        return $this->generateCreditNumber($varValue, $settings);
    }


    /**
     * Autogenerate an credit number if it has not been set yet
     * @param mixed
     * @param object
     * @return string
     */
    public function generateCreditNumber($varValue, $settings)
    {
        $autoNr = false;
        $varValue = (int) $varValue;
        $id = \Input::get('id');

        // Generate credit_id if there is none
        if($varValue == 0)
        {
            $objNr = DB::getInstance()->prepare("SELECT `credit_id` FROM `tl_iao_credit` ORDER BY `credit_id` DESC")
                ->limit(1)
                ->execute();

            if($objNr->numRows < 1 || $objNr->credit_id == 0)  $varValue = $settings['iao_credit_startnumber'];
            else  $varValue =  $objNr->credit_id +1;
        }
        else
        {
            $objNr = DB::getInstance()->prepare("SELECT `credit_id` FROM `tl_iao_credit` WHERE `id`=? OR `credit_id`=?")
                ->limit(1)
                ->execute($id, $varValue);

            // Check whether the CreditNumber exists
            if ($objNr->numRows > 1 )
            {
                if (!$autoNr)
                {
                    throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
                }

                $varValue .= '-' . $id;
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
        $result = DB::getInstance()->prepare("SELECT `firstname`,`lastname`,`company` FROM `tl_member`  WHERE id=?")
            ->limit(1)
            ->execute($arrRow['member']);

        $row = $result->fetchAssoc();

        return '
		<div class="cte_type status' . $arrRow['status'] . '"><strong>' . $arrRow['title'] . '</strong> '.$arrRow['credit_id_str'].'</div>
		<div class="limit_height">
		'.$GLOBALS['TL_LANG']['tl_iao_credit']['price_brutto'][0].': <strong>'.number_format($arrRow['price_brutto'],2,',','.').' '.$GLOBALS['TL_CONFIG']['currency_symbol'].'</strong>
		<br>
		'.$GLOBALS['TL_LANG']['tl_iao_credit']['member'][0].': '.$row['firstname'].' '.$row['lastname'].' ('.$row['company'].')
		<br>
		'.(($arrRow['notice'])? $GLOBALS['TL_LANG']['tl_iao_credit']['notice'][0].":".$arrRow['notice']: '').'
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

        return '<a href="'.$this->addToUrl($href).'" title="'.$GLOBALS['TL_LANG']['tl_iao_credit']['toggle'].'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
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

        // Check permissions to publish
        $User = User::getInstance();
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_credit::status', 'alexf'))
        {
            $logger = static::getContainer()->get('monolog.logger.contao');
            $logger->log('Not enough permissions to publish/unpublish comment ID "'.$intId.'"', 'tl_iao_credit toggleActivity', TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new \Versions('tl_iao_credit', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iao_credit']['fields']['status']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_iao_credit']['fields']['status']['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }
        $status = ($blnVisible==1 ) ? '1' : '2';
        // Update the database
        DB::getInstance()->prepare("UPDATE tl_iao_credit SET status=? WHERE id=?")
            ->execute($status, $intId);

        $objVersions->create();
    }
}