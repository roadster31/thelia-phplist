<?php
/*************************************************************************************/
/*                                                                                   */
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*************************************************************************************/

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 05/03/2016 18:11
 */

namespace PhpList\Hook;

use PhpList\PhpList;
use RupturesDeStock\Model\RupturesDeStockQuery;
use RupturesDeStock\RupturesDeStock;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Tools\URL;

class HookManager extends BaseHook
{
    public function onModuleConfigure(HookRenderEvent $event)
    {
        $vars = [
            PhpList::REST_URL => PhpList::getConfigValue(PhpList::REST_URL, ''),
            PhpList::API_LOGIN_NAME => PhpList::getConfigValue(PhpList::API_LOGIN_NAME, ''),
            PhpList::API_PASSWORD => PhpList::getConfigValue(PhpList::API_PASSWORD, ''),
            PhpList::API_SECRET => PhpList::getConfigValue(PhpList::API_SECRET, ''),
            PhpList::LIST_NAME => PhpList::getConfigValue(PhpList::LIST_NAME, '')
        ];

        $event->add(
            $this->render('phplist/module-configuration.html', $vars)
        );
    }
}
