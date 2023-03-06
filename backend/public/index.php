<?php
  use Psr\Http\Message\ResponseInterface as Response;
  use Psr\Http\Message\ServerRequestInterface as Request;
  use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();
//$app->setBasePath("/emfy/backend/public");
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/{leadId}', function (Request $request, Response $response, array $args) {
    $leadId = $args['leadId'];
    $token_file = 'token.txt';
    $client_id = 'bca01eab-b26a-4ad5-8712-6740f17db1ee';
    $client_secret = 'U1chZV5zTedRwSYxB68YUk9qjOzMxftKypOmehGS86nhh8Wt2Btp34QAHy4o8nhI';
    $subdomain = 'falc0shka'; //Поддомен нужного аккаунта
    $link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса
    
    /** Соберем данные для запроса */

    $tokenData = file_get_contents($token_file);
    $data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'refresh_token',
        'refresh_token' => $tokenData,
        'redirect_uri' => 'https://example.com',
    ];
   
    /**
     * Нам необходимо инициировать запрос к серверу.
     * Воспользуемся библиотекой cURL (поставляется в составе PHP).
     * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
     */
    $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
    /** Устанавливаем необходимые опции для сеанса cURL  */
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
    curl_setopt($curl,CURLOPT_URL, $link);
    curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/x-www-form-urlencoded']);
    curl_setopt($curl,CURLOPT_HEADER, false);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 0);
    $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);
    /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
    $code = (int)$code;
    $errors = [
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    ];
    
    try
    {
        /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
        if ($code < 200 || $code > 204) {
            throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
        }
    }
    catch(Exception $e)
    {
        die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
    }
    
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $result = json_decode($out, true);
    
    $access_token = $result['access_token']; //Access токен
    $refresh_token = $result['refresh_token']; //Refresh токен
    $token_type = $result['token_type']; //Тип токена
    $expires_in = $result['expires_in']; //Через сколько действие токена истекает


    $f = fopen($token_file, 'w');
    fwrite($f, $refresh_token);
    fclose($f);




    // Формируем основные запросы


    $headers = [
//        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token,
    ];

    // Запрос списка товаров

    //$method = "/api/v4/catalogs/6936/elements";
    $method = "/api/v4/leads/$leadId"; //3608000

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_URL, "https://$subdomain.amocrm.ru".$method.'?with=catalog_elements');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $code = (int) $code;

    if ($code < 200 || $code > 204) die( "Error $code. " . (isset($errors[$code]) ? $errors[$code] : 'Undefined error') );

    $lead = json_decode($out, true);

    $leadProducts = [];
    foreach ($lead["_embedded"]["catalog_elements"] as $product) {
      array_push($leadProducts, [$product["id"],$product["metadata"]["quantity"]]);
    }

    // Запрос списка товаров в сделке

    $method = "/api/v4/catalogs/6936/elements";

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_URL, "https://$subdomain.amocrm.ru".$method);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $code = (int) $code;

    if ($code < 200 || $code > 204) die( "Error $code. " . (isset($errors[$code]) ? $errors[$code] : 'Undefined error') );

    $products = json_decode($out, true);
    
    $result = '<table>
    <tr>
      <th>Название</th>
      <th>Кол-во</th>
    </tr>';
    foreach ($leadProducts as &$leadProduct) {
      $leadProduct[0] = array_values(array_filter($products["_embedded"]["elements"], fn($product) => $product["id"] === $leadProduct[0]))[0]["name"];
      $result .= "<tr><td>$leadProduct[0]</td><td>$leadProduct[1]</td></tr>";
    }
    $result .= '</table>';

    $response->getBody()->write($result);
   
    return $response
      ->withHeader('Access-Control-Allow-Origin', '*')
      ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});


// Run app
$app->run();

