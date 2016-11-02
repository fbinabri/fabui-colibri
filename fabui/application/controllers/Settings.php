<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 defined('BASEPATH') OR exit('No direct script access allowed');
 
class Settings extends FAB_Controller {
	
	/***
	 *  Settings - Hardware page
	 */
	public function hardware(){
			
		//load libraries, helpers, model, config
		$this->load->library('smart');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('form');
		$this->config->load('fabtotum');
		//load settings (default or customs)
		if(!file_exists($this->config->item('default_settings'))){ // if default settings file doesn't exits, create it
			createDefaultSettings();
 		}
		if(!file_exists($this->config->item('custom_settings'))){
			copy($this->config->item('default_settings'), $this->config->item('custom_settings'));
		}
		$data['defaultSettings'] = json_decode(file_get_contents($this->config->item('default_settings')), true);
		$data['customSettings']  = json_decode(file_get_contents($this->config->item('custom_settings')), true);
		$data['yesNoOptions'] = array('1' => 'Yes', '0' => 'No');
		$data['customizeActionsOptions'] = array('none' => 'None', 'shutdown' => 'Shutdown');
		//main page widget
		$widgetOptions = array(
			'sortable' => false, 'fullscreenbutton' => true,'refreshbutton' => false,'togglebutton' => false,
			'deletebutton' => false, 'editbutton' => false, 'colorbutton' => false, 'collapsed' => false
		);
		$headerToolbar = '
			<ul class="nav nav-tabs pull-right">
				<li class="active"><a data-toggle="tab" href="#hardware-tab"> Hardware</a></li>
				<li><a data-toggle="tab" href="#safety-tab"> Safety</a></li>
				<li><a data-toggle="tab" href="#homing-tab"> Homing</a></li>
				<li><a data-toggle="tab" href="#customized-actions-tab"> Customized actions</a></li>
				<li><a data-toggle="tab" href="#print-tab"> Print</a></li>
				<li><a data-toggle="tab" href="#milling-tab"> Milling</a></li>
				<li><a data-toggle="tab" href="#lighting-tab"> Lighting</a></li>
			</ul>';
		$widgeFooterButtons = $this->smart->create_button('Save', 'primary')->attr(array('id' => 'save'))->attr('data-action', 'exec')->icon('fa-save')->print_html(true);
							  
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'hardware-settings-widget';
		$widget->header = array('icon' => 'fa-cog', "title" => "<h2>Hardware</h2>", 'toolbar'=>$headerToolbar);
		$widget->body   = array('content' => $this->load->view('settings/hardware_widget', $data, true ), 'class'=>'no-padding', 'footer'=>$widgeFooterButtons);
		
		$this->addJsInLine($this->load->view('settings/hardware_js', $data, true));
		//$this->addCSSInLine('<style type="text/css">.custom_settings{display:none !important;}</style>'); 
		$this->content = $widget->print_html(true);
		$this->view();
	}

	/**
	 * save settings 
	 */
	public function saveSettings()
	{
		//get data from post
		$postData = $this->input->post();
		//load libraries, helpers, model, config
		$this->load->helpers('utility_helper');
		$this->load->helpers('fabtotum_helper');
		//create settings array
		$settingsToSave = arrayFromPost($postData);
		if($postData['settings_type'] == 'default'){ //don't override those vaules for default settings
			unset($settingsToSave['e']);
			unset($settingsToSave['a']);
			unset($settingsToSave['feeder']); 
			unset($settingsToSave['invert_x_endstop_logic']);
		}
		//load settings
		$loadedSettings = loadSettings($postData['settings_type']);
		$newSettings = array_replace ($loadedSettings, $settingsToSave);
		saveSettings($newSettings, $postData['settings_type']);
		if($postData['settings_type'] == 'custom'){
			$defaultSettings = loadSettings('default');
			saveSettings(array_replace($defaultSettings, array('settings_type' => 'custom')), 'default');
		}
		//update settings on session
		$this->session->settings = $newSettings;
		//reload configuration settings
		resetController();
		$this->output->set_content_type('application/json')->set_output(true);
	}
	
	/***
	 *  Settings - Network - Ethernet page
	 */
	public function ethernet($action = '')
	{
		$postData = $this->input->post();
		
		//load libraries, helpers, model, config
		$this->load->library('smart');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('os_helper');
		$this->load->helper('form');
		$this->config->load('fabtotum');

		$data = array();
		$data['info'] = getEthInfo();
		$data['action'] = $action;
		
		//main page widget
		$widgetOptions = array(
			'sortable' => false, 'fullscreenbutton' => true,'refreshbutton' => false,'togglebutton' => false,
			'deletebutton' => false, 'editbutton' => false, 'colorbutton' => false, 'collapsed' => false
		);
		
		$headerToolbar = '';
		
		$widgeFooterButtons = ''; //$this->smart->create_button('Save new address', 'primary')->attr(array('id' => 'save'))->attr('data-action', 'exec')->icon('fa-save')->print_html(true);
		
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'ethernet-settings-widget';
		$widget->header = array('icon' => 'fa-sitemap', "title" => "<h2>Ethernet</h2>", 'toolbar'=>$headerToolbar);
		$widget->body   = array('content' => $this->load->view('settings/ethernet_widget', $data, true ), 'class'=>'no-padding', 'footer'=>$widgeFooterButtons);
		
		$this->addJsInLine($this->load->view('settings/ethernet_js', $data, true));
		$this->addJSFile('/assets/js/plugin/inputmask/jquery.inputmask.bundle.js');
		//$this->addCSSInLine('<style type="text/css">.custom_settings{display:none !important;}</style>'); 
		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	public function ethernetSaveAddress()
	{
		$postData = $this->input->post();
		
		$this->load->helper('os_helper');
		$result = setEthIPAddress( $postData['ip'] );
		
		$this->output->set_content_type('html')->set_output('ok');
	}
	
	/***
	 *  Settings - Network - WiFi page
	 */
	public function wifi()
	{
		//load libraries, helpers, model, config
		$this->load->library('smart');
		$this->load->helper('os_helper');
		$this->load->helper('form');
		$this->config->load('fabtotum');
		
		$data['wlanInfo'] = getWlanInfo();
		
		//page widget
		$widgetOptions = array(
			'sortable' => false, 'fullscreenbutton' => true,'refreshbutton' => false,'togglebutton' => false,
			'deletebutton' => false, 'editbutton' => false, 'colorbutton' => false, 'collapsed' => false
		);
		
		$widgeFooterButtons = $this->smart->create_button('Hidden Wifi', 'default')->attr(array('id' => 'hiddenWifiButton'))->icon('fa-user-secret')->print_html(true).' '.
							  $this->smart->create_button('Scan', 'primary')->attr(array('id' => 'scanButton'))->attr('data-action', 'exec')->icon('fa-search')->print_html(true);
		$headerToolbar = '';
		if(isset($data['wlanInfo']['ip_address']) && $data['wlanInfo']['ip_address'] != ''){
			$headerToolbar = '<div class="widget-toolbar" role="menu"><button class="btn btn-default show-details"><i class="fa fa-angle-double-up"></i> Details </button></div>';
		}
		
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'hardware-wifi-widget';
		$widget->header = array('icon' => 'fa-wifi', "title" => "<h2>Wi-Fi </h2>", 'toolbar'=>$headerToolbar);
		$widget->body   = array('content' => $this->load->view('settings/wifi_widget', $data, true ), 'class'=>'no-padding', 'footer'=>$widgeFooterButtons);
		$this->addJsInLine($this->load->view('settings/wifi_js', $data, true));
		$this->addJSFile('/assets/js/plugin/bootstrap-progressbar/bootstrap-progressbar.min.js'); // progressbar*/
		$this->addJSFile('/assets/js/plugin/jquery-validate/jquery.validate.min.js'); //validator
		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	/**
	 * scan wifi networks
	 * @return json all scanned networks
	 */
	public function scanWifi($interface = 'wlan0')
	{
		//load helpers
		$this->load->helper('os_helper');
		$nets = scanWlan2($interface);
		$this->output->set_content_type('application/json')->set_output(json_encode($nets));
	}
	
	/**
	 * 
	 */
	public function wifiAction($action)
	{
		$data = $this->input->post();
		$essid = $data['essid'];
		$password = $data['password'];
		//load helpers
		$this->load->helper('os_helper');
		if($action == 'connect') wifiConnect($essid, $password);
		else wifiDisconnect();
		$this->output->set_content_type('application/json')->set_output(json_encode(array(true)));
	}
	
	/***
	 * 
	 */
	public function dnssd()
	{
		$postData = $this->input->post();
		
		//load libraries, helpers, model, config
		$this->load->library('smart');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('os_helper');
		$this->load->helper('form');
		$this->config->load('fabtotum');

		$data = array();
		$data['current_hostname'] = getHostName();
		$data['current_name'] = getAvahiServiceName();
		
		//main page widget
		$widgetOptions = array(
			'sortable' => false, 'fullscreenbutton' => true,'refreshbutton' => false,'togglebutton' => false,
			'deletebutton' => false, 'editbutton' => false, 'colorbutton' => false, 'collapsed' => false
		);
		
		$headerToolbar = '';
		
		$widgeFooterButtons = $this->smart->create_button('Save', 'primary')->attr(array('id' => 'save'))->attr('data-action', 'exec')->icon('fa-save')->print_html(true);
		
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'ethernet-settings-widget';
		$widget->header = array('icon' => 'fa-binoculars', "title" => "<h2>Make the FABtotum Personal Fabricator easily disoverable on local network</h2>", 'toolbar'=>$headerToolbar);
		$widget->body   = array('content' => $this->load->view('settings/dnssd_widget', $data, true ), 'class'=>'no-padding', 'footer'=>$widgeFooterButtons);
		
		$this->addJsInLine($this->load->view('settings/dnssd_js', $data, true));
		$this->addJSFile('/assets/js/plugin/jquery-validate/jquery.validate.min.js'); //validator
		//$this->addCSSInLine('<style type="text/css">.custom_settings{display:none !important;}</style>'); 
		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	public function setHostname()
	{
		$postData = $this->input->post();
		$hostname = $postData['hostname'];
		$name = $postData['name'];
		
		$this->load->helper('os_helper');
		$result = setHostName($hostname, $name);
		echo $result;
	}
	
	public function network()
	{
		$postData = $this->input->post();
		
		//load libraries, helpers, model, config
		$this->load->library('smart');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('os_helper');
		$this->load->helper('form');
		$this->config->load('fabtotum');

		$data = array();
		$data['yesNoOptions'] = array('1' => 'Yes', '0' => 'No');
		$data['current_hostname'] = getHostName();
		$data['current_name'] = getAvahiServiceName();
		
		$ifaces_data = getInterfaces();
		
		//main page widget
		$widgetOptions = array(
			'sortable' => false, 'fullscreenbutton' => true,'refreshbutton' => false,'togglebutton' => false,
			'deletebutton' => false, 'editbutton' => false, 'colorbutton' => false, 'collapsed' => false
		);
		
		$tabs_title = '';
		$is_active = 'active';
		$if_idx = array('eth' => 1, 'wlan' => 1);
		$if_number = array('eth' => 0, 'wlan' => 0);
		foreach($ifaces_data as $iface => $info)
		{
			if(array_key_exists('wireless', $info) )
				$if_number['wlan'] += 1;
			else
				$if_number['eth'] += 1;
			
		}
		
		$tabs_content = '';
		
		foreach($ifaces_data as $iface => $info)
		{
			/* Convert <ip>/<prefix> format to <ip> & <netmask> */
			if( $info['ipv4_address'] != '' )
			{
				$info['netmask_address'] = cidr2NetmaskAddr($info['ipv4_address']);
				$tmp = explode('/', $info['ipv4_address'])[0];
				$info['ipv4_address'] = $tmp;
			}
			else
			{
				$info['ipv4_address'] = '0.0.0.0';
				$info['netmask_address'] = '255.255.255.0';
				$info['gateway'] = '0.0.0.0';
			}
			
			if(array_key_exists('wireless', $info) )
			{
				if($info['wireless']['can_be_ap'] == 'yes')
					$wifiModes = array('static' => 'Static', 'dhcp' => 'Automatic (DHCP)', 'static-ap' => 'Access Point');
				else
					$wifiModes = array('static' => 'Static', 'dhcp' => 'Automatic (DHCP)');
				
				$wifiChannels = array('1' => 'Static', 
									  '2' => 'Automatic (DHCP)');
				
				if(array_key_exists('mode', $info['wireless']) )
					if($info['wireless']['mode'] == 'accesspoint' )
						$info['address_mode'] = 'static-ap';
				
				if(!array_key_exists('passphrase', $info['wireless']) )
				{
					$info['wireless']['passphrase'] = '';
				}
					
				$if_type = 'wlan';
				$title = 'Wireless';
				$tab_data = array(
					'iface' => $iface,
					'info' => $info,
					'addressModeWiFi' => $wifiModes
				);
				$tabs_content .= $this->load->view('settings/wireless_tab', $tab_data, true );
			}
			else
			{
				$if_type = 'eth';
				$title = 'Ethernet';
				$tab_data = array(
					'iface' => $iface,
					'info' => $info,
					'addressModeEth' => array('static' => 'Static', 'dhcp' => 'Automatic (DHCP)')
				);
				$tabs_content .= $this->load->view('settings/ethernet_tab', $tab_data, true );
			}
			
			if( $if_number[$if_type] > 1)
				$title .= ' ('.$if_idx[$if_type].')';
			$if_idx[$if_type] += 1;
			
			$tabs_title .= '<li data-net-type="'.$if_type.'" data-attribute="'.$iface.'" class="tab '.$is_active.'"><a data-toggle="tab" href="'.$iface.'-tab"> '.$title.'</a></li>';
			$is_active = '';
			
			
		}
		$data['iface_tabs'] = $tabs_content;
		
		$tabs_title .= '<li data-attribute="dnssd" class="tab"><a data-toggle="tab" href="#dnssd-tab"> DNS-SD</a></li>';
		
		$headerToolbar = '<ul class="nav nav-tabs pull-right">' . $tabs_title .'</ul>';
		
		$widgeFooterButtons = $this->smart->create_button('Save', 'primary')->attr(array('id' => 'save'))->attr('data-action', 'exec')->icon('fa-save')->print_html(true);
		
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'network-settings-widget';
		$widget->header = array('icon' => 'fa-globe', "title" => "<h2>Network settings</h2>", 'toolbar'=>$headerToolbar);
		$widget->body   = array('content' => $this->load->view('settings/network_widget', $data, true ), 'class'=>'no-padding', 'footer'=>$widgeFooterButtons);
		
		$this->addJsInLine($this->load->view('settings/network_js', $data, true));
		$this->addJSFile('/assets/js/plugin/jquery-validate/jquery.validate.min.js'); //validator
		$this->addJSFile('/assets/js/plugin/inputmask/jquery.inputmask.bundle.js');	
		//$this->addCSSInLine('<style type="text/css">.custom_settings{display:none !important;}</style>'); 
		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	public function saveNetworkSettings()
	{
		//get data from post
		$postData = $this->input->post();
		$result = true;
		
		switch($postData['active-type'])
		{
			case "eth":
				break;
			case "wlan":
				break;
			case "dnssd":
				break;
			default:
				$result = false;
		}
		
		$this->output->set_content_type('application/json')->set_output($result);
	}

 }
 
?>
