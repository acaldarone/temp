<?php
	echo 'Name File: ' . __FILE__;
	
	try {
		// Creamos una conexión SOAP
		$client = new SoapClient(
			'http://magento.local/api/soap/?wsdl',
			array(
				'trace' => 1,
				'connection_timeout' => 120
			)
		);
		
		// Login SOAP
		$session = $client->login('api_temp', 'api_temp_123');
		
		// Pedimos un listado de las ordenes actualizadas entre un rango de fechas.
		$list_update_order = $client->call(
			$session,
			'sales_order.list',
			array(
				'filter' => array(
					'updated_at' => array(
						'from' => '2015-03-02 16:45:30',
						'to' => '2015-03-04 16:45:30'
					)
				)
			)
		);
		
		$list_order = array();
		
		// Iteramos por orden
		foreach ($list_update_order as $value) {
			// Pedimos la info de cada orden y los persistimos en una variable
			$list_order[] = $client->call(
				$session,
				'sales_order.info',
				array(
					$value['increment_id']
				)
			);
		}
		
		$serialize_list_order = serialize($list_order);
		
		echo '<pre> <p>ORDER:</p>';
		print_r($list_order);
		echo '</pre>';
	} catch (Exception $e) {
		echo '<pre> <p>Exception:</p>';
		var_dump($e);
		echo '</pre>';
	}