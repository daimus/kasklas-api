<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends MY_Controller {

	function __construct(){
		parent::__construct();
		$this->load->model('apimodel');
		$this->load->library('authentication');
	}

	public function dashboard()
	{
		// Initialize response
		$result['success'] = false;
		$result['message'] = '';

		// Get Options Data
		if ($data = $this->apimodel->getBalance()){
			$result['success'] = true;
			$result['income'] = $data['income'];
			$result['expense'] = $data['expense'];
		}
		echo json_encode($result);
	}

	public function getOption2(){
		// Initialize response
		$result['success'] = false;
		$result['message'] = '';

		if (empty($this->input->post('code'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		// Get Options Data
		if ($data = $this->apimodel->get('options', array('code' => $this->input->post('code')))){
			$result['success'] = true;
			$result['message'] = 'data';
			$result['code'] = $data->row('code');
			$result['name'] = $data->row('name');
			$result['description'] = $data->row('description');
			$result['value'] = $data->row('value');
			if ($this->input->post('code') == 201){
				$product = $this->apimodel->get('product', array('id' => $data->row('value')))->row();
				$result['value'] = json_encode($product);
			}
		}
		echo json_encode($result);
	}

	public function getOption(){
		$result['success'] = false;
		$result['message'] = '';
		$result['data'] = array();

		$data = $this->apimodel->get('options');
		if ($data){
			$result['success'] = true;
			$result['message'] = 'ok';
			foreach ($data->result() as $key => $value) {
				if ($value->code == 201){
					$product = $this->apimodel->get('product', array('id' => $value->value))->row();
					$value->value = json_encode($product);
					$result['data'][] = $value;
				} else {
					$result['data'][] = $value;
				}
			}
		}
		echo json_encode($result);
	}

	public function saveOption(){
		$result['success'] = false;
		$result['message'] = '';
		if (empty($this->input->post('code')) || empty($this->input->post('value'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		if ($this->input->post('code') == 201){
			$regular_bill = $this->apimodel->get('options', array('code' => $this->input->post('code')))->row();
			if ($this->apimodel->update('product', array('id' => $regular_bill->value), array('amount' => $this->input->post('value')))){
				$result['success'] = true;
				$result['message'] = 'ok';
			} else {
				$result['message'] = 'error when executing query';
			}
		} else {
			if ($this->apimodel->update('options', array('code' => $this->input->post('code')), array('value' => $this->input->post('value')))){
				$result['success'] = true;
				$result['message'] = 'ok';
			} else {
				$result['message'] = 'error when executing query';
			}
		}
		echo json_encode($result);
	}

	// STUDENT API
	public function fetchStudent(){
		$result['success'] = false;
		$result['message'] = '';
		$result['total_rows'] = 0;
		$result['num_rows'] = 0;
		$result['data'] = null;
		$data = $this->apimodel->fetchStudent($this->input->post());
		if ($data){
			$result['success'] = true;
			$result['message'] = 'ok';
			$result['total_rows'] = $data['total_rows'];
			$result['num_rows'] = $data['filtered_rows']->num_rows();
			$result['data'] = $data['filtered_rows']->result_array();
		} else {
			$result['message'] = 'error when executing queries';
		}
		echo json_encode($result);
	}

	public function addStudent(){
		$result['success'] = false;
		$result['message'] = '';

		$data = $this->input->post();
		$data['phone'] = str_replace(array('-', ' ', '+'), '', $data['phone']);
		if (substr($data['phone'],0,1) == 0){
			$data['phone'] = "62".substr($data['phone'],1);
		} else if (substr($data['phone'],0,1) == 8){
			$data['phone'] = "62".$data['phone'];
		}


		if ($this->apimodel->insert('student', $data)){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
		}

		echo json_encode($result);
	}

	public function editStudent(){
		$result['success'] = false;
		$result['message'] = '';

		if (empty($this->input->post('id'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		$data = $this->input->post();
		$data['phone'] = str_replace(array('-', ' ', '+'), '', $data['phone']);
		if (substr($data['phone'],0,1) == 0){
			$data['phone'] = "62".substr($data['phone'],1);
		} else if (substr($data['phone'],0,1) == 8){
			$data['phone'] = "62".$data['phone'];
		}

		if ($this->apimodel->update('student', array('id' => $this->input->post('id')), $data)){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
		}

		echo json_encode($result);
	}

	public function deleteStudent(){
		$result['success'] = false;
		$result['message'] = '';

		if (empty($this->input->post('id'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		if ($this->apimodel->delete('student', array('id' => $this->input->post('id')))){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
		}

		echo json_encode($result);
	}

	// BILLING API
	public function fetchBill(){
		$result['success'] = false;
		$result['message'] = '';
		$result['total_rows'] = 0;
		$result['num_rows'] = 0;
		$result['data'] = null;
		$data = $this->apimodel->fetchBill($this->input->post());
		if ($data){
			$result['success'] = true;
			$result['message'] = 'ok';
			$result['total_rows'] = $data['total_rows'];
			$result['num_rows'] = $data['filtered_rows']->num_rows();
			$result['data'] = $data['result_rows'];
		} else {
			$result['message'] = 'error when executing queries';
		}
		echo json_encode($result);
	}

	// PAYMENT API
	public function fetchPayment(){
		$result['success'] = false;
		$result['message'] = '';
		$result['total_rows'] = 0;
		$result['num_rows'] = 0;
		$result['data'] = null;
		$data = $this->apimodel->fetchPayment($this->input->post());
		if ($data){
			$result['success'] = true;
			$result['message'] = 'ok';
			$result['total_rows'] = $data['total_rows'];
			$result['num_rows'] = $data['filtered_rows']->num_rows();
			$result['data'] = $data['filtered_rows']->result_array();
		} else {
			$result['message'] = 'error when executing queries';
		}
		echo json_encode($result);
	}

	public function fetchpaymentItem(){
		$result['success'] = false;
		$result['message'] = '';
		$result['total_rows'] = 0;
		$result['num_rows'] = 0;
		$result['data'] = null;
		$data = $this->apimodel->getPaymentItem($this->input->post('payment_id'));
		if ($data){
			$result['success'] = true;
			$result['message'] = 'ok';
			$result['data'] = $data->result_array();
		} else {
			$result['message'] = 'error when executing queries';
		}
		echo json_encode($result);
	}

	public function addPayment(){
		$result['success'] = false;
		$result['message'] = '';

		$data = $this->input->post();

		if (empty($data['student_id']) || empty($data['product_id'])){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		$payment_data = array(
			'payment_no'	=> substr("KAS-".sprintf("%06d", mt_rand(0, 999999)), 0, 10),
			'student_id'	=> $data['student_id']
		);

		if ($this->apimodel->insert('payment', $payment_data)){
			$payment_id = $this->db->insert_id();
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
			echo json_encode($result);
			return;
		}

		$grand_amount = 0;
		foreach ($data['product_id'] as $key => $value) {
			if ($data['qty'][$key] > 0){
				$product = $this->apimodel->get('product', array('id' => $value));
				if ($product->num_rows() <= 0){
					$result['success'] = false;
					$result['message'] = 'product not found';
					echo json_encode($result);
					return;
				}
				$payment_item_data = array(
					'payment_id'	=> $payment_id,
					'product_id'	=> $value,
					'qty'					=> $data['qty'][$key],
					'amount'			=> $data['qty'][$key] * $product->row('amount')
				);
				$grand_amount += $payment_item_data['amount'];
				if ($this->apimodel->insert('payment_item', $payment_item_data)){
					$result['success'] = true;
					$result['message'] = 'ok';
				} else {
					$result['success'] = false;
					$result['message'] = 'error when executing queries';
					echo json_encode($result);
					return;
				}
			}
		}

		if ($this->apimodel->update('payment', array('id' => $payment_id), array('amount' => $grand_amount))){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['success'] = false;
			$result['message'] = 'error when executing queries';
			echo json_encode($result);
			return;
		}

		echo json_encode($result);
	}

	public function editPayment(){
		$result['success'] = false;
		$result['message'] = '';

		$data = $this->input->post();

		if ( ! $this->apimodel->delete('payment_item', array('payment_id' => $data['payment_id']))){
			$result['message'] = 'error when executing queries';
			echo json_encode($result);
			return;
		}

		$grand_amount = 0;
		foreach ($data['product_id'] as $key => $value) {
			$product = $this->apimodel->get('product', array('id' => $value));
			if ($product->num_rows() <= 0){
				$result['success'] = false;
				$result['message'] = 'product not found';
				echo json_encode($result);
				return;
			}
			$payment_item_data = array(
				'payment_id'	=> $data['payment_id'],
				'product_id'	=> $value,
				'qty'					=> $data['qty'][$key],
				'amount'			=> $data['qty'][$key] * $product->row('amount')
			);
			$grand_amount += $payment_item_data['amount'];
			if ($this->apimodel->insert('payment_item', $payment_item_data)){
				$result['success'] = true;
				$result['message'] = 'ok';
			} else {
				$result['success'] = false;
				$result['message'] = 'error when executing queries';
				echo json_encode($result);
				return;
			}
		}

		if ($this->apimodel->update('payment', array('id' => $data['payment_id']), array('amount' => $grand_amount, 'student_id' => $data['student_id']))){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['success'] = false;
			$result['message'] = 'error when executing queries';
			echo json_encode($result);
			return;
		}

		echo json_encode($result);
	}

	public function deletePayment(){
		$result['success'] = false;
		$result['message'] = '';

		if ($this->apimodel->delete('payment', array('id' => $this->input->post('payment_id')))){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
			echo json_encode($result);
			return;
		}

		if ($this->apimodel->delete('payment_item', array('payment_id' => $this->input->post('payment_id')))){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
			echo json_encode($result);
			return;
		}

		echo json_encode($result);
	}

	// PAYMENT ITEM] API
	public function fetchProduct(){
		$result['success'] = false;
		$result['message'] = '';
		$result['total_rows'] = 0;
		$result['num_rows'] = 0;
		$result['data'] = null;
		$data = $this->apimodel->fetchProduct($this->input->post());
		if ($data){
			$result['success'] = true;
			$result['message'] = 'ok';
			$result['total_rows'] = $data['total_rows'];
			$result['num_rows'] = $data['filtered_rows']->num_rows();
			$result['data'] = $data['filtered_rows']->result_array();
		} else {
			$result['message'] = 'error when executing queries';
		}
		echo json_encode($result);
	}

	public function addProduct(){
		$result['success'] = false;
		$result['message'] = '';

		$data = $this->input->post();

		if ($this->apimodel->insert('product', $data)){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
		}

		echo json_encode($result);
	}

	public function editProduct(){
		$result['success'] = false;
		$result['message'] = '';

		if (empty($this->input->post('id'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		$data = $this->input->post();

		if ($this->apimodel->update('product', array('id' => $this->input->post('id')), $data)){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
		}

		echo json_encode($result);
	}

	public function deleteProduct(){
		$result['success'] = false;
		$result['message'] = '';

		if (empty($this->input->post('id'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		if ($this->apimodel->delete('product', array('id' => $this->input->post('id')))){
			$result['success'] = true;
			$result['message'] = 'ok';
			if ( ! $this->apimodel->delete('payment_item', array('product_id' => $this->input->post('id')))){
				$result['success'] = false;
				$result['message'] = 'error when executing queries';
				echo json_encode($result);
				return;
			}
		} else {
			$result['message'] = 'error when executing queries';
			echo json_encode($result);
			return;
		}

		echo json_encode($result);
	}

	// EXPENSE API
	public function fetchExpense(){
		$result['success'] = false;
		$result['message'] = '';
		$result['total_rows'] = 0;
		$result['num_rows'] = 0;
		$result['data'] = null;
		$data = $this->apimodel->fetchExpense($this->input->post());
		if ($data){
			$result['success'] = true;
			$result['message'] = 'ok';
			$result['total_rows'] = $data['total_rows'];
			$result['num_rows'] = $data['filtered_rows']->num_rows();
			$result['data'] = $data['filtered_rows']->result_array();
		} else {
			$result['message'] = 'error when executing queries';
		}
		echo json_encode($result);
	}

	public function addExpense(){
		$result['success'] = false;
		$result['message'] = '';

		$data = $this->input->post();

		if ($this->apimodel->insert('expense', $data)){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
		}

		echo json_encode($result);
	}

	public function editExpense(){
		$result['success'] = false;
		$result['message'] = '';

		if (empty($this->input->post('id'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		$data = $this->input->post();

		if ($this->apimodel->update('expense', array('id' => $this->input->post('id')), $data)){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
		}

		echo json_encode($result);
	}

	public function deleteExpense(){
		$result['success'] = false;
		$result['message'] = '';

		if (empty($this->input->post('id'))){
			$result['message'] = 'invalid parameter';
			echo json_encode($result);
			return;
		}

		if ($this->apimodel->delete('expense', array('id' => $this->input->post('id')))){
			$result['success'] = true;
			$result['message'] = 'ok';
		} else {
			$result['message'] = 'error when executing queries';
		}

		echo json_encode($result);
	}
	//
}
