<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace PhpList;

use Thelia\Module\BaseModule;

class PhpList extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'phplist';

    const REST_URL = 'rest_url';
    const API_LOGIN_NAME = 'api_login_name';
    const API_PASSWORD = 'api_password';
    const API_SECRET = 'api_secret';
    const LIST_NAME = 'list_name';

    const RESYNC_EVENT = 'PhpList.resync';
    const BULK_ADD = 'PhpList.bulk_add';
}
