<?php
namespace Iao\Backend\Reminder;

/**
 * @copyright  Sven Rhinow 2011-2018
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

use Iao\Backend\IaoBackend;
use Contao\Database;

/**
 * Class Reminder
 * @package Iao\Backend\Reminder
 */
class Reminder extends IaoBackend
{
	/**
	 * check all Invoices of reminder
	 */
	public function checkReminder()
	{
        $Database = Database::getInstance();

		//get all invoices where is active, not paid and have not reminder
		$invoiceObj = $Database->prepare('SELECT * FROM `tl_iao_invoice` WHERE `status`=? AND `published`=? AND `expiry_date`<?')
										->execute(1,1,time());

		if($invoiceObj->numRows > 0)
		{
			while($invoiceObj->next())
			{
			    // kontrollieren ob es wirklich diese Erinnerung gibt
                $objCheckReminder = $Database->prepare('SELECT * FROM tl_iao_reminder WHERE id=?')
                ->limit(1)
                ->execute((int) $invoiceObj->reminder_id);

//                if($invoiceObj->id == 24) { print_r($objCheckReminder->numRows); exit();}

                if($objCheckReminder->numRows < 1)
				{
                    $set = array
					(
						'invoice_id' => $invoiceObj->id,
						'status' => $invoiceObj->status,
						'tstamp' => time()
					);
					$reminderID = $Database->prepare("INSERT INTO `tl_iao_reminder` %s")->set($set)->execute()->insertId;

				}
				else
				{
					$reminderID = $invoiceObj->reminder_id;
				}

				$reminderObj = Database::getInstance()->prepare('SELECT * FROM `tl_iao_reminder` WHERE `id`=?')
											->limit(1)
						 					->execute($reminderID);


				// only the invoices in past
				if($reminderObj->periode_date > time()) continue;

				// drop all where step > 3. Mahnung
				if($reminderObj->step == 4) continue;

				// drop all where status = 2
				if($reminderObj->status == 2) continue;

				$this->fillReminderFields($invoiceObj->id, $reminderObj);

			}
		}

		\Message::addConfirmation($GLOBALS['TL_LANG']['tl_iao_reminder']['Reminder_is_checked']);

		setcookie('BE_PAGE_OFFSET', 0, 0, '/');
		$this->redirect(str_replace('&key=checkReminder', '', $this->Environment->request));
	}
}
