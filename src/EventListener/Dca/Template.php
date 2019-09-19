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
use Iao\Backend\IaoBackend;

class Template extends IaoBackend
{

    /**
     * Templates constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Check permissions to edit table tl_iao_templates
     */
    public function checkPermission()
    {
        $this->checkIaoSettingsPermission('tl_iao_templates');
    }

    /**
     * Autogenerate an article alias if it has not been set yet
     * @param mixed
     * @param object
     * @return string
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $autoAlias = false;

        // Generate alias if there is none
        if (!strlen($varValue))
        {
            $autoAlias = true;
            $varValue = standardize($dc->activeRecord->title);
        }


        $objAlias = DB::getInstance()->prepare("SELECT id FROM `tl_iao_templates` WHERE id=? OR alias=?")
            ->execute($dc->id, $varValue);

        // Check whether the page alias exists
        if ($objAlias->numRows > 1)
        {
            if (!$autoAlias)
            {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-' . $dc->id;
        }

        return $varValue;
    }

    /**
     * fill date-Field if this empty
     * @param $varValue
     * @param DataContainer $dc
     * @return false|string
     */
    public function  generateCreditDate($varValue, DataContainer $dc)
    {
        return ($varValue==0) ? date('Y-m-d') : $varValue;
    }

    /**
     * fill date-Field if this empty
     * @param $varValue
     * @param DataContainer $dc
     * @return false|string
     */
    public function  generateExpiryDate($varValue, DataContainer $dc)
    {
        if($varValue==0)
        {
            $format = ($GLOBALS['TL_CONFIG']['iao_credit_expiry_date']) ? $GLOBALS['TL_CONFIG']['iao_credit_expiry_date'] : 'd:m+3:Y';

            $parts = explode(':',$format);
            $part['day'] =  substr($parts[0],1);
            $part['month'] =  substr($parts[1],1);
            $part['year'] =  substr($parts[2],1);

            $varValue = date('Y-m-d',mktime(0, 0, 0, date('n')+$part['month'],date('d')+$part['day'], date('Y')+$part['year']));
        }
        return  $varValue;
    }

    /**
     * fill date-Field if this empty
     * @param $varValue
     * @param DataContainer $dc
     * @return false|int
     */
    public function  generateCreditTstamp($varValue, DataContainer $dc)
    {
        $credit_date = $dc->activeRecord->credit_date;
        if($credit_date == 0  && $varValue !=0) return time();

        $idArr =  explode('-',$credit_date);
        return mktime(0, 0, 0, $idArr[1], $idArr[2], $idArr[0]);
    }

    /**
     * fill Adress-Text
     * @param $varValue
     * @param DataContainer $dc
     * @return mixed
     */
    public function fillAdressText($varValue, DataContainer $dc)
    {
        if(strip_tags($dc->activeRecord->address_text)=='')
        {

            if(strlen($varValue)<=0) return $varValue;

            $objMember = DB::getInstance()->prepare('SELECT * FROM `tl_member` WHERE `id`=?')
                ->limit(1)
                ->execute($varValue);

            $text = '<p>'.$objMember->company.'<br />'.($objMember->gender!='' ? $GLOBALS['TL_LANG']['tl_iao_templates']['gender'][$objMember->gender].' ':'').($objMember->title ? $objMember->title.' ':'').$objMember->firstname.' '.$objMember->lastname.'<br />'.$objMember->street.'</p>';
            $text .='<p>'.$objMember->postal.' '.$objMember->city.'</p>';

            DB::getInstance()->prepare('UPDATE `tl_iao_templates` SET `address_text`=? WHERE `id`=?')
                ->limit(1)
                ->execute($text,$dc->id);
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
        return ($User->isAdmin || count(preg_grep('/^tl_iao_templates::/', $User->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : '';
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return mixed
     */
    public function createCreditNumberStr($varValue, DataContainer $dc)
    {
        if(!$varValue)
        {
            $tstamp = $dc->activeRecord->tstamp ? $dc->activeRecord->tstamp : time();

            $format = $GLOBALS['TL_CONFIG']['iao_credit_number_format'];
            $format =  str_replace('{date}',date('Ymd',$tstamp),$format);
            $format =  str_replace('{nr}',$dc->activeRecord->credit_id,$format);
            $varValue = $format;
        }

        return $varValue;
    }

    /**
     * Autogenerate an article alias if it has not been set yet
     * @param mixed
     * @param object
     * @return string
     */
    public function generateCreditNumber($varValue, DataContainer $dc)
    {
        $autoNr = false;
        $varValue = (int) $varValue;

        // Generate credit_id if there is none
        if($varValue == 0)
        {
            $autoNr = true;
            $objNr = DB::getInstance()->prepare("SELECT `credit_id` FROM `tl_iao_templates` ORDER BY `credit_id` DESC")
                ->limit(1)
                ->execute();

            if($objNr->numRows < 1 || $objNr->credit_id == 0)  $varValue = $GLOBALS['TL_CONFIG']['iao_credit_startnumber'];
            else  $varValue =  $objNr->credit_id +1;
        }
        else
        {
            $objNr = DB::getInstance()->prepare("SELECT `credit_id` FROM `tl_iao_templates` WHERE `id`=? OR `credit_id`=?")
                ->limit(1)
                ->execute($dc->id,$varValue);

            // Check whether the CreditNumber exists
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

        $this->import('Database');
        $result = DB::getInstance()->prepare("SELECT `firstname`,`lastname`,`company` FROM `tl_member`  WHERE id=?")
            ->limit(1)
            ->execute($arrRow['member']);

        $row = $result->fetchAssoc();

        return '
		<div class="comment_wrap">
		<div class="cte_type status' . $arrRow['status'] . '"><strong>' . $arrRow['title'] . '</strong> '.$arrRow['credit_id_str'].'</div>
		<div>'.$GLOBALS['TL_LANG']['tl_iao_templates']['price_brutto'][0].': <strong>'.number_format($arrRow['price_brutto'],2,',','.').' '.$GLOBALS['TL_CONFIG']['currency_symbol'].'</strong></div>
		<div>'.$GLOBALS['TL_LANG']['tl_iao_templates']['member'][0].': '.$row['firstname'].' '.$row['lastname'].' ('.$row['company'].')</div>
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
            $this->toggleVisibility(\Input::get('tid'), (\Input::get('state')));
            $this->redirect($this->getReferer());
        }


        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['status']==1 ? 2 : 1);

        if ($row['status']==2)
        {
            $icon = 'logout.gif';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.$GLOBALS['TL_LANG']['tl_iao_templates']['toggle'].'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Disable/enable a user group
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
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_templates::status', 'alexf'))
        {
            $logger->log('Not enough permissions to publish/unpublish comment ID "'.$intId.'"', 'tl_iao_templates toggleActivity', TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new \Versions('tl_iao_templates', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iao_templates']['fields']['status']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_iao_templates']['fields']['status']['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }

        // Update the database
        DB::getInstance()->prepare("UPDATE tl_iao_templates SET status='" . ($blnVisible==1 ? '1' : '2') . "' WHERE id=?")
            ->execute($intId);

        $logger->create();
    }

}