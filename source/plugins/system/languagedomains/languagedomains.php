<?php
/**
 * Joomla! System plugin - Language Domains
 *
 * @author Yireo (info@yireo.com)
 * @copyright Copyright 2014 Yireo.com. All rights reserved
 * @license GNU Public License
 * @link http://www.yireo.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
require_once JPATH_SITE . '/plugins/system/languagefilter/languagefilter.php';

/**
 *
 * @package Joomla!
 * @subpackage System
 */
class plgSystemLanguageDomains extends plgSystemLanguageFilter
{

	/**
	 * Constructor
	 *
	 * @access public
	 * @param mixed $subject
	 * @param mixed $config
	 * @return mixed
	 */
	public function __construct(&$subject, $config)
	{
		JLoader::import('joomla.version');
		$version = new JVersion();
		$majorVersion = $version->getShortVersion();
		if (version_compare($majorVersion, '3.2', 'ge'))
		{
			require_once (JPATH_SITE . '/plugins/system/languagedomains/rewrite-32/associations.php');
			require_once (JPATH_SITE . '/plugins/system/languagedomains/rewrite-32/multilang.php');
		}

		$rt = parent::__construct($subject, $config);

		// If this is the Site-application
		$application = JFactory::getApplication();
		if ($application->isSite() == true)
		{
			// Get the bindings
			$bindings = $this->getBindings();
			if ($bindings)
			{
				// Check if the current default language is correct
				foreach ($bindings as $bindingLanguageTag => $bindingDomain)
				{
					if (stristr(JURI::current(), $bindingDomain) == true)
					{
						// Change the current default language
						$newLanguageTag = $bindingLanguageTag;
						break;
					}
				}

				// Make sure the current language-tag is registered as current
				if (!empty($newLanguageTag) and $newLanguageTag != self::$default_lang)
				{
					JFactory::getLanguage()->setLanguage($newLanguageTag);
					JFactory::getLanguage()->load('joomla', JPATH_SITE, $newLanguageTag, true);
				}
			}
		}

		return $rt;
	}

	/**
	 * Event onAfterInitialise
	 *
	 * @access public
	 * @param
	 *        	null
	 * @return null
	 */
	public function onAfterInitialise()
	{
		// Remove the cookie if it exists
		$languageHash = JApplication::getHash('language');
		if (isset($_COOKIE[$languageHash]))
		{
			$conf = JFactory::getConfig();
			$cookie_domain = $conf->get('config.cookie_domain', '');
			$cookie_path = $conf->get('config.cookie_path', '/');
			setcookie($languageHash, '', time() - 3600, $cookie_path, $cookie_domain);
			JFactory::getApplication()->input->cookie->set($languageHash, '');
		}

		// Reset certain parameters of the parent plugin
		$this->params->set('remove_default_prefix', 1);
		$this->params->set('detect_browser', 0);

		// Enable item-associations
		$application = JFactory::getApplication();
		$application->item_associations = $this->params->get('item_associations', 1);
		$application->menu_associations = $this->params->get('item_associations', 1);

		// If this is the Administrator-application, or if debugging is set, do nothing
		if ($application->isSite() == false)
		{
			return;
		}

		// Disable browser-detection
		$application->setDetectBrowser(false);

		// Detect the language
		$languageTag = JRequest::getString('language');

		// Get the bindings
		$bindings = $this->getBindings();
		if (empty($bindings))
		{
			return;
		}

		// Check for the binding of the current language
		if (!empty($languageTag))
		{
			if (array_key_exists($languageTag, $bindings))
			{
				$domain = $bindings[$languageTag];
				if (stristr(JURI::current(), $domain) == false)
				{

					// Add URL-elements to the domain
					$domain = $this->getUrlFromDomain($domain);

					// Replace the current domain with the new domain
					$currentUrl = JURI::current();
					$newUrl = str_replace(JURI::base(), $domain, $currentUrl);

					// Set the cookie
					$conf = JFactory::getConfig();
					$cookie_domain = $conf->get('config.cookie_domain', '');
					$cookie_path = $conf->get('config.cookie_path', '/');
					// setcookie(JApplication::getHash('language'), $languageTag, time() + 365 * 86400, $cookie_path, $cookie_domain);
					setcookie(JApplication::getHash('language'), null, time() - 365 * 86400, $cookie_path, $cookie_domain);

					// Redirect
					$application->redirect($newUrl);
					$application->close();
				}
			}
		}
		else
		{

			// Check if the current default language is correct
			foreach ($bindings as $bindingLanguageTag => $bindingDomain)
			{
				if (stristr(JURI::current(), $bindingDomain) == true)
				{

					// Change the current default language
					$newLanguageTag = $bindingLanguageTag;

					break;
				}
			}
		}

		// Override the default language if the domain was matched
		if (empty($languageTag) && !empty($newLanguageTag))
		{
			$languageTag = $newLanguageTag;
		}

		// Make sure the current language-tag is registered as current
		if (!empty($languageTag))
		{
			JRequest::setVar('language', $languageTag);
			JFactory::getLanguage()->setLanguage($languageTag);

			$component = JComponentHelper::getComponent('com_languages');
			$component->params->set('site', $languageTag);

			self::$default_lang = $languageTag;
			self::$default_sef = self::$lang_codes[self::$default_lang]->sef;
		}

		// Run the event of the parent-plugin
		$rt = parent::onAfterInitialise();

		// Re-enable item-associations
		$application = JFactory::getApplication();
		$application->item_associations = $this->params->get('item_associations', 1);
		$application->menu_associations = $this->params->get('item_associations', 1);

		return $rt;
	}

	/**
	 * Event onAfterRender
	 *
	 * @access public
	 * @param
	 *        	null
	 * @return null
	 */
	public function onAfterRender()
	{
		// Reset certain parameters of the parent plugin
		$this->params->set('remove_default_prefix', 1);
		$this->params->set('detect_browser', 0);

		// If this is the Administrator-application, or if debugging is set, do nothing
		$application = JFactory::getApplication();
		if ($application->isAdmin() || JDEBUG)
		{
			return;
		}

		// Fetch the document buffer
		$buffer = JResponse::getBody();

		// Get the bindings
		$bindings = $this->getBindings();
		if (empty($bindings))
		{
			return;
		}

		// Loop through the languages and check for any URL
		$languages = JLanguageHelper::getLanguages('sef');
		foreach ($languages as $languageSef => $language)
		{

			$languageCode = $language->lang_code;
			if (!isset($bindings[$languageCode]))
				continue;
			$domain = $bindings[$languageCode];
			$domain = $this->getUrlFromDomain($domain);

			// Replace full URLs
			if (preg_match_all('/([^\'\"]+)\/' . $languageSef . '\//', $buffer, $matches))
			{
				foreach ($matches[0] as $match)
				{
					$workMatch = str_replace('index.php/', '', $match);
					$matchDomain = $this->getDomainFromUrl($workMatch);
					if (empty($matchDomain) || in_array($matchDomain, $bindings) || in_array('www.' . $matchDomain, $bindings))
					{
						$buffer = str_replace($match, $domain, $buffer);
					}
				}
			}

			// Replace shortened URLs
			if (preg_match_all('/([\'\"]{1})\/(' . $languageSef . ')\//', $buffer, $matches))
			{
				foreach ($matches[0] as $index => $match)
				{
					$buffer = str_replace($match, $matches[1][$index] . $domain, $buffer);
				}
			}
		}

		JResponse::setBody($buffer);
	}

	/**
	 * Method to get the bindings for languages
	 *
	 * @access public
	 * @param
	 *        	null
	 * @return null
	 */
	protected function getBindings()
	{
		$bindings = trim($this->params->get('bindings'));
		if (empty($bindings))
		{
			return array();
		}

		$bindingsArray = explode("\n", $bindings);
		$bindings = array();
		foreach ($bindingsArray as $index => $binding)
		{
			$binding = explode('=', $binding);
			if (isset($binding[0]) && isset($binding[1]))
			{

				$languageCode = trim($binding[0]);
				$languageCode = str_replace('_', '-', $languageCode);

				$domain = trim($binding[1]);

				$bindings[$languageCode] = $domain;
			}
		}

		return $bindings;
	}

	/*
	 * Helper-method to get a proper URL from the domain @access public @param string @return string
	 */
	protected function getUrlFromDomain($domain)
	{
		// Add URL-elements to the domain
		if (preg_match('/^(http|https):\/\//', $domain) == false)
		{
			$domain = 'http://' . $domain;
		}

		if (preg_match('/\/$/', $domain) == false)
		{
			$domain = $domain . '/';
		}

		if (JFactory::getConfig()->get('sef_rewrite', 0) == 0 && preg_match('/index\.php/', $domain) == false)
		{
			$domain = $domain . 'index.php/';
		}

		return $domain;
	}

	/*
	 * Helper-method to get a proper URL from the domain @access public @param string @return string
	 */
	protected function getDomainFromUrl($url)
	{
		// Add URL-elements to the domain
		if (preg_match('/^(http|https):\/\/([a-zA-Z0-9\.\-\_]+)/', $url, $match))
		{
			$domain = $match[2];
			$domain = preg_replace('/^www\./', '', $domain);
			return $domain;
		}
		return false;
	}
}
