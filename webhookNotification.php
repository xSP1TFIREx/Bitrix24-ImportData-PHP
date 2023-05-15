<?php
	// $url_out = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 		                    // Адрес страницы для последующей обработки
	$url_out = 'https://testsite.com?name=ТестовоеИмя&software_name=9667&email=testmail@mail.co.jp&phone=79998887766&file_name=testfile.zip&transaction_id=987654321&type_of_deal=115299&assigned_by_id=30541'; 	    // Или ввод адреса вручную 
	$b24Url = "https://xellarus.bitrix24.ru";					                                                                                        // URL нашего Битрикс24
	$b24UserID = "30541";										                                                                                        // ID пользователя
	$b24WebHook = "lb4lub7shtjv0k23";							                                                                                        
	
	// Обрабатываем внешнюю ссылку
	$qu = parse_url($url_out, PHP_URL_QUERY); // Получаем querry параметр из строки
	parse_str($qu, $output); // Разбиваем адрес на get-параметры
		
	// Формируем URL
	$queryURL = "$b24Url/rest/$b24UserID/$b24WebHook";	

    // Создаём переменные
    $name = $output['name'];
    $software_name = $output['software_name'];
    $email = $output['email'];
    $phone = $output['phone'];
    $file_name = $output["file_name"];
    $transaction_id = $output['transaction_id'];            // ID с сайта BIMLIB для идентификации транзакции в случае потери данных
 //   $source_id = $output['source_id'];                      // Сквозная аналитика -> Источник
    $assigned_by_id = $output['assigned_by_id'];            // ID пользователя на которого назначен лид
    $type_of_deal = $output['type_of_deal'];                // Тип обращения
 //   $utm_source = $output['utm_source'];                    // UTM метка
 //   $source_description = $outpur['source_description'];    // Сквозная аналитика -> Описание источника

	// Валидация и формирование запроса
	if ($name != '' && $software_name != '' && ($email != '' && filter_var($email,FILTER_VALIDATE_EMAIL)) && $phone != '' && $file_name != '') {                           // Проверка на заполненность переменных

	    $queryTaskURL = "$queryURL/crm.lead.add.json"; 		                                                                // URL для создания лида
		    $queryData = http_build_query(array(
              "fields" => array(
                    "TITLE" => "Создание тестового лида в Битриксе ",							                            // Заголовок
                    "NAME" => $name,													                                    // Имя лида
                    "ASSIGNED_BY_ID" => filter_var($assigned_by_id, FILTER_SANITIZE_NUMBER_INT),							// На кого назначен лид(7064 - Бурмистрова, 22119 - Григорьев)
                    "COMMENTS" => "Имя файла - $file_name",	                                                                // Комментарий с указанием имени файла
                    "SOURCE_ID" => "UC_8J8W3J",													                            // Источник - Bimlib.pro						
                    "SOURCE_DESCRIPTION" => $transaction_id,										                        // Описание источника
                    "UF_CRM_1583361882" => filter_var($type_of_deal, FILTER_SANITIZE_NUMBER_INT),                           // Тип обращения(115299)
                    "UF_CRM_1557850596" => filter_var($software_name, FILTER_SANITIZE_NUMBER_INT),                          // Применяемые САПР (ArchiCAD - 9665, AutoCAD - 9663, Renga - 19917, Revit - 9667)
                    "UTM_SOURCE" => "bimlib.pro",                                                                           // UTM метка
                    "EMAIL" => array(															                            // Электронная почта
                        "n0" => array(	
                            "VALUE" => $email,										                                        // Адрес электронной почты
                            "VALUE_TYPE" => "Частный",											                            // Тип почты(после добавления будет отображен как "PERSONAL")
                        ),
                    ),  
                    "PHONE" => array(															                            // Телефон в Битрикс24 
                        "n0" => array(
                            "VALUE" =>  filter_var($phone, FILTER_SANITIZE_NUMBER_INT),										// Номер телефона
                            "VALUE_TYPE" => "MOBILE",											                            // Тип номера(после добавления будет отображен как "MOBILE", можно использовать русский язык)
                        ),
                    ),
                ),
                'params' => array("REGISTER_SONET_EVENT" => "Y")				// Y = произвести регистрацию события добавления лида в живой ленте. Дополнительно будет отправлено уведомление ответственному за лид.	
            ));    
        $EndMessage = "Лид успешно создан";                                      // Сообщение для отладки(в конце омжно будет удалить)
       
	} else {
        $queryTaskURL = "$queryURL/im.notify.personal.add.json"; 		                                                                          // URL для создания уведомления
        $queryData = http_build_query(array(
                'USER_ID' => 30541,                                                                                                               // Получатель уведомления
                'MESSAGE' => "Во время передачи данных с сайта BIMLIB произошла ошибка. Идентификатор транзакции $transaction_id",                // Текст уведомления
                ),            
        );
        $EndMessage = "Уведомление успешно отправлено";                                      // Сообщение для отладки(в конце омжно будет удалить)
		};
		
	// Отправляем запрос в Б24 и обрабатываем ответ
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => $queryTaskURL,
		CURLOPT_POSTFIELDS => $queryData,
	));
	$result = curl_exec($curl);
	curl_close($curl);
	$result = json_decode($result,1); 
 
	// Выводим ошибку в случае возникновения
	if(array_key_exists('error', $result))
	{      
		die("Ошибка при сохранении лида: ".$result['error_description']);
	}
	
	echo $EndMessage;
?>