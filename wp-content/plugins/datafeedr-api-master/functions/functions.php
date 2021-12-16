<?php

function dfrapi_get_api_usage_percentage() {
	$account = get_option( 'dfrapi_account' );
	if ( $account ) {
		if ( $account['max_requests'] > 0 ) {
			$percentage = floor ( ( intval( $account['request_count'] ) / intval( $account['max_requests'] ) * 100 ) );
		} else {
			$percentage = 0;
		}
		return $percentage;
	}
	return false;
}

add_action( 'init', 'dfrapi_email_user_about_usage' );
function dfrapi_email_user_about_usage() {
	
	$percentage = dfrapi_get_api_usage_percentage();
	$status = get_option( 'dfrapi_account', array() );
	
	$request_count = ( isset( $status['request_count'] ) ) ? number_format( $status['request_count'] ) : 0;
	$remaining_requests = ( isset( $status['max_requests'] ) ) ? number_format( $status['max_requests'] - $request_count ) : 0;
	
	$reset_date = '';
	if ( isset( $status['bill_day'] ) ) {
		$today = date('j');
		$num_days = date('t');
		if ( $status['bill_day'] > $num_days ) {
			$bill_day = $num_days;
		} else {
			$bill_day = $status['bill_day'];
		}
		if ( $bill_day == 0 ) {
			$reset_date .= '<em>' . __( 'Never', DFRAPI_DOMAIN ) . '</em>';
		} elseif ( $today >= $bill_day ) {
			$reset_date .= date('F', strtotime('+1 month')) . ' ' . $bill_day . ', ' . date('Y', strtotime('+1 month'));
		} else {
			$reset_date .= date('F') . ' ' . $bill_day . ', ' . date('Y');
		}
	}	
	
	$default = array(
		'90_percent'  => '', 
		'100_percent' => '' 
	);
	
	// Don't do anything if less than 90%.
	if ( $percentage < 90 ) {
		update_option( 'dfrapi_usage_notification_tracker', $default );
		return; 
	}
	
	$tracker = get_option( 'dfrapi_usage_notification_tracker', $default );
	
	$params 			= array();
	$params['to'] 		= get_bloginfo( 'admin_email' );
	$params['message']  = "<p>" . __( "This is an automated message generated by: ", DFRAPI_DOMAIN ) . get_bloginfo( 'wpurl' ) . "</p>";
		
	if ( $percentage >= 100 && empty( $tracker['100_percent'] ) ) {
		
		$params['subject']  = get_bloginfo( 'name' ) . __( ': Datafeedr API Usage (Critical)', DFRAPI_DOMAIN );
		
		$params['message'] .= "<p>" . __( "You have used <strong>100%</strong> of your allocated Datafeedr API requests for this period. <u>You are no longer able to query the Datafeedr API to get product information.</u>", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p><strong>" . __( "What to do next?", DFRAPI_DOMAIN ) . "</strong></p>";
		$params['message'] .= "<p>" . __( "We strongly recommend that you upgrade to prevent your product information from becoming outdated.", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p><a href=\"" . dfrapi_user_pages( 'change' ) . "?utm_source=email&utm_medium=link&utm_campaign=upgrade100percentnotice\"><strong>" . __( "UPGRADE NOW", DFRAPI_DOMAIN ) . "</strong></a></p>";
		$params['message'] .= "<p>" . __( "Upgrading only takes a minute. You will have <strong>instant access</strong> to more API requests. Any remaining credit for your current plan will be applied to your new plan.", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p>" . __( "You are under no obligation to upgrade. You may continue using your current plan for as long as you would like.", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p>" . __( "If you have any questions about your account, please ", DFRAPI_DOMAIN );
		$params['message'] .= "<a href=\"" . DFRAPI_EMAIL_US_URL . "?utm_source=email&utm_medium=link&utm_campaign=upgrade100percentnotice\">" . __( "contact us", DFRAPI_DOMAIN ) . "</a>.</p>";
		$params['message'] .= "<p>" . __( "Thanks,<br />Eric &amp; Stefan<br />The Datafeedr Team", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p>";
		$params['message'] .= "<a href=\"" . admin_url( 'admin.php?page=dfrapi_account' ) . "\">" . __( "Account Information", DFRAPI_DOMAIN ) . "</a> | ";
		$params['message'] .= "<a href=\"" . dfrapi_user_pages( 'change' ) . "?utm_source=email&utm_medium=link&utm_campaign=upgrade100percentnotice\">" . __( "Upgrade Account", DFRAPI_DOMAIN ) . "</a>";
		$params['message'] .= "</p>";
		
		$tracker['100_percent'] = 1;
		update_option( 'dfrapi_usage_notification_tracker', $tracker );
		
		add_filter( 'wp_mail_content_type', 'dfrapi_set_html_content_type' );
		wp_mail( $params['to'], $params['subject'], $params['message'] );
		remove_filter( 'wp_mail_content_type', 'dfrapi_set_html_content_type' );

		
	} elseif ( $percentage >= 90 && $percentage < 100 && empty( $tracker['90_percent'] ) ) {

		$params['subject']  = get_bloginfo( 'name' ) . __( ': Datafeedr API Usage (Warning)', DFRAPI_DOMAIN );	
		$params['message'] .= "<p>" . __( "You have used <strong>90%</strong> of your allocated Datafeedr API requests for this period.", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p><strong>" . __( "API Usage", DFRAPI_DOMAIN ) . "</strong></p>";
		$params['message'] .= "<ul>";
		$params['message'] .= "<li>" . __( "API requests used: ", DFRAPI_DOMAIN ) . $request_count . "</li>";
		$params['message'] .= "<li>" . __( "API requests remaining: ", DFRAPI_DOMAIN ) . $remaining_requests . "</li>";
		$params['message'] .= "<li>" . __( "API requests will reset on: ", DFRAPI_DOMAIN ) . $reset_date . "</li>";
		$params['message'] .= "</ul>";
		$params['message'] .= "<p><strong>" . __( "What to do next?", DFRAPI_DOMAIN ) . "</strong></p>";
		$params['message'] .= "<p>" . __( "We recommend that you upgrade to prevent your product information from becoming outdated.", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p><a href=\"" . dfrapi_user_pages( 'change' ) . "?utm_source=email&utm_medium=link&utm_campaign=upgrade90percentnotice\"><strong>" . __( "UPGRADE NOW", DFRAPI_DOMAIN ) . "</strong></a></p>";
		$params['message'] .= "<p>" . __( "Upgrading only takes a minute. You will have <strong>instant access</strong> to more API requests. Any remaining credit for your current plan will be applied to your new plan.", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p>" . __( "You are under no obligation to upgrade. You may continue using your current plan for as long as you would like.", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p>" . __( "If you have any questions about your account, please ", DFRAPI_DOMAIN );
		$params['message'] .= "<a href=\"" . DFRAPI_EMAIL_US_URL . "?utm_source=email&utm_medium=link&utm_campaign=upgrade90percentnotice\">" . __( "contact us", DFRAPI_DOMAIN ) . "</a>.</p>";
		$params['message'] .= "<p>" . __( "Thanks,<br />Eric &amp; Stefan<br />The Datafeedr Team", DFRAPI_DOMAIN ) . "</p>";
		$params['message'] .= "<p>";
		$params['message'] .= "<a href=\"" . admin_url( 'admin.php?page=dfrapi_account' ) . "\">" . __( "Account Information", DFRAPI_DOMAIN ) . "</a> | ";
		$params['message'] .= "<a href=\"" . dfrapi_user_pages( 'change' ) . "?utm_source=email&utm_medium=link&utm_campaign=upgrade90percentnotice\">" . __( "Upgrade Account", DFRAPI_DOMAIN ) . "</a>";
		$params['message'] .= "</p>";
				
		$tracker['90_percent'] = 1;
		update_option( 'dfrapi_usage_notification_tracker', $tracker );
		
		add_filter( 'wp_mail_content_type', 'dfrapi_set_html_content_type' );
		wp_mail( $params['to'], $params['subject'], $params['message'] );
		remove_filter( 'wp_mail_content_type', 'dfrapi_set_html_content_type' );
			
	}
	
	return;
}

function dfrapi_set_html_content_type() {
	return 'text/html';
}

/**
 * Modify affiliate ID if product is a Zanox product. 
 * Replaces $affiliate_id with "zmid".
 */
add_filter( 'dfrapi_affiliate_id', 'dfrapi_get_zanox_zmid', 10, 3 );
function dfrapi_get_zanox_zmid( $affiliate_id, $product, $networks ) {
	if ( isset( $product['source'] ) && preg_match( "/\bZanox\b/", $product['source'] ) ) {
		$zanox = dfrapi_api_get_zanox_zmid( $product['merchant_id'], $affiliate_id );
		$affiliate_id = ( !isset( $zanox[0]['zmid']) ) ? '___MISSING___' : $zanox[0]['zmid'];
	}
	return $affiliate_id;
}

function dfrapi_get_zanox_keys() {
	
	$configuration = (array) get_option( 'dfrapi_configuration' );	
	
	$zanox_connection_key = false;
	$zanox_secret_key = false;
	
	if ( isset( $configuration['zanox_connection_key'] ) && ( $configuration['zanox_connection_key'] != '' ) ) {
		$zanox_connection_key = $configuration['zanox_connection_key'];
	}
	
	if ( isset( $configuration['zanox_secret_key'] ) && ( $configuration['zanox_secret_key'] != '' ) ) {
		$zanox_secret_key = $configuration['zanox_secret_key'];
	}
	
	if ( $zanox_connection_key && $zanox_secret_key ) {
		return array( 
			'connection_key'=> $zanox_connection_key,
			'secret_key' 	=> $zanox_secret_key,
		);
	}
	
	return false;
}

/**
 * Returns a link to a user page on v4.datafeedr.com.
 */
function dfrapi_user_pages( $page ) {

	$pages = array(
		'edit' 		=> 'edit',
		'summary' 	=> 'subscription',
		'invoices' 	=> 'subscription/invoices',
		'billing' 	=> 'subscription/billing',
		'change' 	=> 'subscription/change',
		'cancel' 	=> 'subscription/cancel',
		'signup' 	=> 'subscription/signup',
	);

	$account = get_option( 'dfrapi_account', array() );
	
	if ( empty( $account ) ) { return false; }

	return DFRAPI_HOME_URL . '/user/' . $account['user_id'] . '/' . $pages[$page];
}

/**
 * Adds option name to transient whitelist. This is so we know 
 * all transient options that can be deleted when deleting the 
 * API cache on Tools page.
 */
function dfrapi_update_transient_whitelist( $option_name ) {
	$whitelist = get_option( 'dfrapi_transient_whitelist', array() );
	$whitelist[] = $option_name;
	update_option( 'dfrapi_transient_whitelist', array_unique( $whitelist ) );
}

/**
 * Add affiliate ID and tracking ID to an affiliate link.
 * 
 * @param $product - An array of a single's product's information.
 */
function dfrapi_url( $product ) {
	
	// Get all the user's selected networks.
	$networks = (array) get_option( 'dfrapi_networks' );
	
	// Extract the affiliate ID from the $networks array.
	$affiliate_id = $networks['ids'][$product['source_id']]['aid'];
	$affiliate_id = apply_filters( 'dfrapi_affiliate_id', $affiliate_id, $product, $networks );
	$affiliate_id = trim( $affiliate_id );
	
	// Extract the Tracking ID from the $networks array.
	$tracking_id = ( isset( $networks['ids'][$product['source_id']]['tid'] ) ) ? $networks['ids'][$product['source_id']]['tid'] : '';
	$tracking_id = apply_filters( 'dfrapi_tracking_id', $tracking_id, $product, $networks );
	$tracking_id = trim( $tracking_id );
	
	// Affiliate ID is missing.  Do action and return empty string.
	if ( $affiliate_id == '' ) {
		do_action( 'dfrapi_affiliate_id_is_missing', $product );
		return '';
	}
	
	// Determine which URL field to get: 'url' OR 'ref_url'. Return 'url' if $tracking_id is empty, otherwise, use 'ref_url'.
	$url = ( $tracking_id == '' ) ? $product['url'] : $product['ref_url'];
	
	// Apply filters to URL before affiliate & tracking ID insertion.
	$url = apply_filters( 'dfrapi_before_affiliate_id_insertion', $url, $product, $affiliate_id );
	$url = apply_filters( 'dfrapi_before_tracking_id_insertion', $url, $product, $tracking_id );

	// Replace placeholders in URL.	
	$placeholders = array( "@@@", "###" );
	$replacements = array( $affiliate_id, $tracking_id );
	$url = str_replace( $placeholders, $replacements, $url );
	
	// Apply filters to URL after affiliate & tracking ID insertion.
	$url = apply_filters( 'dfrapi_after_affiliate_id_insertion', $url, $product, $affiliate_id );
	$url = apply_filters( 'dfrapi_after_tracking_id_insertion', $url, $product, $tracking_id );
	
	// Return URL
	return $url;
}

/**
 * Output an error message generated by the API.
 */
function dfrapi_output_api_error( $data ) { 
	$error = @$data['dfrapi_api_error'];
	$params = @$data['dfrapi_api_error']['params'];
	?>
	<div class="dfrapi_api_error">
		<div class="dfrapi_head"><?php _e( 'Datafeedr API Error', DFRAPI_DOMAIN ); ?></div>
		<div class="dfrapi_msg"><strong><?php _e( 'Message:', DFRAPI_DOMAIN ); ?></strong> <?php echo $error['msg']; ?></div>
		<div class="dfrapi_code"><strong><?php _e( 'Code:', DFRAPI_DOMAIN ); ?></strong> <?php echo $error['code']; ?></div>
		<div class="dfrapi_class"><strong><?php _e( 'Class:', DFRAPI_DOMAIN ); ?></strong> <?php echo $error['class']; ?></div>
		<?php if ( is_array( $params ) ) : ?>
			<div class="dfrps_query"><strong><?php _e( 'Query:', DFRAPI_DOMAIN ); ?></strong> <span><?php echo dfrapi_display_api_request( $params ); ?></span></div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Convert a currency code to sign. USD => $
 * 
 * @https://github.com/pelle/bux/blob/master/src/bux/currencies.clj
 * 
 * Currently supported currencies:
 * 
 * AUD	Australia	&#36;
 * BRL	Brazil	R$
 * CAD	Canada	&#36;
 * CHF	Switzerland	Fr
 * DKK	Denmark	kr
 * EUR	Belgium	&euro;
 * EUR	Finland	&euro;
 * EUR	France	&euro;
 * EUR	Germany	&euro;
 * EUR	Ireland	&euro;
 * EUR	Italy	&euro;
 * EUR	Netherlands	&euro;
 * EUR	Spain	&euro;
 * GBP	United Kingdom	&pound;
 * INR	India	&#8377;
 * NOK	Norway	kr
 * NZD	New Zealand	&#36;
 * PLN	Poland	zł
 * SEK	Sweden	kr
 * TRY	Turkey	&#8356;
 * USD	United States	&#36;
 * 
 */
function dfrapi_currency_code_to_sign( $code ) {
	
	$map = array(
		'AUD' => '&#36;',
		'BRL' => 'R$',
		'CAD' => '&#36;',
		'CHF' => 'Fr',
		'DKK' => 'kr',
		'EUR' => '&euro;',
		'GBP' => '&pound;',
		'INR' => '&#8377;',
		'NOK' => 'kr',
		'NZD' => '&#36;',
		'PLN' => 'zł',
		'SEK' => 'kr',
		'TRY' => '&#8356;',
		'USD' => '&#36;',
	);
	
	$map = apply_filters( 'dfrapi_currency_sign_mapper', $map );
	
	if ( isset ( $map[$code] ) ) {
		return $map[$code];
	} else {
		return $map['USD'];
	}
}

/**
 * This displays the API request in PHP format.
 */
function dfrapi_display_api_request( $params=array() ) {

	$html = '';

	if ( empty( $params ) ) { return $html; }
	
	$html .= '$search = $api->searchRequest();<br />';
	foreach ( $params as $k => $v ) {
		
		// Handle query.
		if ( $k == 'query' ) {
			foreach ( $v as $query ) {
				if ( substr( $query, 0, 9 ) !== 'source_id' || substr( $query, 0, 11 ) !== 'merchant_id' ) {
					$query = str_replace( ",", ", ", $query );
				}
				$html .= '$search->addFilter( \''.( $query ).'\' );<br />';
			}
		}
		
		// Handle sort.
		if ( $k == 'sort' ) {
			foreach ( $v as $sort ) {
				$html .= '$search->addSort( \''.stripslashes( $sort ).'\' );<br />';
			}
		}
		
		// Handle limit.
		if ( $k == 'limit' ) {
			$html .= '$search->setLimit( \''.stripslashes( $v ).'\' );<br />';
		}
		
		// Handle Offset.
		if ( $k == 'offset' ) {
			$html .= '$search->setOffset( \''.stripslashes( $v ).'\' );<br />';
		}
		
		// Handle Exclude duplicates.
		if ( $k == 'exclude_duplicates' ) {
			$html .= '$search->excludeDuplicates( \''. $v  . '\' );<br />';
		}
	}
	
	$html .= '$products = $search->execute();';
	return $html;
	
}

function dfrapi_get_query_param( $query, $param ) {
	if ( is_array( $query ) && !empty( $query ) ) {
		foreach( $query as $k => $v ) {
			if ( $v['field'] == $param ) {
				return array(
					'field' 	=> @$v['field'],
					'operator' 	=> @$v['operator'],
					'value' 	=> @$v['value'],
				);
			}
		}
	}
	return false;
}

/**
 * Converts a value in cents into a value with proper
 * decimal placement.
 * 
 * Example: 14999 => 149.99
 */
function dfrapi_int_to_price( $price ) {
	return number_format( ( $price/100 ), 2 );
}

/**
 * Converts decimal or none decimal prices into values in cents.
 * 
 * assert(dfrapi_price_to_int('123')     		==12300);
 * assert(dfrapi_price_to_int('123.4')   		==12340);
 * assert(dfrapi_price_to_int('1234.56') 		==123456);
 * assert(dfrapi_price_to_int('123,4')   		==12340);
 * assert(dfrapi_price_to_int('1234,56') 		==123456);
 * assert(dfrapi_price_to_int('1,234,567')    	==123456700);
 * assert(dfrapi_price_to_int('1,234,567.8')  	==123456780);
 * assert(dfrapi_price_to_int('1,234,567.89') 	==123456789);
 * assert(dfrapi_price_to_int('1.234.567')    	==123456700);
 * assert(dfrapi_price_to_int('1.234.567,8')  	==123456780);
 * assert(dfrapi_price_to_int('1.234.567,89') 	==123456789);
 * assert(dfrapi_price_to_int('FOO 123 BAR')    ==12300);
 */
function dfrapi_price_to_int( $price ) {
    $d = $price;
    $d = preg_replace('~^[^\d.,]+~', '', $d);
    $d = preg_replace('~[^\d.,]+$~', '', $d);

    // 123 => 12300
    if(preg_match('~^(\d+)$~', $d, $m))
        return intval($m[1] . '00');

    // 123.4 => 12340, 123,45 => 12345
    if(preg_match('~^(\d+)[.,](\d{1,2})$~', $d, $m))
        return intval($m[1] . substr($m[2] . '0000', 0, 2));

    // 1,234,567.89 => 123456789
    if(preg_match('~^((?:\d{1,3})(?:,\d{3})*)(\.\d{1,2})?$~', $d, $m)) {
        $f = isset($m[2]) ? $m[2] : '.';
        return intval(str_replace(',', '', $m[1]) . substr($f . '0000', 1, 2));
    }

    // 1.234.567,89 => 123456789
    if(preg_match('~^((?:\d{1,3})(?:\.\d{3})*)(,\d{1,2})?$~', $d, $m)) {
        $f = isset($m[2]) ? $m[2] : '.';
        return intval(str_replace('.', '', $m[1]) . substr($f . '0000', 1, 2));
    }

    return NULL;
}

function dfrapi_html_output_api_error( $data ) { 
	$error = $data['dfrapi_api_error'];
	$params = @$data['dfrapi_api_error']['params'];
	?>
	<div class="dfrapi_api_error">
		<div class="dfrapi_head"><?php _e( 'Datafeedr API Error', DFRAPI_DOMAIN ); ?></div>
		<div class="dfrapi_msg"><strong><?php _e( 'Message:', DFRAPI_DOMAIN ); ?></strong> <?php echo $error['msg']; ?></div>
		<div class="dfrapi_code"><strong><?php _e( 'Code:', DFRAPI_DOMAIN ); ?></strong> <?php echo $error['code']; ?></div>
		<div class="dfrapi_class"><strong><?php _e( 'Class:', DFRAPI_DOMAIN ); ?></strong> <?php echo $error['class']; ?></div>
		<?php if ( is_array( $params ) ) : ?>
			<div class="dfrapi_query"><strong><?php _e( 'Query:', DFRAPI_DOMAIN ); ?></strong> <span><?php echo dfrapi_helper_display_api_request( $params ); ?></span></div>
		<?php endif; ?>
	</div>
	<?php
}

function dfrapi_get_total_products_in_db( $formatted=TRUE, $default=0 ) {
	
	$account = get_option( 'dfrapi_account' );
	$product_count = $default;
	
	if ( $account ) {
		if ( isset( $account['product_count'] ) ) {
			$product_count = intval( $account['product_count'] );
		}
	}
	
	if ( $formatted && is_int( $product_count ) ) {
		$product_count = number_format( $product_count );
	}
	
	return $product_count;
}
