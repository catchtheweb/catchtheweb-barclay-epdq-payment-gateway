<?php

declare( strict_types=1 );
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RapidDive_WC_Gateway_Barclay
 * @property string showLogo
 */
class RapidDive_WC_Gateway_Barclay extends WC_Payment_Gateway {
	public const TEST_URL = 'https://mdepayments.epdq.co.uk/ncol/test/orderstandard.asp';
	public const LIVE_URL = 'https://payments.epdq.co.uk/ncol/prod/orderstandard.asp';
	public const BARCLAY_PAYMENT_SEPARATOR = ';';
	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;
	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;
	/**
	 * @var string
	 */
	protected $access_key;

	/**
	 * @var string
	 */
	private $status;
	/**
	 * @var string
	 */
	private $sha_in;
	/**
	 * @var string
	 */
	private $sha_out;
	/**
	 * @var int|string
	 */
	private $sha_method;
	/**
	 * @var bool
	 */
	private $debug;
	/**
	 * @var string
	 */
	private $error_notice;
	/**
	 * @var int
	 */
	private $cat_url;
	/**
	 * @var string
	 */
	private $aavscheck;
	/**
	 * @var string
	 */
	private $cvccheck;
	/**
	 * @var string
	 */
	private $payment_method;
	/**
	 * @var string
	 */
	private $brand_cards;
	/**
	 * @var string
	 */
	private $secure_3d;
	/**
	 * @var string
	 */
	private $method_list;
	/**
	 * @var string
	 */
	private $com_plus;
	/**
	 * @var string
	 */
	private $param_plus;
	/**
	 * @var string
	 */
	private $param_var;
	/**
	 * @var string
	 */
	private $api_user_id;
	/**
	 * @var string
	 */
	private $operation;
	/**
	 * @var string
	 */
	private $api_user_pswd;
	/**
	 * @var string
	 */
	private $notify_url;
	/**
	 * @var string
	 */
	private $pp_format;
	/**
	 * @var string
	 */
	private $pp_title;
	/**
	 * @var string
	 */
	private $BGCOLOR;
	/**
	 * @var string
	 */
	private $TXTCOLOR;
	/**
	 * @var string
	 */
	private $TBLBGCOLOR;
	/**
	 * @var string
	 */
	private $TBLTXTCOLOR;
	/**
	 * @var string
	 */
	private $BUTTONBGCOLOR;
	/**
	 * @var string
	 */
	private $BUTTONTXTCOLOR;
	/**
	 * @var string
	 */
	private $FONTTYPE;
	/**
	 * @var string
	 */
	private $LOGO;

	/**
	 * RapidDive_WC_Gateway_Barclay constructor.
	 */
	public function __construct() {
		$this->id                 = 'barclay';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Proceed to Barclaycard ePDQ', 'woocommerce' );
		$this->method_title       = __( 'Barclay ePDQ', 'woocommerce' );
		$this->method_description = __(
			'Barclay ePDQ redirects customers to Barclaycard to enter their payment information.',
			'woocommerce'
		);
		$this->supports           = [
			'products',
//            'refunds',
		];
		$this->icon               = plugin_dir_url( __FILE__ ) . '../assets/barclaycard_logo.png';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->title       = ( $this->title !== null && $this->title !== '' ) ? $this->title : __(
			'Barclay ePDQ',
			'woocommerce'
		);
		$this->description = $this->get_option( 'description' );
		$this->access_key  = $this->get_option( 'access_key' );
		$this->debug       = 'yes' === $this->get_option( 'debug', 'no' );
		self::$log_enabled = $this->debug;
		$this->showLogo    = $this->get_option( 'show_logo' );

		$this->status       = $this->get_option( 'status' );
		$this->error_notice = $this->get_option( 'error_notice' );
		$this->sha_in       = $this->get_option( 'sha_in' );
		$this->sha_out      = $this->get_option( 'sha_out' );
		$this->sha_method   = $this->get_option( 'sha_method' );
		$this->sha_method   = ( $this->sha_method != '' ) ? $this->sha_method : 0;

		$this->cat_url = wc_get_page_id( 'shop' );

		$this->aavscheck = $this->get_option( 'aavcheck' );
		$this->cvccheck  = $this->get_option( 'cvccheck' );

		$this->payment_method = is_array( $this->get_option( 'payment_method' ) ) ? implode(
			self::BARCLAY_PAYMENT_SEPARATOR,
			$this->get_option( 'payment_method' )
		) : '';
		$this->brand_cards    = is_array( $this->get_option( 'brand_cards' ) ) ? implode(
			self::BARCLAY_PAYMENT_SEPARATOR,
			$this->get_option( 'brand_cards' )
		) : '';
		$this->secure_3d      = $this->get_option( 'secure_3d' );
		$this->method_list    = is_array( $this->get_option( 'method_list' ) ) ? join(
			self::BARCLAY_PAYMENT_SEPARATOR,
			$this->get_option( 'method_list' )
		) : '';

		$this->com_plus   = $this->get_option( 'com_plus' );
		$this->param_plus = $this->get_option( 'param_plus' );

		$this->param_var = $this->get_option( 'param_var' );

		//Recommended for Direct Payments and Advanced Payments
		$this->operation     = $this->get_option( 'operation' );
		$this->api_user_id   = $this->get_option( 'api_user_id' );
		$this->api_user_pswd = $this->get_option( 'api_user_pswd' );

		$this->notify_url = WC()->api_request_url( 'RapidDive_WC_Gateway_Barclay' );

		// templating
		$this->pp_format      = $this->get_option( 'pp_format ' );
		$this->pp_title       = $this->get_option( 'TITLE' );
		$this->BGCOLOR        = $this->get_option( 'BGCOLOR' );
		$this->TXTCOLOR       = $this->get_option( 'TXTCOLOR' );
		$this->TBLBGCOLOR     = $this->get_option( 'TBLBGCOLOR' );
		$this->TBLTXTCOLOR    = $this->get_option( 'TBLTXTCOLOR' );
		$this->BUTTONBGCOLOR  = $this->get_option( 'BUTTONBGCOLOR' );
		$this->BUTTONTXTCOLOR = $this->get_option( 'BUTTONTXTCOLOR' );
		$this->FONTTYPE       = $this->get_option( 'FONTTYPE' );
		$this->LOGO           = $this->get_option( 'LOGO' );

		$this->add_payment_hooks();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include __DIR__ . '/includes/settings-barclay.php';
	}

	/**
	 * Payment Hooks
	 */
	private function add_payment_hooks() {
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_receipt_barclay', [ $this, 'receipt_page' ] );
		add_action( 'woocommerce_api_rapiddive_wc_gateway_barclay', [ $this, 'check_barclay_response' ] );
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		];
	}

	/**
	 * @param $order
	 */
	public function receipt_page( $order ) {
		echo '<p>' . __(
				'Thank you for your order, please click the button below to pay with Barclay ePDQ.',
				'woocommerce'
			) . '</p>';
		echo $this->generate_barclay_form( $order );
	}

	/**
	 * @param $order
	 *
	 * @return string
	 */
	public function generate_barclay_form( $order ) {
		$order        = wc_get_order( $order );
		$barclay_args = $this->get_barclay_fields( $order );

		$shasign     = '';
		$shasign_arg = [];

		ksort( $barclay_args );

		foreach ( $barclay_args as $key => $value ) {
			if ( $value == '' ) {
				continue;
			}
			$shasign_arg[] = $key . '=' . $value;
		}

		$shasign = hash( $this->getShaMethod(), implode( $this->sha_in, $shasign_arg ) . $this->sha_in );

		$barclay_html_args = [];
		foreach ( $barclay_args as $key => $value ) {
			if ( $value == '' ) {
				continue;
			}
			$barclay_html_args[] = '<input type="hidden" name="' . $key . '" value="' . $value . '"/>';
		}

		if ( isset( $this->status ) && ( $this->status == 'test' || $this->status == 'live' ) ) {
			$url = $this->status == 'test' ? self::TEST_URL : self::LIVE_URL;

			return
				'<form action="' . esc_url( $url ) . '" method="post" id="epdq_payment_form">' .
				implode( '', $barclay_html_args ) .
				'<input type="hidden" name="SHASIGN" value="' . $shasign . '"/>' .
				'<input type="submit" class="button alt" id="submit_epdq_payment_form" value="' . __(
					'Pay via Barclay ePDQ',
					'woocommerce'
				) . '" />' .
				'<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __(
					'Cancel order &amp; restore cart',
					'woocommerce'
				) . '</a>' .
				'</form>';
		} else {
			return '<p class="error">' . $this->error_notice . '</p>';
		}
	}

	/**
	 * @param $order_id
	 *
	 * @return array
	 */
	public function get_barclay_fields( $order_id ) {
		$order = wc_get_order( $order_id );

		$barclay_args = [
			'PSPID'         => $this->access_key,
			'ORDERID'       => $order->id,
			'AMOUNT'        => $order->order_total * 100,
			'CURRENCY'      => get_woocommerce_currency(),
			'LANGUAGE'      => get_bloginfo( 'language' ),
			'CN'            => $order->billing_first_name . ' ' . $order->billing_last_name,
			'EMAIL'         => $order->billing_email,
			'OWNERZIP'      => $order->billing_postcode,
			'OWNERADDRESS'  => $order->billing_address_1,
			'OWNERADDRESS2' => $order->billing_address_2,
			'OWNERCTY'      => $order->billing_country,
			'OWNERTOWN'     => $order->billing_city,
			'OWNERTELNO'    => $order->billing_phone,

			'ACCEPTURL'    => $this->notify_url,
			'DECLINEURL'   => $this->notify_url,
			'EXCEPTIONURL' => $this->notify_url,
			// 'CANCELURL' => $this->notify_url,
			'CANCELURL'    => esc_url_raw( $order->get_cancel_order_url_raw() ),
			'BACKURL'      => '',
			'HOMEURL'      => '',
			'CATALOGURL'   => get_permalink( $this->cat_url ),

			//payment method
			'PM'           => $this->payment_method,
			'BRAND'        => $this->brand_cards,
			'WIN3DS'       => $this->secure_3d,
			'PMLISTTYPE'   => $this->method_list,

			//Redirection on payment result
			'COMPLUS'      => $this->com_plus,
			'PARAMPLUS'    => $this->param_plus,

			//POST payment url
			'PARAMVAR'     => $this->param_var,

			//Recommended for direct payments
			'OPERATION'    => $this->operation,
			'USERID'       => $this->api_user_id,
			'PASWD'        => $this->api_user_pswd,
		];

		if ( $this->pp_format == 'yes' ) {
			$barclay_args['TITLE']          = $this->pp_title;
			$barclay_args['BGCOLOR']        = $this->BGCOLOR;
			$barclay_args['TXTCOLOR']       = $this->TXTCOLOR;
			$barclay_args['TBLBGCOLOR']     = $this->TBLBGCOLOR;
			$barclay_args['TBLTXTCOLOR']    = $this->TBLTXTCOLOR;
			$barclay_args['BUTTONBGCOLOR']  = $this->BUTTONBGCOLOR;
			$barclay_args['BUTTONTXTCOLOR'] = $this->BUTTONTXTCOLOR;
			$barclay_args['FONTTYPE']       = $this->FONTTYPE;
			$barclay_args['LOGO']           = $this->LOGO;
		}

		return $barclay_args;
	}

	/**
	 * @return string
	 */
	private function getShaMethod() {
		switch ( $this->sha_method ) {
			case 1:
				$shaMethod = 'sha256';
				break;
			case 2:
				$shaMethod = 'sha512';
				break;
			default:
				$shaMethod = 'sha1';
		}

		return $shaMethod;
	}

	/**
	 *
	 */
	public function payment_fields() {
		if ( $this->showLogo === 'yes' ) {
			echo '<img src="' . plugin_dir_url( __FILE__ ) . '../assets/epdq.gif"/>';
		}
		parent::payment_fields();
	}

	/**
	 *
	 */
	public function check_barclay_response() {
		ob_clean();
		header( 'HTTP/1.1 200 OK' );

		$datacheck  = [];
		$datacheck1 = [];

		foreach ( $_REQUEST as $key => $value ) {
			if ( $value == "" ) {
				continue;
			}
			$datacheck[ $key ]                = $value;
			$datacheck1[ strtoupper( $key ) ] = strtoupper( $value );
		}

		$verify = $this->checkShaOut( $datacheck );

		if ( $verify ) {
			$this->transaction_successfull( $datacheck1 );
		} else {
			wp_die( 'Transaction is unsuccessfull!' );
		}
	}

	/**
	 * @param array $dataCheck
	 *
	 * @return bool
	 */
	protected function checkShaOut( array $dataCheck ) {
		$__result = false;
		$shaout   = $this->sha_out;

		$origsig = $dataCheck['SHASIGN'];

		unset( $dataCheck['SHASIGN'], $dataCheck['wc-api'] );

		uksort( $dataCheck, 'strcasecmp' );

		$shasig = null;
		foreach ( $dataCheck as $key => $value ) {
			$shasig .= trim( strtoupper( $key ) ) . '=' . utf8_encode( trim( $value ) ) . $shaout;
		}

		$shasig = strtoupper( hash( $this->getShaMethod(), $shasig ) );

		if ( $shasig == $origsig ) {
			$__result = true;
		}

		return $__result;
	}


	/**
	 * @param $args
	 */
	public function transaction_successfull( $args ) {
		global $woocommerce;

		extract( $args );
		$order = new WC_Order( $ORDERID );

		$accepted = [ 4, 5, 9, 41, 51, 91 ];
		$status   = $STATUS;
		$ncerror  = $NCERROR;

		$dienote = '<p>Transection result is uncertain.<p>';
		$dienote .= '<p>Status Code: ' . $STATUS . ' - ' . $this->get_barclay_status_code( $status ) . '';
		$dienote .= '<br>Error Code: ' . $NCERROR . ' - ' . $this->get_barclay_ncerror( $ncerror ) . '</p>';
		$died    = '';
		$died    .= $dienote;
		$died    .= '<p>Your order is cancelled and your cart is emptied.';
		$died    .= '<br>Go to your <a href="' . get_permalink(
				get_option( 'woocommerce_myaccount_page_id' )
			) . '">account</a> to process your order again or ';
		$died    .= 'go to <a href="' . home_url() . '">homepage</a></p>';

		if ( in_array( $STATUS, $accepted ) ) {
			if ( ! empty( $args['ORDERID'] ) ) {
				$note = 'Order ID: ' . $ORDERID . '.<br>';
			} //	order id
			if ( ! empty( $args['AMOUNT'] ) ) {
				$note .= 'Amount: ' . $AMOUNT . '.<br>';
			} //	amount
			if ( ! empty( $args['CURRENCY'] ) ) {
				$note .= 'Order currency: ' . $CURRENCY . '.<br>';
			} //	order currency
			if ( ! empty( $args['PM'] ) ) {
				$note .= 'Payment Method: ' . $PM . '.<br>';
			} //	payment method
			if ( ! empty( $args['ACCEPTANCE'] ) ) {
				$note .= 'Acceptance code returned by acquirer: ' . $ACCEPTANCE . '.<br>';
			}    //	acceptance
			if ( ! empty( $args['STATUS'] ) ) {
				$note .= 'Transaction status : ' . $STATUS . '.<br>';
			} //	status code
			if ( ! empty( $args['CARDNO'] ) ) {
				$note .= 'Masked card number : ' . $CARDNO . '.<br>';
			} //	catd no
			if ( ! empty( $args['PAYID'] ) ) {
				$note .= 'Payment reference in EPDQ system: ' . $PAYID . '.<br>';
			} //	pay id
			if ( ! empty( $args['NCERROR'] ) ) {
				$note .= 'Error Code: ' . $NCERROR . '.<br>';
			} //	ncerror
			if ( ! empty( $args['BRAND'] ) ) {
				$note .= 'Card brand (EPDQ system derives this from the card number) : ' . $BRAND . '.<br>';
			} //	brand
			if ( ! empty( $args['ED'] ) ) {
				$note .= 'Payer\'s card expiry date : ' . $ED . '.<br>';
			} //	expiry date
			if ( ! empty( $args['TRXDATE'] ) ) {
				$note .= 'Transaction Date: ' . $TRXDATE . '.<br>';
			} //	date
			if ( ! empty( $args['CN'] ) ) {
				$note .= 'Cardholder/customer name: ' . $CN . '.<br>';
			} //	payer's name
			if ( ! empty( $args['IP'] ) ) {
				$note .= 'Customer\'s IP: ' . $IP . '.<br>';
			} //	payer's ip


			if ( ! empty( $args['AAVADDRESS'] ) ) {
				$note .= 'AAV result for the address: ' . $AAVADDRESS . ' . <br>';
			} //	aav address
			if ( ! empty( $args['AAVCHECK'] ) ) {
				$note .= 'Result of the automatic address verification: ' . $AAVCHECK . ' . <br>';
			} //	aav check
			if ( ! empty( $args['AAVZIP'] ) ) {
				$note .= 'AAV result for the zip code: ' . $AAVZIP . ' . <br>';
			} // aav zip
			if ( ! empty( $args['BIN'] ) ) {
				$note .= 'First 6 digits of credit card number: ' . $BIN . ' . <br>';
			} // bin
			if ( ! empty( $args['CCCTY'] ) ) {
				$note .= 'Country where the card was issued: ' . $CCCTY . ' . <br>';
			}
			if ( ! empty( $args['COMPLUS'] ) ) {
				$note .= 'Custom value passed: ' . $COMPLUS . ' . <br>';
			}

			if ( ! empty( $args['CVCCHECK'] ) ) {
				$note .= 'Result of the card verification code check: ' . $CVCCHECK . ' . <br>';
			}
			if ( ! empty( $args['ECI'] ) ) {
				$note .= 'Electronic Commerce Indicator: ' . $ECI . ' . <br>';
			}
			if ( ! empty( $args['FXAMOUNT'] ) ) {
				$note .= 'FXAMOUNT: ' . $FXAMOUNT . ' . <br>';
			}
			if ( ! empty( $args['FXCURRENCY'] ) ) {
				$note .= 'FXCURRENCY: ' . $FXCURRENCY . ' . <br>';
			}
			if ( ! empty( $args['IPCTY'] ) ) {
				$note .= 'Originating country of the IP address: ' . $IPCTY . ' . <br>';
			}
			if ( ! empty( $args['SUBBRAND'] ) ) {
				$note .= 'SUBBRAND: ' . $SUBBRAND . ' . <br>';
			}
			if ( ! empty( $args['VC'] ) ) {
				$note .= 'Virtual Card type: ' . $SUBBRAND . ' . <br>';
			}


			$woocommerce->cart->empty_cart();

			if ( in_array( $STATUS, [ 4, 5, 9 ] ) ) {
				$note = 'Barclay ePDQ transaction is confirmed.<br>';
				$note .= $note;
				$order->add_order_note( $note );
				$order->payment_complete();
			}

			if ( in_array( $STATUS, [ 41, 51, 91 ] ) ) {
				$note = 'Barclay ePDQ transaction is awaiting for confirmation.<br>';
				$note .= $note;
				$order->update_status( 'on-hold', $note );
			}
		} elseif ( $STATUS == 2 || $STATUS == 93 ) {
			$dienote .= '<br>Order is failed.';
			$order->update_status( 'failed', $dienote );
			$woocommerce->cart->empty_cart();
		} elseif ( $STATUS == 52 || $STATUS == 92 ) {
			$dienote .= '<br>Order is failed.';
			$order->update_status( 'failed', $dienote );
			$woocommerce->cart->empty_cart();
		} elseif ( $STATUS == 1 ) {
			$dienote .= '<br>Order is cancelled.';
			$order->update_status( 'cancelled', $dienote );
			$woocommerce->cart->empty_cart();
		} else {
			$dienote .= '<br>Order is failed.';
			$order->update_status( 'failed', $dienote );
			$woocommerce->cart->empty_cart();
		}
		self::log( $dienote );
		wp_redirect( $this->get_return_url( $order ) );
		exit;
	}

	/**
	 * @param int|string $code
	 *
	 * @return string
	 */
	public function get_barclay_status_code( $code ) {
		return RapidDive_Epdq_SuccessCodes::getMessage( $code );
	}

	/**
	 * @param int|string $code
	 *
	 * @return string
	 */
	public function get_barclay_ncerror( $code ): ?string {
		return RapidDive_Epdq_ErrorCodes::getErrorMessage( $code );
	}

	/**
	 * @param $message
	 * @param string $level
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( self::$log === null ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, [ 'source' => 'barclay' ] );
		}
	}

	/**
	 * Get a link to the transaction on the 3rd party gateway site (if applicable).
	 *
	 * @param WC_Order $order the order object.
	 *
	 * @return string transaction URL, or empty string.
	 */
	public function get_transaction_url( $order ): string {
		if ( 'live' == $this->status ) {
			$this->view_transaction_url = self::LIVE_URL;
		} else {
			$this->view_transaction_url = self::TEST_URL;
		}

		return parent::get_transaction_url( $order );
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function validate_text_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;

		return wp_kses_post( trim( stripslashes( $value ) ) );
	}
}
