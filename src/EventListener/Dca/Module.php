<?php
/**
 * Created by c4.pringitzhonig.de.
 * Developer: Sven Rhinow (sven@sr-tag.de)
 * Date: 19.09.19
 */

namespace Srhinow\ProjectManagerBundle\EventListener\Dca;


use Contao\DataContainer;
use Iao\Backend\IaoBackend;

class Module extends IaoBackend {

    /**
     * Return all info templates as array
     *
     * @param DataContainer $dc
     * @return array
     */
    public function getIaoTemplates(DataContainer $dc)
    {
        $arrTemplates = \Controller::getTemplateGroup('iao_');

        return $arrTemplates;
    }

}