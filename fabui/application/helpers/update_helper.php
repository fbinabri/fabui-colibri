<?php
/**
 *
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 *
 */
if ( ! function_exists('isInternetAvaiable'))
{
	/**
	 * Check whether there is access to the internet.
	 */
	function isInternetAvaiable() {
		return !$sock = @fsockopen('www.google.com', 80, $num, $error, 2) ? false : true;
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('getLocalBundles')){
	
	/**
	 * return all bundles
	 */
	function getLocalBundles()
	{
		$list = explode(PHP_EOL, trim(shell_exec('colibrimngr list')));
		unset($list[0]); //remove comand argument
		
		$bundles = array();
		$states = array('A'=> 'active', 'D'=>'disabled');
		
		$re = '/\[(\!*)(A|D|)\]\s(\d+)\s:(\s.*?\s):\s([0-9.]+)/';
		
		foreach($list as $line){
			preg_match_all($re, $line, $matches);
			$bundle_name = isset($matches[4]) ? trim($matches[4][0]) : '';
			$temp = array(
					'name'     => $bundle_name, 
					'version'  => isset($matches[5]) ? $matches[5][0] : '',
					'state'    => isset($matches[2]) ? $states[$matches[2][0]] : '',
					'priority' => isset($matches[3]) ? $matches[3][0] : '',
					'invalid'  => isset($matches[1]) && $matches[1][0] == '!' ? true : false,
			);
			$bundles[$bundle_name] = $temp;
		}
		return $bundles;
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('getRemoteBundles')){
	
	/**
	 * 
	 */
	function getRemoteBundles()
	{
		$systemRemoteVersions = getSystemRemoteVersions();
		if($systemRemoteVersions != false)
			return $systemRemoteVersions['bundles'];
		return false;
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('getSystemRemoteVersions')){

	/**
	 *
	 */
	function getSystemRemoteVersions()
	{
		$CI =& get_instance();
		$CI->config->load('fabtotum');
		$CI->load->helper('os_helper');
		$endpoint = $CI->config->item('colibri_endpoint').getArchitecture().'/version.json';
		$remoteContent = getRemoteFile($endpoint);
		if($remoteContent != false) return json_decode($remoteContent, true);
		else false;
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('getArchitecture'))
{
	/**
	 * return architecture for updates
	 */
	function getArchitecture()
	{
		$versions = array(
			'armv7l' => 'armhf',
			'armv6'  => 'armhf',
		);
		$arch = trim(str_replace(PHP_EOL, '', shell_exec('uname -m')));
		return $versions[$arch];
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('getBundlesStatus'))
{
	/**
	 *  check wich bundle needs an update
	 */
	function getBundlesStatus()
	{
		$localBundles  = getLocalBundles();
		$remoteBundles = getRemoteBundles();
		
		$status = array();
		
		foreach($localBundles as $bundleName => $localBundleData)
		{
			$remoteBundle = $remoteBundles[$bundleName];
			$latestVersion = str_replace('v', '', $remoteBundle['latest']);
			$status[$bundleName] = array(
					'latest' => $latestVersion,
					'local' => $localBundleData['version'],
					'update' => version_compare($localBundleData['version'], $latestVersion) == -1 ? true : false
			); 
			
		}
		return $status;
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('flashFirmware'))
{
	/**
	 *  Flash Firmware
	 */
	function flashFirmware($type, $argument = '')
	{
		$CI =& get_instance();
		$CI->load->helper('fabtotum');
		$args = '';
		
		switch($type)
		{
			case "factory":
				$args = 'factory';
				break;
			case "custom":
				$args = 'update '.$argument;
				break;
			case "remote": // remote update
				$args = 'remote-update '.$argument;
				break;
			default:
				return false;
		}
		
		return startBashScript('totumduino_manager.sh', $args, false, true);
	}
}
?>
