<?php
/**
 * @package       WT Amocrm Library
 * @version       1.2.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright (c) 2022 - October 2023 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\User\Wtjmoodleusersync\Extension;
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Utilities\ArrayHelper;
use Webtolk\JMoodle\JMoodle;

class Wtjmoodleusersync extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	protected $allowLegacyListeners = false;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onUserAfterSave'          => 'onUserAfterSave',
			'onUserAfterDelete'        => 'onUserAfterDelete',
			'onUserAfterLogin'         => 'onUserAfterLogin',
			'onUserLoginFailure'       => 'onUserLoginFailure',
			'onUserLogout'             => 'onUserLogout',
			'onUserAfterResetRequest'  => 'onUserAfterResetRequest',
			'onUserAfterResetComplete' => 'onUserAfterResetComplete',
			'onUserBeforeSave'         => 'onUserBeforeSave',
			'onAjaxWtjmoodleusersync'  => 'onAjaxWtjmoodleusersync',
		];
	}

	/**
	 * Method is called before user data is stored in the database
	 *
	 * @param $event Event
	 *
	 * @return  void
	 *
	 * @since   5.0.0
	 */
	public function onUserBeforeSave($event): void
	{
		/**
		 * @var   array   $user  Holds the old user data.
		 * @var   boolean $isNew True if a new user is stored.
		 * @var   array   $data  Holds the new user data.
		 */
		[$user, $isNew, $data] = array_values($event->getArguments());
		$data['username'] = strtolower($data['username']);
		$event->setArgument('data', $data);
		$event->setArgument('result', [true]);
		// !!! Event onUserBeforeSave only accepts Boolean results.

	}

	/**
	 * On saving user data logging method
	 *
	 * Method is called after user data is stored in the database.
	 * This method logs who created/edited any user's data
	 *
	 * @param $event Event
	 *
	 * @return  void
	 *
	 * @since   3.9.0
	 */
	public function onUserAfterSave($event): void
	{
		/**
		 * @var   array   $user    Holds the new user data.
		 * @var   boolean $isnew   True if a new user is stored.
		 * @var   boolean $success True if user was successfully stored in the database.
		 * @var   string  $msg     Message.
		 */
		[$user, $isnew, $success, $msg] = array_values($event->getArguments());

		if (!$success)
		{
			return;
		}

		$moodle = new JMoodle();

		/** @var  $joomla_user_id int Joomla user id */
		$joomla_user_id = ArrayHelper::getValue($user, 'id', 0, 'int');

		// We have a new user. Let's register he in Moodle
		if ($isnew)
		{
			$user_data = [
				'users' => [
					[
						'username'  => strtolower($user['username']),
						'password'  => $user['password_clear'],
						'firstname' => $user['name'],
						'lastname'  => $user['name'],
						'email'     => $user['email'],
					]
				]
			];

			/**
			 * @var $moodle_users array An array of Moodle users created
			 *                    Array
			 *                        (
			 *                         [0] =>
			 *                             Array
			 *                                 (
			 *                                     [id] => int
			 *                                     [username] => string
			 *                                 )
			 *                        )
			 */
			$moodle_users = $moodle->request('core_user_create_users', $user_data);
			if (count($moodle_users) > 0 && !array_key_exists('error_code', $moodle_users))
			{

				$moodle_user_id = $moodle_users[0]['id'];
				// Save relations
				$moodle::addJoomlaMoodleUserSync($joomla_user_id, $moodle_user_id);
			}
			else
			{
				$moodle::saveToLog("WT JMoodle user sync plugin, onUserAfterSave: for Joomla user with id $joomla_user_id haven't created related Moodle user", 'ERROR');
			}
		}
		else
		{

			// Have we moodle user id for this Joomla user? False or (int) moodle user id.
			$moodle_user_id = $moodle::checkIsMoodleUser($joomla_user_id);

			if ($moodle_user_id)
			{
				$user_data = [
					'users' => [
						[
							'id'        => $moodle_user_id,
							'username'  => strtolower($user['username']),
							'firstname' => $user['name'],
							'lastname'  => $user['name'],
							'email'     => $user['email'],
						]
					]
				];
				// New password if specified
				if (!empty($user['password_clear']))
				{
					$user_data['password'] = $user['password_clear'];
				}

				$result = $moodle->request('core_user_update_users', $user_data);
				$moodle::saveToLog("WT JMoodle user sync plugin, onUserAfterSave: Moodle user with id $moodle_user_id has been updated", 'info');
			}
			else
			{
				// We loose moodle user id :((
				$moodle::saveToLog("WT JMoodle user sync plugin, onUserAfterSave: Joomla user with id $joomla_user_id haven't related Moodle user id in database", 'warning');
			}
		}
	}

	/**
	 * On deleting user data logging method
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param $event Event
	 *
	 * @return  void
	 *
	 * @since   3.9.0
	 */
	public function onUserAfterDelete($event): void
	{

		/**
		 * @var   array   $user    Holds the user data
		 * @var   boolean $success True if user was successfully stored in the database
		 * @var   string  $msg     Message
		 */
		[$user, $success, $msg] = array_values($event->getArguments());

		if (!$success)
		{
			return;
		}

		$joomla_user_id = ArrayHelper::getValue($user, 'id', 0, 'int');

		if (!empty($joomla_user_id))
		{
			$moodle         = new JMoodle();
			$moodle_user_id = $moodle::checkIsMoodleUser($joomla_user_id);

			if ($moodle_user_id)
			{
				$data = [
					'userids' => [$moodle_user_id]
				];
				// Delete the user in Moodle first
				$result = $moodle->request('core_user_delete_users', $data);

				// Moodle returns an empty array if it was a successful request
				if (!array_key_exists('error_code', $result))
				{
					// Remove from Joomla-to-Moodle user link in database
					$moodle::removeJoomlaMoodleUserSync([$joomla_user_id]);

					$log_message = 'WT JMoodle user sync plugin, onUserAfterDelete: User id ' . $joomla_user_id . ' has been deleted from Moodle too (id ' . $moodle_user_id . ')';
					$moodle::saveToLog($log_message, 'notice');
					$this->getApplication()->enqueueMessage($log_message, 'notice');
				}
			}
			else
			{

				$log_message = 'WT JMoodle user sync plugin, onUserAfterDelete: User id ' . $joomla_user_id . ' has not related Moodle user id';
				$moodle::saveToLog($log_message, 'notice');
				$this->getApplication()->enqueueMessage($log_message, 'notice');
			}
		}
	}

	/**
	 * Method to log user login success action
	 *
	 * @param $event Event
	 *
	 * @return  void
	 *
	 * @since   3.9.0
	 */
	public function onUserAfterLogin($event): void
	{

		if(!$this->params->get('use_sso'))
		{
			// SSO is disabled
			return;
		}

		/**
		 * @var  array $options Array holding options (user, responseType)
		 * @var  array $subject Array Response object with status variable filled in for last plugin or first successful plugin.
		 */
		[$options, $subject] = array_values($event->getArguments());
		$user = $options['user'];

		$moodle = new JMoodle();

		$data = [
			'username' => strtolower($user->username)
		];

		$config   = Factory::getContainer()->get('config');
		$temppath = $config->get('tmp_path');
		$file     = $temppath . '/' . UserHelper::genRandomPassword() . '.txt';
		// First make sure we can write to file
		touch($file);
		if (!file_exists($file))
		{
			$moodle::saveToLog('Plugin WT JMoodle user sync can\'t create temporary file for cookie SSO', 'error');

		}

		$curl_options    = [
			CURLOPT_COOKIEJAR      => $file,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER         => 1,
			CURLOPT_SSL_VERIFYPEER => false
		];
		$moodle_reposnse = $moodle->customRequest('/auth/jmoodle/jmoodle_login.php', $data, 'POST', $curl_options);

		/**
		 * Thanks to Joomdle for next lines.
		 * Deprecated.
		 * It will be replaced by working with the Set-Cookie header.
		 */
		$f = fopen($file, 'ro');

		if (!$f)
		{
			$moodle::saveToLog('COM_JOOMDLE_ERROR_CANT_OPEN_CURL_FILE', 'error');
		}

		while (!feof($f))
		{
			$line = fgets($f);
			if (($line == '\n') || ((is_array($line)) && ($line[0] == '#')))
			{
				continue;
			}
			$parts = explode("\t", $line);
			if (array_key_exists(5, $parts))
			{
				$name  = $parts[5];
				$value = trim($parts[6]);
				// SET cookie_domain in Joomla settings with leading DOT
				setcookie($name, $value, 0, $config->get('cookie_path', '/'), $config->get('cookie_domain', ''));
			}
		}
		unlink($file);
	}

	/**
	 * Method to log user login failed action
	 *
	 * @param   array  $response  Array of response data.
	 *
	 * @return  void
	 *
	 * @since   3.9.0
	 */
	public function onUserLoginFailure($response): void
	{

	}

	/**
	 * Method to log user's logout action
	 *
	 * @param $event Event
	 *
	 * @return  void
	 *
	 * @since   3.9.0
	 */
	public function onUserLogout($event): void
	{
		/**
		 * @var   array $user    Holds the user data
		 * @var   array $options Array holding options (remember, autoregister, group)
		 */
		[$user, $options] = array_values($event->getArguments());

//		$loggedOutUser = $this->getUserFactory()->loadUserById($user['id']);
//
//		if ($loggedOutUser->block) {
//			return;
//		}
	}

	/**
	 * On after Reset password request
	 *
	 * Method is called after user request to reset their password.
	 *
	 * @param $event Event
	 *
	 * @return  void
	 *
	 * @since   4.2.9
	 */
	public function onUserAfterResetRequest($event): void
	{
		/**
		 * @var   array $user Holds the user data.
		 */
		$user = $event->getArgument(0);

	}

	/**
	 * On after Completed reset request
	 *
	 * Method is called after user complete the reset of their password.
	 *
	 * @param   array  $user  Holds the user data.
	 *
	 * @return  void
	 *
	 * @since   4.2.9
	 */
	public function onUserAfterResetComplete($user)
	{

	}

	public function onAjaxWtjmoodleusersync($event): void
	{
		$app    = $this->getApplication();
		$token  = $app->getInput()->json->getCmd('token');
		$action = $app->getInput()->getCmd('action');
		$moodle = new JMoodle();

		// Check we have income request from true Moodle host
		if ($token !== $moodle::getMoodleToken())
		{
			$moodle::saveToLog('There was an attempt to make an external request to the WT Moodle user sync plugin with an invalid token', '');
			throw new \Exception('Wrong token', 403);
		}

		// Check action
		if ($action == 'check_joomla_user_session')
		{
			$username = $app->getInput()->json->getCmd('username');
			$user     = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($username);

			$db             = Factory::getContainer()->get('DatabaseDriver');
			$query          = $db->getQuery(true)
				->select('username')
				->from($db->quoteName('#__session'))
				->where($db->quoteName('userid') . ' = ' . $db->quote($user->id))
				->where('time > '
					. Factory::getDate('- ' . Factory::getContainer()->get('config')->get('lifetime', 15) . 'minute')->toUnix())
				->where($db->quoteName('client_id') . ' = ' . $db->quote('0')) // frontend login
				->where($db->quoteName('guest') . ' = ' . $db->quote('0'));
			$logged_in_user = $db->setQuery($query)->loadResult();
			$logged_in      = [
				'username'  => $username,
				'logged_in' => false
			];
			if (!empty($logged_in_user))
			{
				$logged_in['logged_in'] = true;
			}

			$event->setArgument('result', $logged_in);
		}

	}

}
