<?php
	echo 'Name File: ' . __FILE__ . PHP_EOL;

	try {
		$list_order = array();
		$list_shipment = array();
		$info_shipment = array();

		$now_date = new \DateTime();
		$tomorrow = $now_date->modify('+2 day');

		// Magento Localhost
		/**/
		$config['name'] = 'Magento Localhost';
		$config['url'] = 'http://magento.local/api/soap/?wsdl';
		$config['user'] = 'magento_api';
		$config['key'] = 'magento_api';
		$config['status'] = array('pending');
		$config['store_id'] = array(1);
		$config['search_store_name'] = 'English';
		$config['from_date'] = '2013-01-01 00:00:00';
		$config['to_date'] = $tomorrow->format('Y-m-d H:i:s');
		$config['increment_id'] = array('100000095');
		$config['order_id'] = array('192');
		/**/

		// Creamos una conexión SOAP
		$client = new SoapClient(
			$config['url'],
			array(
				'trace' => 1,
				'connection_timeout' => 120
			)
		);

		// Login SOAP
		$session = $client->login($config['user'], $config['key']);

		// Store
		/** /
		$storeList = $client->call($session, 'store.list');
		$store = $client->call($session, 'store.info', $config['store_id']);
		/**/

		// Pedimos un listado de las ordenes segun filtros aplicados
		/**/
		$list_update_order = $client->call(
			$session,
			'sales_order.list',
			array(
				'filter' => array(
					//'status' => $config['status'],
					'store_id' => $config['store_id'],
					//'store_name' => array('like' => '%' . $config['search_store_name']),
					'updated_at' => array(
						'from' => $config['from_date'],
						'to' => $config['to_date']
					),
					//'increment_id' => $config['increment_id'],
					//'order_id' => $config['order_id'],
				)
			)
		);
		/**/

		// Iteramos por orden
		/** /
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
		/**/

		/** /
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
		/**/

		/** /
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
		/**/

		/** /
		$serialize_list_order = serialize($list_order);
		/**/

		echo '<pre>';
		echo '<p>' . $config['name'] . '</p>';
		echo 'Data config: ' . PHP_EOL; print_r($config); echo '<hr />';
		//echo '$storeList: ' . PHP_EOL;         print_r($storeList);         echo '<hr />';
		//echo '$store: ' . PHP_EOL;             print_r($store);             echo '<hr />';
		echo '$list_update_order: Count: ' . count($list_update_order) . PHP_EOL; print_r($list_update_order); echo '<hr />';
		echo '$list_order: Count: ' . count($list_order) . PHP_EOL;           print_r($list_order);        echo '<hr />';
		echo '$list_shipment: Count: ' . count($list_shipment) . PHP_EOL;     print_r($list_shipment);     echo '<hr />';
		echo '$info_shipment: Count: ' . count($info_shipment) . PHP_EOL;     print_r($info_shipment);     echo '<hr />';
		echo '</pre>';
	} catch (Exception $e) {
		echo '<pre> <p>Exception: </p>';
		var_dump($e);
		echo '</pre>';
	}
