<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace PhpList\Form;
use PhpList\Api\PhpListRESTApiClient;
use PhpList\PhpList;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Thelia\Form\BaseForm;

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 18/07/2016 20:10
 */
class ConfigurationForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(PhpList::REST_URL, "text", array(
                "label" => $this->translator->trans("Php List REST API URL", [], PhpList::DOMAIN_NAME),
                "required" => true,
                "constraints" => array(
                    new NotBlank(),
                ),
                "label_attr" => [
                    'help' => $this->translator->trans("The URL to the REST API, sometehing like http://your.phplist.domain/lists/admin/?page=call&pi=restapi", [], PhpList::DOMAIN_NAME),
                ]
            ))
            ->add(PhpList::API_LOGIN_NAME, "text", array(
                "label" => $this->translator->trans("Login name", [], PhpList::DOMAIN_NAME),
                "required" => true,
                "constraints" => array(
                    new NotBlank(),
                ),
                "label_attr" => [
                    'help' => $this->translator->trans("The username of an account with administration rights", [], PhpList::DOMAIN_NAME),
                ]
            ))
            ->add(PhpList::API_PASSWORD, "text", array(
                "label" => $this->translator->trans("Password", [], PhpList::DOMAIN_NAME),
                "required" => true,
                "constraints" => array(
                    new NotBlank(),
                    new Callback(array(
                        "methods" => array(
                            array($this, "checkApiLogin")
                        ),
                    )),
                ),
                "label_attr" => [
                    'help' => $this->translator->trans("The password of the admin user above", [], PhpList::DOMAIN_NAME),
                ]
            ))
            ->add(PhpList::API_SECRET, "text", array(
                "label" => $this->translator->trans("API secret key", [], PhpList::DOMAIN_NAME),
                "required" => true,
                "label_attr" => [
                    'help' => $this->translator->trans("This is the secret code defined in the PhpList settings. Enter this code only if \"Require the secret code for Rest API calls\" is set to \"Yes\".", [], PhpList::DOMAIN_NAME),
                ]
            ))
        ;

        if (null !== PhpList::getConfigValue(PhpList::REST_URL)) {
            $this->formBuilder->add(
                PhpList::LIST_NAME,
                "choice",
                array(
                    "label" => $this->translator->trans("User list name", [], PhpList::DOMAIN_NAME),
                    "required" => true,
                    "choices" => $this->getListNames(),
                    "label_attr" => [
                        'help' => $this->translator->trans("The name of the list the users are added or removed", [], PhpList::DOMAIN_NAME),
                    ]
                )
            );
        }
    }

    public function checkApiLogin($value, ExecutionContextInterface $context)
    {
        $data = $context->getRoot()->getData();

        $message = false;

        try {
            $api = new PhpListRESTApiClient(
                $data[PhpList::REST_URL],
                $data[PhpList::API_LOGIN_NAME],
                $data[PhpList::API_PASSWORD],
                $data[PhpList::API_SECRET]
            );

            if (! $api->login()) {
                $message = $this->translator->trans(
                    "Failed to login to the phpList REST API. Please check credentials.",
                    [],
                    PhpList::DOMAIN_NAME
                );
            }
        } catch (\Error $ex) {
            $message = $this->translator->trans(
                "Failed to login to the phpList REST API. Unexpected error occured: %err",
                [ '%err' => $ex->getMessage() ],
                PhpList::DOMAIN_NAME
            );
        }

        if ($message) {
            $context->addViolation($message);
        }
    }

    private function getListNames()
    {
        $api = new PhpListRESTApiClient(
            PhpList::getConfigValue(PhpList::REST_URL),
            PhpList::getConfigValue(PhpList::API_LOGIN_NAME),
            PhpList::getConfigValue(PhpList::API_PASSWORD),
            PhpList::getConfigValue(PhpList::API_SECRET)
        );

        $result = ['(none)' => $this->translator->trans("No list found !", [], PhpList::DOMAIN_NAME)];

        if ($api->login()) {
            if (false !== $listsData = $api->listsGet()) {
                $result = [];

                foreach ($listsData as $item) {
                    $result[$item->id] = $item->name;
                }
            }
        } else {
            throw new \Exception($this->translator->trans(
                "Failed to login to the phpList REST API. Please check credentials",
                [],
                PhpList::DOMAIN_NAME
            ));
        }

        return $result;
    }
}
