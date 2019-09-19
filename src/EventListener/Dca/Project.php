<?php
/**
 * Created by c4.pringitzhonig.de.
 * Developer: Sven Rhinow (sven@sr-tag.de)
 * Date: 19.09.19
 */

namespace Srhinow\ProjectManagerBundle\EventListener\Dca;


use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\User;
use Iao\Backend\IaoBackend;

class Project extends IaoBackend
{
    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check permissions to edit table tl_iao_projects
     */
    public function checkPermission()
    {
        $this->checkIaoModulePermission('tl_iao_projects');
    }

    /**
     * Return the edit header button
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function editHeader($row, $href, $label, $title, $icon, $attributes)
    {
        return (User::getInstance()->isAdmin || count(preg_grep('/^tl_iao_projects::/', User::getInstance()->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : '';
    }

    /**
     * List a particular record
     * @param array
     * @return string
     */
    public function listEntries($arrRow)
    {
        $result = Database::getInstance()->prepare("SELECT `firstname`,`lastname`,`company` FROM `tl_member`  WHERE id=?")
            ->limit(1)
            ->execute($arrRow['member']);

        $row = $result->fetchAssoc();

        return '
		<div class="comment_wrap">
		<div class="cte_type status' . $arrRow['status'] . '"><strong>' . $arrRow['title'] . '</strong> '.$arrRow['credit_id_str'].'</div>
		<div>'.$GLOBALS['TL_LANG']['tl_iao_projects']['price_brutto'][0].': <strong>'.number_format($arrRow['price_brutto'],2,',','.').' '.$GLOBALS['TL_CONFIG']['currency_symbol'].'</strong></div>
		<div>'.$GLOBALS['TL_LANG']['tl_iao_projects']['member'][0].': '.$row['firstname'].' '.$row['lastname'].' ('.$row['company'].')</div>
		'.(($arrRow['notice'])?"<div>".$GLOBALS['TL_LANG']['tl_iao_projects']['notice'][0].":".$arrRow['notice']."</div>": '').'
		</div>' . "\n    ";
    }

    /**
     * Auto-generate the reference alias if it has not been set yet
     *
     * @param mixed         $varValue
     * @param DataContainer $dc
     *
     * @return string
     *
     * @throws Exception
     */
    public function generateReferenceAlias($varValue, DataContainer $dc)
    {
        $autoAlias = false;

        // Generate alias if there is none
        if ($varValue == '')
        {
            $autoAlias = true;
            $varValue = StringUtil::generateAlias($dc->activeRecord->reference_title);
        }

        $objAlias = Database::getInstance()->prepare("SELECT id FROM tl_iao_projects WHERE reference_alias=? AND id!=?")
            ->execute($varValue, $dc->id);

        // Check whether the news alias exists
        if ($objAlias->numRows)
        {
            if (!$autoAlias)
            {
                throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-' . $dc->id;
        }

        return $varValue;
    }

}