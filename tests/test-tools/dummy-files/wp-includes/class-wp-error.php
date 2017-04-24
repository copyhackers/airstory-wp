<?php
/**
 * Mock class for WP_Error.
 */

class WP_Error {

	public $errors = array();
	public $error_data = array();

	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->add( $code, $message, $data );
	}

	public function add( $code = '', $message = '', $data = '' ) {
		if ( empty( $code ) ) {
			return;
		}

		$this->errors[ $code ][] = $message;

		if ( ! empty( $data ) ) {
			$this->error_data[ $code ] = $data;
		}
	}

	public function get_error_messages() {
		$messages = array();

		foreach ( $this->errors as $code => $msg ) {
			$messages = array_merge( $messages, $this->errors[ $code ] );
		}

		return $messages;
	}

	public function get_error_message() {
		$messages = $this->get_error_messages();

		return array_shift( $messages );
	}

	public function get_error_code() {
		$codes = array_keys( $this->errors );

		return array_shift( $codes );
	}
}
