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
		
		// Store
		$storeList = $client->call($session, 'store.list');
		$store = $client->call($session, 'store.info', '1');

		// Pedimos un listado de las ordenes actualizadas entre un rango de fechas.
		$list_update_order = $client->call(
			$session,
			'sales_order.list',
			array(
				'filter' => array(
					//'status' => array('pending'),
					'store_id' => array('1'),
					//'store_name' => array('like' => '%English'),
					'updated_at' => array(
						'from' => '2012-03-02 16:45:30',
						'to' => '2015-03-13 16:45:30'
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
		print_r($storeList);
		echo '</pre>';
	} catch (Exception $e) {
		echo '<pre> <p>Exception:</p>';
		var_dump($e);
		echo '</pre>';
	}
