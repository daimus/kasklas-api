<?php

class AuthenticationModel extends MY_Model {

  function __construct(){
    parent::__construct();
  }

  public function getUserData($hash){
    $this->db->select('user.*');
    $this->db->from('session');
    $this->db->join('user', 'session.user_id = user.id');
    $this->db->where('session.hash', $hash);
    return $this->db->get();
  }

  public function createUser($user_data){
    if ($this->db->insert('user', $user_data)){
      return true;
    } else {
      return false;
    }
  }
}


?>
