<?php
/**
 * This file is part of the netsuitephp/netsuite-php library
 * AND originally from the NetSuite PHP Toolkit.
 *
 * New content:
 *
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

class GeneralToken extends Record {
	/**
	 * Var \NetSuite\Classes\RecordRef
	 */
	public $entity;
	/**
	 * Var string
	 */
	public $mask;
	/**
	 * Var \NetSuite\Classes\GeneralTokenSupportedOperationsListList
	 */
	public $supportedOperationsList;
	/**
	 * Var \NetSuite\Classes\RecordRef
	 */
	public $paymentMethod;
	/**
	 * Var string
	 */
	public $memo;
	/**
	 * Var \NetSuite\Classes\PaymentInstrumentState
	 */
	public $state;
	/**
	 * Var boolean
	 */
	public $isInactive;
	/**
	 * Var boolean
	 */
	public $preserveOnFile;
	/**
	 * Var boolean
	 */
	public $isDefault;
	/**
	 * Var string
	 */
	public $token;
	/**
	 * Var string
	 */
	public $tokenExpirationDate;
	/**
	 * Var \NetSuite\Classes\TokenFamily
	 */
	public $tokenFamily;
	/**
	 * Var string
	 */
	public $tokenNamespace;
	/**
	 * Var string
	 */
	public $internalId;
	/**
	 * Var string
	 */
	public $externalId;
	public static $paramtypesmap = array(
		'entity' => 'RecordRef',
		'mask' => 'string',
		'supportedOperationsList' => 'GeneralTokenSupportedOperationsListList',
		'paymentMethod' => 'RecordRef',
		'memo' => 'string',
		'state' => 'PaymentInstrumentState',
		'isInactive' => 'boolean',
		'preserveOnFile' => 'boolean',
		'isDefault' => 'boolean',
		'token' => 'string',
		'tokenExpirationDate' => 'dateTime',
		'tokenFamily' => 'TokenFamily',
		'tokenNamespace' => 'string',
		'internalId' => 'string',
		'externalId' => 'string',
	);
}
