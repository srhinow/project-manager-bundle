<?php
/**
 * Created by c4.pringitzhonig.de.
 * Developer: Sven Rhinow (sven@sr-tag.de)
 * Date: 19.09.19
 */

namespace Srhinow\ProjectManagerBundle\EventListener\Dca;


use Iao\Backend\IaoBackend;

class TaxRate extends IaoBackend
{
    /**
     * TaxRates constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check permissions to edit table tl_iao_tax_rates
     */
    public function checkPermission()
    {
        $this->checkIaoSettingsPermission('tl_iao_tax_rates');
    }

    /**
     * List a particular record
     * @param array
     * @return string
     */
    public function listEntries($arrRow)
    {
        $return = $arrRow['name'];
        if($arrRow['default_value']) $return .= ' <span style="color:#b3b3b3; padding-left:3px;">[Standart]</span>';

        return $return;
    }
}