<?php

/**
 * http://stackoverflow.com/questions/737385/easiest-form-validation-library-for-php
 * 
 * Pork Formvalidator. validates fields by regexes and can sanatize them. Uses PHP filter_var built-in functions and extra regexes
 * @package pork
 */

/**
 * Pork.FormValidator
 * Validates arrays or properties by setting up simple arrays
 *
 * @package pork
 * @author SchizoDuckie
 * @copyright SchizoDuckie 2009
 * @version 1.0
 * @access public
 */
class FormValidator {

	public static $regexes = array(
		'paiva'			 => "^[0-9]{1,2}[-/.][0-9]{1,2}[-/.][0-9]{4}",
		'summa'			 => "^[-]?[0-9]+\$",
		'numero'		 => "^[-]?[0-9,]+\$",
		'kirjain_numero' => "^[0-9a-zA-Z ,.-_\\s\?\!]+\$",
		'ei_tyhja'		 => "[a-z0-9A-Z]+",
		'sanoja'		 => "^[A-Za-z]+[A-Za-z \\s]*\$",
		'puh'			 => "^[0-9]{10,11}\$",
		'postino'		 => "^[1-9][0-9]{3}[a-zA-Z]{2}\$",
		'hinta'			 => "^[0-9.,]*(([.,][-])|([.,][0-9]{2}))?\$",
		'2digitopt'		 => "^\d+(\,\d{2})?\$",
		'2digitforce'	 => "^\d+\,\d\d\$",
		'mitavaan'		 => "^[\d\D]{1,}\$"
	);
	public $regex_meanings = array(
		'paiva'			 => "Pitää olla päivä",
		'summa'			 => "Pitää olla summa",
		'numero'		 => "Pitää olla numero",
		'kirjain_numero' => "Pitää olla kirjain tai numero",
		'ei_tyhja'		 => "Ei saa olla tyhjä",
		'sanoja'		 => "Vain sanoja",
		'puh'			 => "Pitää olla puhelinnumero",
		'postino'		 => "Pitää olla postinumero",
		'hinta'			 => "Pitää olla hinta",
		'2digitopt'		 => "Optionaalinen 2 desimaalia",
		'2digitforce'	 => "Pitää olla 2 desimaali",
		'mitavaan'		 => "Voi olla mitä vain"
	);
	private $validations, $sanatations, $mandatories, $errors, $corrects, $fields;

	public function __construct($validations = array(), $mandatories = array(), $sanatations = array(
)) {
		$this->validations = $validations;
		$this->sanatations = $sanatations;
		$this->mandatories = $mandatories;
		$this->errors = array();
		$this->corrects = array();
	}

	/**
	 * Validates an array of items (if needed) and returns true or false
	 *
	 */
	public function validate($items) {
		$this->fields = $items;
		$havefailures = false;
		foreach ($items as $key => $val) {
			if (!is_array($val)) {
				if ((strlen($val) == 0 || array_search($key, $this->validations) === false) && array_search($key, $this->mandatories) === false) {
					$this->corrects[] = $key;
					continue;
				}
				$result = self::validateItem($val, $this->validations[$key]);
				if ($result === false) {
					$havefailures = true;
					$this->addError($key, $this->validations[$key]);
				}
				else {
					$this->corrects[] = $key;
				}
			}
			else {
				$havefailures = $this->validate($val);
			}
		}

		return !$havefailures;
	}

	/**
	 *
	 * 	Adds unvalidated class to thos elements that are not validated. Removes them from classes that are.
	 */
	public function getScript() {
		if (!empty($this->errors)) {
			$output = "alert('Seuraavissa kentissä oli virheitä.";
			foreach ($this->errors as $key => $val) {
				$output .= '\n'.$key.': '.$this->regex_meanings[$val];
			}
			$output .= "');";
			$output = "<script type='text/javascript'>{$output} </script>";
		}

		return $output;
	}

	public function getErrors() {
		return $this->errors;
	}

	/**
	 *
	 * Sanatizes an array of items according to the $this->sanatations
	 * sanatations will be standard of type string, but can also be specified.
	 * For ease of use, this syntax is accepted:
	 * $sanatations = array('fieldname', 'otherfieldname'=>'float');
	 */
	public function sanatize($items) {
		foreach ($items as $key => $val) {
			if (array_search($key, $this->sanatations) === false && !array_key_exists($key, $this->sanatations))
				continue;
			$items[$key] = self::sanatizeItem($val, $this->validations[$key]);
		}
		return $items;
	}

	/**
	 *
	 * Adds an error to the errors array.
	 */
	private function addError($field, $type = 'string') {
		$this->errors[$field] = $type;
	}

	/**
	 *
	 * Sanatize a single var according to $type.
	 * Allows for static calling to allow simple sanatization
	 */
	public static function sanatizeItem($var, $type) {
		$flags = NULL;
		switch ($type) {
			case 'url':
				$filter = FILTER_SANITIZE_URL;
				break;
			case 'int':
				$filter = FILTER_SANITIZE_NUMBER_INT;
				break;
			case 'float':
				$filter = FILTER_SANITIZE_NUMBER_FLOAT;
				$flags = FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND;
				break;
			case 'email':
				$var = substr($var, 0, 254);
				$filter = FILTER_SANITIZE_EMAIL;
				break;
			case 'string':
			default:
				$filter = FILTER_SANITIZE_STRING;
				$flags = FILTER_FLAG_NO_ENCODE_QUOTES;
				break;
		}
		$output = filter_var($var, $filter, $flags);
		return $output;
	}

	/**
	 *
	 * Validates a single var according to $type.
	 * Allows for static calling to allow simple validation.
	 *
	 */
	public static function validateItem($var, $type) {
		if (array_key_exists($type, self::$regexes)) {
			$returnval = filter_var($var, FILTER_VALIDATE_REGEXP, array("options" => array(
							"regexp" => '!'.self::$regexes[$type].'!i'))) !== false;
			if ($returnval) {
				$returnval = self::validateContent($var, $type);
			}
			
			return($returnval);
		}
		$filter = false;
		switch ($type) {
			case 'email':
				$var = substr($var, 0, 254);
				$filter = FILTER_VALIDATE_EMAIL;
				break;
			case 'int':
				$filter = FILTER_VALIDATE_INT;
				break;
			case 'boolean':
				$filter = FILTER_VALIDATE_BOOLEAN;
				break;
			case 'ip':
				$filter = FILTER_VALIDATE_IP;
				break;
			case 'url':
				$filter = FILTER_VALIDATE_URL;
				break;
		}
		return ($filter === false) ? false : filter_var($var, $filter) !== false ? true : false;
	}

	public static function validateContent($var, $type) {

		switch ($type) {
			case 'paiva':
				//for now this function can only validate dates in dd.-/mm.-/YYYY format
				//regexp allows user to give days in mm.-/dd.-/YYYY format
				if ($date_array = explode('.', $var)) {
					$is_ok = checkdate($date_array[1], $date_array[0], $date_array[2]);
				}
				elseif ($date_array = explode('-', $var)) {
					$is_ok = checkdate($date_array[1], $date_array[0], $date_array[2]);
				}
				else {
					$date_array = explode('/', $var);
					$is_ok = checkdate($date_array[1], $date_array[0], $date_array[2]);
				}
				break;
		}

		return $is_ok;
	}
}
