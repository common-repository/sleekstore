<?php
/*
Plugin Name: sleekStore lite
Plugin URI: http://www.sleekstore.net
Description: sleekStore
Version: 2.3
Author: w9 multimedia
Author URI: http://www.w9.pl
License: All rights reserved
*/

include_once('w9ss_base.php');
if (is_admin()) include_once('w9ss_admin.php');

$w9ss = new w9ssPlugin();
class w9ssPlugin {
	public $name = "w9ss";
	public $version = 2;
	public $versionm = 3; 
	public $dbversion = 11;  
	protected $w9fw;

	
	function __construct() {
		
		include_once( dirname(__FILE__) . '/w9fw.php' );
		$this->w9fw = new w9ss_w9framework(
				$this->name, 
				plugin_dir_url(__FILE__), 
				plugin_dir_path(__FILE__)
				);
		$this->w9fw->setEb();
		$this->w9fw->addAE(array('Shop:remove', 'Shop:removeAll', 'Shop:order'));
		
		register_activation_hook(__FILE__,array($this, 'dbcreate'));
				
		add_action('init', array($this, 'init_sessions'));
		add_action('init', array($this, 'setTranslations'));
		add_action('wp', array($this->w9fw, 'aheadExecute'));
		
		add_filter('the_content', array($this, 'the_content'));
		if (get_option( 'w9ss_addcartnumber', 1 )) add_filter('the_title', array($this, 'the_title'), 10, 2);
		add_shortcode('ss_addtocart', array($this, 'shortcode_addtocart'));
		add_shortcode('ss_addproduct', array($this, 'shortcode_addproduct'));
		add_shortcode('ss_productlist', array($this, 'shortcode_productlist'));
		
		add_action('plugins_loaded', array($this, 'dbupdatecheck'));
		add_action('setup_theme', array($this, 'moduleurlset'));
		
		add_action('wp_print_styles',array($this,'Styles'));
		
		add_action('widgets_init', create_function( '', 'register_widget( "w9ssCartWidget" );' ) );
		
		if (is_admin()) {
			add_action( 'admin_menu', array(&$this, 'admin_menu'));
			add_action( 'add_meta_boxes', array( &$this, 'metaboxes' ) );
			add_action( 'save_post', array( &$this, 'metaboxes_save') );
			add_action( 'post_edit_form_tag', array(&$this, 'metaboxes_add_multipart_encoding'));
		}
	}

	function moduleurlset() {
		$this->w9fw->setModuleUrl(get_permalink(get_option( $this->name . '_pageid')));
	}
	
	
	function dbcreate() {
		global $wpdb;
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		$table_order = $wpdb->prefix . "w9ss_order";
		$sql = "CREATE TABLE $table_order (
		id int(11) NOT NULL AUTO_INCREMENT,
	    status smallint(6) DEFAULT 0,
	    descr varchar(255) collate utf8_unicode_ci DEFAULT NULL,
	    comments varchar(255) collate utf8_unicode_ci DEFAULT NULL,
	    date_start datetime DEFAULT NULL,
	    date_end datetime DEFAULT NULL,
	    ip_client varchar(36) collate utf8_unicode_ci DEFAULT NULL,
	    invoice varchar(255) collate utf8_unicode_ci DEFAULT NULL,
	    email varchar(255) collate utf8_unicode_ci DEFAULT NULL,	    
	    name varchar(255) collate utf8_unicode_ci DEFAULT NULL,
	    surname varchar(255) collate utf8_unicode_ci DEFAULT NULL,
	    street varchar(255) collate utf8_unicode_ci DEFAULT NULL,
	    nbuilding varchar(50) collate utf8_unicode_ci DEFAULT NULL,
	    napartment varchar(50) collate utf8_unicode_ci DEFAULT NULL,	    
 	    city varchar(100) collate utf8_unicode_ci DEFAULT NULL,
	    postcode varchar(50) collate utf8_unicode_ci DEFAULT NULL,
	    country varchar(100) collate utf8_unicode_ci DEFAULT NULL,
	    phone varchar(255) collate utf8_unicode_ci DEFAULT NULL,
	    client_id int(11) DEFAULT NULL,
        sum decimal(10,2) DEFAULT NULL,
        currency varchar(5) DEFAULT NULL,
        payment varchar(255) collate utf8_unicode_ci DEFAULT 0,
        delivery varchar(255) collate utf8_unicode_ci DEFAULT 0,
		UNIQUE KEY id (id)
		);";
		dbDelta($sql); 
		
		$table_item = $wpdb->prefix . "w9ss_item";
		$sql = "CREATE TABLE $table_item (
		id int(11) NOT NULL AUTO_INCREMENT,
  		type smallint(6) DEFAULT 0,
  		product_id int(11) DEFAULT NULL,
  		name varchar(255) collate utf8_unicode_ci DEFAULT NULL,
		amount varchar(20) collate utf8_unicode_ci DEFAULT '1',  		
  		unit varchar(20) collate utf8_unicode_ci DEFAULT NULL,
  		price decimal(10,2) DEFAULT 0,
  		order_id int(11) NOT NULL,
		UNIQUE KEY id (id)
		);";
		dbDelta($sql);
		
		update_option($this->name . "_db_version", $this->dbversion);
	}
	
	function dbupdatecheck() {
		if (get_option($this->name . '_db_version', 0) < $this->dbversion) {
			$this->dbcreate();
		}	
	}

	function init_sessions()
	{
		if (!session_id()) session_start();
	}
	
	function the_content($content)
	{
		// główna funkcja zmieniająca content
		
		
		if (get_the_ID()==get_option( $this->name . '_pageid') ) return $this->w9fw->dispatch("Shop:cart");

		// autmatyczne dodanie koszyka
		// na końcu lub na początku
		if ($opt = get_option( 'w9ss_autocart', 1 )) {
			if (get_post_meta(get_the_ID(), '_w9ss_price', true)) {
				if ($opt==2) return $content . $this->w9fw->execute('Shop:addToCart');
					else return $this->w9fw->execute('Shop:addToCart') . $content;
			}
				
		}
		
		return $content;
	}
	
	function the_title($title, $id)
	{
		if ($id==get_option( $this->name . '_pageid')) {
			if ($count=$this->w9fw->get('Koszyk')->getCount()) $title.=' (' . $count . ')';
		}
		return $title;
	}
	
	function shortcode_addtocart($atts) {
		return $this->w9fw->execute("Shop:addToCart", array('atts' => $atts ));
	}

	function shortcode_addproduct($atts) {
		$def = array(
				'name' => __('Product', 'w9ss'),
				'price' => '0',
				);
		return $this->w9fw->execute("Components:addProduct", array('atts' => shortcode_atts($def, $atts) ));
	}
	
	function shortcode_productlist($atts) {
		return $this->w9fw->execute("Components:productList", array('atts' => $atts ));
	}
	
	function admin_menu() {
		add_menu_page(__('sleekStore settings', 'w9ss'), 'sleekStore', 'add_users','w9ss_admin_page' , array($this, 'admin_zamowienia_page'));
		add_submenu_page( 'w9ss_admin_page', __('Orders', 'w9ss'), __('Orders', 'w9ss'), 'manage_options', 'w9ss_admin_page', array($this, 'admin_zamowienia_page') );
		add_submenu_page( 'w9ss_admin_page', __('Inventory', 'w9ss'), __('Inventory', 'w9ss'), 'manage_options', 'w9ss_admin_inventory', array($this, 'admin_inventory_page') );
		add_submenu_page( 'w9ss_admin_page', __('Settings', 'w9ss'), __('Settings', 'w9ss'), 'manage_options', 'w9ss_admin_options', array($this, 'admin_options_page') );
	}

	function admin_zamowienia_page() {
		if (!get_option( $this->name . '_pageid')) echo $this->w9fw->execute("Admin:index", array('firstrun' => true));
		else echo $this->w9fw->dispatch("Zamowienie:index");
	} 

	function admin_inventory_page() {
		if (!get_option( $this->name . '_pageid')) echo $this->w9fw->execute("Admin:index", array('firstrun' => true));		
		else echo $this->w9fw->dispatch("Inventory:index");
	}
	
	function admin_options_page() {
		if (!get_option( $this->name . '_pageid')) echo $this->w9fw->execute("Admin:index", array('firstrun' => true));		
		else echo $this->w9fw->dispatch("Admin:index");
	}

	function metaboxes() {
		add_meta_box(
				'w9ss_metabox'
				,__('sleekStore - product settings', 'w9ss')
				,array( &$this, 'metabox_content' )
				, get_option( 'w9ss_posttype', 'page')
				,'normal'
				,'high'
		);
	}
	
	function metabox_content()
	{
		echo $this->w9fw->execute('Admin:metabox');
	}
	
	function metaboxes_save() {
		$this->w9fw->execute('Admin:metaboxUpdate');
	}
	
	function metaboxes_add_multipart_encoding() {
		echo ' enctype="multipart/form-data"';
	}

	function Styles()
	{
		if (get_option( 'w9ss_usecss' , 1)) 
			wp_enqueue_style('w9ss_styles', plugin_dir_url(__FILE__) . '/w9ss.css');
	}
	
	function setTranslations()
	{
		load_plugin_textdomain( 'w9ss', null,  basename(plugin_dir_path( __FILE__ )) . '/languages');
	}
	
	function getFw() { return $this->w9fw; }
	function getName() { return $this->name; }		
}


// template tags

function w9ss_addtocart($atts = array()) {
	global $w9ss;
	echo $w9ss->getFw()->execute("Shop:addToCart", array('atts' => $atts ));
}

function w9ss_cartwidget() {
	global $w9ss;
	echo $w9ss->getFw()->execute('Components:cartWidget');
}



// *************** widget **************************


class w9ssCartWidget extends WP_Widget
{
	function __construct() {
		parent::__construct(
				'w9ss_cartwidget', // Base ID
				__('sleekStore Cart', 'w9ss'), // Name
				array( 'description' => __( 'sleekStore cart', 'w9ss' ), ) // Args
		);

	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		
		global $w9ss;
		echo $w9ss->getFw()->execute('Components:cartWidget');
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Cart', 'w9ss' );
		}
		?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php
}

}

