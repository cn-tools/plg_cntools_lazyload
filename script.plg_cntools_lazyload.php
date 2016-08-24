<?php
/**
 * plg_cntools_lazyload - Joomla Plugin
 *
 * @package    Joomla
 * @subpackage Plugin
 * @author Clemens Neubauer
 * @link https://github.com/cn-tools/
 * @license		GNU/GPL, see LICENSE.php
 * plg_cntools_bannerext is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
defined('_JEXEC') or die();

class PlgSystemPlg_CNTools_LazyLoadInstallerScript
{
	public function preflight($type, $parent)
	{
		
		$minJoomla = '3.6.0';
		$errorCount = '0';
	
		if (!version_compare(JVERSION, $minJoomla, 'ge'))
		{
			$error = "<p>You need Joomla! $minJoomla or later to install this extension!<br/>Actual Joomla! Version:".JVERSION."</p>";
			JLog::add($error, JLog::WARNING, 'jerror');
			$errorCount++;
		}
		
		if($errorCount != 0)
		{
			return false;
		}

		return true;
	}
}
