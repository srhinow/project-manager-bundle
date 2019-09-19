<?php
/**
 * Created by c4.pringitzhonig.de.
 * Developer: Sven Rhinow (sven@sr-tag.de)
 * Date: 19.09.19
 */

namespace Srhinow\ProjectManagerBundle\EventListener\Dca;


use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Iao\Backend\IaoBackend;

class Member
{
    /**
     * @param DataContainer $dc
     */
    public function setCustomerGroup(DataContainer $dc)
    {
        $this->settings = IaoBackend::getInstance()->getSettings();
        // Return if there is no active record (override all)
        if (!$dc->activeRecord || $dc->id == 0)
        {
            return;
        }
        Database::getInstance()->prepare("UPDATE tl_member SET iao_group=? WHERE id=?")
            ->execute($this->settings['iao_costumer_group'],$dc->id);
    }

    /**
     * fill Address-Text
     * @param $intMember int
     * @param DataContainer $dc
     * @return mixed
     */
    public function fillAddressText($varValue, DataContainer $dc)
    {
//        print_r($varValue); exit();

        if($varValue == 1) {

            $text = '<p>'.$dc->activeRecord->company.'<br />'.($dc->activeRecord->gender!='' ? $GLOBALS['TL_LANG']['tl_iao']['gender'][$dc->activeRecord->gender].' ':'').($dc->activeRecord->title ? $dc->activeRecord->title.' ':'').$dc->activeRecord->firstname.' '.$dc->activeRecord->lastname.'<br />'.$dc->activeRecord->street.'</p>';
            $text .='<p>'.$dc->activeRecord->postal.' '.$dc->activeRecord->city.'</p>';

            $set = array(
                'address_text' => $text,
                'text_generate' => ''
            );

            Database::getInstance()->prepare('UPDATE `tl_member` %s WHERE `id`=?')
                ->set($set)
                ->limit(1)
                ->execute($dc->id);

            Controller::reload();
        }
        return $varValue;
    }

}