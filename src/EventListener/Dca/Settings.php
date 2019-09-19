<?php
/**
 * Created by c4.pringitzhonig.de.
 * Developer: Sven Rhinow (sven@sr-tag.de)
 * Date: 19.09.19
 */

namespace Srhinow\ProjectManagerBundle\EventListener\Dca;


use Contao\DataContainer;
use Contao\Image;
use Iao\Backend\IaoBackend;

class Settings extends IaoBackend
{

    protected $settings = array();

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check permissions to edit table tl_iao_settings
     */
    public function checkPermission()
    {
        $this->checkIaoSettingsPermission('tl_iao_settings');
    }


    /**
     * Return the link picker wizard
     * @param DataContainer $dc
     * @return string
     */
    public function pagePicker(DataContainer $dc)
    {
        $strField = 'ctrl_' . $dc->field . ((\Contao\Input::get('act') == 'editAll') ? '_' . $dc->id : '');
        return ' ' . Image::getHtml('pickpage.gif', $GLOBALS['TL_LANG']['MSC']['pagepicker'], 'style="vertical-align:top; cursor:pointer;" onclick="Backend.pickPage(\'' . $strField . '\')"');
    }

}