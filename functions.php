<?php
	function mySqlConnect()
	{
		try {
			$mySql = new PDO(
				"mysql:host=".HOST.";dbname=".DATABASE,
				USER,
				PASSWORD,
				array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
				if (isset($_GET['debug']))
				{
					$mySql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}


		} catch (PDOException $e) {

			die("Error!: " . $e->getMessage() . "<br/>");

		}

		return $mySql;

	}

	function show($data)
	{
		echo '<pre>';
		print_r($data);
		echo '</pre>';

	}

	function logg($text, $level = 0)
	{
		if ($level <= DEBUG)
		{
			if (!ob_get_status())
			{
				ob_start();

			}

			echo $text .'<br />';
			echo str_pad('',4096);
			ob_flush();
			flush();
		}

	}

	function getHtmlObject($url)
	{
		try {

			$htmlList = file_get_contents($url);

			if ($htmlList === FALSE) {

				logg('Did not retreive html content from '.$url.' because :'.$http_response_header[0]);
				return FALSE;
			}

			$htmlList = cleanHtml($htmlList);

			$domList = new DOMDocument;
			$domList->loadHTML($htmlList);

		} catch (PDOException $e) {

			logg($e->getMessages());
		}

		return $domList;
	}

	function ultimativeDecode($input, $iteration = 0)
	{
		return utf8_decode($input);

	}
