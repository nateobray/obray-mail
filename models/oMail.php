<?php
	
	Class omail extends ODBO{
				
		function __construct(){
			
			
			
			/*********************************************
				TABLE DEFINITION
			*********************************************/
			
			$this->table = "omail";
			$this->table_definition = array(
				"omail_id" => 				array( "primary_key" => TRUE ),
				"oorder_id" =>				array( "data_type" => "integer" 		),
				"ouser_to" =>				array( "data_type" => "integer",		"to" => "ouser_id:/m/oUsers/"	),
				"ouser_from" =>				array( "data_type" => "integer", 		"from" => "ouser_id:/m/oUsers/"	),
				"omail_to" =>				array( "data_type" => "varchar(100)"	),
				"omail_cc" =>				array( "data_type" => "varchar(100)"	),
				"omail_bcc" => 				array( "data_type" => "varchar(100)"	),
				"omail_from" => 			array( "data_type" => "varchar(100)"	),
				"omail_subject" => 			array( "data_type" => "varchar(100)"	),
				"omail_message" => 			array( "data_type" => "text"			),
				"omail_status_code" => 		array( "data_type" => "varchar(50)"		),
				"omail_error_code" => 		array( "data_type" => "varchar(50)"		),
				"omail_error_message" => 	array( "data_type" => "varchar(255)"	),
				"omail_request_id" => 		array( "data_type" => "varchar(255)"	),
				"omail_message_id" => 		array( "data_type" => "varchar(255)"	),
				"omail_datetime_sent" => 	array( "data_type" => "datetime"		)
			);
			
			$this->permissions = array( 
				"object" => "any",
				"add" => 0,
				"update" => 0,
				"delete" => 0,
				"get" => 0,
				"getTableDefinition" => 0,
				"queue" => 0,
				"send" => "any"
			 ); 
			 
			 parent::__construct();
			 
		}
		
		
		
		/*******************************
			Route
		*******************************/
		
		function queue(){ return $this->add(); }
		
		/*********************************************
			SENDS QUEUED EMAILS
		*********************************************/
		
		function send( $params=array() ){
			
			if( empty($params["omail_status_code"]) ){ $params["omail_status_code"] = -1; }
			if( empty($params["omail_id"]) ){
				$this->add($params);	
			} else {
				$this->get($params);
			}
			
			$omails = $this->data;
			
			$data = new stdClass;
			$data->batch = array();
			
			forEach( $omails as $omail ){
				
				$message = $omail->omail_message;
				ob_start();
				include __SELF__."views/mail/vMail.phtml";
				$omail->omail_message = ob_get_clean();
				
				$data->batch[] = $this->route('/m/aws/oAWS/sendEmail/?subject='.urlencode($omail->omail_subject).'&to='.urlencode($omail->omail_to).'&from='.urlencode($omail->omail_from).'&cc='.urlencode($omail->omail_cc).'&bcc='.urlencode($omail->omail_bcc).'&message='.urlencode($omail->omail_message).'&omail_id='.$omail->omail_id);
				
				$params['omail_id'] = $omail->omail_id;
				$params['omail_status_code'] = $data->batch[count($data->batch)-1]->data->status_code;
				if( isSet($data->batch[count($data->batch)-1]->errors) || !empty($data->batch[count($data->batch)-1]->errors)){
					$this->throwError("Unable to send email");
					$params["omail_error_code"] = $data->batch[count($data->batch)-1]->data->code;
					$params["omail_error_message"] = $data->batch[count($data->batch)-1]->data->message;
				} else {
					$params["omail_request_id"] = $data->batch[count($data->batch)-1]->data->request_id;
					$params["omail_message_id"] = $data->batch[count($data->batch)-1]->data->message_id;
					$params["omail_datetime_sent"] = date('Y-m-d h:i:s');
				}
				unset($this->data);
				$this->update($params);
				
			}
			
		}
			
	
	}

?>