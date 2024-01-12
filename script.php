<?php
/**
 * @package       WT JMoodle user sync
 * @version       1.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright (c) January 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {

	public function register(Container $container): void
	{
		$container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
			/**
			 * The application object
			 *
			 * @var  AdministratorApplication
			 *
			 * @since  1.0.0
			 */
			protected AdministratorApplication $app;

			/**
			 * The Database object.
			 *
			 * @var   DatabaseDriver
			 *
			 * @since  1.0.0
			 */
			protected DatabaseDriver $db;

			/**
			 * Minimum Joomla version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  1.0.0
			 */
			protected string $minimumJoomla = '4.3';

			/**
			 * Minimum PHP version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  1.0.0
			 */
			protected string $minimumPhp = '7.4';

			/**
			 * @var array $providersInstallationMessageQueue
			 * @since 2.0.3
			 */
			protected $providersInstallationMessageQueue = [];

			/**
			 * Constructor.
			 *
			 * @param AdministratorApplication $app The application object.
			 *
			 * @since 1.0.0
			 */
			public function __construct(AdministratorApplication $app)
			{
				$this->app = $app;
				$this->db = Factory::getContainer()->get('DatabaseDriver');
			}

			/**
			 * Function called after the extension is installed.
			 *
			 * @param InstallerAdapter $adapter The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function install(InstallerAdapter $adapter): bool
			{
				return true;
			}

			/**
			 * Function called after the extension is updated.
			 *
			 * @param InstallerAdapter $adapter The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function update(InstallerAdapter $adapter): bool
			{
				return true;
			}

			/**
			 * Function called after the extension is uninstalled.
			 *
			 * @param InstallerAdapter $adapter The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function uninstall(InstallerAdapter $adapter): bool
			{
				return true;
			}

			/**
			 * Function called before extension installation/update/removal procedure commences.
			 *
			 * @param string $type The type of change (install or discover_install, update, uninstall)
			 * @param InstallerAdapter $adapter The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function preflight(string $type, InstallerAdapter $adapter): bool
			{
				return true;
			}

			/**
			 * Function called after extension installation/update/removal procedure commences.
			 *
			 * @param string $type The type of change (install or discover_install, update, uninstall)
			 * @param InstallerAdapter $adapter The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function postflight(string $type, InstallerAdapter $adapter): bool
			{
				$smile = '';

				if ($type !== 'uninstall') {
					if ($type != 'uninstall') {
						$smiles = ['&#9786;', '&#128512;', '&#128521;', '&#128525;', '&#128526;', '&#128522;', '&#128591;'];
						$smile_key = array_rand($smiles, 1);
						$smile = $smiles[$smile_key];
					}
				} else {
					$smile = ':(';
				}

				$element = 'PLG_' . strtoupper($adapter->getElement());
				$type = strtoupper($type);

				$html = '
				<div class="row bg-white m-0">
				<div class="col-12 col-md-8 p-0 pe-2">
				<h2>' . $smile . ' ' . Text::_($element . '_AFTER_' . $type) . ' <br/>' . Text::_($element) . '</h2>
				' . Text::_($element . '_DESC');

				$html .= Text::_($element . '_WHATS_NEW');

				if ($type !== 'uninstall')
				{
					/**
					 * Joomla WT JMoodle library
					 */

					$wt_jmoodle_library_url = 'https://web-tolk.ru/get?element=wtjmoodle';
					if (!$this->installDependencies($adapter, $wt_jmoodle_library_url))
					{

					}
				}


				$html .= '</div>
				<div class="col-12 col-md-4 p-0 d-flex flex-column justify-content-start">
				<img width="180" src="https://web-tolk.ru/web_tolk_logo_wide.png">
				<p>Joomla Extensions</p>
				<p class="btn-group">
					<a class="btn btn-sm btn-outline-primary" href="https://web-tolk.ru" target="_blank"> https://web-tolk.ru</a>
					<a class="btn btn-sm btn-outline-primary" href="mailto:info@web-tolk.ru"><i class="icon-envelope"></i> info@web-tolk.ru</a>
				</p>
				<p><a class="btn btn-danger w-100" href="https://t.me/joomlaru" target="_blank">' . Text::_($element . '_JOOMLARU_TELEGRAM_CHAT') . '</a></p>
				' . Text::_($element . "_MAYBE_INTERESTING") . '
				</div>
				';
				$this->app->enqueueMessage($html, 'info');

				return true;
			}

			/**
			 * @param $adapter
			 *
			 * @return bool
			 * @throws Exception
			 *
			 *
			 * @since 1.0.0
			 */
			protected function installDependencies($adapter, $url)
			{
				// Load installer plugins for assistance if required:
				PluginHelper::importPlugin('installer');

				$package = null;

				// This event allows an input pre-treatment, a custom pre-packing or custom installation.
				// (e.g. from a JSON description).
//                $results = $this->app->triggerEvent('onInstallerBeforeInstallation', array($this, &$package));
//
//                if (in_array(true, $results, true))
//                {
//                    return true;
//                }
//
//                if (in_array(false, $results, true))
//                {
//                    return false;
//                }


				// Download the package at the URL given.
				$p_file = InstallerHelper::downloadPackage($url);

				// Was the package downloaded?
				if (!$p_file) {
					$this->app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL'), 'error');

					return false;
				}

				$config = Factory::getContainer()->get('config');
				$tmp_dest = $config->get('tmp_path');

				// Unpack the downloaded package file.
				$package = InstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

				// This event allows a custom installation of the package or a customization of the package:
//                $results = $this->app->triggerEvent('onInstallerBeforeInstaller', array($this, &$package));

//                if (in_array(true, $results, true))
//                {
//                    return true;
//                }

//                if (in_array(false, $results, true))
//                {
//                    InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
//
//                    return false;
//                }

				// Get an installer instance.
				$installer = new Installer();

				/*
				 * Check for a Joomla core package.
				 * To do this we need to set the source path to find the manifest (the same first step as JInstaller::install())
				 *
				 * This must be done before the unpacked check because JInstallerHelper::detectType() returns a boolean false since the manifest
				 * can't be found in the expected location.
				 */
				if (is_array($package) && isset($package['dir']) && is_dir($package['dir'])) {
					$installer->setPath('source', $package['dir']);

					if (!$installer->findManifest()) {
						InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
						$this->app->enqueueMessage(Text::sprintf('COM_INSTALLER_INSTALL_ERROR', '.'), 'warning');

						return false;
					}
				}

				// Was the package unpacked?
				if (!$package || !$package['type']) {
					InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
					$this->app->enqueueMessage(Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'), 'error');

					return false;
				}

				// Install the package.
				if (!$installer->install($package['dir'])) {
					// There was an error installing the package.
					$msg = Text::sprintf('COM_INSTALLER_INSTALL_ERROR',
						Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
					$result = false;
					$msgType = 'error';
				} else {
					// Package installed successfully.
					$msg = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS',
						Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
					$result = true;
					$msgType = 'message';
				}

				// This event allows a custom a post-flight:
//                $this->app->triggerEvent('onInstallerAfterInstaller', array($adapter, &$package, $installer, &$result, &$msg));

				$this->app->enqueueMessage($msg, $msgType);

				// Cleanup the install files.
				if (!is_file($package['packagefile'])) {
					$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
				}

				InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

				return $result;
			}

		});
	}
};