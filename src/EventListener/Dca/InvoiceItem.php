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

class InvoiceItem extends IaoBackend
{

    protected $settings = array();

    /**
     * InvoiceItems constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * add all iao-Settings in array
     */
    public function setIaoSettings()
    {
        $id = \Input::get('id');

        if($id)
        {
            $dbObj = DB::getInstance()->prepare('SELECT * FROM `tl_iao_invoice` WHERE `id`=?')
                ->limit(1)
                ->execute($id);

            $this->settings = ($dbObj->numRows > 0) ? $this->getSettings($dbObj->setting_id) : array();
        }
    }

    /**
     * @param $href
     * @param $label
     * @param $title
     * @param $class
     * @return string|void
     */
    public function showPDFButton($href, $label, $title, $class)
    {
        $objPdfTemplate = 	\FilesModel::findByUuid($this->settings['iao_invoice_pdf']);

        if(strlen($objPdfTemplate->path) < 1 || !file_exists(TL_ROOT . '/' . $objPdfTemplate->path) ) return;  // template file not found

        return '&nbsp; :: &nbsp;<a href="contao/main.php?do=iao_invoice&table=tl_iao_invoice&'.$href.'" title="'.specialchars($title).'" class="'.$class.'">'.$label.'</a> ';
    }

    /**
     * Check permissions to edit table tl_iao_invoice_items
     */
    public function checkPermission()
    {
        $this->checkIaoModulePermission('tl_iao_invoice_items');
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

            return '<div class="cte_type' . $key . $pagebreak . '"><strong>' . $arrRow['headline'] . '</strong></div>
		 	<div class="limit_height h100"><p>
		 	Netto: '.number_format($arrRow['price_netto'],2,',','.') .$GLOBALS['TL_CONFIG']['iao_currency_symbol'].'
		 	<br />Brutto: ' . number_format($arrRow['price_brutto'],2,',','.') .$GLOBALS['TL_CONFIG']['iao_currency_symbol']. ' (inkl. '.$arrRow['vat'].'% MwSt.)
		 	</p>
		 	'.$arrRow['text'].'
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
        if (!$dc->activeRecord)
        {
            return;
        }

        $itemObj = DB::getInstance()->prepare('SELECT `price`,`count`,`vat`,`vat_incl`,`operator` FROM `tl_iao_invoice_items` WHERE `pid`=? AND published =?')
            ->execute($dc->activeRecord->pid,1);


        if($itemObj->numRows > 0)
        {
            $allNetto = 0;
            $allBrutto = 0;

            while($itemObj->next())
            {
                $englprice = str_replace(',','.',$itemObj->price);
                $priceSum = $englprice * $itemObj->count;

                //if MwSt inclusive
                if($itemObj->vat_incl == 1)
                {
                    $Netto = $priceSum;
                    $Brutto = $this->getBruttoPrice($priceSum,$itemObj->vat);
                }
                else
                {
                    $Netto = $this->getNettoPrice($priceSum,$itemObj->vat);
                    $Brutto = $priceSum;
                }

                //which operator is set?
                if($itemObj->operator == '-')
                {
                    $allNetto -= $Netto;
                    $allBrutto -= $Brutto;
                }
                else
                {
                    $allNetto += $Netto;
                    $allBrutto += $Brutto;
                }

                DB::getInstance()->prepare('UPDATE `tl_iao_invoice` SET `price_netto`=?, `price_brutto`=? WHERE `id`=?')
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

        //von den Haupteinstellungen holen ob diese MwSt befreit ist, dann Brutto und Netto gleich setzen.
        $invoiceObj = DB::getInstance()->prepare('SELECT * FROM `tl_iao_invoice` WHERE `id`=?')
            ->limit(1)
            ->execute($dc->activeRecord->pid);

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

        if($invoiceObj->noVat)
        {
            $Netto = $englprice;
            $Brutto = $englprice;
        }

        $nettoSum = round($Netto,2) * $dc->activeRecord->count;
        $bruttoSum = round($Brutto,2) * $dc->activeRecord->count;

        DB::getInstance()->prepare('UPDATE `tl_iao_invoice_items` SET `price_netto`=?, `price_brutto`=? WHERE `id`=?')
            ->limit(1)
            ->execute($nettoSum, $bruttoSum, $dc->id);
    }

    /**
     * Return the link picker wizard
     * @param object
     * @return string
     */
    public function pagePicker(DataContainer $dc)
    {
        $strField = 'ctrl_' . $dc->field . (($this->Input->get('act') == 'editAll') ? '_' . $dc->id : '');
        return ' ' . Image::getHtml('pickpage.gif', $GLOBALS['TL_LANG']['MSC']['pagepicker'], 'style="vertical-align:top; cursor:pointer;" onclick="Backend.pickPage(\'' . $strField . '\')"');
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
        $User = User::getInstance();

        if (strlen(\Input::get('tid')))
        {
            $this->toggleVisibility(\Input::get('tid'), (\Input::get('state') == 1));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_invoice_items::published', 'alexf'))
        {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published'])
        {
            $icon = 'invisible.gif';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
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
        $this->checkPermission();

        // Check permissions to publish
        $User = User::getInstance();
        if (!$User->isAdmin && !$User->hasAccess('tl_iao_invoice_items::published', 'alexf'))
        {
            $logger = static::getContainer()->get('monolog.logger.contao');
            $logger->log('Not enough permissions to publish/unpublish event ID "'.$intId.'"', 'tl_iao_invoice_items toggleVisibility', TL_ERROR);

            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new \Versions('tl_iao_invoice_items', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iao_invoice_items']['fields']['published']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_iao_invoice_items']['fields']['published']['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }

        // Update the database
        DB::getInstance()->prepare("UPDATE tl_iao_invoice_items SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
            ->execute($intId);

        $objVersions->create();

        // Update the RSS feed (for some reason it does not work without sleep(1))
        sleep(1);
    }

    /**
     * Generate a button to put a posten-template for invoices
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function addPostenTemplate($row, $href, $label, $title, $icon, $attributes)
    {
        $User = User::getInstance();

        if (!$User->isAdmin) return '';

        if (\Input::get('key') == 'addPostenTemplate' && \Input::get('ptid') == $row['id'])
        {
            $result = DB::getInstance()->prepare('SELECT * FROM `tl_iao_invoice_items` WHERE `id`=?')
                ->limit(1)
                ->execute($row['id']);

            //Insert Invoice-Entry
            $postenset = array
            (
                'tstamp' => time(),
                'headline' => $result->headline,
                'headline_to_pdf' => $result->headline_to_pdf,
                'sorting' => $result->sorting,
                'date' => $result->date,
                'time' => $result->time,
                'text' => $result->text,
                'count' => $result->count,
                'amountStr' => $result->amountStr,
                'operator' => $result->operator,
                'price' => $result->price,
                'price_netto' => $result->price_netto,
                'price_brutto' => $result->price_brutto,
                'published' => $result->published,
                'vat' => $result->vat,
                'vat_incl' => $result->vat_incl,
                'position' => 'invoice',
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
     * get all invoice-posten-templates
     * @param DataContainer $dc
     * @return array
     */
    public function getPostenTemplate(DataContainer $dc)
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
     * fill Text before
     * @param $varValue
     * @param DataContainer $dc
     * @return mixed
     */
    public function fillPostenFields($varValue, DataContainer $dc)
    {

        if(strlen($varValue)<=0) return $varValue;

        //Posten-Template holen
        $postenTemplObj = $this->getTemplateObject('tl_iao_templates_items',$varValue);

        if($postenTemplObj->numRows > 0)
        {
            $headline = $this->changeIAOTags($postenTemplObj->headline,'agreement',(object) $postenTemplObj->row());
            $date = $postenTemplObj->date;
            $time = $postenTemplObj->time;
            $text = $this->changeIAOTags($postenTemplObj->text,'agreement',(object) $postenTemplObj->row());
        } else {
            $headline = $text = '';
            $time = $date = 0;
        }


        //Insert Invoice-Entry
        $postenset = array
        (
            'tstamp' => time(),
            'headline' => $headline,
            'headline_to_pdf' => $postenTemplObj->headline_to_pdf,
            'sorting' => $postenTemplObj->sorting,
            'date' => $date,
            'time' => $time,
            'text' => $text,
            'count' => $postenTemplObj->count,
            'price' => $postenTemplObj->price,
            'amountStr' => $postenTemplObj->amountStr,
            'operator' => $postenTemplObj->operator,
            'price_netto' => $postenTemplObj->price_netto,
            'price_brutto' => $postenTemplObj->price_brutto,
            'published' => $postenTemplObj->published,
            'vat' => $postenTemplObj->vat,
            'vat_incl' => $postenTemplObj->vat_incl,
            'posten_template'=>0
        );

        DB::getInstance()->prepare('UPDATE `tl_iao_invoice_items` %s WHERE `id`=?')
            ->set($postenset)
            ->execute($dc->id);

        $this->reload();

        return $varValue;
    }


    /**
     * calculate and update fields
     * @param DataContainer $dc
     */
    public function updateRemaining(DataContainer $dc)
    {
        $itemObj =	DB::getInstance()->prepare('SELECT SUM(`price_netto`) as `brutto_sum` FROM `tl_iao_invoice_items` WHERE `pid`=? AND `published`=?')
            ->execute($dc->activeRecord->pid, 1);

        $sumObj = $itemObj->fetchRow();

        if($itemObj->numRows > 0)
        {
            $parentObj = DB::getInstance()->prepare('SELECT `paid_on_dates` FROM `tl_iao_invoice` WHERE `id`=?')
                ->limit(1)
                ->execute($dc->activeRecord->pid);

            $paidsArr = unserialize($parentObj->paid_on_dates);
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

            $set = array(
                'remaining' => $dif,
                'status' => $status,
                'paid_on_date' => $paid_on_date
            );

            DB::getInstance()->prepare('UPDATE `tl_iao_invoice` %s WHERE `id`=?')
                ->set($set)
                ->execute($dc->id);
        }
    }

}