<?php 

function w9ss_ecAdmin() {
	if (class_exists('w9ss_AdminController'))
		return;
	class w9ss_AdminController {
		public function adminChk() {
			if (!is_admin())
				wp_die(
						__(
								'You do not have sufficient permissions to access this page.'));
			if (!current_user_can('manage_options')) {
				wp_die(
						__(
								'You do not have sufficient permissions to access this page.'));
			}
		}
		public function indexAction($firstrun = false) {
			$this->adminChk();
			$updated = false;
			if (!empty($_POST) && check_admin_referer('index', 'w9ss_admin')) {
				$pageid = $_POST['w9ss_pageid'];
				if (($pageid == 0) && isset($_POST['w9ss_newpageadd'])
						&& $_POST['w9ss_newpageadd']) {
					$newpost = array('post_status' => 'publish',
							'post_title' => $_POST['w9ss_newpage'],
							'comment_status' => 'closed',
							'ping_status' => 'closed', 'post_type' => 'page',);
					$pageid = wp_insert_post($newpost);
				}
				update_option('w9ss_pageid', $pageid);
				update_option('w9ss_email_from', $_POST['w9ss_email_from']);
				update_option('w9ss_autocart', $_POST['w9ss_autocart']);
				update_option('w9ss_usecss', $_POST['w9ss_usecss']);
				update_option('w9ss_posttype', $_POST['w9ss_posttype']);
				update_option('w9ss_currency', $_POST['w9ss_currency']);
				update_option('w9ss_gotocart', $_POST['w9ss_gotocart']);
				update_option('w9ss_useamount', $_POST['w9ss_useamount']);
				update_option('w9ss_unit', $_POST['w9ss_unit']);
				update_option('w9ss_hidenoinv', $_POST['w9ss_hidenoinv']);
				update_option('w9ss_addcartnumber',
						$_POST['w9ss_addcartnumber']);
				$updated = true;
				if ($firstrun)
					return $this->w9fw->render('Admin:configured.html.php');
			}
			global $w9ss;
			$version = $w9ss->version . '.' . $w9ss->versionm;
			$ret = array('pageid' => get_option('w9ss_pageid'),
					'w9ss_email_from' => get_option('w9ss_email_from',
							get_option('admin_email')),
					'w9ss_autocart' => get_option('w9ss_autocart', 1),
					'w9ss_usecss' => get_option('w9ss_usecss', 1),
					'w9ss_posttype' => get_option('w9ss_posttype', 'page'),
					'w9ss_currency' => get_option('w9ss_currency',
							__('USD', 'w9ss')),
					'w9ss_gotocart' => get_option('w9ss_gotocart', 0),
					'w9ss_useamount' => get_option('w9ss_useamount', 0),
					'w9ss_unit' => get_option('w9ss_unit', __('pcs', 'w9ss')),
					'w9ss_hidenoinv' => get_option('w9ss_hidenoinv', 0),
					'w9ss_addcartnumber' => get_option('w9ss_addcartnumber', 1),
					'post_types' => get_post_types(array('public' => true),
							'objects'), 'version' => $version,
					'updated' => $updated,
					'nonce' => wp_nonce_field('index', 'w9ss_admin', true,
							false),);
			if ($firstrun)
				$ret['_template'] = 'Admin:firstrun.html.php';
			return $ret;
		}
		public function paymentAction() {
			$this->adminChk();
			$updated = false;
			$shopOptions = $this->w9fw->get('ShopOptions');
			if (!empty($_POST)
					&& check_admin_referer('payment', 'w9ss_admin')) {
				$methods = $_POST['methods'];
				$options = $_POST['options'];
				$shopOptions->setPaymentMethods($methods);
				$shopOptions->setPaymentOptions($options);
				$updated = true;
			}
			$methods = $shopOptions->getPaymentMethods();
			$options = $shopOptions->getPaymentOptions();
			return array('updated' => $updated, 'methods' => $methods,
					'options' => $options,
					'nonce' => wp_nonce_field('payment', 'w9ss_admin', true,
							false),);
		}
		public function deliveryAction() {
			$this->adminChk();
			$updated = false;
			$shopOptions = $this->w9fw->get('ShopOptions');
			if (!empty($_POST)
					&& check_admin_referer('delivery', 'w9ss_admin')) {
				$shopOptions
						->setDelivery($_POST['dv'], $_POST['newdv0'],
								$_POST['newdv1']);
				$updated = true;
			}
			return array('delivery' => $shopOptions->getDelivery(),
					'currency' => get_option('w9ss_currency', 'PLN'),
					'updated' => $updated,
					'nonce' => wp_nonce_field('delivery', 'w9ss_admin', true,
							false),);
		}
		public function metaboxAction() {
			$this->adminChk();
			global $post;
			$unit = get_post_meta($post->ID, '_w9ss_unit', true);
			if (!$unit)
				$unit = get_option('w9ss_unit', __('pcs', 'w9ss'));
			return array(
					'price' => get_post_meta($post->ID, '_w9ss_price', true),
					'currency' => get_option('w9ss_currency', 'PLN'),
					'inventory' => get_post_meta($post->ID, '_w9ss_inventory',
							true),
					'useinv' => get_post_meta($post->ID, '_w9ss_useinv', true),
					'useamount' => get_post_meta($post->ID, '_w9ss_useamount',
							true),
					'limit' => get_post_meta($post->ID, '_w9ss_limit', true),
					'unit' => $unit,);
		}
		public function metaboxUpdateAction() {
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				return;
			if (!isset($_POST['w9ss_metan'])
					|| !wp_verify_nonce($_POST['w9ss_metan'], 'upd'))
				return;
			$this->adminChk();
			$post_ID = $_POST['post_ID'];
			update_post_meta($post_ID, '_w9ss_price', $_POST['w9ss_price']);
			update_post_meta($post_ID, '_w9ss_useinv', $_POST['w9ss_useinv']);
			update_post_meta($post_ID, '_w9ss_inventory',
					$_POST['w9ss_inventory']);
			update_post_meta($post_ID, '_w9ss_useamount',
					$_POST['w9ss_useamount']);
			update_post_meta($post_ID, '_w9ss_unit', $_POST['w9ss_unit']);
			update_post_meta($post_ID, '_w9ss_limit', $_POST['w9ss_limit']);
		}
	}
}
function w9ss_ecZamowienie() {
	if (class_exists('w9ss_ZamowienieController'))
		return;
	if (!is_admin())
		wp_die(
				__(
						'You do not have sufficient permissions to access this page.'));
	if (!current_user_can('manage_options')) {
		wp_die(
				__(
						'You do not have sufficient permissions to access this page.'));
	}
	class w9ss_ZamowienieController {
		public function indexAction($status = "") {
			global $wpdb;
			if ($status == "closed") {
				$statusin = '10,11';
				$status = 2;
			} else if ($status == "active") {
				$statusin = '1,2,3,4,5,6,7,8,9';
				$status = 1;
			} else {
				$statusin = '0';
				$status = 0;
			}
			$entities = $wpdb
					->get_results(
							"SELECT * FROM {$wpdb->prefix}w9ss_order WHERE status IN ($statusin) ORDER BY status ASC, date_start DESC");
			$sum = 0.0;
			foreach ($entities as $entity) {
				$sum += $entity->sum;
			}
			return array('entities' => $entities,
					'statusy' => $this->getStatusy(),
					'newcount' => $wpdb
							->get_var(
									"SELECT COUNT(*) FROM {$wpdb->prefix}w9ss_order WHERE status IN (0)"),
					'activecount' => $wpdb
							->get_var(
									"SELECT COUNT(*) FROM {$wpdb->prefix}w9ss_order WHERE status IN (1,2,3,4,5,6,7,8,9)"),
					'closedcount' => $wpdb
							->get_var(
									"SELECT COUNT(*) FROM {$wpdb->prefix}w9ss_order WHERE status IN (10, 11)"),
					'status' => $status, 'sum' => $sum,);
		}
		public function showAction($id, $statusupdated = false) {
			global $wpdb;
			$entity = $wpdb
					->get_row(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_order WHERE id = %d",
											$id));
			$items = $wpdb
					->get_results(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_item WHERE order_id = %d",
											$id));
			if (!$entity)
				return __('Order not found', 'w9ss');
			return array(
					'entity' => $this->w9fw->get('ShopOptions')
							->addInfoFields($entity), 'items' => $items,
					'statusy' => $this->getStatusy(),
					'statusupdated' => $statusupdated,);
		}
		public function getStatusy() {
			return array(__('New', 'w9ss'), __('* Payment started', 'w9ss'),
					__('*** Payment received', 'w9ss'),
					__('* Payment rejected', 'w9ss'),
					__('* Payment canceled', 'w9ss'),
					__('* Payment for verification', 'w9ss'),
					__('Accepted', 'w9ss'), __('Waiting for payment', 'w9ss'),
					__('Waiting for shipment', 'w9ss'), __('Send', 'w9ss'),
					__('Closed', 'w9ss'), __('Canceled', 'w9ss'));
		}
		public function editAction($id) {
			global $wpdb;
			$entity = $wpdb
					->get_row(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_order WHERE id = %d",
											$id), ARRAY_A);
			if (!$entity)
				return __('Order not found', 'w9ss');
			$shopOptions = $this->w9fw->get('ShopOptions');
			$editForm = $this->w9fw->createFormBuilder("order_form", $entity)
					->add('status', 'choice',
							array('choices' => $this->getStatusy()))
					->add('sum', null, array('label' => __('Sum', 'w9ss')))
					->add('currency', null,
							array('label' => __('Currency', 'w9ss')))
					->add('payment', 'choice',
							array(
									'choices' => $shopOptions
											->getPaymentChoices(),
									'label' => __('Payment', 'w9ss')))
					->add('delivery', 'choice',
							array(
									'choices' => $shopOptions
											->getDeliveryChoices(),
									'label' => __('Delivery', 'w9ss')))
					->add('descr', 'textarea',
							array('label' => __('Description', 'w9ss')))
					->add('comments', 'textarea',
							array('label' => __('Comments', 'w9ss')))
					->add('email', null,
							array('label' => __('Email address', 'w9ss')))
					->add('name', null,
							array('label' => __('First name', 'w9ss')))
					->add('surname', null,
							array('label' => __('Surname', 'w9ss')))
					->add('street', null,
							array('label' => __('Street', 'w9ss')))
					->add('nbuilding', null,
							array('label' => __('Building no', 'w9ss')))
					->add('napartment', null,
							array('label' => __('Apartment no', 'w9ss')))
					->add('city', null, array('label' => __('City', 'w9ss')))
					->add('postcode', null,
							array('label' => __('Post code', 'w9ss')))
					->add('country', null,
							array('label' => __('Country', 'w9ss')))
					->add('phone', null, array('label' => __('Phone', 'w9ss')));
			if (isset($_POST['zedit']) && ($_POST['zedit'] == 1)) {
				$editForm->bind($_POST);
				if ($editForm->isValid()) {
					$eupdate = $editForm->getUpdatedData();
					if ($eupdate)
						$wpdb
								->update($wpdb->prefix . "w9ss_order",
										$eupdate, array('id' => $id));
					return $this->w9fw
							->execute('Zamowienie:show', array('id' => $id));
				}
			}
			return array('entity' => $entity, 'edit_form' => $editForm,);
		}
		public function updateStatusAction($id, $status) {
			global $wpdb;
			$wpdb
					->update($wpdb->prefix . "w9ss_order",
							array('status' => $status), array('id' => $id));
			$zamowienie = $wpdb
					->get_row(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_order WHERE id = %d",
											$id), ARRAY_A);
			if (isset($_POST['statusemail']) && $_POST['statusemail']) {
				$statusy = $this->getStatusy();
				$this->w9fw->get('MailerAdv')->clear()
						->setSubject(__('Order status change', 'w9ss'))
						->setFrom(get_option('w9ss_email_from'))
						->setTo($zamowienie['email'], $zamowienie['surname'],
								$zamowienie['name'])
						->setBody(
								$this->w9fw
										->render('Shop:status_email.txt.php',
												array('order' => $zamowienie,
														'status' => $statusy[$zamowienie['status']],
														'sender' => get_option(
																'w9ss_email_from'))))
						->send();
			}
			return $this->w9fw
					->execute('Zamowienie:show',
							array('id' => $id, 'statusupdated' => true));
		}
		public function deleteAction($id) {
			global $wpdb;
			if ($id > 0) {
				$wpdb
						->query(
								$wpdb
										->prepare(
												"DELETE FROM {$wpdb->prefix}w9ss_item
    						WHERE order_id = %d", $id));
				$wpdb
						->query(
								$wpdb
										->prepare(
												"DELETE FROM {$wpdb->prefix}w9ss_order
    						WHERE id = %d", $id));
			}
			return $this->w9fw->execute('Zamowienie:index');
		}
	}
}
function w9ss_ecInventory() {
	if (class_exists('w9ss_InventoryController'))
		return;
	if (!is_admin())
		wp_die(
				__(
						'You do not have sufficient permissions to access this page.'));
	if (!current_user_can('manage_options')) {
		wp_die(
				__(
						'You do not have sufficient permissions to access this page.'));
	}
	class w9ss_InventoryController {
		public function indexAction($status = "") {
			global $wpdb;
			$undefsql = "
				FROM   $wpdb->posts posts
				LEFT JOIN  $wpdb->postmeta key1
				ON key1.post_id = posts.id
				AND key1.meta_key = '_w9ss_useinv'
				LEFT JOIN  $wpdb->postmeta key3
				ON key3.post_id = posts.id
				AND key3.meta_key = '_w9ss_price'
				WHERE COALESCE(key1.meta_value, '0') <> '1' AND key3.meta_value <> ''
		";
			$invsql = "
				FROM   $wpdb->posts posts
				LEFT JOIN  $wpdb->postmeta key1
				ON key1.post_id = posts.id
				AND key1.meta_key = '_w9ss_useinv'
				LEFT JOIN  $wpdb->postmeta key2
				ON key2.post_id = posts.id
				AND key2.meta_key = '_w9ss_inventory'
				LEFT JOIN  $wpdb->postmeta key3
				ON key3.post_id = posts.id
				AND key3.meta_key = '_w9ss_price'
				WHERE key1.meta_value = '1' AND key3.meta_value <> ''
		";
			if ($status == "undef") {
				$query = "SELECT   posts.id, posts.post_title, posts.post_status, '-' as inventory, key3.meta_value as price"
						. $undefsql;
				$status = 2;
				$posts = $wpdb->get_results($query);
				$noinvcount = count($posts);
				$invcount = $wpdb->get_var("SELECT count(*)" . $invsql);
			} else {
				$query = "SELECT   posts.id, posts.post_title, posts.post_status, key2.meta_value as inventory, key3.meta_value as price"
						. $invsql;
				$status = 1;
				$posts = $wpdb->get_results($query);
				$invcount = count($posts);
				$noinvcount = $wpdb->get_var("SELECT   count(*)" . $undefsql);
			}
			return array('status' => $status, 'entities' => $posts,
					'invcount' => $invcount, 'noinvcount' => $noinvcount,);
		}
	}
}
function w9ss_evAdmin_configured_html_php($params = null) {
	extract($params);
?><div class="wrap">

<br />
<h1><?php echo __('Welcome to sleekStore!', 'w9ss') ?></h1>

<br />

<h2><?php echo __('Well done!', 'w9ss') ?> <br />
<?php echo __('You have successfuly configured sleekStore.', 'w9ss') ?></h2>

<br />

<a href="http://www.sleekstore.pl/dokumentacja/" target="_blank" ><?php echo __(
			'Read plugin documentation', 'w9ss') ?></a>.

<br /><br />

<a href=""><?php echo __('Continue', 'w9ss') ?></a>
<?php }
function w9ss_evAdmin_delivery_html_php($params = null) {
	extract($params); ?><?php if ($updated) : ?>
<div class="updated"><p><strong><?php echo __('Saved.', 'w9ss') ?></strong></p></div>
<?php endif; ?>

<div class="wrap">
<?php echo $w9fw->render('Admin:header.html.php', array('option' => 3)) ?>

<form action="<?php echo htmlspecialchars($w9fw->path('Admin:delivery')) ?>" method="post">
<?php echo $nonce ?>

<h3><?php echo __('Payment in advance', 'w9ss') ?></h3>
<?php foreach ($delivery as $key => $dv)
		if ($dv['type'] == 0) : ?>
<input type="checkbox" name="dv[<?php echo htmlspecialchars($key) ?>][active]" value="1" <?php if (isset(
					$dv['active']) && $dv['active'])
				echo 'CHECKED' ?>
 /> 
<input type="text" name="dv[<?php echo htmlspecialchars($key) ?>][name]" value="<?php echo htmlspecialchars(
					$dv['name']) ?>" />
<?php echo __('Price', 'w9ss') ?>: <input type="text" name="dv[<?php echo htmlspecialchars(
					$key) ?>][price]" value="<?php echo htmlspecialchars(
					$dv['price']) ?>" /> <?php echo htmlspecialchars($currency) ?>   
&nbsp; &nbsp; &nbsp; &nbsp; <?php echo __('Id', 'w9ss') ?>: <input type="text" name="dv[<?php echo htmlspecialchars(
					$key) ?>][id]" value="<?php echo htmlspecialchars($key) ?>" />
<br /><br />
<input type="hidden" name="dv[<?php echo htmlspecialchars($key) ?>][type]" value="0" />
<?php endif; ?>

<b><?php echo __('Add new:', 'w9ss') ?></b> <br /><br />

<input type="checkbox" name="newdv0[active]" value="1" /> 
<input type="text" name="newdv0[name]" value="" />
<?php echo __('Price', 'w9ss') ?>: <input type="text" name="newdv0[price]" value="" /> <?php echo htmlspecialchars(
			$currency) ?> 
&nbsp; &nbsp; &nbsp; &nbsp; <?php echo __('Id', 'w9ss') ?>: <input type="text" name="newdv0[id]" value="" />
<input type="hidden" name="newdv0[type]" value="0" />
<br /><br />

<input type="submit" name="Submit" class="button-primary" value="<?php echo __(
			'Save Changes', 'w9ss') ?>" />

<hr />
<h3><?php echo __('Payment on delivery', 'w9ss') ?></h3>
<?php foreach ($delivery as $key => $dv)
		if ($dv['type'] == 1) : ?>
<input type="checkbox" name="dv[<?php echo htmlspecialchars($key) ?>][active]" value="1" <?php if (isset(
					$dv['active']) && $dv['active'])
				echo 'CHECKED' ?>
 /> 
<input type="text" name="dv[<?php echo htmlspecialchars($key) ?>][name]" value="<?php echo htmlspecialchars(
					$dv['name']) ?>" />
<?php echo __('Price', 'w9ss') ?>: <input type="text" name="dv[<?php echo htmlspecialchars(
					$key) ?>][price]" value="<?php echo htmlspecialchars(
					$dv['price']) ?>" /> <?php echo htmlspecialchars($currency) ?>   
&nbsp; &nbsp; &nbsp; &nbsp; <?php echo __('Id', 'w9ss') ?>: <input type="text" name="dv[<?php echo htmlspecialchars(
					$key) ?>][id]" value="<?php echo htmlspecialchars($key) ?>" />
<br /><br />
<input type="hidden" name="dv[<?php echo htmlspecialchars($key) ?>][type]" value="1" />
<?php endif; ?>

<b><?php echo __('Add new:', 'w9ss') ?></b> <br /><br />

<input type="checkbox" name="newdv1[active]" value="1" /> 
<input type="text" name="newdv1[name]" value="" />
<?php echo __('Price', 'w9ss') ?>: <input type="text" name="newdv1[price]" value="" /> <?php echo htmlspecialchars(
			$currency) ?> 
&nbsp; &nbsp; &nbsp; &nbsp; <?php echo __('Id', 'w9ss') ?>: <input type="text" name="newdv1[id]" value="" />
<input type="hidden" name="newdv1[type]" value="1" />
<br /><br />

<input type="submit" name="Submit" class="button-primary" value="<?php echo __(
			'Save Changes', 'w9ss') ?>" />

</form>

</div>
<?php }
function w9ss_evAdmin_firstrun_html_php($params = null) {
	extract($params); ?><div class="wrap">

<br />
<h1><?php echo __('Welcome to sleekStore lite!', 'w9ss') ?></h1>

<br />

<a href="http://www.sleekstore.pl/dokumentacja/" target="_blank" ><?php echo __(
			'Read plugin documentation', 'w9ss') ?></a>.

<br /><br />

<hr />

<form name="form1" method="post" action="">
<?php echo $nonce ?>

<h2><?php echo __('First-time sleekStore setup', 'w9ss') ?></h2>

<br /><br />

<h3><?php echo __('If you are happy with default settings just click', 'w9ss') ?>:</h3>

<input type="submit" name="Submit" class="button-primary" value="<?php echo __(
			'Configure sleekStore', 'w9ss') ?>" />

<br /><br /><br /><br />
<h3><?php echo __('The most important option', 'w9ss') ?>:</h3>

<p><label for="w9ss_pageid"><?php echo __('Module/cart page', 'w9ss') ?>:</label>  
<?php echo wp_dropdown_pages(
			array('name' => 'w9ss_pageid', 'echo' => 0,
					'show_option_none' => __('&mdash; Select &mdash;'),
					'option_none_value' => '0', 'selected' => $pageid)); ?>
<?php if ($pageid == 0) : ?>
 <?php echo __('OR', 'w9ss') ?>: <input type="checkbox" name="w9ss_newpageadd" id="w9ss_newpageadd" value="1" checked="checked"/> <?php echo __(
				'add new page titled', 'w9ss') ?> <input type="text" name="w9ss_newpage" id="w9ss_newpage" value="<?php echo __(
				'Cart', 'w9ss') ?>"/>.
<?php endif; ?> 
</p>

<br />

<h3><?php echo __('Detailed settings', 'w9ss') ?>:</h3>

<p><label for="w9ss_posttype"><?php echo __('Product\'s post type', 'w9ss') ?>:</label>
<select name="w9ss_posttype" id="w9ss_posttype">
<?php foreach ($post_types as $post_type) : ?>
<option value="<?php echo htmlspecialchars($post_type->name) ?>" <?php if ($w9ss_posttype
				== $post_type->name)
			echo 'SELECTED ' ?>
 ><?php echo htmlspecialchars($post_type->labels->name) ?></option>
<?php endforeach; ?>
</select>  
</p>

<p><label for="w9ss_currency"><?php echo __('Currency', 'w9ss') ?>:</label>
<input type="text" id="w9ss_currency" name="w9ss_currency" value="<?php echo htmlspecialchars(
			$w9ss_currency) ?>"/> <br />
<i><?php echo __(
			'Always make sure that defined currency is compatible with your payment methods.',
			'w9ss') ?></i>  
</p>

<p><label for="w9ss_unit"><?php echo __('Default unit for products', 'w9ss') ?>:</label>
<input type="text" id="w9ss_unit" name="w9ss_unit" value="<?php echo htmlspecialchars(
			$w9ss_unit) ?>"/>
</p>

<p><label for="w9ss_email_from"><?php echo __(
			'E-mail address used in a store', 'w9ss') ?>:</label>
<input type="text" id="w9ss_email_from" name="w9ss_email_from" value="<?php echo htmlspecialchars(
			$w9ss_email_from) ?>"/>
</p>

<p>
<label for="w9ss_autocart"><?php echo __(
			'Automatically place the "add to cart" button on the posts/pages with defined price',
			'w9ss') ?>: </label>
<select name="w9ss_autocart" id="w9ss_autocart">
<option value="0" <?php if ($w9ss_autocart == 0)
		echo 'SELECTED ' ?>
><?php echo __('No', 'w9ss') ?></option>
<option value="1" <?php if ($w9ss_autocart == 1)
		echo 'SELECTED ' ?>
><?php echo __('Before post content', 'w9ss') ?></option>
<option value="2" <?php if ($w9ss_autocart == 2)
		echo 'SELECTED ' ?>
><?php echo __('After post content', 'w9ss') ?></option>
</select>
</p>

<br />
<h3><?php echo __('Other settings', 'w9ss') ?>:</h3>

<p>
<input type="checkbox" name="w9ss_addcartnumber" id="w9ss_addcartnumber" <?php if ($w9ss_addcartnumber)
		echo 'CHECKED '; ?>/>
<label for="w9ss_addcartnumber"><?php echo __(
			'Append number of items in cart to cart page title', 'w9ss') ?></label>
</p>

<p>
<input type="checkbox" name="w9ss_useamount" id="w9ss_useamount" <?php if ($w9ss_useamount)
		echo 'CHECKED '; ?>/>
<label for="w9ss_useamount"><?php echo __(
			'Show quantity selection with "add to cart" button', 'w9ss') ?></label>
</p>

<p>
<input type="checkbox" name="w9ss_hidenoinv" id="w9ss_hidenoinv" <?php if ($w9ss_hidenoinv)
		echo 'CHECKED '; ?>/>
<label for="w9ss_hidenoinv"><?php echo __(
			'Hide product after running out of stock (set status to "draft")',
			'w9ss') ?></label>
</p>

<p>
<input type="checkbox" name="w9ss_gotocart" id="w9ss_gotocart" <?php if ($w9ss_gotocart)
		echo 'CHECKED '; ?>/>
<label for="w9ss_gotocart"><?php echo __('Add to cart redirects to cart page',
			'w9ss') ?></label>
</p>

<p>
<input type="checkbox" name="w9ss_usecss" id="w9ss_usecss" <?php if ($w9ss_usecss)
		echo 'CHECKED '; ?>/>
<label for="w9ss_usecss"><?php echo __('Use default CSS styles', 'w9ss') ?></label>
</p>

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php echo __(
			'Configure sleekStore', 'w9ss') ?>" />
</p>

</form>

<br />

&copy; w9 multimedia 2012
<br /><br />

</div>
<?php }
function w9ss_evAdmin_header_html_php($params = null) {
	extract($params); ?><h2 class="nav-tab-wrapper">
<a href="<?php echo htmlspecialchars($w9fw->path('Admin:index')) ?>" class="nav-tab <?php if ($option
			== 1)
		echo 'nav-tab-active' ?>
"><?php echo __('Module settings', 'w9ss') ?></a>
<a href="<?php echo htmlspecialchars($w9fw->path('Admin:payment')) ?>" class="nav-tab <?php if ($option
			== 2)
		echo 'nav-tab-active' ?>
"><?php echo __('Payment methods', 'w9ss') ?></a>
<a href="<?php echo htmlspecialchars($w9fw->path('Admin:delivery')) ?>" class="nav-tab <?php if ($option
			== 3)
		echo 'nav-tab-active' ?>
"><?php echo __('Delivery types', 'w9ss') ?></a>
</h2>
<br />
<?php }
function w9ss_evAdmin_index_html_php($params = null) {
	extract($params); ?><?php if ($updated) : ?>
<div class="updated"><p><strong><?php echo __('Saved.', 'w9ss') ?></strong></p></div>
<?php endif; ?>

<div class="wrap">
<?php echo $w9fw->render('Admin:header.html.php', array('option' => 1)) ?> 

<h1>sleekStore Lite</h1>

<h3><?php echo __('Version', 'w9ss') ?> <?php echo htmlspecialchars($version) ?></h3>

<a href="<?php echo __('http://www.sleekstore.net/documentation/', 'w9ss') ?>" target="_blank" ><?php echo __(
			'Plugin documentation', 'w9ss') ?></a>

<br /><br />

&copy; w9 multimedia 2012-2013
<br /><br />

<hr />

<br />
<b><?php echo __('Plugin configuration', 'w9ss') ?></b>

<form name="form1" method="post" action="">
<?php echo $nonce ?>

<p><label for="w9ss_pageid"><?php echo __('Module/cart page', 'w9ss') ?>:</label>  
<?php echo wp_dropdown_pages(
			array('name' => 'w9ss_pageid', 'echo' => 0,
					'show_option_none' => __('&mdash; Select &mdash;'),
					'option_none_value' => '0', 'selected' => $pageid)); ?>
<?php if ($pageid == 0) : ?>
 <?php echo __('OR', 'w9ss') ?>: <input type="checkbox" name="w9ss_newpageadd" id="w9ss_newpageadd" value="1" checked="checked"/> <?php echo __(
				'add new page titled', 'w9ss') ?> <input type="text" name="w9ss_newpage" id="w9ss_newpage" value="<?php echo __(
				'Cart', 'w9ss') ?>"/>.
<?php endif; ?> 
</p>

<p><label for="w9ss_posttype"><?php echo __('Product\'s post type', 'w9ss') ?>:</label>
<select name="w9ss_posttype" id="w9ss_posttype">
<?php foreach ($post_types as $post_type) : ?>
<option value="<?php echo htmlspecialchars($post_type->name) ?>" <?php if ($w9ss_posttype
				== $post_type->name)
			echo 'SELECTED ' ?>
 ><?php echo htmlspecialchars($post_type->labels->name) ?></option>
<?php endforeach; ?>
</select>  
</p>

<p><label for="w9ss_currency"><?php echo __('Currency', 'w9ss') ?>:</label>
<input type="text" id="w9ss_currency" name="w9ss_currency" value="<?php echo htmlspecialchars(
			$w9ss_currency) ?>"/> <br />
<i><?php echo __(
			'Always make sure that defined currency is compatible with your payment methods.',
			'w9ss') ?></i>  
</p>

<p><label for="w9ss_unit"><?php echo __('Default unit for products', 'w9ss') ?>:</label>
<input type="text" id="w9ss_unit" name="w9ss_unit" value="<?php echo htmlspecialchars(
			$w9ss_unit) ?>"/>
</p>

<p><label for="w9ss_email_from"><?php echo __(
			'E-mail address used in a store', 'w9ss') ?>:</label>
<input type="text" id="w9ss_email_from" name="w9ss_email_from" value="<?php echo htmlspecialchars(
			$w9ss_email_from) ?>"/>
</p>

<p>
<label for="w9ss_autocart"><?php echo __(
			'Automatically place the "add to cart" button on the posts/pages with defined price',
			'w9ss') ?>: </label>
<select name="w9ss_autocart" id="w9ss_autocart">
<option value="0" <?php if ($w9ss_autocart == 0)
		echo 'SELECTED ' ?>
><?php echo __('No', 'w9ss') ?></option>
<option value="1" <?php if ($w9ss_autocart == 1)
		echo 'SELECTED ' ?>
><?php echo __('Before post content', 'w9ss') ?></option>
<option value="2" <?php if ($w9ss_autocart == 2)
		echo 'SELECTED ' ?>
><?php echo __('After post content', 'w9ss') ?></option>
</select>
</p>

<p>
<input type="checkbox" name="w9ss_addcartnumber" id="w9ss_addcartnumber" <?php if ($w9ss_addcartnumber)
		echo 'CHECKED '; ?>/>
<label for="w9ss_addcartnumber"><?php echo __(
			'Append number of items in cart to cart page title', 'w9ss') ?></label>
</p>

<p>
<input type="checkbox" name="w9ss_useamount" id="w9ss_useamount" <?php if ($w9ss_useamount)
		echo 'CHECKED '; ?>/>
<label for="w9ss_useamount"><?php echo __(
			'Show quantity selection with "add to cart" button', 'w9ss') ?></label>
</p>

<p>
<input type="checkbox" name="w9ss_hidenoinv" id="w9ss_hidenoinv" <?php if ($w9ss_hidenoinv)
		echo 'CHECKED '; ?>/>
<label for="w9ss_hidenoinv"><?php echo __(
			'Hide product after running out of stock (set status to "draft")',
			'w9ss') ?></label>
</p>

<p>
<input type="checkbox" name="w9ss_gotocart" id="w9ss_gotocart" <?php if ($w9ss_gotocart)
		echo 'CHECKED '; ?>/>
<label for="w9ss_gotocart"><?php echo __('Add to cart redirects to cart page',
			'w9ss') ?></label>
</p>

<p>
<input type="checkbox" name="w9ss_usecss" id="w9ss_usecss" <?php if ($w9ss_usecss)
		echo 'CHECKED '; ?>/>
<label for="w9ss_usecss"><?php echo __('Use default CSS styles', 'w9ss') ?></label>
</p>

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php echo __(
			'Save Changes', 'w9ss') ?>" />
</p>

</form>

</div>
<?php }
function w9ss_evAdmin_metabox_html_php($params = null) {
	extract($params); ?><?php wp_nonce_field('upd', 'w9ss_metan') ?>

<label for="w9ss_price"><?php echo __('Price', 'w9ss') ?>: </label>
<input type="text" name="w9ss_price" id="w9ss_price" value="<?php echo htmlspecialchars(
			$price) ?>"/> <?php echo htmlspecialchars($currency) ?>

<div class="alignright">
<input type="checkbox" name="w9ss_useinv" id="w9ss_useinv" value="1" <?php if ($useinv)
		echo "CHECKED" ?>
 /> <label for="w9ss_useinv"><?php echo __('Use inventory balance', 'w9ss') ?>:</label>
<input type="number" name="w9ss_inventory" id="w9ss_inventory" min="0" value="<?php echo htmlspecialchars(
			$inventory) ?>" />
</div>

<br />

<label for="w9ss_unit"><?php echo __('Quantity unit', 'w9ss') ?>:</label> <input type="text" name="w9ss_unit" id="w9ss_unit" value="<?php echo htmlspecialchars(
			$unit) ?>"/>

<div class="alignright">

<label for="w9ss_useamount"><?php echo __('Quantity selection', 'w9ss') ?>:</label> 
<select name="w9ss_useamount" id="w9ss_useamount">
<option value="0" <?php if ($useamount == 0)
		echo 'SELECTED ' ?>
><?php echo __('Default', 'w9ss') ?></option>
<option value="1" <?php if ($useamount == 1)
		echo 'SELECTED ' ?>
><?php echo __('Hide', 'w9ss') ?></option>
<option value="2" <?php if ($useamount == 2)
		echo 'SELECTED ' ?>
><?php echo __('Show', 'w9ss') ?></option>
</select>

<label for="w9ss_limit"><?php echo __('Quantity limit', 'w9ss') ?>:</label> <input type="number" name="w9ss_limit" id="w9ss_limit" min="0" value="<?php echo htmlspecialchars(
			$limit) ?>"/>

</div>

<div class="ssclb">&nbsp;</div>

<br />
	
<?php }
function w9ss_evAdmin_payment_html_php($params = null) {
	extract($params); ?><?php if ($updated) : ?>
<div class="updated"><p><strong><?php echo __('Saved.', 'w9ss') ?></strong></p></div>
<?php endif; ?>

<div class="wrap">
<?php echo $w9fw->render('Admin:header.html.php', array('option' => 2)) ?>

<form action="<?php echo htmlspecialchars($w9fw->path('Admin:payment')) ?>" method="post">
<?php echo $nonce ?>

<h3><?php echo __('PayPal payments', 'w9ss') ?></h3>

<input type="checkbox" name="methods[paypal]" value="1" <?php if (isset(
			$methods['paypal']) && $methods['paypal'])
		echo 'CHECKED' ?>
 /> <?php echo __('active channel', 'w9ss') ?> <br />
<?php echo __('Name in store', 'w9ss') ?>: <input type="text" name="options[paypal_name]" value="<?php echo htmlspecialchars(
			$options['paypal_name']) ?>" /> <br />
<br />
<?php echo __('PayPal email', 'w9ss') ?>: <input type="text" name="options[paypal_email]" value="<?php echo htmlspecialchars(
			$options['paypal_email']) ?>" /> <br />
<br /><br />
<input type="submit" name="Submit" class="button-primary" value="<?php echo __(
			'Save Changes', 'w9ss') ?>" />

<hr />

<h3><?php echo __('Classic wire transfer', 'w9ss') ?></h3>

<input type="checkbox" name="methods[przelew]" value="1" <?php if (isset(
			$methods['przelew']) && $methods['przelew'])
		echo 'CHECKED' ?>
 /> <?php echo __('active channel', 'w9ss') ?> <br />
<?php echo __('Name in store', 'w9ss') ?>: <input type="text" name="options[przelew_name]" value="<?php echo htmlspecialchars(
			$options['przelew_name']) ?>" /> <br /><br />
<?php echo __('Account numbers and additional info for the client', 'w9ss') ?>:
<br />
<textarea name="options[przelew_info]" cols="100" rows="10"><?php echo htmlspecialchars(
			$options['przelew_info']) ?></textarea>

<br />
<input type="submit" name="Submit" class="button-primary" value="<?php echo __(
			'Save Changes', 'w9ss') ?>" />

<hr />

<h3><?php echo __('Payment on delivery', 'w9ss') ?></h3>
<?php echo __(
			'Payment on delivery is available automatically when you select the appropriate delivery methods.',
			'w9ss') ?> 
<br />
<?php echo __('Name in store', 'w9ss') ?>: <input type="text" name="options[odbior_name]" value="<?php echo htmlspecialchars(
			$options['odbior_name']) ?>" /> <br />
<br />
<input type="submit" name="Submit" class="button-primary" value="<?php echo __(
			'Save Changes', 'w9ss') ?>" />

</form>

</div>
<?php }
function w9ss_evZamowienie_edit_html_php($params = null) {
	extract($params); ?><div class="wrap">
<h2><?php _e('Edit order', 'w9ss') ?></h2>

<form action="<?php echo $w9fw
			->path('Zamowienie:edit', array('id' => $entity['id'])) ?>" method="post" >
    <?php echo $edit_form; ?>
    <input type="hidden" name="zedit" value="1" />
    <p>
        <button type="submit"><?php _e('Save', 'w9ss') ?></button>
    </p>
</form>

        <form action="<?php echo $w9fw
			->path('Zamowienie:delete', array('id' => $entity['id'])) ?>" method="post">
            <button type="submit" class="warnbtn">!!! <?php _e('Delete', 'w9ss') ?> !!!</button>
        </form>
        
<br /><br />        
<hr class="clb" />
        <a href="<?php echo $w9fw->path() ?>">
            <?php _e('Back to list', 'w9ss') ?>
        </a>
            
</div>
<?php }
function w9ss_evZamowienie_index_html_php($params = null) {
	extract($params); ?><div class="wrap">
<h2><?php echo __('Orders', 'w9ss') ?></h2>

<ul class="subsubsub">
	<li class="active"><a <?php if ($status == 0)
		echo 'class="current"' ?>
 href="<?php echo htmlspecialchars($w9fw->path()) ?>"><?php echo __('New',
			'w9ss') ?> <span class="count">(<?php echo htmlspecialchars(
			$newcount) ?>)</span></a> |</li>
	<li class="active"><a <?php if ($status == 1)
		echo 'class="current"' ?>
 href="<?php echo htmlspecialchars(
			$w9fw->path(null, array('status' => 'active'))) ?>"><?php echo __(
			'Active', 'w9ss') ?> <span class="count">(<?php echo htmlspecialchars(
			$activecount) ?>)</span></a> |</li>
	<li class="closed"><a <?php if ($status == 2)
		echo 'class="current"' ?>
 href="<?php echo htmlspecialchars(
			$w9fw->path(null, array('status' => 'closed'))) ?>"><?php echo __(
			'Closed', 'w9ss') ?> <span class="count">(<?php echo htmlspecialchars(
			$closedcount) ?>)</span></a></li>
</ul>

<br /><br />

<table class="wp-list-table widefat fixed zamowienia">
    <thead>
        <tr>
            <th><?php echo __('Status', 'w9ss') ?></th>
			<th><?php echo __('Order no & date', 'w9ss') ?></th>
            <th><?php echo __('Value', 'w9ss') ?></th>
            <th><?php echo __('Payment', 'w9ss') ?></th>
            <th><?php echo __('Delivery', 'w9ss') ?></th>
            <th><?php echo __('Customer', 'w9ss') ?></th>
            <th><?php echo __('Comments', 'w9ss') ?></th>
            <th><?php echo __('Description', 'w9ss') ?></th>
           </tr>
    </thead>
    <tbody>
    <?php foreach ($entities as $entity) : ?>
        <tr>
            <td><?php echo htmlspecialchars($statusy[$entity->status]) ?></td>
            <td><a href="<?php echo htmlspecialchars(
				$w9fw->path('Zamowienie:show', array('id' => $entity->id))) ?>"><b><?php echo htmlspecialchars(
				$entity->id) ?></b><br />
            <?php echo htmlspecialchars($entity->date_start) ?></a></td>
            <td><b><?php echo htmlspecialchars($entity->sum) ?> <?php echo htmlspecialchars(
				$entity->currency) ?></b></td>
            <td class="tinfo"><?php echo htmlspecialchars($entity->payment) ?></td>
            <td class="tinfo"><?php echo htmlspecialchars($entity->delivery) ?></td>
            <td><?php echo htmlspecialchars($entity->email) ?> <?php echo htmlspecialchars(
				$entity->name) ?> <?php echo htmlspecialchars($entity->surname) ?> tel. <?php echo htmlspecialchars(
				$entity->phone) ?><br />
            <?php echo htmlspecialchars($entity->postcode) ?> <?php echo htmlspecialchars(
				$entity->city) ?>, <?php echo htmlspecialchars($entity->street) ?> <?php echo htmlspecialchars(
				$entity->nbuilding) ?>/<?php echo htmlspecialchars(
				$entity->napartment) ?></td>
            <td><?php echo htmlspecialchars(substr($entity->comments, 0, 50)) ?>...</td>
            <td><?php echo htmlspecialchars(substr($entity->descr, 0, 50)) ?>...</td>
        </tr>
    <?php endforeach; ?>
	<tr><td></td><td><?php echo __('Sum:', 'w9ss') ?></td><td><b><?php echo htmlspecialchars(
			$sum) ?></b></td><td colspan="5"></td></tr>    
    </tbody>
</table>

</div>
<?php }
function w9ss_evZamowienie_show_html_php($params = null) {
	extract($params); ?><?php if ($statusupdated) : ?>
<div class="updated"><p><strong><?php echo __('Saved.', 'w9ss') ?></strong></p></div>
<?php endif; ?>

<div class="wrap">

<h2><?php echo __('Order', 'w9ss') ?></h2>

<table class="zam1">
	<tr>
		<td>

<b><?php echo __('Order No', 'w9ss') ?>: </b><?php echo htmlspecialchars(
			$entity->id) ?><br />
<b><?php echo __('Status', 'w9ss') ?>: </b><?php echo htmlspecialchars(
			$statusy[$entity->status]) ?> <br />
<b><?php echo __('Payment method', 'w9ss') ?>:</b> <?php echo htmlspecialchars(
			$entity->paymentText) ?> <br />
<b><?php echo __('Delivery type', 'w9ss') ?>:</b> <?php echo htmlspecialchars(
			$entity->deliveryText) ?> <br />
<b><?php echo __('Value', 'w9ss') ?>:</b> <?php echo htmlspecialchars(
			$entity->sum) ?> <?php echo htmlspecialchars($entity->currency) ?>

<br /><br />
<b><?php echo __('Shipment details', 'w9ss') ?>:</b> <br />
<?php echo htmlspecialchars($entity->email) ?> <br />
<?php echo htmlspecialchars($entity->name) ?> <?php echo htmlspecialchars(
			$entity->surname) ?> <br />
<?php echo htmlspecialchars($entity->street) ?> <?php echo htmlspecialchars(
			$entity->nbuilding) ?>/<?php echo htmlspecialchars(
			$entity->napartment) ?><br />
<?php echo htmlspecialchars($entity->postcode) ?> <?php echo htmlspecialchars(
			$entity->city) ?> <br />
<?php echo __('Phone', 'w9ss') ?>: <?php echo htmlspecialchars($entity->phone) ?> <br />
<?php echo __('IP address', 'w9ss') ?>: <?php echo htmlspecialchars(
			$entity->ip_client) ?> <br />

<br />
<b><?php echo __('Comments', 'w9ss') ?>:</b><br />
<?php echo htmlspecialchars($entity->comments) ?>
<br /><br />

</td>
		<td class="pozycje">
		<?php foreach ($items as $key => $pozycja) : ?>
<?php echo htmlspecialchars($key + 1) ?>. <?php echo htmlspecialchars(
				$pozycja->name) ?> - <?php echo htmlspecialchars(
				$pozycja->price) ?> <?php echo htmlspecialchars(
				$entity->currency) ?> x <?php echo htmlspecialchars(
				$pozycja->amount) ?> <?php echo htmlspecialchars($pozycja->unit) ?> = <?php echo htmlspecialchars(
				$pozycja->price * $pozycja->amount) ?> <?php echo htmlspecialchars(
				$entity->currency) ?><br />
		<?php endforeach; ?>
		
		<br /><br />
		<b><?php echo __('Value', 'w9ss') ?>: </b> <?php echo htmlspecialchars(
			$entity->sum) ?> <?php echo htmlspecialchars($entity->currency) ?>

		<br /><br />
		
<b><?php echo __('Description', 'w9ss') ?>: </b> <br />
<?php echo htmlspecialchars($entity->descr) ?>
		</td>
	</tr>
</table>

<hr />

<form action="<?php echo htmlspecialchars(
			$w9fw->path('Zamowienie:updateStatus', array('id' => $entity->id))) ?>" method="post" >
<?php echo __('Change status', 'w9ss') ?>: 
<select name="status">
<?php foreach ($statusy as $key => $status) : ?>
<option value="<?php echo htmlspecialchars($key) ?>" <?php if ($entity->status
				== $key)
			echo "SELECTED" ?>
><?php echo htmlspecialchars($status) ?></option>
<?php endforeach ?>
</select>
<button type="submit" class="zapiszstatus"><?php _e('Save status', 'w9ss') ?></button>
<br />
<input type="checkbox" name="statusemail" id="statusemail" /> <label for="statusemail"><?php _e(
			'Send confirmation to client', 'w9ss') ?></label>
</form>

<hr class="clb" />     
    
<a href="<?php echo htmlspecialchars($w9fw->path()) ?>"><?php echo __(
			'Back to list', 'w9ss') ?></a> | 

<a href="<?php echo htmlspecialchars(
			$w9fw->path('Zamowienie:edit', array('id' => $entity->id))) ?>"><?php echo __(
			'Edit order', 'w9ss') ?></a>

</div>
<?php }
function w9ss_evInventory_index_html_php($params = null) {
	extract($params); ?><div class="wrap">
<h2><?php echo __('Inventory', 'w9ss') ?></h2>

<ul class="subsubsub">
	<li class="active"><a <?php if ($status == 1)
		echo 'class="current"' ?>
 href="<?php echo htmlspecialchars($w9fw->path()) ?>"><?php echo __(
			'Defined inventory', 'w9ss') ?> <span class="count">(<?php echo htmlspecialchars(
			$invcount) ?>)</span></a> |</li>
	<li class="closed"><a <?php if ($status == 2)
		echo 'class="current"' ?>
 href="<?php echo htmlspecialchars(
			$w9fw->path(null, array('status' => 'undef'))) ?>"><?php echo __(
			'Undefined inventory', 'w9ss') ?> <span class="count">(<?php echo htmlspecialchars(
			$noinvcount) ?>)</span></a></li>
</ul>

<br /><br />

<table class="wp-list-table widefat fixed zamowienia">
    <thead>
        <tr>
            <th><?php echo __('Product name', 'w9ss') ?></th>
			<th><?php echo __('Price', 'w9ss') ?></th>
            <th><?php echo __('Inventory', 'w9ss') ?></th>
            <th><?php echo __('Status', 'w9ss') ?></th>
           </tr>
    </thead>
    <tbody>
    <?php foreach ($entities as $entity) : ?>
        <tr>
            <td><a href="post.php?post=<?php echo htmlspecialchars($entity->id) ?>&action=edit"><?php echo htmlspecialchars(
				$entity->post_title) ?></a></td>
            <td><?php echo htmlspecialchars($entity->price) ?></td>
            <td><?php echo htmlspecialchars($entity->inventory) ?></td>
            <td><?php echo htmlspecialchars($entity->post_status) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</div>
<?php }
