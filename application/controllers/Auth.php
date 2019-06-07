<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

	function __construct(){
		parent::__construct();
		$this->load->library('authentication');
		$this->load->model('apimodel');
	}

	public function signin(){
		// Initialize response
		$result['success'] = false;
		$result['status'] = 'error';
		$result['message'] = '';

		if (empty($this->input->post('username'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			die();
    }
		$result = $this->authentication->signin($this->input->post('username'), $this->input->post('password'), true);
		echo json_encode($result);
	}

	public function validateSession(){
		// Initialize response
		$result['success'] = false;
		$result['status'] = 'error';
		$result['message'] = '';

		if (empty($this->input->post('hash'))){
			$result['success'] = false;
			$result['status'] = 'error';
			$result['message'] = 'missing hash';
			echo json_encode($result);
			die();
    }

    $user_data = $this->authentication->getUserData($this->input->post('hash'));
		if (empty($user_data)){
			$result['success'] = false;
			$result['status'] = 'error';
			$result['message'] = 'invalid hash or session expired';
			echo json_encode($result);
			die();
		}

		if ((double)$this->input->post('version') < (double)$this->apimodel->get('options', array('code' => 302))->row('value')){
			$result['success'] = true;
			$result['status'] = 'warning';
			$result['message'] = 'newer app version detected';
			echo json_encode($result);
			die();
		}

		$result['success'] = true;
		$result['status'] = 'success';
		$result['message'] = 'ok';
		echo json_encode($result);
	}
}
