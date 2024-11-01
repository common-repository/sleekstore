<?php 

function w9ss_esKoszyk() {
	if (class_exists('w9ss_Koszyk'))
		return;
	class w9ss_Koszyk {
		private $cartitems;
		public function __construct() {
			if (!isset($_SESSION["w9ss.cart.items"])) {
				$this->cartitems = array();
				$this->persistCart();
			}
			$this->cartitems = unserialize($_SESSION["w9ss.cart.items"]);
		}
		private function persistCart() {
			$_SESSION["w9ss.cart.items"] = serialize($this->cartitems);
			$_SESSION["w9ss.cart.count"] = count($this->getCount());
		}
		public function getCount() {
			$count = 0;
			foreach ($this->cartitems as $item)
				$count += $item[2];
			return $count;
		}
		public function removeAll() {
			$this->cartitems = array();
			$this->persistCart();
		}
		public function addToCart($prodid, $name, $price, $ilosc = 1, $typ = 0) {
			foreach ($this->cartitems as $key => $item) {
				if (($item[0] == $prodid) && ($item[1] == $name)
						&& ($item[3] == $price)) {
					$this->cartitems[$key][2] += $ilosc;
					$prodid = false;
					break;
				}
			}
			if ($prodid) {
				$item[0] = $prodid;
				$item[1] = $name;
				$item[2] = $ilosc;
				$item[3] = $price;
				$item[4] = $typ;
				$this->cartitems[] = $item;
			}
			$this->persistCart();
		}
		public function removeItem($pos, $id) {
			if ($this->cartitems[$pos][0] == $id)
				unset($this->cartitems[$pos]);
			$this->persistCart();
		}
		public function getItems() {
			return $this->cartitems;
		}
		public function getItemsAdv() {
			$items = $this->cartitems;
			foreach ($items as $key => $item)
				if ($item[0] > 0) {
					$items[$key]['unit'] = get_post_meta($item[0],
							'_w9ss_unit', true);
					$instock = $this->getInStock($item[0]);
					if (is_numeric($instock) && ($item[2] > $instock)) {
						$items[$key]['outofstock'] = $item[2];
						$items[$key][2] = $instock;
					}
				}
			return $items;
		}
		public function getSuma($items = NULL) {
			if (!$items)
				$items = $this->cartitems;
			$suma = 0.00;
			foreach ($items as $item) {
				$suma += $item[2] * str_replace(',', '.', $item[3]);
			}
			return $suma;
		}
		public function getInStock($pid, $raw = false) {
			if (get_post_meta($pid, '_w9ss_useinv', true)) {
				$instock = intval(get_post_meta($pid, '_w9ss_inventory', true));
				if ($instock < 0)
					$instock = 0;
			} else
				$instock = 'undef';
			if (!$raw) {
				$limit = intval(get_post_meta($pid, '_w9ss_limit', true));
				if (($limit > 0)
						&& (!is_numeric($instock) || ($limit < $instock)))
					$instock = $limit;
			}
			return $instock;
		}
		public function hasInStock($pid, $amount) {
			$instock = $this->getInStock($pid);
			if (!is_numeric($instock))
				return true;
			if ($instock >= $amount)
				return true;
			return false;
		}
		public function setStock($pid, $stock) {
			update_post_meta($pid, '_w9ss_inventory', $stock);
			if (($stock == 0) && (get_option('w9ss_hidenoinv', 0))) {
				global $wpdb;
				$wpdb
						->query(
								"
					UPDATE $wpdb->posts
					SET post_status = 'draft'
					WHERE ID = " . $pid);
			}
		}
	}
}
function w9ss_esShopOptions() {
	if (class_exists('w9ss_ShopOptions'))
		return;
	class w9ss_ShopOptions {
		private $deliveries = null;
		private $poptions = null;
		function getPaymentMethods() {
			$methods = array('paypal' => false, 'przelew' => true,);
			return get_option('w9ss_payment_methods', $methods);
		}
		function setPaymentMethods($methods) {
			update_option('w9ss_payment_methods', $methods);
		}
		function getPaymentOptions($option = null) {
			if (!$this->poptions) {
				$options = array(
						'przelew_name' => __("Wire Transfer", 'w9ss'),
						'przelew_info' => __(
								"Bank Name\nBank Address\nAccount Name\nAccount Number\nPlease reference order number.",
								'w9ss'),
						'dotpay_name' => __("Dotpay - secure online payments",
								'w9ss'), 'dotpay_id' => "", 'dotpay_pin' => "",
						'payu_name' => __("PayU - secure online payments",
								'w9ss'), 'payu_id' => "",
						'payu_auth_key' => "", 'payu_key1' => "",
						'payu_key2' => "", 'paypal_name' => 'PayPal',
						'paypal_email' => get_option('w9ss_email_from',
								get_option('admin_email')),
						'odbior_name' => __("Cash on delivery", 'w9ss'),);
				$this->poptions = get_option('w9ss_payment_options', $options);
			}
			if ($option)
				return $this->poptions[$option];
			else
				return $this->poptions;
		}
		function setPaymentOptions($options) {
			update_option('w9ss_payment_options', $options);
		}
		function getDelivery($activeonly = false) {
			if (!$this->deliveries) {
				$delivery = array(
						'mail' => array('active' => 1,
								'name' => __('Standard Mail', 'w9ss'),
								'price' => '18', 'type' => 0,),
						'courier' => array('active' => 1,
								'name' => __('Courier', 'w9ss'),
								'price' => '35', 'type' => 0,),
						'email' => array('active' => 0,
								'name' => __('Electronic shipment', 'w9ss'),
								'price' => '0', 'type' => 0,),
						'cod' => array('active' => 1,
								'name' => __('Courier COD', 'w9ss'),
								'price' => '50', 'type' => 1,),
						'pickup' => array('active' => 1,
								'name' => __('Local pickup', 'w9ss'),
								'price' => '0', 'type' => 1,),);
				$this->deliveries = get_option('w9ss_deliveries', $delivery);
			}
			if ($activeonly) {
				$retdev = $this->deliveries;
				foreach ($retdev as $key => $dv) {
					if (!isset($dv['active']) || !$dv['active'])
						unset($retdev[$key]);
				}
				return $retdev;
			} else
				return $this->deliveries;
		}
		function setDelivery($deliveries, $newdv0 = array(), $newdv1 = array()) {
			$tosave = array();
			foreach ($deliveries as $key => $dv)
				if ($did = $dv['id']) {
					unset($dv['id']);
					$tosave[$did] = $dv;
				}
			if ($did = $newdv0['id']) {
				unset($newdv0['id']);
				$tosave[$did] = $newdv0;
			}
			if ($did = $newdv1['id']) {
				unset($newdv1['id']);
				$tosave[$did] = $newdv1;
			}
			update_option('w9ss_deliveries', $tosave);
		}
		function getPayments($deliveryid) {
			$ret = array();
			$payments = $this->getPaymentMethods();
			$deliv = $this->getDelivery();
			$options = $this->getPaymentOptions();
			if ($deliv)
				if ($deliv[$deliveryid]['type'] == 1) {
					$ret['odbior'] = $options['odbior_name'];
				} else
					foreach ($payments as $pname => $pval) {
						if ($pval)
							$ret[$pname] = $options[$pname . '_name'];
					}
			return $ret;
		}
		function getDeliveryPrice($deliveryid) {
			$deliv = $this->getDelivery();
			if (isset($deliv[$deliveryid]))
				return $deliv[$deliveryid]['price'];
			else
				return false;
		}
		function getPaymentInfo($paymentid) {
			$ret = NULL;
			if ($paymentid == 'przelew') {
				$ret = $this->getPaymentOptions('przelew_info');
			}
			return $ret;
		}
		function getPaymentText($paymentid) {
			if ($paymentid)
				return $this->getPaymentOptions($paymentid . '_name');
			else
				return __('None', 'w9ss');
		}
		function getDeliveryText($deliveryid) {
			$deliv = $this->getDelivery();
			if (isset($deliv[$deliveryid]))
				return $deliv[$deliveryid]['name'];
			else
				return false;
		}
		function getPaymentChoices() {
			$pays = $this->getPaymentOptions();
			$ret = array('przelew' => $pays['przelew_name'],
					'paypal' => $pays['paypal_name'],
					'odbior' => $pays['odbior_name']);
			return $ret;
		}
		function getDeliveryChoices() {
			$ret = array();
			$deliv = $this->getDelivery();
			foreach ($deliv as $key => $del) {
				$ret[$key] = $del['name'];
			}
			return $ret;
		}
		function addInfoFields($zamowienie) {
			$zamowienie->paymentText = $this
					->getPaymentText($zamowienie->payment);
			$zamowienie->deliveryText = $this
					->getDeliveryText($zamowienie->delivery);
			$zamowienie->paymentInfo = $this
					->getPaymentInfo($zamowienie->payment);
			return $zamowienie;
		}
	}
}
function w9ss_esMailerAdv() {
	if (class_exists('w9ss_MailerAdv'))
		return;
	class w9ss_MailerAdv {
		protected $phpmailer;
		public function __construct() {
			global $phpmailer;
			if (!is_object($phpmailer) || !is_a($phpmailer, 'PHPMailer')) {
				require_once ABSPATH . WPINC . '/class-phpmailer.php';
				require_once ABSPATH . WPINC . '/class-smtp.php';
				$phpmailer = new PHPMailer(true);
			}
			$this->phpmailer = $phpmailer;
		}
		public function clear() {
			$this->phpmailer->ClearAddresses();
			$this->phpmailer->ClearAllRecipients();
			$this->phpmailer->ClearAttachments();
			$this->phpmailer->ClearBCCs();
			$this->phpmailer->ClearCCs();
			$this->phpmailer->ClearCustomHeaders();
			$this->phpmailer->ClearReplyTos();
			return $this;
		}
		public function setTo($email, $name = '', $surname = '') {
			$this->phpmailer->ClearAllRecipients();
			if ($name) {
				if ($surname)
					$name = $name . ' ' . $surname;
				$this->phpmailer->AddAddress($email, $name);
			} else
				$this->phpmailer->AddAddress($email);
			return $this;
		}
		public function setFrom($email, $name = '', $surname = '') {
			if ($name) {
				if ($surname)
					$name = $name . ' ' . $surname;
				$this->phpmailer->From = $email;
				$this->phpmailer->FromName = $name;
			} else {
				$this->phpmailer->From = $email;
				$this->phpmailer->FromName = '';
			}
			return $this;
		}
		public function setSubject($subject) {
			$this->phpmailer->Subject = $subject;
			return $this;
		}
		public function setBody($body) {
			$this->phpmailer->Body = $body;
			return $this;
		}
		public function addAttachment($attachment) {
			$this->phpmailer->AddAttachment($attachment);
			return $this;
		}
		public function addStringAttachment($filedata, $filename) {
			$this->phpmailer->AddStringAttachment($filedata, $filename);
			return $this;
		}
		public function removeAttachments() {
			$this->phpmailer->ClearAttachments();
			return $this;
		}
		public function send() {
			$this->phpmailer->IsMail();
			$this->phpmailer->ContentType = 'text/plain';
			$this->phpmailer->CharSet = get_bloginfo('charset');
			try {
				$this->phpmailer->Send();
			} catch (phpmailerException $e) {
				return false;
			}
			return true;
		}
	}
}
function w9ss_ecShop() {
	if (class_exists('w9ss_ShopController'))
		return;
	class w9ss_ShopController {
		public function cartAction() {
			$koszyk = $this->w9fw->get('Koszyk');
			if (!$koszyk->getCount())
				return $this->w9fw->render('Shop:emptycart.html.php');
			$items = $koszyk->getItemsAdv();
			return array('cart' => $items, 'cartcount' => $koszyk->getCount(),
					'cartvalue' => $koszyk->getSuma($items),
					'deliveries' => $this->w9fw->get('ShopOptions')
							->getDelivery(true),
					'currency' => get_option('w9ss_currency', 'PLN'),);
		}
		public function addItemToCartAction() {
			$koszyk = $this->w9fw->get("Koszyk");
			global $w9ss_addItemToCart;
			if (isset($_POST["w9ssAddItem"])
					&& (wp_verify_nonce($_POST['w9ss_cart'],
							'addtocart' . $_POST["w9ssAddItem"]))) {
				$addid = $_POST["w9ssAddItem"];
				if ($addid > 0) {
					if ($koszyk->getInStock($addid)) {
						$cena = get_post_meta($addid, '_w9ss_price', true);
						if (isset($_POST["w9ssAmount"])
								&& ($_POST["w9ssAmount"] > 0))
							$amount = $_POST["w9ssAmount"];
						else
							$amount = 1;
						$this->w9fw->get("Koszyk")
								->addToCart($addid, get_the_title($addid),
										$cena, $amount);
						$w9ss_addItemToCart = $addid;
					} else
						$w9ss_addItemToCart = $addid * -1;
				}
				unset($_POST['w9ssAddItem']);
			}
			;
		}
		public function addToCartAction($atts = array()) {
			$defatts = array();
			$atts = shortcode_atts($defatts, $atts);
			global $post;
			$cena = get_post_meta($post->ID, '_w9ss_price', true);
			if (!$cena)
				return "";
			$infotext = "";
			$koszyk = $this->w9fw->get("Koszyk");
			$instock = $koszyk->getInStock($post->ID);
			global $w9ss_addItemToCart;
			if (isset($w9ss_addItemToCart)
					&& ($post->ID == abs($w9ss_addItemToCart))) {
				if ($w9ss_addItemToCart > 0)
					$infotext = __('Added to cart', 'w9ss');
				else
					$infotext = __('Out of stock', 'w9ss');
			}
			if (get_option('w9ss_gotocart', 0))
				$actionlink = $this->w9fw->path('Shop:cart');
			else
				$actionlink = "";
			$useamount = get_post_meta($post->ID, '_w9ss_useamount', true);
			if ($useamount)
				$useamount = $useamount - 1;
			else
				$useamount = get_option('w9ss_useamount', 0);
			$ret = array('price' => $cena, 'instock' => $instock,
					'infotext' => $infotext, 'count' => $koszyk->getCount(),
					'currency' => get_option('w9ss_currency', 'PLN'),
					'actionlink' => $actionlink, 'useamount' => $useamount,
					'unit' => get_post_meta($post->ID, '_w9ss_unit', true),
					'nonce' => wp_nonce_field('addtocart' . get_the_ID(),
							'w9ss_cart', true, false),);
			return $ret;
		}
		public function removeAction($pos, $id) {
			$this->w9fw->get('Koszyk')->removeItem($pos, $id);
			if (!headers_sent()) {
				wp_redirect($this->w9fw->path('Shop:cart'));
				exit;
			}
			return $this->w9fw->execute('Shop:cart');
		}
		public function removeAllAction() {
			$this->w9fw->get('Koszyk')->removeAll();
			if (!headers_sent()) {
				wp_redirect($this->w9fw->path('Shop:cart'));
				exit;
			}
			return $this->w9fw->execute('Shop:cart');
		}
		public function orderAction() {
			if (!isset($_POST["orderdo"]))
				return $this->w9fw->execute('Shop:cart');
			$form = $this->getOrderForm();
			$shopOptions = $this->w9fw->get('ShopOptions');
			$koszyk = $this->w9fw->get('Koszyk');
			if ($_POST["orderdo"] == 1) {
				if (!wp_verify_nonce($_POST['w9ss_cart'], 'order'))
					return $this->w9fw->execute('Shop:cart');
				$items = $_POST['items'];
				if (!is_array($items) || (count($items) == 0))
					return $this->w9fw->execute('Shop:cart');
				if (isset($_POST['calculatebtn'])
						&& ($_POST['calculatebtn'] == 'calculate')) {
					$koszyk->removeAll();
					foreach ($items as $item) {
						if ($item[2] > 0)
							$koszyk
									->addToCart($item[0], $item[1], $item[3],
											$item[2]);
					}
					if (!headers_sent()) {
						wp_redirect($this->w9fw->path('Shop:cart'));
						exit;
					}
					return $this->w9fw->execute('Shop:cart');
				}
				$_SESSION['w9ss_order_items'] = serialize($items);
				$delivery = $_POST['delivery'];
				$kwota = 0.0;
				$itemscount = 0;
				foreach ($items as $item)
					if ($item[2] > 0) {
						$kwota += $item[2] * str_replace(',', '.', $item[3]);
						$itemscount++;
					}
				if (!$itemscount)
					return $this->w9fw->execute('Shop:cart');
				$kwota += $shopOptions->getDeliveryPrice($delivery);
				$_SESSION['w9ss_delivery'] = $delivery;
				if ($kwota > 0)
					$payments = $shopOptions->getPayments($delivery);
				else
					$payments = array();
			} else if ($_POST["orderdo"] == 2) {
				if (!wp_verify_nonce($_POST['w9ss_cart'], 'orderdo'))
					return $this->w9fw->execute('Shop:cart');
				$form->bind($_POST['order_form']);
				if ($form->isValid()) {
					if (isset($_SESSION['w9ss_order_items']))
						$items = unserialize($_SESSION['w9ss_order_items']);
					if ((!isset($items)) || !is_array($items)
							|| (count($items) == 0))
						return $this->w9fw->execute('Shop:cart');
					$order = $form->getData();
					$delivery = $_SESSION['w9ss_delivery'];
					$order['delivery'] = $delivery;
					if (isset($_POST['payment']))
						$order['payment'] = $_POST['payment'];
					else
						$order['payment'] = '';
					$order['status'] = 0;
					$order['date_start'] = date('c');
					$order['ip_client'] = $_SERVER['REMOTE_ADDR'];
					$order['currency'] = get_option('w9ss_currency', 'PLN');
					$kwota = 0.0;
					foreach ($items as $item)
						$kwota += $item[2] * str_replace(',', '.', $item[3]);
					$kwota += $shopOptions
							->getDeliveryPrice($order['delivery']);
					$order['sum'] = $kwota;
					global $wpdb;
					$wpdb->insert($wpdb->prefix . "w9ss_order", $order);
					$order_id = $wpdb->insert_id;
					foreach ($items as $item) {
						if ($item[0] > 0) {
							$inv = $koszyk->getInStock($item[0]);
							if (is_numeric($inv)) {
								if ($item[2] > $inv)
									$item[2] = $inv;
								$realstock = $koszyk
										->getInStock($item[0], true);
								if (is_numeric($realstock))
									$koszyk
											->setStock($item[0],
													$realstock - $item[2]);
							}
						}
						if ($item[2] > 0) {
							$pozycja = array();
							$pozycja['order_id'] = $order_id;
							$pozycja['product_id'] = $item[0];
							$pozycja['name'] = $item[1];
							$pozycja['amount'] = $item[2];
							$pozycja['price'] = str_replace(',', '.', $item[3]);
							if ($item[0] > 0)
								$pozycja['unit'] = get_post_meta($item[0],
										'_w9ss_unit', true);
							$wpdb
									->insert($wpdb->prefix . "w9ss_item",
											$pozycja);
						}
					}
					unset($_SESSION['w9ss_order_items']);
					$koszyk->removeAll();
					$_SESSION['w9ss_order_id'] = $order_id;
					$this->sendConfirmMails($order_id);
					return $this->w9fw->execute('Shop:payment');
				}
			}
			return array('form' => $form, 'payments' => $payments,);
		}
		public function paymentAction() {
			$order_id = $_SESSION['w9ss_order_id'];
			if ($order_id == 0)
				return $this->w9fw->execute('Shop:cart');
			$shopOptions = $this->w9fw->get('ShopOptions');
			global $wpdb;
			$order = $wpdb
					->get_row(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_order WHERE id = %d",
											$order_id), ARRAY_A);
			if (!$order)
				return __('Order not found', 'w9ss');
			switch ($order['payment']) {
			case 'przelew':
			case 'odbior':
			case '':
				return $this->w9fw->execute('Shop:receipt');
			case 'paypal':
				$paymentForm = $this->w9fw
						->execute('Gateway:' . $order['payment'] . 'Form',
								array('order' => $order));
			}
			return array('order' => $order, 'paymentForm' => $paymentForm,);
		}
		public function receiptAction() {
			$order_id = $_SESSION['w9ss_order_id'];
			if ($order_id == 0)
				return $this->w9fw->execute('Shop:cart');
			global $wpdb;
			$order = $wpdb
					->get_row(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_order WHERE id = %d",
											$order_id));
			$items = $wpdb
					->get_results(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_item WHERE order_id = %d",
											$order_id));
			if (!$order)
				return __('Order not found', 'w9ss');
			if (isset($_GET['status']) && ($_GET['status'] == 'error'))
				$error = true;
			else
				$error = false;
			return array(
					'order' => $this->w9fw->get('ShopOptions')
							->addInfoFields($order), 'items' => $items,
					'error' => $error,);
		}
		public function getOrderForm() {
			$form = $this->w9fw->createFormBuilder("order_form")->add('name')
					->add('surname')
					->add('email', 'email', array('required' => true),
							array('type' => 'email'))->add('street')
					->add('nbuilding')->add('napartment')->add('city')
					->add('postcode')->add('country')->add('phone')
					->add('comments');
			return $form;
		}
		public function sendConfirmMails($order_id) {
			if ($order_id == 0)
				return false;
			global $wpdb;
			$order = $wpdb
					->get_row(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_order WHERE id = %d",
											$order_id));
			$items = $wpdb
					->get_results(
							$wpdb
									->prepare(
											"SELECT * FROM {$wpdb->prefix}w9ss_item WHERE order_id = %d",
											$order_id));
			if (!$order)
				return false;
			$mailer = $this->w9fw->get('MailerAdv');
			$emailfrom = get_option('w9ss_email_from');
			$mailer->clear()->setFrom($emailfrom)
					->setTo($order->email, $order->name, $order->surname)
					->setSubject(__('Order no ', 'w9ss') . $order->id)
					->setBody(
							$this->w9fw
									->render('Shop:receipt_email.txt.php',
											array(
													'order' => $this->w9fw
															->get('ShopOptions')
															->addInfoFields(
																	$order),
													'items' => $items,
													'sender' => $emailfrom,)))
					->send();
			if ($emailfrom) {
				$mailer->setSubject(__('New sleekStore order', 'w9ss'))
						->setTo($emailfrom)
						->setFrom($order->email, $order->name, $order->surname)
						->send();
			}
		}
	}
}
function w9ss_ecComponents() {
	if (class_exists('w9ss_ComponentsController'))
		return;
	class w9ss_ComponentsController {
		public function cartWidgetAction() {
			$koszyk = $this->w9fw->get('Koszyk');
			return array('cart' => $koszyk->getItems(),
					'cartcount' => $koszyk->getCount(),
					'cartvalue' => $koszyk->getSuma(),
					'currency' => get_option('w9ss_currency', 'PLN'),);
		}
		public function addProductAction($atts) {
			$atts['currency'] = get_option('w9ss_currency', 'PLN');
			$atts['nonce'] = wp_nonce_field('addproduct', 'w9ss_cart', true,
					false);
			if (isset($_POST["w9ssprice"])
					&& (wp_verify_nonce($_POST['w9ss_cart'], 'addproduct'))) {
				$this->w9fw->get("Koszyk")
						->addToCart(0, $_POST['w9ssname'], $_POST['w9ssprice']);
				$atts['infotext'] = __('Added to cart', 'w9ss');
				unset($_POST["w9ssprice"]);
			}
			return $atts;
		}
		public function productListAction($atts) {
			$defaults = array('parent' => null, 'category' => 0,
					'category_name' => '', 'sort_column' => 'post_title',
					'sort_order' => 'ASC', 'meta_key' => '',
					'meta_value' => '', 'number' => '', 'offset' => 0,);
			global $wpdb;
			$products = false;
			$atts = shortcode_atts($defaults, $atts);
			extract($atts, EXTR_SKIP);
			$post_type = get_option('w9ss_posttype', 'page');
			$number = (int) $number;
			$offset = (int) $offset;
			$join = '';
			$where = " ";
			if (!empty($meta_key) || !empty($meta_value)) {
				$join = " LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )";
				$meta_key = stripslashes($meta_key);
				$meta_value = stripslashes($meta_value);
				if (!empty($meta_key))
					$where .= $wpdb
							->prepare(" AND $wpdb->postmeta.meta_key = %s",
									$meta_key);
				if (!empty($meta_value))
					$where .= $wpdb
							->prepare(" AND $wpdb->postmeta.meta_value = %s",
									$meta_value);
			}
			$hierarchical_post_types = get_post_types(
					array('hierarchical' => true));
			if (in_array($post_type, $hierarchical_post_types)) {
				global $post;
				if ($parent === null)
					$parent = $post->ID;
				if ($parent >= 0)
					$where .= $wpdb->prepare(' AND post_parent = %d ', $parent);
			}
			if ($category_name) {
				$category = get_category_by_slug($category_name);
				$category = $category->cat_ID;
			}
			$category = (int) $category;
			if ($category) {
				$join .= $wpdb
						->prepare(
								"INNER JOIN $wpdb->term_relationships
    			ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) 
				INNER JOIN $wpdb->term_taxonomy
				ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
				AND $wpdb->term_taxonomy.taxonomy = 'category'
				AND $wpdb->term_taxonomy.term_id IN (%d)", $category);
			}
			$orderby_array = array();
			$allowed_keys = array('author', 'post_author', 'date', 'post_date',
					'title', 'post_title', 'name', 'post_name', 'modified',
					'post_modified', 'modified_gmt', 'post_modified_gmt',
					'menu_order', 'parent', 'post_parent', 'ID', 'rand',
					'comment_count');
			foreach (explode(',', $sort_column) as $orderby) {
				$orderby = trim($orderby);
				if (!in_array($orderby, $allowed_keys))
					continue;
				switch ($orderby) {
				case 'menu_order':
					break;
				case 'ID':
					$orderby = "$wpdb->posts.ID";
					break;
				case 'rand':
					$orderby = 'RAND()';
					break;
				case 'comment_count':
					$orderby = "$wpdb->posts.comment_count";
					break;
				default:
					if (0 === strpos($orderby, 'post_'))
						$orderby = "$wpdb->posts." . $orderby;
					else
						$orderby = "$wpdb->posts.post_" . $orderby;
				}
				$orderby_array[] = $orderby;
			}
			$sort_column = !empty($orderby_array) ? implode(',', $orderby_array)
					: "$wpdb->posts.post_title";
			$sort_order = strtoupper($sort_order);
			if ('' !== $sort_order
					&& !in_array($sort_order, array('ASC', 'DESC')))
				$sort_order = 'ASC';
			$where_post_type = $wpdb
					->prepare("post_type = %s AND post_status = %s",
							$post_type, 'publish');
			$query = "SELECT * FROM $wpdb->posts $join WHERE ($where_post_type) $where ";
			$query .= " ORDER BY " . $sort_column . " " . $sort_order;
			if (!empty($number))
				$query .= ' LIMIT ' . $offset . ',' . $number;
			$products = $wpdb->get_results($query);
			$ret = array('items' => $products);
			return $ret;
		}
	}
}
function w9ss_ecGateway() {
	if (class_exists('w9ss_GatewayController'))
		return;
	class w9ss_GatewayController {
		public function paypalFormAction($order) {
			$shopOptions = $this->w9fw->get('ShopOptions');
			return array('order' => $order,
					'paypal_email' => $shopOptions
							->getPaymentOptions('paypal_email'),);
		}
	}
}
function w9ss_evShop_addToCart_html_php($params = null) {
	extract($params);
?><div class="sscart">
	<form action="<?php echo $actionlink ?>" method="post">
		<input type="hidden" name="w9ssAddItem" value="<?php the_ID() ?>"/>
		<input type="hidden" name="w9ss_ae" value="Shop_addItemToCart" />
		
		
		<?php echo __('Price', 'w9ss') ?>: <span class="ssprice"><?php echo htmlspecialchars(
			$price) ?></span> <?php echo htmlspecialchars($currency) ?><br />
		
		<?php if ($instock) : ?>
			<?php if ($useamount) : ?>
			<input type="number" name="w9ssAmount" class="sscart_amount" value="1" min="1" /> <?php echo htmlspecialchars(
					$unit) ?> <br />
			<?php endif; ?>
		<button class="sscart_btn sscart_btnadd"><?php echo __('Add to cart',
				'w9ss') ?></button>
		<?php else : ?>
		<div class="sscart_outofstock"><?php echo __('Out of stock', 'w9ss') ?></div>
		<?php endif; ?>
		
		<br />
		
		<?php if ($infotext) : ?>
		<div class="ssinfo">Dodano do <a href="<?php echo htmlspecialchars(
				$w9fw->path('Shop:cart')) ?>">koszyka</a>.</div>
		<?php endif; ?>
		
		<?php ?>
		
		<?php echo $nonce ?>
	</form>
</div>
<?php }
function w9ss_evShop_cart_html_php($params = null) {
	extract($params); ?><h3><?php echo __('Items in shopping cart', 'w9ss') ?></h3>

<a class="sscart_empty" href="<?php echo htmlspecialchars(
			$w9fw->path('Shop:removeAll')) ?>"><?php echo __('Empty cart',
			'w9ss') ?></a><br /><br /><br />

<form action="<?php echo htmlspecialchars($w9fw->path('Shop:order')) ?>" method="post">

<table class="sscartitems">

	<tr>
		<th><?php echo __('Name', 'w9ss') ?></th>
		<th><?php echo __('Quantity', 'w9ss') ?></th>
		<th><?php echo __('Price', 'w9ss') ?></th>
		<th><?php echo __('Value', 'w9ss') ?></th>
		<th></th>
	</tr>

	<?php foreach ($cart as $key => $item) : ?>
	<tr>
		<td><?php echo htmlspecialchars($item[1]) ?></td>
		<td <?php if (isset($item['outofstock'])) : ?>class="stockwarning"<?php endif; ?>><span class="iloscval"><input type="number" min="0" name="items[<?php echo htmlspecialchars(
				$key) ?>][2]" value="<?php echo htmlspecialchars($item[2]) ?>" /> <?php echo htmlspecialchars(
				$item['unit']) ?></span></td>
		<td><span class="cenaval"><?php echo htmlspecialchars($item[3]) ?> <?php echo htmlspecialchars(
				$currency) ?></span></td>
		<td><?php echo htmlspecialchars($item[2] * $item[3]) ?> <?php echo htmlspecialchars(
				$currency) ?></td>
		<td>
			<a href="<?php echo htmlspecialchars(
				$w9fw
						->path('Shop:remove',
								array('pos' => $key, 'id' => $item[0]))) ?>"><?php echo __(
				'Remove from cart', 'w9ss') ?></a> 
		</td>
	</tr>

	<tr class="opcje">
		<td colspan="4">
			<input type="hidden" name="items[<?php echo htmlspecialchars($key) ?>][0]" value="<?php echo htmlspecialchars(
				$item[0]) ?>" />
			<input type="hidden" name="items[<?php echo htmlspecialchars($key) ?>][1]" value="<?php echo htmlspecialchars(
				$item[1]) ?>" />
			<input type="hidden" name="items[<?php echo htmlspecialchars($key) ?>][3]" value="<?php echo htmlspecialchars(
				$item[3]) ?>" />
		</td>
	</tr>
	<?php endforeach; ?>
	
	<tr>
		<td colspan="2"></td>
		<td><?php echo __('Total', 'w9ss') ?>: <br /></td>
		<td><span class="sscartval"><?php echo htmlspecialchars($cartvalue) ?></span> <?php echo htmlspecialchars(
			$currency) ?></td>
		<td><button class="sscart_btn sscart_calculatebtn" name="calculatebtn" value="calculate">Przelicz</button></a></td>
	</tr>

	<tr>
	<td colspan="4"><br />
	<?php echo __('Shipment', 'w9ss') ?>:<br />	
<?php $first = true;
	foreach ($deliveries as $key => $delivery) : ?>
	<input type="radio" name="delivery" class="delivery" value="<?php echo htmlspecialchars(
				$key) ?>" <?php if ($first)
			echo 'CHECKED' ?>
> <?php echo htmlspecialchars($delivery['name']) ?> 
	<?php if ($delivery['price'] > 0) : ?>(<?php echo htmlspecialchars(
					$delivery['price']) ?> zł)<?php endif ?> <br />
<?php $first = false;
	endforeach; ?>
	
	</td>
	</tr>

	<tr>
		<td colspan="4" class="sstd_btn"><button class="sscart_btn sscart_orderbtn" name="orderbtn" value="order"><?php echo __(
			'Place order', 'w9ss') ?></button></td>
	</tr>
	
</table>

<input type="hidden" name="orderdo" value="1"/>
<?php wp_nonce_field('order', 'w9ss_cart'); ?>
</form>
<?php }
function w9ss_evShop_emptycart_html_php($params = null) {
	extract($params); ?><h3><?php echo __('Items in shopping cart', 'w9ss') ?></h3>

<b><?php echo __('Your shopping cart is empty.', 'w9ss') ?></b>
<?php }
function w9ss_evShop_order_html_php($params = null) {
	extract($params); ?><h3><?php echo __('Order - step 2/3', 'w9ss') ?></h3>

<form action="<?php echo htmlspecialchars($w9fw->path('Shop:order')) ?>" method="post">

<input type="hidden" name="orderdo" value="2"/>

<br />
<?php if ($payments) : ?>
<b><?php echo __('PAYMENT METHOD', 'w9ss') ?>:</b><br /><br />	
<?php $first = true;
		foreach ($payments as $key => $payment) : ?>
<input type="radio" name="payment" class="payment" value="<?php echo htmlspecialchars(
					$key) ?>" <?php if ($first)
				echo 'checked="checked"' ?>
 /> <?php echo htmlspecialchars($payment) ?> <br />
<?php $first = false;
		endforeach; ?>
<br /><br />
<?php endif; ?>	

<b><?php echo __('CUSTOMER', 'w9ss') ?>:</b> <br /><br />
<table class="ssorder">
	<tr>
		<th><label for="order_form_name"><?php echo __('First name', 'w9ss') ?>:</label></th>
		<td><input id="order_form_name" type="text" name="order_form[name]" value="<?php echo htmlspecialchars(
			$form->get('name')) ?>" /></td>
	</tr>
	<tr>
		<th><label for="order_form_surname"><?php echo __('Surname', 'w9ss') ?>:</label></th>
		<td><input id="order_form_surname" type="text" name="order_form[surname]" value="<?php echo htmlspecialchars(
			$form->get('surname')) ?>" /></td>
	</tr>
	<tr>
		<th><label for="order_form_email"><?php echo __('E-mail', 'w9ss') ?>: *</label></th>
		<td><input type="email" name="order_form[email]" value="<?php echo htmlspecialchars(
			$form->get('email')) ?>"  required="required"/>
		<?php if ($form->getError('email')) : ?>
		<div class="form_error"><?php echo htmlspecialchars(
				$form->getError('email')) ?></div>
		<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><label for="order_form_street"><?php echo __('Street', 'w9ss') ?>:</label></th>
		<td><input id="order_form_street" type="text" name="order_form[street]" value="<?php echo htmlspecialchars(
			$form->get('street')) ?>" /></td>
	</tr>
	<tr>
		<th><label for="order_form_nbuilding"><?php echo __('Building no',
			'w9ss') ?> / <?php echo __('Apartment no', 'w9ss') ?>:</label></th>
		<td><input id="order_form_nbuilding" type="text" name="order_form[nbuilding]" value="<?php echo htmlspecialchars(
			$form->get('nbuilding')) ?>" class="ss_shorti" /> / <input id="order_form_napartment" type="text" name="order_form[napartment]" value="<?php echo htmlspecialchars(
			$form->get('napartment')) ?>" class="ss_shorti" /></td>
	</tr>
	<tr>
		<th><label for="order_form_city"><?php echo __('City', 'w9ss') ?>:</label></th>
		<td><input id="order_form_city" type="text" name="order_form[city]" value="<?php echo htmlspecialchars(
			$form->get('city')) ?>" /></td>
	</tr>
	<tr>
		<th><label for="order_form_postcode"><?php echo __('Post code', 'w9ss') ?>:</label></th>
		<td><input id="order_form_postcode" type="text" name="order_form[postcode]" value="<?php echo htmlspecialchars(
			$form->get('postcode')) ?>" /></td>
	</tr>
	<tr>
		<th><label for="order_form_country"><?php echo __('Country', 'w9ss') ?>:</label></th>
		<td><input id="order_form_country" type="text" name="order_form[country]" value="<?php echo htmlspecialchars(
			$form->get('country')) ?>" /></td>
	</tr>
	<tr>
		<th><label for="order_form_phone"><?php echo __('Phone', 'w9ss') ?>:</label></th>
		<td><input id="order_form_phone" type="text" name="order_form[phone]" value="<?php echo htmlspecialchars(
			$form->get('phone')) ?>" /></td>
	</tr>
	<tr>
		<th><label for="order_form_comments"><?php echo __('Comments', 'w9ss') ?>:</label></th>
		<td><textarea name="order_form[comments]" id="order_form_comments" cols="30" rows="3"><?php echo htmlspecialchars(
			$form->get('comments')) ?></textarea></td>
	</tr>
	<tr>
		<td colspan="2" class="sstd_btn">
			<button class="sscart_btn sscart_paybtn"><?php echo __(
			'Finalize order', 'w9ss') ?></button>			
		</td>
	</tr>
</table>
<?php wp_nonce_field('orderdo', 'w9ss_cart'); ?>
</form>
<?php }
function w9ss_evShop_payment_html_php($params = null) {
	extract($params); ?><h3><?php echo __('Order finalization', 'w9ss') ?></h3> <br />
<?php echo __('Order no', 'w9ss') ?> <?php echo htmlspecialchars($order['id']) ?> <br />
<?php echo __('Amount', 'w9ss') ?>: <?php echo htmlspecialchars(
			str_replace('.', ',', $order['sum'])) ?> <?php echo htmlspecialchars(
			$order['currency']) ?><br />
<?php echo $paymentForm ?>

<?php }
function w9ss_evShop_receipt_html_php($params = null) {
	extract($params); ?><h3><?php echo __('Order confirmation', 'w9ss') ?></h3>
<br />
<?php echo __('Thank you, your order has been placed.', 'w9ss') ?> <br />
<br />
<?php if ($error) : ?>
<b><?php echo __('Warning: there was a problem with online payment.', 'w9ss') ?> <br /><br />
<?php echo __(
				'If you have trouble finalizing your order, please repeat your order or use another payment channel.',
				'w9ss') ?></b><br />
<?php endif; ?>

<?php if ($order->paymentInfo) : ?>
<b><?php echo __('Payment info', 'w9ss') ?>:</b> <br />
<?php echo nl2br($order->paymentInfo) ?>
<?php endif; ?>

<hr />
<b><?php echo __('Details of your order', 'w9ss') ?>:</b> <br />
<i><?php echo __(
			'Details of your order were also sent to your e-mail address.',
			'w9ss') ?></i>
<br /><br />
<?php echo __('Order no', 'w9ss') ?>: <b><?php echo htmlspecialchars($order->id) ?></b> <br />
<?php echo __('Amount', 'w9ss') ?>: <b><?php echo htmlspecialchars($order->sum) ?> <?php echo htmlspecialchars(
			$order->currency) ?></b> <br /> <br />
<?php echo __('Payment', 'w9ss') ?>: <?php echo htmlspecialchars(
			$order->paymentText) ?> <br />
<?php echo __('Shipment', 'w9ss') ?>: <?php echo htmlspecialchars(
			$order->deliveryText) ?> <br /><br />
<?php echo __('Buyer', 'w9ss') ?>: <br />
<?php echo __('E-mail', 'w9ss') ?>: <?php echo htmlspecialchars($order->email) ?> <br />
<?php echo htmlspecialchars($order->name) ?> <?php echo htmlspecialchars(
			$order->surname) ?> <br />
<?php if ($order->street) : ?><?php echo htmlspecialchars($order->street) ?> <?php echo htmlspecialchars(
				$order->nbuilding) ?>/<?php echo htmlspecialchars(
				$order->napartment) ?>,<?php endif; ?> 
<?php echo htmlspecialchars($order->postcode) ?> <?php echo htmlspecialchars(
			$order->city) ?> <?php echo htmlspecialchars($order->country) ?> <br />
<?php if ($order->phone) : ?><?php echo __('Phone', 'w9ss') ?>: <?php echo htmlspecialchars(
				$order->phone) ?> <br /><?php endif; ?>
<br />
<?php echo __('Items', 'w9ss') ?>: <br />
<?php foreach ($items as $key => $item) : ?>
<?php echo htmlspecialchars($key + 1) ?>. <?php echo htmlspecialchars(
				$item->name) ?> - <?php echo htmlspecialchars($item->price) ?> <?php echo htmlspecialchars(
				$order->currency) ?> x <?php echo htmlspecialchars(
				$item->amount) ?> <?php echo htmlspecialchars($item->unit) ?> = <?php echo htmlspecialchars(
				$item->price * $item->amount) ?> <?php echo htmlspecialchars(
				$order->currency) ?><br />
<?php endforeach; ?>
<br />

<hr />
<?php if ($order->comments) : ?>
<?php echo __('Comments', 'w9ss') ?>: <?php echo nl2br($order->comments) ?>

<br /><br />
<hr />
<?php endif; ?>
<a href="<?php bloginfo('url') ?>"><?php echo __('Back to main page', 'w9ss') ?></a>
<?php }
function w9ss_evShop_receipt_email_txt_php($params = null) {
	extract($params); ?><?php echo __('Thank you for your order.', 'w9ss') ?> 
 
<?php echo __('Details of your order', 'w9ss') ?>: 
 
---------------------------------------------------- 
<?php echo __('Order no', 'w9ss') ?>: <?php echo htmlspecialchars($order->id) ?> 
<?php echo __('Amount', 'w9ss') ?>: <?php echo htmlspecialchars($order->sum) ?> <?php echo htmlspecialchars(
			$order->currency) ?> 
 
<?php echo __('Payment', 'w9ss') ?>: <?php echo htmlspecialchars(
			$order->paymentText) ?> 
<?php echo __('Shipment', 'w9ss') ?>: <?php echo htmlspecialchars(
			$order->deliveryText) ?> 
 
<?php echo __('Buyer', 'w9ss') ?>: 
<?php echo htmlspecialchars($order->name) ?> <?php echo htmlspecialchars(
			$order->surname) ?> 
<?php if ($order->street) : ?><?php echo htmlspecialchars($order->street) ?> <?php echo htmlspecialchars(
				$order->nbuilding) ?>/<?php echo htmlspecialchars(
				$order->napartment) ?>,<?php endif; ?> <?php echo htmlspecialchars(
			$order->postcode) ?> <?php echo htmlspecialchars($order->city) ?> <?php echo htmlspecialchars(
			$order->country) ?>   
<?php if ($order->phone) : ?><?php echo __('Phone', 'w9ss') ?>: <?php echo htmlspecialchars(
				$order->phone) ?><?php endif; ?> 
 
<?php echo __('Items', 'w9ss') ?>: 
<?php foreach ($items as $key => $item) : ?>
<?php echo htmlspecialchars($key + 1) ?>. <?php echo htmlspecialchars(
				$item->name) ?> - <?php echo htmlspecialchars($item->price) ?> <?php echo htmlspecialchars(
				$order->currency) ?> x <?php echo htmlspecialchars(
				$item->amount) ?> <?php echo htmlspecialchars($item->unit) ?> = <?php echo htmlspecialchars(
				$item->price * $item->amount) ?> <?php echo htmlspecialchars(
				$order->currency) ?> 
<?php endforeach; ?>
 
<?php if ($order->comments) : ?>
<?php echo __('Comments', 'w9ss') ?>:  
<?php echo htmlspecialchars($order->comments) ?> 
<?php endif; ?>
 
---------------------------------------------------- 
 
<?php if ($order->paymentInfo) : ?>
<?php echo __('Payment info', 'w9ss') ?>: 
<?php echo $order->paymentInfo ?> 
<?php endif; ?>
 
<?php echo __(
			'If you have additional questions, don\'t hesitate to contact us.',
			'w9ss') ?> 
<?php echo htmlspecialchars($sender) ?> 
<?php }
function w9ss_evShop_status_email_txt_php($params = null) {
	extract($params); ?><?php echo __('Hello!', 'w9ss') ?> 
 
<?php echo __('Your order status has changed.', 'w9ss') ?> 
 
<?php printf(__('Currently, Your order #%1$s has status %2$s.', 'w9ss'),
			$order['id'], $status) ?> 
 
<?php echo __(
			'If you have additional questions, don\'t hesitate to contact us.',
			'w9ss') ?> 
 
<?php echo __('Best regards,', 'w9ss') ?> 
<?php echo htmlspecialchars($sender) ?> 
<?php }
function w9ss_evComponents_addProduct_html_php($params = null) {
	extract($params); ?><div class="sscart ssproduct">
	<form action="<?php the_permalink() ?>" method="post">

		<input type="hidden" name="w9ssname" value="<?php echo htmlspecialchars(
			$name) ?>"/>
		<input type="hidden" name="w9ssprice" value="<?php echo htmlspecialchars(
			$price) ?>" />
		
		<?php echo __('Price', 'w9ss') ?>: <span class="ssprice"><?php echo htmlspecialchars(
			$price) ?></span> <?php echo htmlspecialchars($currency) ?><br />
		
		<button class="sscart_btn sscart_btnpadd"><?php echo __('Add to cart',
			'w9ss') ?></button>

		<?php if ($infotext) : ?>
		<div class="ssinfo"><?php echo htmlspecialchars($infotext) ?></div>
		<?php endif; ?>
		
		<?php echo $nonce ?>
	</form>
</div>
<?php }
function w9ss_evComponents_cartWidget_html_php($params = null) {
	extract($params); ?><?php echo __('Items in shopping cart', 'w9ss') ?>: <?php echo htmlspecialchars(
			$cartcount) ?>
<br />
<?php echo __('Total', 'w9ss') ?>: <?php echo htmlspecialchars($cartvalue) ?> <?php echo htmlspecialchars(
			$currency) ?>
<br /><br />
<a href="<?php echo htmlspecialchars($w9fw->path('Shop:cart')) ?>"><?php echo __(
			'Show cart', 'w9ss') ?></a>
<?php }
function w9ss_evComponents_productList_html_php($params = null) {
	extract($params); ?><div class="productgrid">
	<ul class="productlist">
	
	<?php global $post;
	$oldpost = $post;
	foreach ($items as $post) :
		setup_postdata($post); ?>
	
	<li>
	<h2><?php the_title() ?></h2>
	<?php global $more;
		$more = 0;
		the_content(); ?>
	</li>
	
	<?php endforeach;
	$post = $oldpost;
	wp_reset_postdata(); ?>
	
	</ul>
</div>
<?php }
function w9ss_evGateway_paypalForm_html_php($params = null) {
	extract($params); ?>
<br />

<form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" 
method="post">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="<?php echo htmlspecialchars(
			$paypal_email) ?>">
<input type="hidden" name="item_name" value="<?php printf(
			__('Order no %s', 'w9ss'), $order['id']) ?>">
<input type="hidden" name="item_number" value="<?php echo htmlspecialchars(
			$order['id']) ?>">
<input type="hidden" name="currency_code" value="<?php echo htmlspecialchars(
			$order['currency']) ?>">
<input type="hidden" name="amount" value="<?php echo htmlspecialchars(
			$order['sum']) ?>">

<input type="hidden" name="charset" value="utf-8">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="return" value="<?php echo htmlspecialchars(
			$w9fw->path('Shop:receipt')) ?>">
<input type="hidden" name="cancel_return" value="<?php echo htmlspecialchars(
			$w9fw->path('Shop:receipt', array('status' => 'error'))) ?>">
<input type="hidden" name="no_note" value="1">
<center>
<input type="image" src="<?php echo __(
			'https://www.paypal.com/en_US/i/btn/btn_paynow_LG.gif', 'w9ss') ?>" 
name="submit" alt="<?php echo __('PayPal - fast and secure online payments.',
			'w9ss') ?>">

</center>
</form>

<br>
<i><?php echo __(
			'When you click "Pay Now" you will be redirected to a secure PayPal site that handles online payments.',
			'w9ss') ?></i>
<br />
<?php switch (get_locale()) :
	case 'pl_PL': ?>
	<!-- PayPal Logo --><table border="0" cellpadding="10" cellspacing="0" align="center"><tr><td align="center"></td></tr><tr><td align="center"><a href="#" title="Jak działa PayPal" onclick="javascript:window.open('https://www.paypal.com/webapps/mpp/paypal-popup','WIPaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=700, height=600');"><img src="https://www.paypalobjects.com/webstatic/mktg/logo-center/banner_pl_secured_payments_by_pp_319x110.jpg" border="0" alt="Znak akceptacji PayPal"></a></td></tr></table><!-- PayPal Logo -->
<?php break;
	default: ?>
	<!-- PayPal Logo --><table border="0" cellpadding="10" cellspacing="0" align="center"><tr><td align="center"></td></tr><tr><td align="center"><a href="#" title="How PayPal Works" onclick="javascript:window.open('https://www.paypal.com/webapps/mpp/paypal-popup','WIPaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700');"><img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_SbyPP_mc_vs_dc_ae.jpg" border="0" alt="PayPal Acceptance Mark"></a></td></tr></table><!-- PayPal Logo -->
<?php endswitch ?>

<?php }
