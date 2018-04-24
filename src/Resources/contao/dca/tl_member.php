<?php
namespace Iao\Dca;

use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\MemberModel;
use Iao\Backend\IaoBackend;

/**
 * @copyright  Sven Rhinow 2011-2018
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

/**
 * Table tl_member
 */
$GLOBALS['TL_DCA']['tl_member']['palettes']['iao_customer']   =  '{import_settings:hide},myid;{personal_legend},title,firstname,lastname,dateOfBirth,gender;{address_legend:hide},company,street,postal,city,state,country;{custom_addresstext_legend:hide},text_generate,address_text;{contact_legend},phone,mobile,fax,email,website;{groups_legend},groups;{login_legend},login;{homedir_legend:hide},assignDir;{account_legend},disable,start,stop';

$GLOBALS['TL_DCA']['tl_member']['fields']['title'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_member']['title'],
	'exclude'                 => true,
	'search'                  => true,
	'sorting'                 => true,
	'flag'                    => 1,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>false, 'maxlength'=>255, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'personal'),
	'sql'					  => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['myid'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_member']['myid'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
	'sql'					  => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['iao_group'] = array
(
	'sql'					=> "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['text_generate'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['text_generate'],
    'flag'                    => 1,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr','submitOnChange'=>true),
    'save_callback' => array
    (
        array('Iao\Dca\IaoMember', 'fillAdressText')
    ),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['address_text'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['address_text'],
    'search'                  => true,
    'inputType'               => 'textarea',
    'eval'                    => array('rte'=>'tinyMCE','style'=>'height:60px;', 'tl_class'=>'clr'),
    'explanation'             => 'insertTags',
    'sql'					  => "mediumtext NULL"
);

/**
 * Class IaoMember
 * @package Iao\Dca
 */
class IaoMember
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
     * fill Adress-Text
     * @param $intMember int
     * @param DataContainer $dc
     * @return mixed
     */
    public function fillAdressText($varValue, DataContainer $dc)
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
