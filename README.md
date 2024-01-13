# WT JMoodle user sync
A plugin for synchronizing Joomla and Moodle users. Single Sing On (SSO) for Joomla and Moodle.

First of all, I want to mention an already existing solution to the same problem - [Joomdle](https://joomdle.com) component. This extension was considered as a possible way to solve the problem of integrating Joomla and Moodle, including single sign-on. Some approaches were then "spied on" in it (the GPL license allows this).

However, Joomdle appeared around 2008-2009 and has changed little internally since then. Under the hood of this component is code that uses (at the time of writing this text in January 2024) the methods of Joomla 1.6-2.5. This means that on Joomla 5, if it will work without errors, then only with the backward compatibility plugin. And it won't work on Joomla 6 anymore. The developer has declared the functionality of the component on Joomla 4.

Since the codebase of the component and related plugins is quite large, its upgrade to modern Joomla standards is unlikely to happen soon, if at all. It should also be borne in mind that Moodle also did not stand still and it has a fairly developed REST API, while Joomdle used its entry point to LMS Moodle bypassing the REST API. This has historically happened due to the fact that Joomdle added its functionality probably before it appeared in the REST API Moodle.

Therefore, it was decided to create a library WT JMoodle library to work with the Moodle REST API from Joomla, as well as to create plugins to solve various tasks for working with Moodle from Joomla that will use this library.

# Plugin dependencies
The plugin requires an installed and configured library to integrate Joomla 4 / Joomla 5 and Moodle - WT JMoodle library.
# Plugin features
- creating a Moodle user when creating or self-registering a Joomla user
- updating Moodle user data when updating Joomla user data. So far, standard user data is being synchronized: name, login, password, etc. Mapping of user fields has not yet been implemented.
- deleting a Moodle user when deleting a Joomla user
- SSO - Single Sign On - single sign-on for both engines (on cookies). Optional.
# Synchronization of Joomla 4 / Joomla 5 and Moodle 4.3 users
Data synchronization occurs automatically at the time of actions on users in Joomla. The database contains a table of links between Joomla and Moodle users, which is created when installing the WT JMoodle library. On the Moodle side, the external service needs to be allowed to use методов core_user_create_users , core_user_update_users , core_user_delete_users . 
You can check if they are available for the current configuration in the plugin `User - WT JMoodle User sync`.
# SSO (Single Sign On) for Joomla and Moodle
## Introduction
Single sign-on technology is used by those companies that have multiple sites and services for their users. We are all used to the fact that Google, Yandex, VK (Mail.ru ) and other large sites allow us to use the same account (account) for all their services (mail, advertising account, social network, etc.). Usually for the tasks of storing user credentials, authorization (login / logout) and interaction with other services of the company, a separate service (website) is allocated, which is called the Identity portal.

And in this case, if we have only 2 sites (Joomla and Moodle), then to manage users, we need to raise another portal site - identity portal. If there are only 2 sites, such a solution will be rather redundant and it is easier to set up direct data synchronization between the two engines. If the prospect of growth to several independent services is assumed, then in this case you need to configure the classic SSO.

**This plugin is a solution for direct integration of Joomla and Moodle without using Identity portal, it is not inherently a classic SSO, but provides this functionality.**
## Single sign-on scheme for Joomla 5 and Moodle 4.3 when using the WT JMoodle user sync plugin
When a user logs in on the Joomla side, authorization on the Moodle side occurs according to the following scheme:

![image](https://github.com/WebTolk/WT-JMoodle-user-sync/assets/6236403/566be12f-ddcf-4a5d-82e5-2f4b62677098)

1. When a user logs in to Joomla, the `onUserAfterLogin` plugin sends a request to the **WT JMoodle auth** authentication plugin on the Moodle side. The request includes a token created for the Moodle REST API and specified in the settings of the WT JMoodle library.
2. The WT JMoodle auth authentication plugin on the Moodle side checks:
   1. is the token in the request empty
   2. has a token been created for an external service whose id is specified in the settings of the WT JMoodle auth plugin on the Moodle side.
   3. Compares whether the token in the request matches the token in the database for this external service.
   4. Checks whether the user specified in the request exists in Moodle.
3. If all previous checks are successful, the WT JMoodle auth plugin on the Moodle side in turn makes a request to Joomla to verify whether the requested user is actually logged in to Joomla? The request also contains a token for the web service.
4. Joomla checks if this user is logged in and gives a Moodle response.
5. On the Moodle side, the authentication plugin receives a response from Joomla and if the user is really logged in to Joomla, it authorizes the user on the Moodle side and sends a cookie to Joomla.
6. This way, you can log in to both sites at the same time.
   
## Cookie domain setting for SSO to work
In order for this authorization scheme to work, Moodle and Joomla must be on the same level 2 domain. Usually a Joomla site is located on the main domain of the 2nd level, for example site.ru . There are 2 possible locations for Moodle:
- On a subdomain like `moodle.site.ru`.
- In the subfolder of the main site like `site.ru/moodle`.

If Moodle is located in a subfolder, cookies issued by both systems by the browser will be perceived as cookies of the same site. While `site.ru` and `moodle.site.ru` are different sites and cookies of one will not be available to the other.
However, in both systems - both Joomla and Moodle - there is a parameter in the settings `cookie domain`. In both engines, you need to specify a level 2 domain in this parameter, for example, `site.ru`.

If Joomla is located on a level 2 domain, and Moodle is on a subdomain, then you can omit this parameter on the Joomla side. If Joomla is also on a subdomain, then this setting is required. Go to the **left menu / System / Global configuration / Site tab / at the very bottom of the page**.
