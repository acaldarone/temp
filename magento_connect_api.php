<?php
	echo 'Name File: ' . __FILE__ . PHP_EOL;
	
	try {
		
		// Magento Localhost
		/**/
		$name = 'Magento Localhost';
		$url = 'http://magento.local/api/soap/?wsdl';
		$user = 'magento_api';
		$key = 'magento_api';
		$status = array('pending');
		$store = array(1);
		$search_store_name = 'English';
		$from_date = '2015-04-01 00:00:00';
		$to_date = '2015-04-30 23:59:59';
		$increment_id = array('100000095');
		$order_id = array('192');
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
		$store = $client->call($session, 'store.info', $store);

		// Pedimos un listado de las ordenes segun filtros aplicados
		$list_update_order = $client->call(
			$session,
			'sales_order.list',
			array(
				'filter' => array(
					//'status' => $status,
					'store_id' => $store,
					//'store_name' => array('like' => '%' . $search_store_name),
					'updated_at' => array(
						'from' => $from_date,
						'to' => $to_date
					),
					//'increment_id' => $increment_id,
					//'order_id' => $order_id,
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

		foreach ($list_order as $value) {
			$list_shipment[] = $client->call(
				$session,
				'sales_order_shipment.list',
				array(
					'filter' => array(
						'order_id' => $value['order_id']
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

		echo '<pre>';
		echo '<p>' . $name . '</p>';
		//echo '$storeList: ' . PHP_EOL;         print_r($storeList);         echo '<hr />';
		//echo '$store: ' . PHP_EOL;             print_r($store);             echo '<hr />';
		//echo '$list_update_order: ' . PHP_EOL; print_r($list_update_order); echo '<hr />';
		echo '$list_order: ' . PHP_EOL;        print_r($list_order);        echo '<hr />';
		echo '$list_shipment: ' . PHP_EOL;     print_r($list_shipment);     echo '<hr />';
		echo '$info_shipment: ' . PHP_EOL;     print_r($info_shipment);     echo '<hr />';
		echo '</pre>';
	} catch (Exception $e) {
		echo '<pre> <p>Exception: </p>';
		var_dump($e);
		echo '</pre>';
	}
