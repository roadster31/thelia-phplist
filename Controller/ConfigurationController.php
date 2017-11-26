<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 20/07/2016 15:18
 */

namespace PhpList\Controller;

use PhpList\PhpList;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Log\Tlog;
use Thelia\Tools\URL;

class ConfigurationController extends BaseAdminController
{
    public function configure()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'phpList', AccessManager::UPDATE)) {
            return $response;
        }
        
        // Create the Form from the request
        $configurationForm = $this->getTheliaFormFactory()->createForm('phplist.configuration.form');
        
        try {
            // Check the form against constraints violations
            $form = $this->validateForm($configurationForm, "POST");
            
            // Get the form field values
            $data = $form->getData();
            
            foreach ($data as $name => $value) {
                if (is_array($value)) {
                    $value = implode(';', $value);
                }
                
                PhpList::setConfigValue($name, $value);
            }
            
            // Log configuration modification
            $this->adminLogAppend(
                "phplist.configuration.message",
                AccessManager::UPDATE,
                sprintf("PhpList configuration updated")
            );
            
            // Redirect to the success URL,
            if ($this->getRequest()->get('save_mode') == 'stay') {
                // If we have to stay on the same page, redisplay the configuration page/
                $route = '/admin/module/PhpList';
            } else {
                // If we have to close the page, go back to the module back-office page.
                $route = '/admin/modules';
            }
            
            return new RedirectResponse(URL::getInstance()->absoluteUrl($route));
            
        } catch (FormValidationException $ex) {
            // Form cannot be validated. Create the error message using
            // the BaseAdminController helper method.
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
            
            Tlog::getInstance()->error($ex->getTraceAsString());
        }
        catch (\Exception $ex) {
            // Any other error
            $error_msg = $ex->getMessage();
    
            Tlog::getInstance()->error($ex->getTraceAsString());
        }
        
        // At this point, the form has errors
        $this->setupFormErrorContext(
            $this->getTranslator()->trans("PhpList configuration", [], PhpList::DOMAIN_NAME),
            $error_msg,
            $configurationForm,
            $ex
        );
    
    
        return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/PhpList'));
    }
    
    public function sync()
    {
        $this->getDispatcher()->dispatch(PhpList::RESYNC_EVENT);
        
        return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/PhpList'));
    }
    
    public function bulkAdd()
    {
        $this->getDispatcher()->dispatch(PhpList::BULK_ADD);
        
        return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/PhpList'));
    }
}
