<?php
/**
 * Joomla! System plugin - Language Domains
 *
 * @author     Yireo (info@yireo.com)
 * @copyright  Copyright 2015 Yireo.com. All rights reserved
 * @license    GNU Public License
 * @link       https://www.yireo.com
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Class PlgSystemLanguageDomainsHelper
 */
class PlgSystemLanguageDomainsHelper
{
	/**
	 * Method to detect whether Falang is active or not
	 *
	 * @return bool
	 */
	public function isFalangDatabaseDriver()
	{
		$db = JFactory::getDbo();

		if ($db instanceof JFalangDatabase)
		{
			return true;
		}

		return false;
	}

	/**
	 * Helper-method to get a proper URL from the domain
	 *
	 * @param   string $url URL to obtain the domain from
	 *
	 * @return string
	 */
	public function getDomainFromUrl($url)
	{
		// Add URL-elements to the domain
		if (preg_match('/^(http|https):\/\/([a-zA-Z0-9\.\-\_]+)/', $url, $match))
		{
			$domain = $match[2];

			return $domain;
		}

		return false;
	}

	/**
	 * Helper-method to get a proper URL from the domain
	 *
	 * @param   string $domain Domain to obtain the URL from
	 *
	 * @return string
	 */
	public function getUrlFromDomain($domain)
	{
		// Add URL-elements to the domain
		if (preg_match('/^(http|https):\/\//', $domain) == false)
		{
			$domain = ($this->isSSL()) ? 'https://' . $domain : 'http://' . $domain;
		}

		if (preg_match('/\/$/', $domain) == false)
		{
			$domain = $domain . '/';
		}

		$config = JFactory::getConfig();

		if ($config->get('sef_rewrite', 0) == 0 && preg_match('/index\.php/', $domain) == false)
		{
			$domain = $domain . 'index.php/';
		}

		return $domain;
	}

	/**
	 * Method to override certain Joomla classes
	 */
	public function overrideClasses()
	{
		JLoader::import('joomla.version');
		$version      = new JVersion;
		$majorVersion = $version->getShortVersion();

		if (version_compare($majorVersion, '3.2', 'ge'))
		{
			require_once JPATH_SITE . '/plugins/system/languagedomains/rewrite-32/associations.php';
			require_once JPATH_SITE . '/plugins/system/languagedomains/rewrite-32/multilang.php';
		}
	}

	/**
	 * Reset the current language (with $%& VirtueMart support)
	 */
	public function resetDefaultLanguage()
	{
		if (!class_exists('VmConfig'))
		{
			$vmConfigFile = JPATH_ROOT . '/administrator/components/com_virtuemart/helpers/config.php';

			if (file_exists($vmConfigFile))
			{
				defined('DS') or define('DS', DIRECTORY_SEPARATOR);

				include_once $vmConfigFile;
			}
		}

		if (class_exists('VmConfig'))
		{
			VmConfig::loadConfig();
			VmConfig::$vmlang = false;
			VmConfig::setdbLanguageTag();
		}
	}

	/**
	 * Helper-method to check whether SSL is active or not
	 *
	 * @return bool
	 */
	protected function isSSL()
	{
		// Support for proxy headers
		if (isset($_SERVER['X-FORWARDED-PROTO']))
		{
			if ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
			{
				return true;
			}

			return false;
		}

		$uri = JUri::getInstance();

		return (bool) $uri->isSSL();
	}
}
