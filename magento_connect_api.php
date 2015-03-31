<?php
	echo 'Name File: ' . __FILE__ . PHP_EOL;
	
	try {
		// Magento Localhost
		/**/
		$url = 'http://magento.local/api/soap/?wsdl';
		$user = 'magento_api';
		$key = 'magento_api';
		$store = 1;
		/**/

		// Creamos una conexión SOAP
		$client = new SoapClient(
			$url,
			array(
				'trace' => 1,
				'connection_timeout' => 120
			)
		);

		// Login SOAP
		$session = $client->login($user, $key);

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
					'store_id' => array($store),
					//'store_name' => array('like' => '%English'),
					'updated_at' => array(
						'from' => '2015-03-30 00:00:00',
						'to' => '2015-03-31 23:59:59'
					),
					//'increment_id' => array('100000203'),
				)
			)
		);

		$list_order = array();
		$list_shipment = array();
		$info_shipment = array();

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

		foreach ($list_order as $order) {
			$list_shipment[] = $client->call(
				$session,
				'sales_order_shipment.list',
				array(
					'filter' => array(
						'order_id' => $order['order_id']
					)
				)
			);
		}

		foreach ($list_shipment as $shipment) {
			foreach ($shipment as $value) {
				$info_shipment[] = $client->call(
					$session,
					'sales_order_shipment.info',
					array(
						$value['increment_id']
					)
				);
			}
		}

		$serialize_list_order = serialize($list_order);

		echo '<pre> <p>Info: </p>';
		//print_r($storeList); echo '<hr />';
		//print_r($store); echo '<hr />';
		//print_r($list_update_order); echo '<hr />';
		print_r($list_order); echo '<hr />';
		print_r($list_shipment); echo '<hr />';
		print_r($info_shipment); echo '<hr />';
		echo '</pre>';
	} catch (Exception $e) {
		echo '<pre> <p>Exception: </p>';
		var_dump($e);
		echo '</pre>';
	}
