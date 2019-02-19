<?php
namespace Iao\Modules\Fe;

/**
 * @copyright  Sven Rhinow 2011-2018
 * @author     sr-tag Sven Rhinow Webentwicklung <http://www.sr-tag.de>
 * @package    project-manager-bundle
 * @license    LGPL
 * @filesource
 */

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Module;
use Srhinow\IaoProjectsModel;

/**
 * Class ModulePublicProjectDetails
 *
 * Frontend module "project-manager-bundle"
 */
class ModulePublicProjectDetails extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'iao_public_project_details';


    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### IAO PUBLIC PROJECT DETAILS ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['project']) && $GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item']))
        {
            \Input::setGet('project', \Input::get('auto_item'));
        }

        // Ajax Requests abfangen
        if(\Input::get('project') && \Environment::get('isAjaxRequest')){
            $this->generateAjax();
            exit;
        }

        // Do not index or cache the page if no news item has been specified
        if (!\Input::get('project'))
        {
            global $objPage;
            $objPage->noSearch = 1;
            $objPage->cache = 0;
            return '';
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        global $objPage;

        $arrProjectData = $this->getProjectData();

        $this->Template->setData($arrProjectData);
        $this->Template->isAjax = false;
        $this->Template->referer = 'javascript:history.go(-1)';
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

        //SEO-Werte setzen
        if(strlen($arrProjectData['reference_title']) > 0) {
            $objPage->title = strip_tags(strip_insert_tags($arrProjectData['reference_title']));
//            $objPage->pageTitle = strip_tags(strip_insert_tags($arrProjectData['reference_title']));
        }
//        if(strlen($arrProjectData['reference_subtitle']) > 0) $objPage->pageTitle .= ' - '.$arrProjectData['reference_subtitle'];

        if(strlen($arrProjectData['reference_todo']) > 0) $GLOBALS['TL_KEYWORDS'] .= strip_tags(strip_insert_tags($arrProjectData['reference_todo']));
        $objPage->description = \Contao\StringUtil::substr($arrProjectData['reference_customer'].' '.$arrProjectData['reference_todo'].' '.$arrProjectData['reference_desription'],320);

    }

    /**
     * generiert die Details ohne den kompletten DOM
     */
    public function generateAjax() {
        /** @var FrontendTemplate|object $objTemplate */
        $objTemplate = new \FrontendTemplate($this->fe_iao_template);
        $objTemplate->setData($this->getProjectData());
        $objTemplate->isAjax = true;
        echo $objTemplate->parse();
    }

    /**
     * holt die Projekt-Referenz-Daten fürs Template
     * @return array|void
     */
    protected function getProjectData() {

        $conditions['finished'] = 1;
        $conditions['in_reference'] = 1;
        $projectData = array();

        // Get the total number of items
        $objProject = IaoProjectsModel::findProjectByIdOrReferenceAlias(\Input::get('project'));

        // falsche Abfragen verhindern
        $falseCondition = false;
        foreach($conditions as $con => $val)
        {
            if($objProject->{$con} != $val) $falseCondition = true;
        }

        if ($objProject === null || $falseCondition)
        {
            throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
        }

        $projectData = $objProject->row();

        // Website
        $projectData['url'] = (substr($projectData['url'],0,4) != 'http') ? 'http://'.$projectData['url'] : $projectData['url'];

        // Add the article image as enclosure
        $image = '';

        if ($projectData['singleSRC'] !== null)
        {
            $objFile = \FilesModel::findByUuid($projectData['singleSRC']);

            if ($objFile !== null)
            {
                $projectData['image'] = $objFile->path;
            }
        }

        return $projectData;
    }
}
