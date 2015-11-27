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

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class PlgSystemPlg_CNTools_LazyLoad extends JPlugin
{
	var $_ImagesCount;
	var $_ClassPraefix;
	var $_Placeholder1;
	var $_Placeholder2;
	var $_BaseUrl;
	
	//-- constructor ----------------------------------------------------------
	public function PlgSystemPlg_CNTools_LazyLoad( &$subject, $config )
	{
		parent::__construct( $subject, $config );
		$this->_ImagesCount = 0;
		$this->_ClassPraefix = 'JPLGCNTLL';
		$this->_BaseUrl = JURI::base();
		
		//-- build placeholder string ---------------------------------
		$this->_Placeholder1 = '';
		$this->_Placeholder2 = '';
		if ($this->params->get('placeholder'))
		{
			$lFileName = 'plugins/system/plg_cntools_lazyload/assets/images/' . $this->params->get('placeholder', 'transparent.gif');
			if (file_exists($lFileName))
			{
				$this->_Placeholder1 = ' src=\'' . $this->_BaseUrl . $lFileName . '\'';
				$this->_Placeholder2 = ' src="' . $this->_BaseUrl . $lFileName . '"';
			}
		}
	}
	
	//-- onAfterRender --------------------------------------------------------
	public function onAfterRender ()
	{
		$app = JFactory::getApplication();
		if($app->isAdmin() === true)
		{
			return;
		}
		
		//-- find all to use  images --------------------------------------
		$lFullDocument = JResponse::getBody();
		if ($this->params->get('container', '') != '')
		{
			include_once('plugins/system/plg_cntools_lazyload/assets/simple_html_dom.php');
			$lWorkDoc = new simple_html_dom();
			$lWorkDoc->load($lFullDocument);
			$lWorkHtmlText = $lWorkDoc->getElementById($this->params->get('container', ''))->innertext;
			if (!empty($lWorkHtmlText))
			{
				$lReworkedHtmlText = $this->renderHTML($lWorkHtmlText);
				$lWorkDoc->getElementById($this->params->get('container', ''))->innertext = $lReworkedHtmlText;
				$lFullDocument = $lWorkDoc->outertext;
			}
			unset($lWorkDoc);
		}
		else
		{
			$lFullDocument = $this->renderHTML($lFullDocument);
		}
		
		//-- add 'header'-part ------------------------------------------------
		if ($this->_ImagesCount >= 1)
		{
			$lScript = '';
			//-- add no display in noscript section ---------------------------
			if (($this->params->get('placeholder', '') != '') and ($this->params->get('noscript')))
			{
				$lScript .= '<noscript><style>.' . $this->_ClassPraefix . '{ display: none !important; }</style></noscript>';
			}

			//-- add JS file and jQuery execute part --------------------------
			$lScript .= '<script src="' . $this->_BaseUrl . 'plugins/system/plg_cntools_lazyload/assets/js/' . $this->params->get('jsfile') . '" type="text/javascript"></script>';
			$lScript .= '<script type="text/javascript">jQuery(function($){ $("img.' . $this->_ClassPraefix . '").lazyload({' . $this->getLazyLoadOptions() . '}) });</script>';

			$lFullDocument = str_ireplace('</head>', $lScript.'</head>', $lFullDocument);
		}		

		JResponse::setBody($lFullDocument);

		return true;
	}
	
	//-- get options for LazyLoad javascript section --------------------------
	protected function getLazyLoadOptions()
	{
		$lOptions = '';
		if ($this->params->get('threshold', '') != '')
		{
			$lOptions = $this->addJSOption($lOptions, 'threshold: ' . $this->params->get('threshold', '') . '');
		}
		if ($this->params->get('effect', '') != '')
		{
			$lOptions = $this->addJSOption($lOptions, 'effect: "' . $this->params->get('effect', '') . '"');
		}
/*
		if ($this->params->get('container', '') != '')
		{
			$lOptions = $this->addJSOption($lOptions, 'container: $("#' . $this->params->get('container', '') . '")');
		}
*/
		if ($this->params->get('event', '') != '')
		{
			$lOptions = $this->addJSOption($lOptions, 'event: "' . $this->params->get('event', '') . '"');
		}

		return $lOptions;
	}
	
	//-- addOption ------------------------------------------------------------
	protected function addJSOption($BaseValue, $AddText){
		if ($BaseValue!='') { $BaseValue .= ', ';}
		$BaseValue .= $AddText;
		return $BaseValue;
	}

	//-- renderHTML -----------------------------------------------------------
	protected function renderHTML($htmlcode)
	{
		$regex = '/(<img).*?(>)/is';
		$htmlcode = preg_replace_callback($regex, array('PlgSystemPlg_CNTools_LazyLoad', 'renderIMG'), $htmlcode, -1, $count);
		return $htmlcode;
	}
	
	//-- renderIMG ------------------------------------------------------------
	public function renderIMG(&$matches){
		$lResult = $matches[0];
		
		$lDoRender = true;
/*
		//-- check images if it should be used by class -----------------------
		if ($this->params->get('inexcss') != '0')
		{
			$image_class = $this->params->get('inexlazycss', '');
			if ($image_class != '')
			{
				if ($this->params->get('inexcss', '') == '1')
				{ // only use images with specific class param
					if(!preg_match('@class=[\"\'].*'.$image_class.'.*[\"\']@Ui', $lResult))
					{
						$lDoRender = false;
					}
				}
				elseif ($this->params->get('inexcss', '') == '0')
				{ // ignore images with specific class param
					if(preg_match('@class=[\"\'].*'.$image_class.'.*[\"\']@Ui', $lResult))
					{
						$lDoRender = false;
					}
				}
			}
		}
*/
		if ($lDoRender)
		{
			$lImageSrc = '';
			$lOrigImgHtmlCode = $lResult;
			list($lWorkString, $lImageSrc) = $this->reworkSrc($lResult);
			
			//-- check images if it should be used by image size --------------
			if ((!empty($lImageSrc)) and ($this->params->get('imagesize')) and file_exists($lImageSrc))
			{
				list($width, $height) = getimagesize($lImageSrc);
				if (isset($width) or isset($height))
				{
					if (($width >= 1) and ($width >= $this->params->get('imagesize')))
					{
						//do nothing
					}
					elseif (($height >= 1) and ($height >= $this->params->get('imagesize')))
					{
						// do nothing
					}
					else 
					{
						// image is to small for lazyload
						unset($lWorkString);
					}
				}
			}

			if (!empty($lWorkString))
			{
				$lWorkString = $this->reworkClass($lWorkString);
				$lWorkString = $this->reworkNoScript($lWorkString, $lOrigImgHtmlCode);
				$this->_ImagesCount = $this->_ImagesCount + 1;
				
				$lResult = $lWorkString;
			}
		}

		return $lResult;
	}

	//-- change src and data-original in the html text ------------------------
	protected function reworkSrc($htmlcode)
	{
		$imageUrl = '';
		if (stripos($htmlcode, ' src="'))
		{
			$imageUrl = $this->getSrc($htmlcode, '"');
			$htmlcode = str_ireplace(' src="', '' . $this->_Placeholder2 . ' data-original="', $htmlcode);
		}
		elseif (stripos($htmlcode, ' src=\''))
		{
			$imageUrl = $this->getSrc($htmlcode, '\'');
			$htmlcode = str_ireplace(' src=\'', '' . $this->_Placeholder1 . ' data-original=\'', $htmlcode);
		}
		
		return array($htmlcode, $imageUrl);
	}
	
	//-- getSrc ---------------------------------------------------------------
	protected function getSrc($matches, $trenner)
	{
		$lStartPos = stripos($matches, 'src='.$trenner) + 5;
		$lValue = substr($matches, $lStartPos);
		$lEndPos = stripos($lValue, $trenner);
		$lValue = substr($matches, $lStartPos, $lEndPos);
		$lValue = substr($lValue, strlen($this->_BaseUrl));
		
		return $lValue;
	}
	
	//-- change class option --------------------------------------------------
	protected function reworkClass($htmlcode)
	{
		if (stripos($htmlcode, ' data-original="'))
		{
			if (stripos($htmlcode, ' class='))
			{
				$htmlcode = str_ireplace(' class="', ' class="' . $this->_ClassPraefix . ' ', $htmlcode);
			}
			else 
			{
				$htmlcode = str_ireplace(' data-original=', ' class="' . $this->_ClassPraefix . '" data-original=', $htmlcode);
			}
		}
		elseif (stripos($htmlcode, ' data-original=\''))
		{
			if (stripos($htmlcode, ' class='))
			{
				$htmlcode = str_ireplace(' class=\'', ' class=\'' . $this->_ClassPraefix . ' ', $htmlcode);
			}
			else 
			{
				$htmlcode = str_ireplace(' data-original=', ' class=\'' . $this->_ClassPraefix . '\' data-original=', $htmlcode);
			}
		}
		return $htmlcode;
	}

	//-- add noscript option --------------------------------------------------
	protected function reworkNoScript($htmlcode, $origImgHtmlCode)
	{
		if ($this->params->get('noscript') and (!empty($origImgHtmlCode)))
		{
			$htmlcode .= '<noscript>' . $origImgHtmlCode . '</noscript>';
		}
		
		return $htmlcode;
	}
}
?>
