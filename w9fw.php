<?php

/* version 2.6 lite
 * 
 * 
 * 
 */

class w9ss_w9framework
{
	protected $name;
	protected $basedir;
	protected $baseurl;
	protected $moduleurl = NULL;
	protected $controllers = array();
	protected $services = array();
	protected $eb = false;
	protected $ebv = array();
	protected $tE = NULL;
	protected $cache = false;
	protected $ae_action = NULL;
	protected $ae = NULL;
	protected $ae_list = NULL;
	
	function __construct($name = "w9fw_plugin", $baseurl = NULL, $basedir = NULL )
	{
		$this->name = $name;
		$this->baseurl = $baseurl;
		if ($basedir) $this->basedir = $basedir;
		else $this->basedir = dirname(__FILE__) . '/';
	}
	
	function dispatch($defaction = 'Default:index')
	{
		if (isset($_GET['_a'])) {
			$rcount = 1;
			$action = str_replace('_', ':', $_GET['_a'], $rcount);
		} else $action = $defaction;

		if ($this->ae_action==$action) return $this->ae;		
		return $this->execute($action, array_merge($_GET, $_POST));
	}
	
	function execute($action, $params = NULL) 
	{	
		$m = explode(':', $action);

		if (!isset($m[1])) $m[1] = "index";
		
		$actclassname = $this->name . '_' . $m[0] . 'Controller';
		$actclassmethod = $m[1] . 'Action';
		
		if (!isset($this->controllers[$m[0]])) 
		{
			// define controller class
				$cfunc = $this->name . '_ec' . $m[0];
				$cfunc();
			
			if (!class_exists($actclassname)) return NULL;

			$actclass = new $actclassname;
			$actclass->w9fw = $this;
			
			$this->controllers[$m[0]] = $actclass;
		
		} else $actclass = $this->controllers[$m[0]];
		
		
		try {
		 	$r = new ReflectionMethod($actclassname, $actclassmethod);
		} catch (Exception $e) {
			trigger_error(sprintf('call to %s failed', $actclassname . ":" . $actclassmethod), E_USER_ERROR);
		}
		$real_params = array();
		if ($params) {
			$real_params = array();
			foreach ($r->getParameters() as $param) {
				$pname = $param->getName();
				if (array_key_exists($pname, $params))
				{
					$real_params[] = $params[$pname];
				}
				else if ($param->isDefaultValueAvailable()) {
					$real_params[] = $param->getDefaultValue();
				}
				else
				{
					trigger_error(sprintf('call to %s missing parameter %s', $actclassname . ":" . $actclassmethod, $pname), E_USER_ERROR);
					return NULL;
				}			
			}
		}
		$result =  $r->invokeArgs($actclass, $real_params);
	
		if (is_array($result)) {
	
			// template
			if (isset($result['_template'])) {
				$template = $result['_template'];
				unset($result['_template']);
			} else $template = $action . '.html.php';
	
			return $this->render($template, $result);	
				
		} else return $result; 
		
	}

	function aheadExecute()
	{
		$action = null;
		if (isset($_REQUEST[$this->name . '_ae'])) {
			$rcount = 1;
			$action = str_replace('_', ':', $_REQUEST[$this->name . '_ae'], $rcount);
		} else if (is_array($this->ae_list) && isset($_GET['_a']) && in_array($_GET['_a'], $this->ae_list)) {
			$rcount = 1;
			$action = str_replace('_', ':', $_GET['_a'], $rcount);
		} 
		
		if ($action) {
			$this->ae = $this->execute($action, array_merge($_GET, $_POST));
			$this->ae_action = $action;
		}
	}
	
	
	function render($template, $params = null) 
	{
		$t = explode(':', $template);
		
		//template include
		ob_start();
		
			$params['w9fw'] = $this;
			$tfunc = $this->name . '_ev' . $t[0] .  '_' . str_replace('.', '_', $t[1]);
			$tfunc($params);
		
		$ret = ob_get_contents();
		ob_end_clean();
		
		return $ret;
	}

	function path($action = null, $params = null, $moduleurl = null)
	{
		if ($moduleurl) $link = $moduleurl;
		else if (is_admin()) $link = get_admin_url(null, 'admin.php?page=' . $_GET["page"]); 
		else if ($this->moduleurl) $link = $this->moduleurl;
		else $link = get_permalink();
		
		if ($action) {
			$path = explode(':', $action);
			
			if (strpos($link, "?")>-1) $link.="&"; else $link.="?";
			$link .= "_a=" . $path[0];
			if ($path[1]) $link .= "_" . $path[1];			
		} else if (strpos($link, "?")===FALSE) $link.="?";
		
		if (is_array($params)) foreach ($params as $key => $param) {
				$link .= "&" . $key . '=' . urlencode($param);
		}
	
		return $link;
	}

	function get($service) {
		if (!isset($this->services[$service])) {
			
				$sfunc = $this->name . '_es' . $service;
				$sfunc();
				
			$serviceclass = $this->name . '_' . $service;
			if (class_exists($serviceclass)) {
				$sinstance = new $serviceclass;
				$sinstance->w9fw = $this;
				$this->services[$service] = $sinstance;
			} else return NULL;
		} 
		return $this->services[$service];
	}
	
	function getTE() {
		return NULL; 
	}
	
	public function setModuleUrl($moduleurl) { $this->moduleurl = $moduleurl; }
	public function setEb($ebv = null) { $this->eb = true; if ($ebv) $this->ebv = $ebv; }
	public function setCache($on = true) { $this->cache = $on; }
	public function addAE($param) {
		$param = str_replace(':', '_', (array) $param);
		if (!is_array($this->ae_list)) $this->ae_list = $param;
		else $this->ae_list = array_merge($this->ae_list, $param);
	}

	public function getName() { return $this->name; }
	public function getBaseDir() { return $this->basedir; }
	public function getBaseUrl() { return $this->baseurl; }	
	public function getModuleUrl() { return $this->moduleurl; }
		
	public function createFormBuilder($name = "form", $defaults = null)
	{
		if (class_exists('w9ss_w9fwForm')) return new w9ss_w9fwForm($name, $defaults);
		else return false;
	}
	
}




 


class w9ss_w9fwForm {
	private $name;
	private $fields;
	private $defaults;
	
	
	function __construct($name, $defaults = null)
	{
		$this->name = $name;
		$this->defaults = $defaults;
	}
	
	function add($name, $type = null, $options = null, $constraints = null)
	{
		if (isset($this->fields[$name])) unset ($this->fields[$name]);
		
		if (!$type) $type = "text";
		if (!$options) $options = array();
		if (!$constraints) $constraints = array();
		
		if (isset($this->defaults[$name])) $value = $this->defaults[$name];
		    else $value = null; 
		
		$field = new w9ss_w9fField($name, $type, $options, $constraints, $value, $this->name);
		
		$this->fields[$name] = $field;
		
		return $this;
	}	
	
	function bind($vals)
	{
		if (is_array($vals)) foreach ($this->fields as $fname => $field) {
			if (isset($vals[$fname]) && ($field->value != $vals[$fname])) {
				$field->value = $vals[$fname]; 
				$field->changed = true;
			}
		}
	}
	
	function getData()
	{
		$obj = array();
		foreach ($this->fields as $fname => $field) $obj[$fname] = $field->value;
		return $obj;
	}
	
	function getField($name) {
		if (isset($this->fields[$name])) return $this->fields[$name];
			else return null;
	}
	
	function getError($name) {
		if (isset($this->fields[$name])) return $this->fields[$name]->getError();
			else return null;
	}

	function get($name) {  // get value
		if (isset($this->fields[$name])) return $this->fields[$name]->getValue();
		else return null;
	}
	
	
	function getUpdatedData()
	{
		$obj = array();
		foreach ($this->fields as $fname => $field) if ($field->changed) $obj[$fname] = $field->value;
		if (count($obj)>0) return $obj;
			else return NULL;
	}

	function isValid()   // in progress / beta
	{ 
		$errors = 0;
		foreach ($this->fields as $field) {
			if (!$field->isValid()) $errors++;			
		}
		if ($errors>0) return false;
		else return true;
	}

	
	function __toString()
	{
		$render = "";
		
		foreach ($this->fields as $field) {
			$render.=$field . "<br />\n";			
		}	
					
		return $render;
	}
	
	public function getFormName($param) { return $this->name; }
}



class w9ss_w9fField {
	var $name;
	var $type;
	var $options;
	var $value;
	var $constraints;
	var $errormsg = false;
	var $changed = false;
	var $formname;

	function __construct($name, $type, $options, $constraints, $value, $formname)
	{
		$this->name = $name;
		$this->type = $type;
		$this->options = $options;
		$this->constraints = $constraints;
		$this->value = $value;
		$this->formname = $formname;
	}

	function isValid()  // in progress / beta
	{  
		$valid = true;
		
		if (isset($this->options["required"]) && $this->options["required"] && ($this->value == "")) {
			$this->errormsg = __('Required field.', 'w9ss');
			return false;
		}
		
		foreach ($this->constraints as $key => $constr) {
			switch ($key) {
				case 'type': switch($constr) {
						case 'email': if (!is_email($this->value)) {
								$valid = false;
								$this->errormsg = __('Valid e-mail address is required.', 'w9ss');
									}	
								break;
						};
					break;
				case 'MinLength': if (strlen($this->value)<$constr) {
					$valid = false;
					$this->errormsg = __('To few characters.', 'w9ss');
				}
				break;
			}
		}
		return $valid;
	}
	
	function getError() {
		return $this->errormsg;
	}
	
	function getValue() {
		return $this->value;
	}
	
	function renderLabel() {
		if (isset($this->options['label'])) $label = $this->options['label']; 
			else $label = ucfirst($this->name);
		return "<label for=\"{$this->formname}_{$this->name}\">$label</label> ";		
	}
	
	function render() {
		if (isset($this->options["required"]) && $this->options["required"]) $req = ' required="required"';
			else $req = "";
		$pname = "name=\"{$this->formname}[{$this->name}]\"";
		$pid = "id=\"{$this->formname}_{$this->name}\"";
		switch ($this->type) {
			case 'text': return "<input type=\"text\" $pname $pid value=\"" . htmlspecialchars($this->value) . "\" $req/>";
			case 'password': return "<input type=\"password\" $pname $pid value=\"" . htmlspecialchars($this->value) . "\" $req/>";
			case 'email': return "<input type=\"email\" $pname $pid value=\"" . htmlspecialchars($this->value) . "\" $req/>";
			case 'textarea': return "<textarea $pname $pid $req>" . htmlspecialchars($this->value) . "</textarea>";
			case 'choice': {
				$ret = "<select $pname $pid $req>";
				foreach ($this->options['choices'] as $key => $choice) {
					$ret.="<option value=\"$key\"";
					if ($this->value == $key) $ret.=" SELECTED";
					$ret.=">" . htmlspecialchars($choice) . "</option>";
				}
				$ret.= "</select>";
				return $ret;
			};
		}
	}
	
	function renderError() {
		if ($this->getError()) return "<div class=\"form_error\">{$this->getError()}</div>";
			else return "";
	}
	
	function __toString() {
		return $this->renderError() . $this->renderLabel() . $this->render();
	}
	
	
}


