<?php

    # Anothony Matarazzo - http://phpprofessional.us
    # Travis Ballard - http://travis ballard.com

    define( "API_LOGIN_ID", "" );
    define( "API_TRANSACTION_KEY", "" );
    define( "API_URL", "https://secure.authorize.net/gateway/transact.dll" );

    class AuthorizeNet
    {
        private $post_array = array(
            'x_login'           => API_LOGIN_ID,
            'x_version'         => '3.1',
            'x_delim_char'      => '|',
            'x_delim_data'      => 'TRUE',
            'x_url'             => 'FALSE',
            'x_type'            => 'AUTH_CAPTURE',
            'x_method'          => 'CC',
            'x_tran_key'        => API_TRANSACTION_KEY,
            'x_relay_response'  => 'FALSE',
            'x_card_num'        => null,
            'x_card_code'       => null,
            'x_exp_date'        => null,
            'x_description'     => null,
            'x_amount'          => null,
            'x_first_name'      => null,
            'x_last_name'       => null,
            'x_address'         => null,
            'x_city'            => null,
            'x_state'           => null,
            'x_zip'             => null,
            'x_country'         => 'US',
            'x_test_request'    => 'FALSE'
        );

        // response variables, populated after each process
        public $response_code;
        public $response_subcode;
        public $response_reason_code;
        public $response_reason_text;
        public $approval_code;
        public $avs_result_code;
        public $transaction_id;
        public $invoice_number;
        public $description;
        public $amount;
        public $method;
        public $transaction_type;
        public $customer_id;

        /**
        * set credit card info
        *
        * @param mixed $num
        * @param mixed $exp
        * @param mixed $code
        */
        public function cc_info($num, $exp, $code)
        {
            $this->post_array["x_card_num"] = $num;
            $this->post_array["x_exp_date"] = $exp;
            $this->post_array["x_card_code"] = $code;
        }

        /**
        * set user info
        *
        * @param mixed $fname
        * @param mixed $lname
        * @param mixed $address
        * @param mixed $city
        * @param mixed $state
        * @param mixed $zip
        * @param mixed $country
        */
        public function user_info($fname, $lname, $address, $city, $state, $zip, $country = "US")
        {
            $this->post_array["x_first_name"] = $fname;
            $this->post_array["x_last_name"] = $lname;
            $this->post_array["x_address"] = $address;
            $this->post_array["x_city"] = $city;
            $this->post_array["x_state"] = $state;
            $this->post_array["x_zip"] = $zip;
            $this->post_array["x_country"] = $country;
        }

        /**
        * set order info
        *
        * @param mixed $amount
        * @param mixed $card_number
        * @param mixed $card_exp_date
        * @param mixed $ccv_code
        * @param mixed $desc
        */
        public function order_info($amount, $card_number, $card_exp_date, $ccv_code, $desc = "Online Transaction")
        {
            $this->post_array["x_amount"] = (float)$amount;
            $this->post_array["x_card_num"] = $card_number;
            $this->post_array["x_card_code"] = $ccv_code;
            $this->post_array["x_exp_date"] = $card_exp_date;
            $this->post_array["x_description"] = $desc;
        }

        /**
        * validate funds
        *
        */
        public function validate_funds()
        {
            $this->post_array["x_type"] = "AUTH_ONLY";
            return( $this->process_transaction() );
        }

        /**
        * process funds
        *
        */
        public function process_funds()
        {
            $this->post_array["x_type"] = "AUTH_CAPTURE";
            return( $this->process_transaction() );
        }

        /**
        * process transaction
        *
        */
        public function process_transaction()
        {
            $post_data = "";

            foreach( $this->post_array as $key => $value )
                $post_data .= (($post_data != "") ? "&" : "") . $key ."=". urlencode( $value );

            // initialize response data
            $this->response_code = null;
            $this->response_subcode = null;
            $this->response_reason_code = null;
            $this->response_reason_text = null;
            $this->approval_code = null;
            $this->avs_result_code = null;
            $this->transaction_id = null;
            $this->invoice_number = null;
            $this->description = null;
            $this->amount = null;
            $this->method = null;
            $this->transaction_type = null;
            $this->customer_id = null;

            if ( ($ch = curl_init( API_URL )) )
            {
                curl_setopt( $ch , CURLOPT_HEADER , 0 );                                    // set to 0 to eliminate header info from response
                curl_setopt( $ch , CURLOPT_RETURNTRANSFER , 1 );                            // Returns response data instead of TRUE(1)
                curl_setopt( $ch , CURLOPT_POSTFIELDS , $post_data );                        // use HTTP POST to send form data
                curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , false );                        // uncomment this line if you get no gateway response.
                $resp = curl_exec($ch);                                                        //execute post and get results
                curl_close($ch);
                return( $this->parse_transaction($resp) );
            } else
                return( false );
        }

        /**
        * parse transaction response
        *
        * @param mixed $str
        */
        public function parse_transaction($str)
        {
            if ( strpos($str, "|") !== false )
            {
                $arr = explode("|", $str);
                $this->response_code = $arr[0];
                $this->response_subcode = $arr[1];
                $this->response_reason_code = $arr[2];
                $this->response_reason_text = $arr[3];
                $this->approval_code = $arr[4];
                $this->avs_result_code = $arr[5];
                $this->transaction_id = $arr[6];
                $this->invoice_number = $arr[7];
                $this->description = $arr[8];
                $this->amount = $arr[9];
                $this->method = $arr[10];
                $this->transaction_type = $arr[11];
                $this->customer_id = $arr[12];
                if ( (int)$this->response_code != 1 )
                    return( false );
                else
                    return( true );
            } else
                return( false );
        }

        /**
        * set as a test transaction
        *
        */
        public function is_test_transaction(){
            $this->post_array['x_test_request'] = 'TRUE';
        }
    }