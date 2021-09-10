<?php
/**
 * This file is part of the netsuitephp/netsuite-php library
 * AND originally from the NetSuite PHP Toolkit.
 *
 * New content:
 * Package    ryanwinchester/netsuite-php
 * Copyright  Copyright (c) Ryan Winchester
 * License    http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * Link       https://github.com/netsuitephp/netsuite-php
 *
 * Original content:
 * Copyright  Copyright (c) NetSuite Inc.
 * License    https://raw.githubusercontent.com/netsuitephp/netsuite-php/master/original/NetSuite%20Application%20Developer%20License%20Agreement.txt
 * Link       http://www.netsuite.com/portal/developers/resources/suitetalk-sample-applications.shtml
 */

namespace NetSuite\Classes;

class DepositOther {
	/**
	 * Var \NetSuite\Classes\RecordRef
	 */
	public $entity;
	/**
	 * Var float
	 */
	public $amount;
	/**
	 * Var \NetSuite\Classes\RecordRef
	 */
	public $account;
	/**
	 * Var \NetSuite\Classes\RecordRef
	 */
	public $paymentMethod;
	/**
	 * Var string
	 */
	public $refNum;
	/**
	 * Var \NetSuite\Classes\RecordRef
	 */
	public $department;
	/**
	 * Var \NetSuite\Classes\RecordRef
	 */
	public $class;
	/**
	 * Var \NetSuite\Classes\RecordRef
	 */
	public $location;
	/**
	 * Var string
	 */
	public $memo;
	public static $paramtypesmap = array(
		'entity' => 'RecordRef',
		'amount' => 'float',
		'account' => 'RecordRef',
		'paymentMethod' => 'RecordRef',
		'refNum' => 'string',
		'department' => 'RecordRef',
		'class' => 'RecordRef',
		'location' => 'RecordRef',
		'memo' => 'string',
	);
}
