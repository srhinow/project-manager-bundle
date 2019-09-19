<?php
/**
 * @copyright  Sven Rhinow 2011-2019
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */
namespace Srhinow\ProjectManagerBundle\EventListener\Cron;

use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Email;
use Contao\Frontend;
use Psr\Log\LogLevel;
use Srhinow\IaoAgreementsModel;

/**
 * Class DailyCron
 */
class CronListener
{

	public function sendAgreementRemindEmail()
	{
        $agrObj = IaoAgreementsModel::findBy('sendEmail',1);
        if(null === $agrObj) return;

        $today = time();
        while($agrObj->next())
        {
            //Erinnerungs-Zeitstempel aus Einstellungen generieren
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
                    $logger = Controller::getContainer()->get('monolog.logger.contao');
                    $logger->log(LogLevel::NOTICE,'Vertrag-Erinnerung von '.$agrObj->title.' gesendet', array('contao' => new ContaoContext('sendAgreementRemindEmail', 'CRON')));
                }
            }
        }
	}
}
