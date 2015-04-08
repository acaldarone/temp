<?php
	echo '<p>Begin Script File Name: ' . __FILE__ . '</p>';

	try {
		$list_order = array();
		$list_shipment = array();
		$info_shipment = array();
		$tmp_list_update_order = array();

		// Fecha y Hora Actual
		$now_date = new \DateTime('now');
		// Fecha Actual y Hora 00:00:00
		//$now_date = new \DateTime('today');

		// Fecha De Mañana y Hora 00:00:00
		$tomorrow_date = new \DateTime('tomorrow');
		// Fecha de Mañana con la Hora Actual
		//$tomorrow_date = new \DateTime('now');
		//$tomorrow_date->modify('+1 day');

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
		$config['to_date'] = $tomorrow_date->format('Y-m-d H:i:s');
		$config['increment_id'] = array('100000095');
		$config['order_id'] = array('192');
		/**/

		// Calculamos la diferencia de dias entre el Desde y el Hasta
		$obj_from_date = new DateTime($config['from_date']);
		$obj_to_date   = new DateTime($config['to_date']);

		$interval = $obj_from_date->diff($obj_to_date)->days;

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

		// Funcion Anonima
		// Pedimos un listado de las ordenes segun filtros aplicados
		$fa_sales_orders_list = function($from, $to) use($client, $session, $config, &$tmp_list_update_order) {
			$result = $client->call(
				$session,
				'sales_order.list',
				array(
					'filter' => array(
						//'status' => $config['status'],
						'store_id' => $config['store_id'],
						//'store_name' => array('like' => '%' . $config['search_store_name']),
						'updated_at' => array(
							'from' => $from,
							'to' => $to
						),
						//'increment_id' => $config['increment_id'],
						//'order_id' => $config['order_id'],
					)
				)
			);

			if (!empty($result)) {
				$tmp_list_update_order[] = $result;
			}
		};

		// Por un tema de cantidad de pedidos vamos a tener que realizar llamadas pequeñas para que no supere el limite de memoria
		// Si tenemos mas de un dia de diferencia
		if ($interval > 1) {
			// Realizamos una iteracion por dia hasta alcanzar la fecha actual
			for ($i = 0; $i < $interval; $i++) {
				$tmp = new \DateTime($obj_from_date->format('Y-m-d H:i:s'));

				// Incrementamos 1 dia
				$obj_to_date = $tmp->modify("+1 day");

				// En la ultima iteracion reemplazamos el valor del Hasta por la Fecha/Hora Actual
				if (($i + 1) == $interval) {
					$obj_to_date = $now_date;
				}

				// Cada 10 iteraciones retrasamos la ejecución 5 segundos ( es necesario para no tener problemas de memoria )
				if (($i % 10) == 0) {
					sleep(0.25);
				}

				/**/
				echo $i . '). From: ' . $obj_from_date->format('Y-m-d H:i:s') . ' - To: ' . $obj_to_date->format('Y-m-d H:i:s') . PHP_EOL;
				$fa_sales_orders_list($obj_from_date->format('Y-m-d H:i:s'), $obj_to_date->format('Y-m-d H:i:s'));
				/**/

				$obj_from_date = $obj_to_date;
			}
		} else {
			/** /
			$fa_sales_orders_list($config['from_date'], $config['to_date']);
			/**/
		}

		// Iteramos por el primer elemento que representa la fecha
		foreach ($tmp_list_update_order as $list) {
			// Iteramos por la cantidad de pedidos que tengamos por fecha
			foreach ($list as $value) {
				$list_update_order[] = $value;
			}
		}

		// Iteramos por orden
		/** /
		foreach ($list_update_order as $value) {
			// Pedimos la info de cada orden y los persistimos en una variable
			$tmp = $client->call(
				$session,
				'sales_order.info',
				array(
					$value['increment_id']
				)
			);

			foreach ($tmp['items'] as &$tmp_value) {
				$tmp_value['product_options'] = unserialize($tmp_value['product_options']);
			}

			$list_order[] = $tmp;
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

	echo '<p>End Script File Name: ' . __FILE__ . '</p>';
