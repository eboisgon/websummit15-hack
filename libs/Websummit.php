<?php



class Websummit
{
    
    # Mode debug ? 0 : none; 1 : errors only; 2 : all
    var $debug = 0;

    var $authToken = '';
    
    var $call_url="https://my.websummit.net/v1/conferences/ws15/info/startups";
    
    # Constructor function
    public function __construct($authToken="")
    {
        if ($authToken) {
            $this->$authToken = $authToken;
        }

    }

    public function __call($resource, $args)
    {
        # Parameters array
        $params  = (sizeof($args) > 0) ? $args[0] : array();

        # Request method, GET by default
        if (isset($params["method"]))
        {
            $request = strtoupper($params["method"]);
            unset($params['method']);
        }
        else
        {
            $request = 'GET';
        }

        # Request ID, empty by default
        $id = isset($params["ID"]) ? $params["ID"] : '';

        /*
            Using SendAPI without the "to" parameter but with "cc" AND/OR "bcc"
            Our API needs the "to" parameter filled to send email
            We give it a default value with an email @example.org. See http://en.wikipedia.org/wiki/Example.com
        */
        if ($resource == "sendEmail" && (empty($params["to"]) && (!empty($params["cc"]) || !empty($params["bcc"])))) {
            $params["to"] = "mailjet@example.org";
        }

        if ($id == '')
        {
            # Request Unique field, empty by default
            $unique  = isset($params["unique"]) ? $params["unique"] : '';
            unset($params["unique"]);
            # Make request
            $result = $this->sendRequest($resource, $params, $request, $unique);
        }
        else
        {
            # Make request
            $result = $this->sendRequest($resource, $params, $request, $id);
        }

        # Return result
        $return = ($result === true) ? $this->_response : false;
        if ($this->debug == 2 || ($this->debug == 1 && $return == false)) {
            $this->debug();
        }

        return $return;
    }

    /**
     *
     *  @param string   $method         REST or DATA
     *  @param string   $resourceBase   Base resource
     *  @param int      $resourceID     Base resource ID
     *  @param string   $action         Action on resource
     *
     *  @return string Returns the call's url.
     */
    private function makeUrl($method, $resourceBase, $resourceID, $action)
    {
        return $this->apiUrl.'/'.$method.'/'.$resourceBase.'/'.$resourceID.'/'.strtolower($action);
    }

    /**
     *
     *  @param string   $method         REST or DATA
     *  @param string   $resourceBase   Base resource
     *  @param int      $resourceID     Base resource ID
     *  @param string   $resource       The whole resource, before parsing
     *
     *  @return string Returns the call's url.
     */
    private function makeUrlFromFilter($method, $resourceBase, $resourceID, $resource)
    {
        $matches = array();
        preg_match('/'.$resourceBase.'([a-zA-Z]+)/', $resource, $matches);

        $action = $matches[1];
        return $this->makeUrl($method, $resourceBase, $resourceID, $action);
    }

    public function requestUrlBuilder($resource, $params = array(), $request, $id)
    {
        

        if ($request == "GET" || $request == "POST") {
            if (count($params) > 0)
            {
                $this->call_url .= '?';

                foreach ($params as $key => $value) {
                    // In a GET request, put an underscore char in front of params to avoid it being treated as a filter
                    $firstChar = substr($key, 0, -(strlen($key) - 1));

                    if ($request == "GET") {
                        $okFirstChar = ($firstChar != "_");
                        $queryStringKey = $key;
                    }
                    else {
                        $okFirstChar = ($firstChar == "_");
                        $queryStringKey = substr($key, 1);
                    }

                    if ($okFirstChar && ($key != "ID"))
                    {
                        $query_string[$queryStringKey] = $queryStringKey . '=' . urlencode($value);
                        $this->call_url .= $query_string[$queryStringKey] . '&';
                    }
                }

                $this->call_url = substr($this->call_url, 0, -1);
            }
        }

        return $this->call_url;
    }

    public function sendRequest($resource = false, $params = array(), $request = "GET", $id = '')
    {
        # Method
        $this->_method  = $resource;
        $this->_request = $request;

        # Build request URL
        $url = $this->requestUrlBuilder($resource, $params, $request, $id);
	print($url);
	//exit;
        # Set up and execute the curl process
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.81 Safari/537.36');
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 2);
        //curl_setopt($curl_handle, CURLOPT_USERPWD, $this->apiKey . ':' . $this->secretKey);

        $this->_request_post = false;

        

        $buffer = curl_exec($curl_handle);
	print($buffer);

        if ($this->debug == 2) {
            var_dump($buffer);
        }

        # Response code
        $this->_response_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
print 
        # Close curl process
        curl_close($curl_handle);

        # Return response
        if (($this->_response_code == 200) && ($resource == "getHTMLbody")) {
            $this->_response = $buffer;
        }
        else
        {
            /*
             *  This prevents the rounding error on 32 bits systems with PHP version >= 5.4
             */
            if (defined('JSON_BIGINT_AS_STRING'))
            {
                $this->_response = json_decode($buffer, true, 512, JSON_BIGINT_AS_STRING);
            }
            else
            {   // PHP v <= 5.3.* doens't support the fourth parameter of json_decode
                $this->_response = json_decode($buffer, true, 512);
            }
        }

        if ($request == 'POST') {
            return ($this->_response_code == 201 || $this->_response_code == 200) ? true : false;
        }
        if ($request == 'DELETE') {
            return ($this->_response_code == 204) ? true : false;
        }
        return ($this->_response_code == 200) ? true : false;
    }

 
}
