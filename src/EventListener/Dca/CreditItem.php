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

class CreditItem extends IaoBackend
{

    protected $settings = array();

    /**
     * Iao\Dca\CreditItems constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * add all iao-Settings in array
     */
    public function setIaoSettings(DataContainer $dc)
    {
        if($dc->id)
        {
            $dbObj = DB::getInstance()->prepare('SELECT `p`.* FROM `tl_iao_credit` `p` LEFT JOIN `tl_iao_credit_items` `i` ON `p`.`id`= `i`.`pid` WHERE `i`.`id`=?')
                ->limit(1)
                ->execute($dc->id);

            $this->settings = ($dbObj->numRows > 0) ? $this->getSettings($dbObj->setting_id) : array();
        }
    }

    /**
     * @param $href
     * @param $label
     * @param $title
     * @param $class
     * @return string
     */
    public function showPDFButton($href, $label, $title, $class)
    {
        return '&nbsp; :: &nbsp;<a href="contao/main.php?do=iao_credit&table=tl_iao_credit&'.$href.'" title="'.specialchars($title).'" class="'.$class.'">'.$label.'</a> ';
    }

    /**
     * Check permissions to edit table Iao\Dca\CreditItems
     */
    public function checkPermission()
    {
        $this->checkIaoModulePermission('Iao\Dca\CreditItems');
    }

    /**
     * Add the type of input field
     * @param array
     * @return string
     */
    public function listItems($arrRow)
    {
        if($arrRow['type']=='devider')
        {
            return '<div class="pdf-devider"><span>PDF-Trenner</span></div>';
        }
        else
        {
            $time = time();
            $key = ($arrRow['published']) ? ' published' : ' unpublished';
            $vat = ($arrRow['vat_incl']==1) ? 'netto' : 'brutto';
            $pagebreak = ($arrRow['pagebreak_after']==1) ? ' pagebreak' : '';

            return '<div class="cte_type' . $key . $pagebreak . '">
            <strong>' . $arrRow['headline'] . '</strong>
            <br />Netto: '.number_format($arrRow['price_netto'],2,',','.') .$this->settings['iao_currency_symbol'].'
            <br />Brutto: ' . number_format($arrRow['price_brutto'],2,',','.') .$this->settings['iao_currency_symbol']. ' (inkl. '.$arrRow['vat'].'% MwSt.)
            <br />'.$arrRow['text'].'
            </div>' . "\n";
        }
    }

    /**
     * save the price from all items in parent_table
     * @param mixed
     * @param object
     * @return string
     */
    public function saveAllPricesToParent(DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord) return;

        $itemObj = DB::getInstance()->prepare('SELECT `price`,`count`,`vat`,`vat_incl` FROM `tl_iao_credit_items` WHERE `pid`=? AND published =?')
            ->execute($dc->activeRecord->pid,1);

        if($itemObj->numRows > 0)
        {
            $allNetto = 0;
            $allBrutto = 0;

            while($itemObj->next())
            {
                $englprice = str_replace(',','.',$itemObj->price);
                $priceSum = $englprice * $itemObj->count;

                if($itemObj->vat_incl == 1)
                {
                    $allNetto += $priceSum;
                    $allBrutto += $this->getBruttoPrice($priceSum,$itemObj->vat);
                }
                else
                {
                    $allNetto += $this->getNettoPrice($priceSum,$itemObj->vat);
                    $allBrutto += $priceSum;
                }

                DB::getInstance()->prepare('UPDATE `tl_iao_credit` SET `price_netto`=?, `price_brutto`=? WHERE `id`=?')
                    ->limit(1)
                    ->execute($allNetto, $allBrutto, $dc->activeRecord->pid);

            }
        }
    }

    /**
     * save the price_netto and price_brutto from actuell item
     * @param mixed
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
        $priceSum = $englprice * $dc->activeRecord->count;

        $Netto = 0;
        $Brutto = 0;

        if($dc->activeRecord->vat_incl == 1)
        {
            $Netto = $priceSum;
            $Brutto = $this->getBruttoPrice($priceSum,$dc->activeRecord->vat);
        }
        else
        {
            $Netto = $this->getNettoPrice($priceSum,$dc->activeRecord->vat);
            $Brutto = $priceSum;
        }

        DB::getInstance()->prepare('UPDATE `tl_iao_credit_items` SET `price_netto`=?, `price_brutto`=? WHERE `id`=?')
            ->limit(1)
            ->execute($Netto, $Brutto, $dc->id);
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
            $this->toggleVisibility($this->Input->get('tid'), ($this->Input->get('state') == 1));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        $User = User::getInstance();
        if (!$User->isAdmin && !$User->hasAccess('Iao\Dca\CreditItems::published', 'alexf'))
        {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published'])
        {
            $icon = 'invisible.gif';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
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
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_credit_items::published', 'alexf'))
        {
            $logger = static::getContainer()->get('monolog.logger.contao');
            $logger->log('Not enough permissions to publish/unpublish event ID "'.$intId.'"', 'Iao\Dca\CreditItems toggleVisibility', TL_ERROR);

            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new \Versions('tl_iao_credit_items', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iao_credit_items']['fields']['published']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_iao_credit_items']['fields']['published']['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }

        // Update the database
        DB::getInstance()->prepare("UPDATE tl_iao_credit_items SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
            ->execute($intId);

        $objVersions->create();

        // Update the RSS feed (for some reason it does not work without sleep(1))
        sleep(1);

    }

    /**
     * Generate a button to put a posten-template for credit
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function addPostenTemplate($row, $href, $label, $title, $icon, $attributes)
    {

        if (!User::getInstance()->isAdmin)
        {
            return '';
        }

        if (\Input::get('key') == 'addPostenTemplate' && \Input::get('ptid') == $row['id'])
        {
            $result = DB::getInstance()->prepare('SELECT * FROM `tl_iao_credit_items` WHERE `id`=?')
                ->limit(1)
                ->execute($row['id']);

            //Insert Invoice-Entry
            $postenset = array (
                'tstamp' => time(),
                'headline' => $result->headline,
                'headline_to_pdf' => $result->headline_to_pdf,
                'sorting' => $result->sorting,
                'date' => $result->date,
                'time' => $result->time,
                'text' => $result->text,
                'count' => $result->count,
                'price' => $result->price,
                'amountStr' => $result->amountStr,
                'operator' => $result->operator,
                'price_netto' => $result->price_netto,
                'price_brutto' => $result->price_brutto,
                'published' => $result->published,
                'vat' => $result->vat,
                'vat_incl' => $result->vat_incl,
                'position' => 'credit',
            );

            $newposten = DB::getInstance()->prepare('INSERT INTO `tl_iao_templates_items` %s')
                ->set($postenset)
                ->execute();

            $newPostenID = $newposten->insertId;

            $this->redirect('contao/main.php?do=iao_setup&mod=iao_templates_items&table=tl_iao_templates_items&act=edit&id='.$newPostenID);
        }

        $href.='&amp;ptid='.$row['id'];
        return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * get all credit-posten-templates
     * @param DataContainer $dc
     * @return array
     */
    public function getPostenTemplate(DataContainer $dc)
    {
        $varValue= array();

        $all = DB::getInstance()->prepare('SELECT `id`,`headline` FROM `tl_iao_templates_items` WHERE `position`=?')
            ->execute('credit');

        while($all->next())
        {
            $varValue[$all->id] = $all->headline;
        }

        return $varValue;
    }

    /**
     * fill Text before
     * @param $varValue
     * @param DataContainer $dc
     * @return mixed
     */

    public function fillPostenFields($varValue, DataContainer $dc)
    {

        if(strlen($varValue)<=0) return $varValue;

        $result = DB::getInstance()->prepare('SELECT * FROM `tl_iao_templates_items` WHERE `id`=?')
            ->limit(1)
            ->execute($varValue);

        //Insert Invoice-Entry
        $postenset = array (
            'tstamp' => time(),
            'headline' => $result->headline,
            'headline_to_pdf' => $result->headline_to_pdf,
            'sorting' => $result->sorting,
            'author' => $result->author,
            'date' => $result->date,
            'time' => $result->time,
            'text' => $result->text,
            'count' => $result->count,
            'price' => $result->price,
            'amountStr' => $result->amountStr,
            'operator' => $result->operator,
            'price_netto' => $result->price_netto,
            'price_brutto' => $result->price_brutto,
            'published' => $result->published,
            'vat' => $result->vat,
            'vat_incl' => $result->vat_incl
        );

        DB::getInstance()->prepare('UPDATE `tl_iao_credit_items` %s WHERE `id`=?')
            ->set($postenset)
            ->execute($dc->id);

        $this->reload();

        return $varValue;
    }

}