<?php

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Iao\Backend;

use Contao\BackendUser as User;
use Contao\Database as DB;
use Contao\CoreBundle\Session;
use Contao\DataContainer;
use Contao\MemberModel;
use Iao\Iao;
use Srhinow\IaoProjectsModel;
use Exception;

/**
 * Class iaoBackend
 *
 * Parent class for iaoBackend modules.
 * @copyright  Sven Rhinow 2011-2017
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 */
abstract class IaoBackend extends Iao
{
	/**
	* check permissions for dca-modules
	* @param string
	*/
	public function checkIaoModulePermission($table)
	{
		$User = User::getInstance();
        $logger = static::getContainer()->get('monolog.logger.contao');

        $objUser = DB::getInstance()->prepare("SELECT iaomodules, iaomodulep FROM tl_user WHERE id=?")
            ->limit(1)
            ->execute($User->id);

	    if ($User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (!is_array($objUser->iaomodules) || count($objUser->iaomodules) < 1)
		{
			$root = array(0);
		}
		else
		{
			$root = $objUser->iaomodules;
		}

		$GLOBALS['TL_DCA'][$table]['list']['sorting']['root'] = $root;

		// Check permissions to add archives
		if (!$User->hasAccess('create', 'newp'))
		{
			$GLOBALS['TL_DCA'][$table]['config']['closed'] = true;
		}

		// Check current action
		switch (\Input::get('act'))
		{
			case 'create':
			case 'select':
				// Allow
			    break;

			case 'edit':
				// Dynamically add the record to the user profile
				if (!in_array(\Input::get('id'), $root))
				{
					$arrNew = $this->Session->get('new_records');

					if (is_array($arrNew[$table]) && in_array(\Input::get('id'), $arrNew[$table]))
					{
						// Add permissions on user level
						if ($User->inherit == 'custom' || !$User->groups[0])
						{

							$arrModulep = deserialize($objUser->iaomodulep);

							if (is_array($arrModulep) && in_array('create', $arrModulep))
							{
								$arrModules = deserialize($objUser->iaomodules);
								$arrModules[] = \Input::get('id');

								DB::getInstance()->prepare("UPDATE tl_user SET iaomodules=? WHERE id=?")
											   ->execute(serialize($arrModules), $User->id);
							}
						}

						// Add permissions on group level
						elseif ($User->groups[0] > 0)
						{
							$objGroup = DB::getInstance()->prepare("SELECT iaomodules, iaomodulep FROM tl_user_group WHERE id=?")
													   ->limit(1)
													   ->execute($User->groups[0]);

							$arrModulep = deserialize($objGroup->iaomodulep);

							if (is_array($arrModulep) && in_array('create', $arrModulep))
							{
								$arrModules = deserialize($objGroup->iaomodules);
								$arrModules[] = \Input::get('id');

								DB::getInstance()->prepare("UPDATE tl_user_group SET iaomodules=? WHERE id=?")
											   ->execute(serialize($arrModules), $User->groups[0]);
							}
						}

						// Add new element to the user object
						$root[] = \Input::get('id');
						$User->iaomodules = $root;
					}
				}
				// No break;

			case 'copy':
			case 'delete':
			case 'show':
				if (!in_array(\Input::get('id'), $root) || (\Input::get('act') == 'delete' && !$User->hasAccess('delete', 'iaomodulep')))
				{
                    $logger->log('Not enough permissions to '.\Input::get('act').' iao module ID "'.\Input::get('id').'"', $table.' checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}
			break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
				$session = $this->Session->getData();
				if (\Input::get('act') == 'deleteAll' && !$User->hasAccess('delete', 'iaomodulep'))
				{
					$session['CURRENT']['IDS'] = array();
				}
				else
				{
					$session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $root);
				}
				$this->Session->setData($session);
			break;

			default:
				if (strlen(\Input::get('act')))
				{
                    $logger->log('Not enough permissions to '.\Input::get('act').' iao modules', $table.' checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}
			break;
		}
	}

	/**
	* check permissions for dca-settings
	* @param string
	*/
	public function checkIaoSettingsPermission($table)
	{
        $User = User::getInstance();
        $logger = static::getContainer()->get('monolog.logger.contao');

        $objUser = DB::getInstance()->prepare("SELECT iaomodules, iaomodulep FROM tl_user WHERE id=?")
            ->limit(1)
            ->execute($User->id);

        if ($User->isAdmin)
        {
            return;
        }

        // Set root IDs
        if (!is_array($objUser->iaomodules) || count($objUser->iaomodules) < 1)
        {
            $root = array(0);
        }
        else
        {
            $root = $objUser->iaomodules;
        }
		// Check permissions to add archives
		if (!$User->hasAccess('create', 'newp'))
		{
			$GLOBALS['TL_DCA'][$table]['config']['closed'] = true;
		}

		// Check current action
		switch (\Input::get('act'))
		{
			case 'create':
			case 'select':
				// Allow
			break;

			case 'edit':
				// Dynamically add the record to the user profile
				if (!in_array(\Input::get('id'), $root))
				{
					$arrNew = $this->Session->get('new_records');

					if (is_array($arrNew[$table]) && in_array(\Input::get('id'), $arrNew[$table]))
					{
						// Add permissions on user level
						if ($User->inherit == 'custom' || !$User->groups[0])
						{
							$objUser = DB::getInstance()->prepare("SELECT iaosettings, iaosettingp FROM tl_user WHERE id=?")
							->limit(1)
							->execute($User->id);

							$arrModulep = deserialize($objUser->iaosettingp);

							if (is_array($arrModulep) && in_array('create', $arrModulep))
							{
								$arrModules = deserialize($objUser->iaosettings);
								$arrModules[] = \Input::get('id');

								DB::getInstance()->prepare("UPDATE tl_user SET iaosettings=? WHERE id=?")
                                    ->execute(serialize($arrModules), $User->id);
							}
						}

						// Add permissions on group level
						elseif ($User->groups[0] > 0)
						{
							$objGroup = DB::getInstance()->prepare("SELECT iaosettings, iaosettingp FROM tl_user_group WHERE id=?")
													   ->limit(1)
													   ->execute($User->groups[0]);

							$arrModulep = deserialize($objGroup->iaosettingp);

							if (is_array($arrModulep) && in_array('create', $arrModulep))
							{
								$arrModules = deserialize($objGroup->iaosettings);
								$arrModules[] = \Input::get('id');

								DB::getInstance()->prepare("UPDATE tl_user_group SET iaosettings=? WHERE id=?")
											   ->execute(serialize($arrModules), $User->groups[0]);
							}
						}

						// Add new element to the user object
						$root[] = \Input::get('id');
                        $User->iaosettings = $root;
					}
				}
				// No break;

			case 'copy':
			case 'delete':
			case 'show':
				if (!in_array(\Input::get('id'), $root) || (\Input::get('act') == 'delete' && !$User->hasAccess('delete', 'iaosettingp')))
				{
                    $logger->log('Not enough permissions to '.\Input::get('act').' iao module ID "'.\Input::get('id').'"', $table.' checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}
			break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
				$session = $this->Session->getData();
				if (\Input::get('act') == 'deleteAll' && !$User->hasAccess('delete', 'iaosettingp'))
				{
					$session['CURRENT']['IDS'] = array();
				}
				else
				{
					$session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $root);
				}
				$this->Session->setData($session);
			break;

			default:
				if (strlen(\Input::get('act')))
				{
                    $logger->log('Not enough permissions to '.\Input::get('act').' iao modules', $table.' checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}
			break;
		}
	}

	/**
	 * get options for tax rates
	 * @param object
	 * @return array
	 */
	public function getTaxRatesOptions($dc)
	{
		$varValue= array();

		$all = DB::getInstance()->prepare('SELECT `value`,`name` FROM `tl_iao_tax_rates`  ORDER BY `sorting` ASC')
				->execute();

		while($all->next())
		{
			$varValue[$all->value] = $all->name;
		}

		return $varValue;
	}

	/**
	 * get options for item units
	 * @param object
	 * @return array
	 */
	public function getItemUnitsOptions(DataContainer $dc)
	{
		$varValue= array();

		$all = DB::getInstance()->prepare('SELECT `value`,`name` FROM `tl_iao_item_units`  ORDER BY `sorting` ASC')
				->execute();

		while($all->next())
		{
			$varValue[$all->value] = $all->name;
		}
		return $varValue;
	}	

	/**
	 * get all members to valid groups
	 * @param object
	 * @return array
	 */
	public function getMemberOptions(DataContainer $dc)
	{
		//fallback
		$setId = ($dc->activeRecord->setting_id)?:1;
		$settings = $this->getSettings($setId);
		$varValue= array();

		if(!$settings['iao_costumer_group'])  return $varValue;

		$member = DB::getInstance()->prepare('SELECT `id`,`groups`,`firstname`,`lastname`,`company` FROM `tl_member` WHERE `iao_group`=?')
						->execute($settings['iao_costumer_group']);

		while($member->next())
		{
			$varValue[$member->id] =  $member->firstname.' '.$member->lastname.' ('.$member->company.')';
		}

		return $varValue;
	}

	/**
	 * get all settings as select-option-values
	 * @param object
	 * @return array
	 */
	public function getSettingOptions(DataContainer $dc)
	{
		$varValue= array();

		$settings = DB::getInstance()->prepare('SELECT `id`,`name` FROM `tl_iao_settings` ORDER BY `fallback` DESC, `name` DESC')
						 ->execute();

		while($settings->next())
		{
			$varValue[$settings->id] =  $settings->name;
		}

		return $varValue;
	}

	/**
	 * get all projects as select-option-values
	 * @param object
	 * @return array
	 */
	public function getProjectOptions($dc)
	{
		$varValue= array();

		$settings = DB::getInstance()->prepare('SELECT `id`,`name` FROM `tl_iao_projects`')->execute();

		while($settings->next())
		{
			$varValue[$settings->id] =  $settings->name;
		}

		return $varValue;
	}

    /**
     * fill Reminderfields
     * @param int $invoiceID
     * @param $reminderObj
     */
	public function fillReminderFields($invoiceID = 0, $reminderObj)
	{
        $settings = [];

		$objMember = DB::getInstance()->prepare('SELECT `m`.*,`i`.`price_brutto`,`i`.`address_text`,`i`.`invoice_id_str` FROM `tl_iao_invoice` as `i` LEFT JOIN `tl_member` as `m` ON `i`.member = `m`.`id` WHERE `i`.`id`=?')
									->limit(1)
									->execute($invoiceID);

		if(!empty($objMember->address_text))
		{
			$address_text = $objMember->address_text;
		}
		else
		{
			$address_text = '<p>'.$objMember->company.'<br />'.($objMember->gender!='' ? $GLOBALS['TL_LANG']['tl_iao_reminder']['gender'][$objMember->gender].' ':'').($objMember->title ? $objMember->title.' ':'').$objMember->firstname.' '.$objMember->lastname.'<br />'.$objMember->street.'</p>';
			$address_text .='<p>'.$objMember->postal.' '.$objMember->city.'</p>';
		}

		$testStepObj = DB::getInstance()->prepare('SELECT `step`,`sum` FROM `tl_iao_reminder` WHERE `invoice_id`=? AND `id`!=? ORDER BY `id` DESC')
										->limit(1)
										->execute($invoiceID,$reminderObj->id);
//        print $testStepObj->numRows; exit();
		$newStep = ($testStepObj->numRows > 0) ? (int) $testStepObj->step +1 : 1;

		//set an error if newStep > 4
		if($newStep > 4)
		{
			$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['tl_iao_reminder']['to_much_steps'].$objMember->invoice_id_str;
			$_SESSION['TL_CONFIRM'] = '';
			setcookie('BE_PAGE_OFFSET', 0, 0, '/');
			$this->redirect(str_replace('&act=create', '', $this->Environment->request));
		}

		$newUnpaid = (($testStepObj->numRows > 0) && ((int) $testStepObj->sum > 0)) ? $testStepObj->sum : $objMember->price_brutto;
		$tax =  (float) $settings['iao_reminder_'.$newStep.'_tax'];
		$postage = (float) $settings['iao_reminder_'.$newStep.'_postage'];
		$periode_date = (int) $this->getPeriodeDate($reminderObj);

		$set = array
		(
			'title' => $GLOBALS['TL_LANG']['tl_iao_reminder']['steps'][$newStep].'::'.$invoiceID,
			'address_text' => $address_text,
			'member' =>  $objMember->id,
			'unpaid' => $newUnpaid,
			'step' => $newStep,
			'text' => $settings['iao_reminder_'.$newStep.'_text'],
			'periode_date' => $periode_date,
			'tax' => $tax,
			'postage' =>  $postage
		);

		DB::getInstance()->prepare('UPDATE `tl_iao_reminder` %s WHERE `id`=?')
						->set($set)
						->execute($reminderObj->id);

		//set sum after other facts is saved
		$text_finish = $this->changeIAOTags($settings['iao_reminder_'.$newStep.'_text'],'reminder',$reminderObj);
		$text_finish = $this->changeTags($text_finish);

		$set = array
		(
	    	'sum' => $this->getReminderSum($reminderObj->id),
	    	'text_finish' => $text_finish
		);

		DB::getInstance()->prepare('UPDATE `tl_iao_reminder` %s WHERE `id`=?')
						->set($set)
						->execute($reminderObj->id);

		//update invoice-data with current reminder-step
		DB::getInstance()->prepare('UPDATE `tl_iao_invoice` SET `reminder_id` = ?  WHERE `id`=?')
						->execute($reminderObj->id, $invoiceID);
	}

	/**
	* if GET-Param projonly then fill member and address-field
	* @param string
	* @param integer
	* @param array
	* @param object
	*/
	public function setMemmberfieldsFromProject($table, $id, $set, $obj)
	{
		if(\Input::get('onlyproj') == 1 && (int) $set['pid'] > 0)
		{
			$objProject = iaoProjectsModel::findProjectByIdOrAlias($set['pid']);

			if($objProject !== null)
			{
				$objMember = MemberModel::findById($objProject->member);

				$text = '<p>'.$objMember->company.'<br />'.($objMember->gender!='' ? $GLOBALS['TL_LANG']['tl_iao_invoice']['gender'][$objMember->gender].' ':'').($objMember->title ? $objMember->title.' ':'').$objMember->firstname.' '.$objMember->lastname.'<br />'.$objMember->street.'</p>';
				$text .='<p>'.$objMember->postal.' '.$objMember->city.'</p>';

				$set = array
				(
					'member' => $objProject->member,
					'address_text' => $text
				);

				DB::getInstance()->prepare('UPDATE '.$table.' %s WHERE `id`=?')
								->set($set)
								->limit(1)
								->execute($id);
			}
		}
	}

    /**
     * generate a Invoice-number-string if not set
     * @param integer
     * @param integer
     * @param array
     * @return string
     */
    public function generateInvoiceNumberStr($invoiceId, $tstamp, $settings)
    {
            $format = $settings['iao_invoice_number_format'];
            $format =  str_replace('{date}',date('Ymd',$tstamp),$format);
            $format =  str_replace('{nr}',$invoiceId, $format);

            return $format;
    }

    /**
     * generate a invoice-number if not set
     * @param integer
     * @param array
     * @return string
     */
    public function generateInvoiceNumber($varValue, $settings)
    {
        $autoNr = false;
        $varValue = (int) $varValue;
        $id = \Input::get('id');

        // Generate invoice_id if there is none
        if((int) $varValue == 0)
        {
            $objNr = DB::getInstance()->prepare("SELECT `invoice_id` FROM `tl_iao_invoice` ORDER BY `invoice_id` DESC")
                ->limit(1)
                ->execute();

            $varValue = ($objNr->numRows < 1 || $objNr->invoice_id == 0)? $settings['iao_invoice_startnumber'] : $objNr->invoice_id +1;
        }
        else
        {
            $objNr = DB::getInstance()->prepare("SELECT `invoice_id` FROM `tl_iao_invoice` WHERE `id`=? OR `invoice_id`=?")
                ->limit(1)
                ->execute($id, $varValue);

            // Check whether the InvoiceNumber exists
            if ($objNr->numRows > 1 )
            {
                if (!$autoNr)
                {
                    throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
                }

                $varValue .= '-' . $id;
            }
        }
        return $varValue;
    }

    /**
     * genriert HTML für das Anschrift-Feld für Rechnung, Angebot,Gutschrift etc.
     * @param $intMember
     * @return string
     */
    public function getAdressText($intMember) {

        $text = '';
        if((int) $intMember < 1) return $text;

        $objMember = MemberModel::findById($intMember);

        $text = '<p>'.$objMember->company.'<br />'.($objMember->gender!='' ? $GLOBALS['TL_LANG']['tl_iao']['gender'][$objMember->gender].' ':'').($objMember->title ? $objMember->title.' ':'').$objMember->firstname.' '.$objMember->lastname.'<br />'.$objMember->street.'</p>';
        $text .='<p>'.$objMember->postal.' '.$objMember->city.'</p>';

        return $text;
    }
}
