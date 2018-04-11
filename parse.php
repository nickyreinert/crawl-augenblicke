<?php
	set_time_limit (-1);

	error_reporting(E_ERROR);
//	error_reporting(E_ERROR | E_PARSE | E_NOTICE);

	require_once('../config.php');
	require_once('functions.php');
	

	class Parse
	{

		private $allMessages = array();

		private $lastMessageIdInDatabase = 0;

		private $lastMessageIdOnSite = 0;

		private $countMessages = 0;

		private $mySqlSelect = NULL;
		private $mySqlInsert = NULL;

		private $limit = 0;

		private $isHidden = 0;
		
		public function __construct()
		{
			$this->config = json_decode(file_get_contents(PARSE_CONFIG_FILE));

			$this->setLiveParameters();

			$this->mySqlSelect = mySqlConnect();

			logg('Found '.$this->getCountMessages().' messages in database.');

			logg('Last message in database has id '.$this->getLastMessageIdInDatabase());

			logg('Last message on site has id '.$this->getLastMessageIdOnSite());

			if (isset($_GET['hiddenItems'])) {

				$this->isHidden = 1;
				
				$this->getHiddenMessageUrls();

			} else {

				$this->isHidden = 0;
				
				$this->getPublicMessageUrls();

			}
			

			logg('Found '.sizeof($this->allMessages).' message urls to parse');

			$this->getAndSaveMessages();

		}

		private function setLiveParameters() {

			if (isset($_GET['help'])) {

				logg('Available Get-Parameters');
				logg('- Limit');
				logg('- hiddenItems');
				die();

			}

			if (isset($_GET['limit']))
			{
				$this->limit = $_GET['limit'];
			}




		}

		private function getAndSaveMessages()
		{

			$this->mySqlInsert = mySqlConnect();

			$dbInsert = $this->mySqlInsert->prepare($this->config->query->insertMessage);

			$dbSelect = $this->mySqlSelect->prepare($this->config->query->getContentId);

			$cHtmlContent = curl_init();

			curl_setopt($cHtmlContent, CURLOPT_RETURNTRANSFER, 1);

			$count = 0;
			
			foreach ($this->allMessages as $contentId => $content) {

				if ($this->limit > 0 AND $this->limit <= $count) 
				{
				    logg('Limit reached.');
				    break;
				    
				}

				$dbSelect->execute(array(':contentId' => $contentId));

				if ($dbSelect->rowCount() == 0)
				{

					logg('Receiving message with content id '.$contentId.' from site.');

					$this->allMessages[$contentId] = $this->getMessage($content['url']);

					$this->allMessages[$contentId]['contentId'] = $contentId;
					$this->allMessages[$contentId]['url'] = $content['url'];
					
					logg('Saving message with content id '.$contentId.' to database');

					try {
						
					    $dbInsert->execute($this->allMessages[$contentId]);
					
					
					} catch (PDOException $e) {

					    logg($e->getMessages());

					}
					++$count;

				} else {
			    
				    logg('Skip message with content id '.$contentId.' because it already exists.');

				}

			}

		}

		private function extractLine($domContent)
		{
			foreach($domContent->getElementsByTagName('span') as $span)
			{

				if ($span->getAttribute('class') == 'icon-t__line')
				{
			 		$result = str_replace
					(
						'icon-t icon-t--', '', $span->parentNode->getAttribute('class')
					);

					break;

				} else if ($span->getAttribute('class') == 'icon-t__type') {

					$result = $span->parentNode->parentNode->textContent;

					break;

				}

			}

			return ultimativeDecode($result);

		}

		private function extractDateMet($domContent)
		{
			$result = date('Y-m-d H:s:i', strtotime('today'));;

			foreach($domContent->getElementsByTagName('dl') as $dl)
			{

				if ($dl->getAttribute('class') == 'moment-info')
				{
					foreach ($dl->childNodes as $index => $childNode)
					{
						if ($childNode->textContent == 'Datum:')
						{
							$result = date
							(
								'Y-m-d H:s:i', strtotime($dl->childNodes->item($index + 2)->textContent)
							);

							break(2);
						}

					}

				}

			}
			
			return ultimativeDecode($result);

		}

		private function extractDatePosted($domContent)
		{
			$result = date('Y-m-d H:s:i', strtotime('today'));

			foreach($domContent->getElementsByTagName('dl') as $dl)
			{

				if ($dl->getAttribute('class') == 'moment-info')
				{
					foreach ($dl->childNodes as $index => $childNode)
					{

						if ($childNode->textContent == 'Verfasst am:')
						{
							$result = date
							(
								'Y-m-d H:s:i', strtotime($dl->childNodes->item($index + 2)->textContent)
							);

							break(2);
						}

					}

				}

			}

			return ultimativeDecode($result);

		}

		private function extractAuthor($domContent)
		{
			$result = NULL;

			foreach($domContent->getElementsByTagName('dl') as $dl)
			{

				if ($dl->getAttribute('class') == 'moment-info')
				{
					foreach ($dl->childNodes as $index => $childNode)
					{

						if ($childNode->textContent == 'Von:')
						{

							$result = $dl->childNodes->item($index + 2)->textContent;

							break(2);

						}

					}

				}

			}

			return ultimativeDecode($result);

		}

		private function extractTitle($domContent)
		{
			$result = NULL;

			foreach($domContent->getElementsByTagName('dl') as $dl)
			{

				if ($dl->getAttribute('class') == 'moment-info')
				{
					foreach ($dl->childNodes as $index => $childNode)
					{

						if ($childNode->textContent == 'Titel:')
						{
							$result = $dl->childNodes->item($index + 2)->textContent;

							break(2);
						}

					}

				}

			}

			return ultimativeDecode($result);

		}

		private function extractText($domContent)
		{
			$result = NULL;

			foreach($domContent->getElementsByTagName('div') as $div)
			{
				if ($div->getAttribute('class') == 'moment-message')
				{
					$result = trim(preg_replace('/^\r|\n/', '',$div->textContent), ' ');

					break;

				}

			}

			return ultimativeDecode($result);

		}

		private function getMessage($contentUrl)
		{
			$message = array();

			
			$domContent = getHtmlObject($contentUrl);

			if ($domContent != FALSE)
			{

				$message['line'] = $this->extractLine($domContent);
				
				$message['date_met'] = $this->extractDateMet($domContent);
				
				$message['date_posted'] = $this->extractDatePosted($domContent);
				
				$message['author'] = $this->extractAuthor($domContent);
				
				$message['title'] = $this->extractTitle($domContent);

				$message['message'] = $this->extractText($domContent);

				$message['hidden'] = $this->isHidden;

				if (($message['date_posted'] == '') AND
					($message['message'] == '') AND
					($message['title']) == '') {
					logg('Empty message on this url: '.$contentUrl);
					$message['valid'] = 0;
					
				} else {

					$message['valid'] = 1;

				}

			} else {

				$message['line'] = '';
				
				$message['date_met'] = date('Y-m-d');

				$message['date_posted'] = date('Y-m-d');

				$message['author'] = '';

				$message['title'] = '';

				$message['message'] = '';

				$message['hidden'] = $this->isHidden;

				$message['valid'] = 0;

			}
			
			return $message;

		}

		private function getPublicMessageUrls()
		{
			$count = 0;
		    
			for ($currentMessageId = $this->lastMessageIdInDatabase; $currentMessageId <= $this->lastMessageIdOnSite; ++$currentMessageId)
			{

				if ($this->limit > 0 AND $this->limit <= $count) {logg('Limit reached.');break;}

				if(!array_key_exists($currentMessageId, $this->allMessages))
				{
					$this->allMessages[(int) $currentMessageId] = array(
						'url' => sprintf(URL_MESSAGE, $currentMessageId),
						'contentId' => $currentMessageId,
						'date_met'	=> NULL,
						'date_posted' => NULL,
						'hidden' => 0
					);
					++$count;
				}

			}

			ksort($this->allMessages);
		    
		}

		private function getHiddenMessageUrls()
		{
			$sql = $this->mySqlSelect->prepare($this->config->query->allContentIds);

			$sql->execute();

			$result = $sql->fetchAll();

			foreach($result as $row)
			{

				$existingMessages[$row['contentId']] = $row;

			}

			$count = 0;

			for ($hiddenContentId = 1; $hiddenContentId < $this->lastMessageIdInDatabase; ++$hiddenContentId)
			{

				if ($this->limit > 0 AND $this->limit <= $count) {logg('Limit reached.');break;}

				if(!array_key_exists($hiddenContentId, $existingMessages))
				{
					$this->allMessages[(int) $hiddenContentId] = array(
						'url' => sprintf(URL_MESSAGE, $hiddenContentId),
						'contentId' => $hiddenContentId,
						'date_met'	=> date('Y-m-d H:s:i', strtotime('today')),
						'date_posted' => date('Y-m-d H:s:i', strtotime('today')),
						'hidden' => 1
					);
					++$count;

				}

			}

			ksort($this->allMessages);

		}

		private function getCountMessages()
		{
			$sql = $this->mySqlSelect->prepare($this->config->query->countMessages);
			$sql->execute();
			$result = $sql->fetchAll();
			$this->countMessages = $result[0]['messageCount'];

			return $result[0]['messageCount'];

		}

		private function getLastMessageIdInDatabase()
		{
			$sql = $this->mySqlSelect->prepare($this->config->query->lastContentId);
			$sql->execute();
			$result = $sql->fetchAll();
			$this->lastMessageIdInDatabase = $result[0]['contentId'];

			return $result[0]['contentId'];

		}

		private function getLastMessageIdOnSite()
		{
		 
			$lastMessageIdOnSite = 0;

			$domList = getHtmlObject(sprintf(URL_MAIN_LIST, 0));
			
			foreach($domList->getElementsByTagName('a') as $link)
			{
				// if it is a pagination-link, get the highest index
				if ($link->getAttribute('class') == 'icon icon--arrow')
				{

				    $momentUrl= parse_url($link->getAttribute('href'));
				    parse_str($momentUrl['query'], $momentUrlParameters);
				    
				    if (array_key_exists('id', $momentUrlParameters) AND $momentUrlParameters['id'] > $lastMessageIdOnSite)
				    {
					$lastMessageIdOnSite = $momentUrlParameters['id'];
				    }

				}

			}
			
			$this->lastMessageIdOnSite = $lastMessageIdOnSite;
			
			return $lastMessageIdOnSite;

		}

	}


	$parse = new Parse;
