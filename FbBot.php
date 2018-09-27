<?php

class FbBot
{
    private $hubVerifyToken = null;
    private $accessToken = null;
    private $tokken = false;
    protected $client = null;
    protected $pdo = null;

    function __construct()
    {
    }

    public function setHubVerifyToken($value)
    {
        $this->hubVerifyToken = $value;
    }

    public function setAccessToken($value)
    {
        $this->accessToken = $value;
    }

    public function verifyTokken($hub_verify_token, $challange)
    {
        try
        {
            if ($hub_verify_token === $this->hubVerifyToken)
            {
                echo $challange;
            }
            else
            {
                throw new Exception("Tokken not verified");
            }
        }
        catch(Exception $ex)
        {
            return $ex->getMessage();
        }
    }

    public function readMessage($input)
    {
    	error_log(print_r($input,true));
        try
        {
            $payloads = null;
            $senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
            $messageText = $input['entry'][0]['messaging'][0]['message']['text'];
            $postback = $input['entry'][0]['messaging'][0]['postback'];
            $loctitle = $input['entry'][0]['messaging'][0]['message']['attachments'][0]['title'];
            $quickReply = $input['entry'][0]['messaging'][0]['message']['quick_reply'];
            $customer_chat = $input['entry'][0]['messaging'][0]['referral']['source'];
            $image = $loctitle = $input['entry'][0]['messaging'][0]['message']['attachments'][0]['type'];
            if (!empty($postback))
            {
                $payloads = $input['entry'][0]['messaging'][0]['postback']['payload'];
                return ['senderid' => $senderId, 'message' => $payloads];
            }
            if (!empty($quickReply))
            {
                $payloads =$input['entry'][0]['messaging'][0]['message']['quick_reply']['payload'];
                return ['senderid' => $senderId, 'message' => $payloads];
            }
            if(!empty($image)){
                 return ['senderid' => $senderId, 'message' => 'emoji'];
            }
            if(!empty($customer_chat)){
                 return ['senderid' => $senderId, 'message' => 'hola'];
            }
            return ['senderid' => $senderId, 'message' => $messageText];
        }
        catch(Exception $ex)
        {
            return $ex->getMessage();
        }
    }

    public function sendMessage($input)
    {

        try
        {
            $url = "https://graph.facebook.com/v2.6/me/messages";
            
            $messageText = strtolower($input['message']);
            $signos = array(',','.','?','¿','!','¡','-');
            $messageText = str_replace( $signos, ' ', $messageText);
            $senderId = $input['senderid'];
            $msgarray = explode(' ', $messageText);

            $response = null;
            $header = array(
                'content-type' => 'application/json'
            );

           /* if(!empty($messageText)){
	            //Marcar mensaje como leído
	            //$response = ['recipient' => ['id' => $senderId], 'sender_action' => "mark_seen", 'access_token' => $this->accessToken];
	           // $response = $client->post($url, ['query' => $response, 'headers' => $header]);
	           // sleep(0.5);
				//Acción  de que esta escribiendo.             
	         $response = ['recipient' => ['id' => $senderId], 'sender_action' => "typing_on", 'access_token' => $this->accessToken];
	            $response = $client->post($url, ['query' => $response, 'headers' => $header]);
	            sleep(0.5);
	         }*/


             if (array_intersect(array('hola','buenas','comenzar','empezar'), $msgarray))
            {

                //Obtener nombre y apellido del usuario
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v2.6/'.$input['senderid'].'?fields=first_name,last_name&access_token='.$this->accessToken);
                $result = curl_exec($ch);
                curl_close($ch);
                $datos = json_decode($result);

                $jsonData = '{
                     "recipient":{
                     "id": "'.$senderId.'"
                     },
                     "message":{
                     "text":"Hola '.$datos->first_name.'!"
                     }
                    }';
                $this->send($jsonData);
                //$answer = ["text" => "Hola ".$datos->first_name."! ¿Cómo te encuentras el día de hoy?", "quick_replies" => [["content_type" => "text", "title" => "Muy bien!","payload" => "bien" ],["content_type" => "text", "title" => "Meeeh", "payload" => "bien" ]]];
            }elseif (!empty($messageText)){
                 //$answer = ["text" => 'Una disculpa, no me queda muy claro lo que necesitas, por favor selecciona una de las siguientes opciones o “Hablar con una persona” para contactar a soporte.', "quick_replies" => [["content_type" => "text", "title" => "Hablar con persona","payload" => "humano" ],["content_type" => "text", "title" => "Quiero registrarme","payload" => "registro" ],["content_type" => "text", "title" => "¿Cuánto cuesta?", "payload" => "costo"],["content_type" => "text", "title" => "¿Qué ofrecen?", "payload" => "funcionalidades"]]];
              $jsonData = '{
                     "recipient":{
                     "id": "'.$senderId.'"
                     },
                     "message":{
                     "text":"No entiendo lo que dices! Por favor intenta de nuevo"
                     }
                    }';
                $this->send($jsonData);
                error_log(print_r($response,true));
            }            
           return true;
        }
        catch(RequestException $e)
        {
            $response = json_decode($e->getResponse()->getBody(true)->getContents());
           error_log($response);
            return $response;
        }
    }
 //funcion para enviar mensajes a la api 
    public function send($jsonData){
        $url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$this->$accessToken;
        $ch = curl_init($url);
        //Tell cURL that we want to send a POST request.
        curl_setopt($ch, CURLOPT_POST, 1);
        //Attach our encoded JSON string to the POST fields.
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));
        //Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
         curl_close($ch);
         return $result;
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array(‘Content-Type: application/x-www-form-urlencoded’));
}

}
?>
