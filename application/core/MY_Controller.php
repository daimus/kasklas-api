<?php

class MY_Controller extends CI_Controller {

  public function __construct(){
    parent::__construct();
    $this->validateHash();
  }

  public function validateHash(){
    if (empty($this->input->post('hash'))){
      echo json_encode(array('success' => false, 'status' => 'error', 'message' => 'missing hash'));
      die();
    }

    $this->load->model('apimodel');
    if ($this->apimodel->get('session', array('hash' => $this->input->post('hash')))->num_rows() <= 0){
      echo json_encode(array('success' => false, 'status' => 'error', 'message' => 'invalid hash'));
      die();
    }
  }

}
?>
