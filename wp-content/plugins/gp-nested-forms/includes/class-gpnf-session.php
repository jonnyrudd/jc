<?php

class GPNF_Session {

	const COOKIE_NAME = 'gpnf_form_session';

	private $_form_id;
	private $_cookie;

	public function __construct( $form_id ) {

		$this->_form_id = $form_id;
		$this->_cookie = $this->get_cookie( $form_id );

	}

	public function get( $prop ) {
		if( ! isset( $this->$prop ) ) {
			if( empty( $this->_cookie ) ) {
				return null;
			}
			// Clean up nested entries; only return non-trashed entries that exist.
			if( $prop == 'nested_entries' ) {
				$this->_cookie[ $prop ] = $this->get_valid_entry_ids( $this->_cookie[ $prop ] );
			}
			return $this->_cookie[ $prop ];
		} else {
			return $this->$prop;
		}
	}

	public function add_child_entry( $child_entry_id ) {

		// @todo review
		$nested_form_field_key = gform_get_meta( $child_entry_id, GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY );

		if( ! array_key_exists( $nested_form_field_key, $this->_cookie['nested_entries'] ) ) {
			$this->_cookie['nested_entries'][ $nested_form_field_key ] = array();
		}

		$this->_cookie['nested_entries'][ $nested_form_field_key ][] = $child_entry_id;

		$this->set_cookie();

	}

	public function set_session_data() {

		$cookie = $this->get_cookie();

		// Existing cookie.
		if( $cookie ) {

			$data = array(
				'form_id'        => $cookie['form_id'],
				'hash'           => $cookie['hash'],
				'user_id'        => $cookie['user_id'],
				'nested_entries' => $cookie['nested_entries'],
			);


		}
		// New cookie.
		else {

			$data = array(
				'form_id'        => $this->_form_id,
				'hash'           => $this->make_hashcode(),
				'user_id'        => get_current_user_id(),
				'nested_entries' => array(),
			);

		}

		$this->_cookie = $data;

		return $this;
	}

	public function make_hashcode() {
		return substr( md5( uniqid( rand(), true ) ), 0, 12 );
	}

	public function set_cookie() {
		setcookie( $this->get_cookie_name(), json_encode( $this->_cookie ), time() + 60 * 60 * 24 * 7, '/', $_SERVER['SERVER_NAME'] );
	}

	public function get_cookie() {

		$cookie_name = $this->get_cookie_name();
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			$cookie = json_decode( stripslashes( $_COOKIE[ $cookie_name ] ), true );
			return $cookie;
		}

		return false;
	}

	public function get_cookie_name() {
		return implode( '_', array( self::COOKIE_NAME, $this->_form_id ) );
	}

	public function delete_cookie() {
		$cookie_name = $this->get_cookie_name();
		unset( $_COOKIE[ $cookie_name ] );
		setcookie( $cookie_name, '', time() - ( 15 * 60 ), '/', $_SERVER['SERVER_NAME'] );
	}

	public function has_data() {
		return ! empty( $this->_cookie );
	}

	public function get_valid_entry_ids( $entries ) {
		global $wpdb;

		if( empty( $entries ) ) {
			return array();
		}

		$all = array();
		foreach( $entries as $field_id => $entry_ids ) {
			$all = array_merge( $all, $entry_ids );
		}

		$sql = "SELECT id FROM {$wpdb->prefix}gf_entry WHERE id IN( " . implode( ', ', $all ) . " ) and status != 'trash'";
		$valid_entry_ids = wp_list_pluck( $wpdb->get_results( $sql ), 'id' );
		$return = array();

		foreach( $entries as $field_id => $entry_ids ) {
			$return[ $field_id ] = array_intersect( $valid_entry_ids, $entry_ids );
		}

		$return = array_filter( $return );

		return $return;
	}

	public static function get_session_script( $form_id ) {
		ob_start();
		?>

		<script>
			( function( $ ) {
				$.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', {
					action: 'gpnf_session',
					form_id: <?php echo $form_id; ?>,
				}, function( response ) {
					// nothing to do
				} );
			} ) ( jQuery );
		</script>

		<?php
		return ob_get_clean();
	}

}