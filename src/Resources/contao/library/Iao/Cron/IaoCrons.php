<?php
namespace Iao\Cron;

/**
 * @copyright  Sven Rhinow 2011-2018
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

use Contao\Database;
use Contao\Email;
use Contao\Frontend;

/**
 * Class IaoCrons
 * @package Iao\Cron
 */
class IaoCrons extends Frontend
{

	public function sendAgreementRemindEmail()
	{

		$agrObj = Database::getInstance()->prepare('SELECT * FROM `tl_iao_agreements` WHERE `sendEmail`=? AND `email_date`=?')
					->execute(1,'');

		if($agrObj->numRows > 0)
		{
			$today = time();
			while($agrObj->next())
			{
				$beforeTime = strtotime($agrObj->remind_before,$agrObj->end_date);

				if($today >= $beforeTime)
				{
					//send email
					$email = new Email();
					$email->from = $agrObj->email_from;
					$email->subject = $agrObj->email_subject;
					$email->text = $agrObj->email_text;
					if($email->sendTo($agrObj->email_to))
					{
						//set this item that reminder is allready send
						$set = array
						(
							'email_date' => $today
						);

						Database::getInstance()->prepare('UPDATE `tl_iao_agreements` %s WHERE `id`=?')
							->set($set)
							->execute($agrObj->id);

                        $logger = static::getContainer()->get('monolog.logger.contao');
                        $logger->log('Vertrag-Erinnerung von '.$agrObj->title.' gesendet','iaoCrons sendAgreementRemindEmail()','CRON');
					}
				}
			}
		}
	}
}
