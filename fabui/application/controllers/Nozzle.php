<?php
/**
 * 
 * @author Krios Mane
 * @author Daniel Kesler
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */

defined('BASEPATH') OR exit('No direct script access allowed');
 
class Nozzle extends FAB_Controller {

	public function index($type = 'length')
	{
		switch($type){
			case 'length':
				$this->doHeightCalibration();
				break;
		}
	}

	// length calibration controller
	public function doHeightCalibration()
	{
		//load libraries, helpers, model
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper('fabtotum_helper');
		$this->config->load('fabtotum');
		$extPath = $this->config->item('ext_path');
		
		//data
		$data = array();
		
		//main page widget
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-feeder-calibration';
		$widget->header = array('icon' => 'icon-fab-print', "title" => "<h2>Nozzle Height Calibration</h2>");
		$widget->body   = array('content' => $this->load->view('nozzle/height_widget', $data, true ), 'class'=>'fuelux');
		
		$this->addJsInLine($this->load->view('nozzle/height_js', $data, true));
		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	public function getLength()
	{
		$this->load->helper('fabtotum_helper');
		$_result = doMacro('read_eeprom');
		$probe_length = $_result['reply']['probe_length'];
		$this->output->set_content_type('application/json')->set_output(
				json_encode( array('probe_length' => $probe_length) )
			);
	}

	public function overrideLenght($override_by)
	{
		$this->load->helper('fabtotum_helper');
		$_result = doMacro('read_eeprom');
		$old_probe_lenght = $_result['reply']['probe_length'];
		$new_probe_lenght = abs($old_probe_lenght) - $override_by;
		
		// override probe value
		doGCode('M710 S'.$new_probe_lenght );
		
		$this->output->set_content_type('application/json')->set_output(
				json_encode( array(
					'probe_length' => $new_probe_lenght,
					'old_probe_lenght' => $old_probe_lenght,
					'over' => $override_by) )
			);
	}
	/**
	 * 
	 */
	public function calibrateHeight()
	{
		$this->load->helper('fabtotum_helper');
		$_result = doMacro('probe_setup_calibrate');
		$this->output->set_content_type('application/json')->set_output(
			json_encode( array(
				'old_probe_lenght' => $_result['reply']['old_probe_lenght'],
				'probe_length'     => $_result['reply']['new_probe_length'],
				'z_max'            => $_result['reply']['z_max']
				) )
			);
	}
	/**
	 * 
	 */
	public function prepare()
	{
		$this->load->helper('fabtotum_helper');
		$result = doMacro('probe_setup_prepare');
		$this->output->set_content_type('application/json')->set_output(json_encode( $result ));
	}
}
 
?>