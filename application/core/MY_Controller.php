<?php

class MY_Controller extends CI_Controller {

  public function __construct(){
    parent::__construct();
    $this->validateHash();
  }


  public function validateHash(){
    $user = $this->input->server('PHP_AUTH_USER');
    $hash = $this->input->server('PHP_AUTH_PW');

    if (empty($hash)){
      header('HTTP/1.0 401 Unauthorized');
      echo json_encode(array('success' => false, 'message' => 'missing hash'));
      die();
    }

    $this->load->model('apimodel');
    if ($this->apimodel->get('session', array('hash' => $hash))->num_rows() <= 0){
      header('HTTP/1.0 401 Unauthorized');
      echo json_encode(array('success' => false, 'message' => 'invalid hash'));
      die();
    }
  }

}
?>
