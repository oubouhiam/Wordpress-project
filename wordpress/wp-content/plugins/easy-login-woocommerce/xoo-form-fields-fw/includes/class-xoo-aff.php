<?php

class Xoo_Aff{

	public $plugin_slug, $fields, $admin;

	public function __construct( $plugin_slug ){
		$this->plugin_slug = $plugin_slug;
		$this->includes();
		$this->hooks();
		$this->init();
	}

	public function hooks(){
		//add_action( 'init', array( $this, 'on_install' ), 1 );
	}

	public function includes(){

		include XOO_AFF_DIR.'/includes/xoo-aff-functions.php';
		include XOO_AFF_DIR.'/admin/class-xoo-aff-fields.php';
		include XOO_AFF_DIR.'/admin/class-xoo-aff-admin.php';

	}

	public function init(){

		$this->fields 		= new Xoo_Aff_Fields( $this );
		$this->admin 		= new Xoo_Aff_Admin( $this );
		
	}


	//Enqueue scripts from the main plugin
	public function enqueue_scripts(){

		wp_enqueue_style( 'xoo-aff-style', XOO_AFF_URL.'/assets/css/xoo-aff-style.css', array(), XOO_AFF_VERSION) ;
		wp_enqueue_style( 'xoo-aff-font-awesome5', 'https://use.fontawesome.com/releases/v5.5.0/css/all.css' );

		$fields = $this->fields->get_fields_data();

		$has_date = $datepicker_data = false;
		if( !empty( $fields ) ){
			foreach ( $fields as $field_id => $field_data) {
				if( !isset( $field_data['type'] ) ) continue;
				if( $field_data['type'] === "date" ){
					$has_date = true;
					$args = array(
						'dateFormat' => isset( $field_data['settings']['date_format'] ) ? $field_data['settings']['date_format'] : "yy-mm-dd",
					);

					$user_args = apply_filters( 'xoo_aff_datepicker_args', array(
						'changeMonth' => true,
						'changeYear'  => true,
						'yearRange' => 'c-100:c+10',
					), $field_id  );

					$datepicker_data[] = array(
						'id' 		=> $field_id,
						'args' 		=> array_merge( $args, $user_args )		
					);
				}
			}
		}

		if( $has_date ){
			wp_enqueue_style( 'jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css' );
			wp_enqueue_script('jquery-ui-datepicker');
		}

		wp_enqueue_script( 'xoo-aff-js', XOO_AFF_URL.'/assets/js/xoo-aff-js.js', array( 'jquery' ), XOO_AFF_VERSION, true );
		wp_localize_script('xoo-aff-js','xoo_aff_localize',array(
			'adminurl'  			=> admin_url().'admin-ajax.php',
			'datepicker_data'		=> json_encode( $datepicker_data ),
			'countries' 			=> json_encode( include XOO_AFF_DIR.'/countries/countries.php' ),
			'states' 				=> json_encode( include XOO_AFF_DIR.'/countries/states.php' ),
		));

	}


	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}


	public function on_install(){
		$db_version = get_option( 'xoo_aff_version', true );
		
		if( version_compare( $db_version, XOO_AFF_VERSION , '<' ) ){
			//Merging front and backend fields in one + creating plugin specific aff field
			$data = json_decode( get_option( 'xoo_aff_easy_login_woocommerce_frontend_fields' ), true );
			print_r($data);
			die();
		}
	}

}


?>