<?php

/*
 * This file is part of project-manager-bundle.
 *
 * Copyright (c) 2004-2019 Sven Rhinow
 *
 * @license LGPL-3.0+
 */

namespace Srhinow\ProjectManagerBundle\EventListener\Hooks;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;

class InsertTagsListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;


    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * replace iao-specific inserttag if get-paramter isset
     * {{iao::BEREICH::COLUMN[::ID|ALIAS]}}
     * z.B. {{iao::invoice::title}} oder {{iao::invoice::title::4}}
     *
     * @param string
     * @return string
     */
    public function replaceFrontendIaoTags($strTag)
    {

        if (substr($strTag,0,5) == 'iao::')
        {
            //inserttag in Stuecke teilen
            $split = explode('::',$strTag);

            //wenn die ID nicht mit übergeben wurde die Detailseite vorraussetzen
            $idAlias = (strlen($split[3]) > 0) ? $split[3] : \Input::get('auto_item');

            switch($split[1]){
                case 'agreement':
                    $objResult = \Srhinow\IaoAgreementsModel::findByIdOrAlias($idAlias);
                    break;
                case 'credit':
                    $objResult = \Srhinow\IaoCreditModel::findByIdOrAlias($idAlias);
                    break;
                case 'credititem':
                    $objResult = \Srhinow\IaoCreditItemsModel::findByIdOrAlias($idAlias);
                    break;
                case 'invoice':
                    $objResult = \Srhinow\IaoInvoiceModel::findByIdOrAlias($idAlias);
                    break;
                case 'invoiceitem':
                    $objResult = \Srhinow\IaoInvoiceItemsModel::findByIdOrAlias($idAlias);
                    break;
                case 'offer':
                    $objResult = \Srhinow\IaoOfferModel::findByIdOrAlias($idAlias);
                    break;
                case 'offeritem':
                    $objResult = \Srhinow\IaoOfferItemsModel::findByIdOrAlias($idAlias);
                    break;
                case 'project':
                    $objResult = \Srhinow\IaoProjectsModel::findByIdOrAlias($idAlias);
                    break;
                default:
                    $objResult = null;
            }

            if($objResult !== null)
            {
                return $objResult->{$split[2]};
            }
        }

        return false;
    }

}
