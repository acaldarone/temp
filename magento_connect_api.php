<?php
	// php /var/www/html/temp/magento_connect_api.php > ~/Escritorio/result.localhost.txt
	// http://temp/magento_connect_api.php

	echo '<p>Begin Script: ' . date('Y-m-d H:i:s') . ' / File Name: ' . __FILE__ . ' </p>' . PHP_EOL;

	try {
		$list_stores = array();
		$info_stores = array();

		$tmp_list_orders = array();
		$list_orders = array();
		$info_orders = array();

		$list_shipments = array();
		$info_shipments = array();

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

		// Creamos una conexión SOAP v.1
		$client = new SoapClient(
			$config['url'],
			array(
				'trace' => 1,
				'connection_timeout' => 120
			)
		);

		// Login SOAP
		$session = $client->login($config['user'], $config['key']);

		// Store List
		/** /
		$list_stores = $client->call($session, 'store.list');
		/**/

		// Store Info
		/** /
		$info_stores = $client->call($session, 'store.info', $config['store_id']);
		/**/

		// Funcion Anonima
		// Pedimos un listado de las ordenes segun filtros aplicados
		$fa_sales_orders_list = function ($from, $to) use ($client, $session, $config, &$tmp_list_orders) {
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
				$key = new \DateTime($to);
				$tmp_list_orders[$key->format('Y-m-d_H:i:s')] = $result;
			}
		};

		// Por un tema de cantidad de pedidos vamos a tener que realizar llamadas pequeñas para que no supere el limite de memoria
		// Si tenemos mas de un dia de diferencia
		if ($interval > 1) {
			// Realizamos una iteracion por dia hasta alcanzar la fecha actual
			for ($i = 0; $i < $interval; $i++) {
				// Creamos un Objeto para la fecha hasta
				$obj_to_date = new \DateTime($obj_from_date->format('Y-m-d H:i:s'));
				// Seteamos la ultima hora del dia
				$obj_to_date->setTime(23, 59, 59);

				// En la ultima iteracion reemplazamos el valor del Hasta por la Fecha/Hora Actual
				if (($i + 1) == $interval) {
					$obj_to_date = $now_date;
				}

				echo '<p> ' . $i . '). Call: sales_order.list - From: ' . $obj_from_date->format('Y-m-d H:i:s') . ' - To: ' . $obj_to_date->format('Y-m-d H:i:s') . ' </p>' . PHP_EOL;

				/**/
				$fa_sales_orders_list($obj_from_date->format('Y-m-d H:i:s'), $obj_to_date->format('Y-m-d H:i:s'));
				/**/

				// Pasamos al siguiente dia
				$obj_from_date = $obj_from_date->modify("+1 day");
				// Seteamos la primer hora del dia
				$obj_from_date->setTime(0, 0, 0);

				// Cada 10 iteraciones retrasamos la ejecucion ( es necesario para no tener problemas de memoria )
				if ($i != 0 && ($i % 10) == 0) {
					echo '<p> sleep: ' . date('Y-m-d H:i:s') . ' - ';
					sleep(2);
					echo date('Y-m-d H:i:s') . ' </p>' . PHP_EOL;
				}
			}
		} else {
			echo '<p> Call: sales_order.list - From: ' . $config['from_date'] . ' - To: ' . $config['to_date'] . ' </p>' . PHP_EOL;

			/**/
			$fa_sales_orders_list($config['from_date'], $config['to_date']);
			/**/
		}

		// Iteramos por fecha
		foreach ($tmp_list_orders as $key => $list) {
			echo '<p> Fecha: ' . $key . ' - Orders Count: ' . count($list) . ' </p>' . PHP_EOL;
			// Iteramos por la cantidad de pedidos que tengamos por fecha
			foreach ($list as $value) {
				// La idea seria tener todas las ordenes juntas ( y no separadas por fecha )
				$list_orders[] = $value;
			}
		}

		// Pedimos la info de cada orden // Iteramos por orden
		/** /
		foreach ($list_orders as $value) {
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

			$info_orders[] = $tmp;
		}
		/**/

		// Pedimos los envios correspondientes a las ordenes // Iteramos por orden
		/** /
		foreach ($info_orders as $value) {
			$list_shipments[] = $client->call(
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

		// Pedimos la info de cada envio // Iteramos por envio
		/** /
		foreach ($list_shipments as $shipment) {
			foreach ($shipment as $value) {
				$info_shipments[] = $client->call(
					$session,
					'sales_order_shipment.info',
					array(
						$value['increment_id']
					)
				);
			}
		}
		/**/

		echo '<pre>' . PHP_EOL;

		/**/
		echo '<p>' . $config['name'] . '</p>' . PHP_EOL;
		/**/

		/** /
		echo '<p> Data config: </p>' . PHP_EOL;
		print_r($config);
		echo '<hr />' . PHP_EOL;
		/**/

		/** /
		echo '<p> $list_stores: </p>' . PHP_EOL;
		print_r($list_stores);
		echo '<hr />' . PHP_EOL;
		/**/

		/** /
		echo '<p> $info_stores: </p>' . PHP_EOL;
		print_r($info_stores);
		echo '<hr />' . PHP_EOL;
		/**/

		/**/
		echo '<p> $list_orders: Count: ' . count($list_orders) . ' </p>' . PHP_EOL;
		//print_r($list_orders);
		echo '<hr />' . PHP_EOL;
		/**/

		/** /
		echo '<p> $info_orders: Count: ' . count($info_orders) . ' </p>' . PHP_EOL;
		print_r($info_orders);
		echo '<hr />' . PHP_EOL;
		/**/

		/** /
		echo '<p> $list_shipments: Count: ' . count($list_shipments) . ' </p>' . PHP_EOL;
		print_r($list_shipments);
		echo '<hr />' . PHP_EOL;
		/**/

		/** /
		echo '<p> $info_shipments: Count: ' . count($info_shipments) . ' </p>' . PHP_EOL;
		print_r($info_shipments);
		echo '<hr />' . PHP_EOL;
		/**/

		echo '</pre>' . PHP_EOL;
	} catch (Exception $e) {
		echo '<pre>' . PHP_EOL;

		echo '<p> Exception: </p>' . PHP_EOL;
		var_dump($e);
		echo '<hr />' . PHP_EOL;

		echo '</pre>' . PHP_EOL;
	}

	echo '<p>End Script: ' . date('Y-m-d H:i:s') . ' / File Name: ' . __FILE__ . ' </p>' . PHP_EOL;
