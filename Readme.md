# PhpList interface

This module keep a phpList contact list of your choice synchronized whith the newsletter subscriptions and unsubscriptions on your shop :

- When a user subscribe to your newsletter on your shop, it is automatically added to the phpList contact list.
- When a user unsubscribe from your list, it is also deleted from the phpList contact list.

Author: Franck Allimant / CVQFDev franck@cqfdev.fr

## Prerequistites

For this module to work, the RESTAPI plugin shoud be installed and configured on your PHP List instance.

To configure this module, please enter the required information, and click the "Save" button.

Once the proper crendentials have been entered, you'll have to choose the list that will be updated when a customer subscribe or unsubscribe to the newsletter.

 

## phpList / Thelia synchronization
To get instant synchronization between phpList and Thelia, be sure to use in the various phpList messages and templates:

    https://yourshop.tld/newsletter instead of [SUBSCRIBEURL]
    https://yourshop.tld/newsletter-unsubscribe instead of [UNSUBSCRIBEURL]

You can also configure an automatic synchronisation in your cron or webcron by invoking the following URL: https://yourshop.tld/admin/module/phplist/sync
