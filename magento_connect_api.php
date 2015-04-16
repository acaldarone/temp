<?php
	// php /var/www/html/temp/magento_connect_api.php
	// php /var/www/html/temp/magento_connect_api.php > /var/www/html/temp/magento_connect_api_result.log
	// http://temp/magento_connect_api.php

	$debug = TRUE;

	// Funcion Anonima
	$fa_logger = function ($msg = NULL, $var = NULL, $tag = NULL, $error = FALSE) use ($debug) {
		if ($debug === FALSE) {
			return;
		}

		if (!empty($msg)) {
			echo '<p> ' . $msg . ' </p>' . PHP_EOL;
		}

		if (!empty($var)) {
			if (empty($error)) {
				print_r($var);
			} else {
				var_dump($var);
			}
		}

		if (!empty($tag)) {
			echo $tag . PHP_EOL;
		}
	};

	$fa_logger('Begin Script: ' . date('Y-m-d H:i:s') . ' / File Name: ' . __FILE__);

	try {
		$list_stores = array();
		$info_stores = array();

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
		$config['filter'] = array(
			//'search_store_name' => 'English',
			//'status' => array('pending'),
			'store_id' => array(1),
			//'order_id' => array('192'),
			//'increment_id' => array('100000095'),
			'from_date' => '2014-01-01 00:00:00',
			'to_date' => $tomorrow_date->format('Y-m-d H:i:s'),
		);
		/**/

		// Calculamos la diferencia de dias entre el Desde y el Hasta
		$interval = 0;

		if (isset($config['filter']['from_date']) && $config['filter']['to_date']) {
			$obj_from_date = new \DateTime($config['filter']['from_date']);
			$obj_to_date   = new \DateTime($config['filter']['to_date']);

			$interval = $obj_from_date->diff($obj_to_date)->days;
		}

		// Creamos los filtros cargados en la config
		$filter = array();

		if (isset($config['filter']) && !empty($config['filter'])) {
			if (isset($config['filter']['status'])) {
				$filter['status'] = $config['filter']['status'];
			}

			if (isset($config['filter']['store_id'])) {
				$filter['store_id'] = $config['filter']['store_id'];
			}

			if (isset($config['filter']['order_id'])) {
				$filter['order_id'] = $config['filter']['order_id'];
			}

			if (isset($config['filter']['increment_id'])) {
				$filter['increment_id'] = $config['filter']['increment_id'];
			}

			if (isset($config['filter']['search_store_name'])) {
				$filter['store_name'] = array('like' => '%' . $config['filter']['search_store_name']);
			}
		}

		// Creamos una conexión SOAP v.1
		$client = new \SoapClient(
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
		$info_stores = array();

		if (isset($config['filter']) && isset($config['filter']['store_id'])) {
			$client->call($session, 'store.info', $config['filter']['store_id']);
		}
		/**/

		// Funcion Anonima
		// Pedimos un listado de las ordenes segun filtros aplicados
		$fa_sales_orders_list = function ($from = NULL, $to = NULL) use ($client, $session, $filter, &$list_orders) {
			// Si tenemos valores para las fechas agregamos el filtro
			// No fue creado este filtro anteriormente porque los datos DESDE y HASTA pueden ser dinamicos
			if (!empty($from) && !empty($to)) {
				$filter['updated_at'] = array(
					'from' => $from,
					'to' => $to
				);
			}

			$result = $client->call(
				$session,
				'sales_order.list',
				array(
					'filter' => $filter
				)
			);

			if (!empty($result)) {
				$list_orders = array_merge($list_orders, $result);
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

				$fa_logger($i . '). Call: sales_order.list - From: ' . $obj_from_date->format('Y-m-d H:i:s') . ' - To: ' . $obj_to_date->format('Y-m-d H:i:s'));

				/**/
				$fa_sales_orders_list($obj_from_date->format('Y-m-d H:i:s'), $obj_to_date->format('Y-m-d H:i:s'));
				/**/

				// Pasamos al siguiente dia
				$obj_from_date = $obj_from_date->modify("+1 day");
				// Seteamos la primer hora del dia
				$obj_from_date->setTime(0, 0, 0);

				// Cada 10 iteraciones retrasamos la ejecucion ( es necesario para no tener problemas de memoria )
				if ($i != 0 && ($i % 10) == 0) {
					$fa_logger('Begin Sleep: ' . date('Y-m-d H:i:s'));

					sleep(2);

					$fa_logger('End Sleep: ' . date('Y-m-d H:i:s'));
				}
			}
		} else {
			if (isset($config['filter']['from_date']) && isset($config['filter']['to_date'])) {
				$msg = 'Call: sales_order.list - From: ' . $config['filter']['from_date'] . ' - To: ' . $config['filter']['to_date'];
				$from = $config['filter']['from_date'];
				$to = $config['filter']['to_date'];
			} else {
				$msg = 'Call: sales_order.list';
				$from = NULL;
				$to = NULL;
			}

			$fa_logger($msg);

			/**/
			$fa_sales_orders_list($from, $to);
			/**/
		}

		// Pedimos la info de cada orden // Iteramos por orden
		/**/
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
			$result = $client->call(
				$session,
				'sales_order_shipment.list',
				array(
					'filter' => array(
						'order_id' => $value['order_id']
					)
				)
			);

			if (!empty($result)) {
				$list_shipments[] = $result;
			}
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

		/**/
		$fa_logger('', '', '<pre>');
		$fa_logger($config['name']);
		$fa_logger('Data config: ', $config, '<hr />');
		$fa_logger('$list_stores: ', $list_stores, '<hr />');
		$fa_logger('$info_stores: ', $info_stores, '<hr />');
		$fa_logger('$list_orders: Count: ' . count($list_orders), $list_orders, '<hr />');
		$fa_logger('$info_orders: Count: ' . count($info_orders), $info_orders, '<hr />');
		$fa_logger('$list_shipments: Count: ' . count($list_shipments), $list_shipments, '<hr />');
		$fa_logger('$info_shipments: Count: ' . count($info_shipments), $info_shipments, '<hr />');
		$fa_logger('', '', '</pre>');
		/**/
	} catch (\Exception $e) {
		$fa_logger('', '', '<pre>');
		$fa_logger('Exception: ', $e, '<hr />', TRUE);
		$fa_logger('', '', '<pre>');
	}

	$fa_logger('End Script: ' . date('Y-m-d H:i:s') . ' / File Name: ' . __FILE__);

