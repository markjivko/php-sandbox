<?php

/**
 * API class
 */
class API {

    // Request keys
    const REQUEST_METHOD = 'method';
    const REQUEST_PAGE   = 'page';
    const REQUEST_DATA   = 'data';
    
    // Result keys
    const RES_STATUS  = 'status';
    const RES_CONTENT = 'content';
    const RES_RESULT  = 'result';

    // Maximum output
    const MAX_OUTPUT = 4096;

    /**
     * Method name
     *
     * @var string|null
     */
    protected $_method = null;

    /**
     * API constructor
     */
    public function __construct() {
        ob_start();

        // Content and page
        $methodData = null;
        $methodPage = null;

        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
	        if (isset($input[self::REQUEST_METHOD])) {
	            $this->_method = 'api' . ucfirst(
                    strtolower(
	                    preg_replace(
	                        '%\W+%',
                            '',
                            $input[self::REQUEST_METHOD]
                        )
                    )
                );
                    
	            if (!method_exists($this, $this->_method)) {
	                $this->_method = null;
	            }
	        }

	        if (isset($input[self::REQUEST_DATA])) {
	        	$methodData = $input[self::REQUEST_DATA];
	        }

            if (isset($input[self::REQUEST_PAGE])) {
	        	$methodPage = strtolower(
                    preg_replace(
                        '%[^\w\-]+%', 
                        '', 
                        $input[self::REQUEST_PAGE]
                    )
                );
	        }
        }

        // Prepare the result & status
        $result  = null;
        $status  = true;
        
        // Valid method provided
        if (null !== $this->_method) {
       	    try {
                $result = call_user_func(
                    [$this, $this->_method], 
                    $methodData,
                    $methodPage
        	    );
       	    } catch (Exception $exc) {
       	        $result = $exc->getMessage();
                $status = false;
       	    }
        }
        
        echo json_encode([
            self::RES_STATUS  => $status,
            self::RES_CONTENT => ob_get_clean(),
            self::RES_RESULT  => $result
        ]);
    }

    /**
     * Write in the code.txt file
     *
     * @var string $content Content
     * @return string
     * @throws Exception
     */
    public function apiWrite($content = null, $page = null) {
    	if (!is_string($content)) {
    	    throw new Exception('Content must be of type string');
    	}

        // File not found - file creation is done server-side only
        if (!is_string($page)
            || !strlen($page) 
            || !is_file($filePath = __DIR__ . "/code/$page.txt")) {
            throw new Exception('Page not found');
        }
    	
    	if (!file_put_contents($filePath, $content)) {
    	    throw new Exception("You are not allowed to edit '$page'");
    	}
    }
    
    /**
     * Execute the code.txt file
     *
     * @var string $content (optional) Content
     * @return string
     * @throws Exception
     */
    public function apiExecute($content = null, $page = null) {
        $start = microtime(true);

        // Up-to-date content
        if (null !== $content) {
            $this->apiWrite($content, $page);
        }

        // File not found - file creation is done server-side only
        if (!is_string($page)
            || !strlen($page) 
            || !is_file(__DIR__ . "/code/$page.txt")) {
            throw new Exception('Page not found');
        }
        
    	// Prepare the command
    	exec(
    	    // Run PHP in Docker with 2CPUs quota; read-only volume; timeout after 3s with error code 2
    	    'docker run --cpus="2" --rm -v /var/www/html/code:/var/www/html/code:ro php:7.4-cli timeout -s 2 3 sh -c "php /var/www/html/code/' . $page . '.txt" 2>&1', 
    	    $output, 
    	    $resultCode
        );
        
        // Prepare the result
    	$result = trim(implode(PHP_EOL, $output));
    	$result = substr($result, 0, self::MAX_OUTPUT) . (strlen($result) > self::MAX_OUTPUT ? '...' : '');

        // Display the elapsed time; 400ms is docker startup
    	echo number_format((microtime(true) - $start) * 1000 - 400, 3);

    	// An error occured
    	if (0 !== $resultCode) {
    	    throw new Exception('[' . $resultCode . '] ' . (strlen($result) ? $result : 'Execution timeout'));
    	}
    	return $result;
    }

}

new API();
