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
 * Date: 18/07/2016 20:02
 */

namespace PhpList\EventListeners;

use PhpList\Api\PhpListRESTApiClient;
use PhpList\PhpList;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Newsletter\NewsletterEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Exception\TheliaProcessException;
use Thelia\Log\Tlog;
use Thelia\Model\Lang;
use Thelia\Model\Newsletter;
use Thelia\Model\NewsletterQuery;

class EventManager implements EventSubscriberInterface
{
    public function subscribe(NewsletterEvent $event)
    {
        $this->doSubscribe($event->getEmail(), PhpList::getConfigValue(PhpList::LIST_NAME));
    }

    public function unsubscribe(NewsletterEvent $event)
    {
        $this->doUnsubscribe($event->getEmail(), PhpList::getConfigValue(PhpList::LIST_NAME));
    }

    public function bulkAdd()
    {
        $theliaSubscribers = NewsletterQuery::create()->select('email')->find()->toArray();

        foreach ($theliaSubscribers as $subscriber) {
            $this->doSubscribe($subscriber, PhpList::getConfigValue(PhpList::LIST_NAME));
        }
    }


    protected function doSubscribe($email, $list)
    {
        $api = $this->createApiClient();

        try {
            $subscriberId = $this->getSubscriberId($api, $email, true);

            if ($api->listSubscriberAdd($list, $subscriberId)) {
                Tlog::getInstance()->info(
                    sprintf(
                        "Email address %s successfully added to phpList ID %s.",
                        $email,
                        $list
                    )
                );
            } else {
                throw new \Exception("Unknown error");
            }
        } catch (\Exception $ex) {
            Tlog::getInstance()->error(
                sprintf(
                    "Failed to add email address %s to phpList ID %s. Error is %s",
                    $email,
                    $list,
                    $ex->getMessage()
                )
            );
        }
    }

    protected function doUnsubscribe($email, $list)
    {
        $api = $this->createApiClient();

        try {
            $subscriberId = $this->getSubscriberId($api, $email, true);

            if ($api->listSubscriberDelete($list, $subscriberId)) {
                Tlog::getInstance()->info(
                    sprintf(
                        "Email address %s successfully removed from phpList ID %s.",
                        $email,
                        $list
                    )
                );
            }
        } catch (\Exception $ex) {
            Tlog::getInstance()->error(
                sprintf(
                    "Failed to remove email address %s from phpList ID %s. Error is %s",
                    $email,
                    $list,
                    $ex->getMessage()
                )
            );
        }
    }

    /**
     * @param PhpListRESTApiClient $api
     * @param string $email
     * @throws \Exception
     */
    protected function getSubscriberId($api, $email, $createIfNotExists = false)
    {
        if (false !== $subscriberId = $api->subscriberFindByEmail($email)) {
            return $subscriberId;
        } elseif ($createIfNotExists) {
            if (false !== $subscriberId = $api->subscriberAdd($email, true, $email . time())) {
                return $subscriberId;
            } else {
                throw new \Exception("Failed to create customer with email $email");
            }
        } else {
            throw new \Exception("Subscriber was not found in phpList");
        }
    }

    /**
     * @return PhpListRESTApiClient
     */
    protected function createApiClient()
    {
        if (null === $url = PhpList::getConfigValue(PhpList::LIST_NAME, null)) {
            throw new TheliaProcessException(
                Translator::getInstance()->trans(
                    "Cannot create Php List REST client. Module is not initialized.",
                    [],
                    PhpList::DOMAIN_NAME
                )
            );
        }

        $api = new PhpListRESTApiClient(
            PhpList::getConfigValue(PhpList::REST_URL),
            PhpList::getConfigValue(PhpList::API_LOGIN_NAME),
            PhpList::getConfigValue(PhpList::API_PASSWORD),
            PhpList::getConfigValue(PhpList::API_SECRET)
        );

        if (false === $api->login()) {
            throw new TheliaProcessException(
                Translator::getInstance()->trans(
                    "Failed to login to phpList, please check credentials.",
                    [],
                    PhpList::DOMAIN_NAME
                )
            );
        }

        return $api;
    }

    public function resync()
    {
        $list = PhpList::getConfigValue(PhpList::LIST_NAME);

        $api = $this->createApiClient();

        if (false !== $subscribers = $api->listSubscribers($list)) {

            $theliaSubscribers = NewsletterQuery::create()->select('email')->find()->toArray();

            $locale = Lang::getDefaultLanguage()->getLocale();

            $phpListSubscribers = [];

            // Add to Thelia the subscribers which are not in the Newsletter table
            foreach ($subscribers as $subscriber) {
                if ($subscriber->confirmed) {
                    $phpListSubscribers[] = $subscriber->email;

                    if (!in_array($subscriber->email, $theliaSubscribers)) {
                        $newsletter = new Newsletter();
                        $newsletter
                            ->setEmail($subscriber->email)
                            ->setLocale($locale)
                            ->save();
                    }
                }
            }

            // Remove from Thelia the subscribers which are not in phpList
            foreach ($theliaSubscribers as $theliaSubscriber) {
                if (!in_array($theliaSubscriber, $phpListSubscribers)) {
                    NewsletterQuery::create()->findOneByEmail($theliaSubscriber)->delete();
                }
            }

            // Add to phpList the missing Thelia subscribers, ignoring unsubscribed emails
            $theliaSubscribers = NewsletterQuery::create()
                ->filterByUnsubscribed(false)
                ->select('email')
                ->find()
                ->toArray();

            foreach ($theliaSubscribers as $theliaSubscriber) {
                if (!in_array($theliaSubscriber, $phpListSubscribers)) {
                    $this->doSubscribe($theliaSubscriber, $list);
                }
            }

            // Remove from phpList unsubscribed Thelia emails
            $theliaSubscribers = NewsletterQuery::create()
                ->filterByUnsubscribed(true)
                ->select('email')
                ->find()
                ->toArray();

            foreach ($theliaSubscribers as $theliaSubscriber) {
                if (in_array($theliaSubscriber, $phpListSubscribers)) {
                    $this->doUnsubscribe($theliaSubscriber, $list);
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::NEWSLETTER_SUBSCRIBE => ["subscribe", 130],
            TheliaEvents::NEWSLETTER_UNSUBSCRIBE => ["unsubscribe", 130],
            PhpList::RESYNC_EVENT => ['resync', 128],
            PhpList::BULK_ADD => ['bulkAdd', 128]
        );
    }
}
