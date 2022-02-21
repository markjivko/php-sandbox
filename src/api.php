<?php

/**
 * API
 * 
 * @copyright  (c) 2022 Mark Jivko, https://github.com/markjivko/php-sandbox
 * @package    php-sandbox
 * @license    GPL v3+, https://gnu.org/licenses/gpl-3.0.txt
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

    // IO limits
    const MAX_INPUT  = 524288;
    const MAX_OUTPUT = 524288;

    // Docker limits
    const DOCKER_CPUS    = 2;
    const DOCKER_TIMEOUT = 3;

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
                            '%\W+%i',
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
                $methodData = substr($input[self::REQUEST_DATA], 0, self::MAX_INPUT);
            }

            if (isset($input[self::REQUEST_PAGE])) {
                $methodPage = strtolower(
					substr(
						preg_replace('%[^\w\-]+%i', '', $input[self::REQUEST_PAGE]), 
						0, 
						128
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
     * @var string $content Content - limited in length to self::MAX_INPUT
     * @var string $page    Page - limited in length to 128; pre-sanitized
     * @throws Exception
     */
    public function apiWrite($content = null, $page = null) {
        if (!is_string($content)) {
            throw new Exception('Content is mandatory');
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
     * Execute the code.txt file; limit output size and prevent OOM attack vectors
     *
     * @var string $content (optional) Content - limited in length to self::MAX_INPUT
     * @var string $page    Page - limited in length to 128; pre-sanitized
     * @return string Output limited in length to self::MAX_OUTPUT
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

        // Start the buffer
        $result = '';
        $resultOverflow = false;

        // Custom output buffer
        ob_start(
            function($chunk) use(&$result, &$resultOverflow) {
                if (strlen($result) < self::MAX_OUTPUT) {
                    $result .= $chunk;
                } elseif (!$resultOverflow) {
                    $resultOverflow = true;
                    $result .= PHP_EOL . '[-- Output limit reached --]';
                }
                
                // Prevent extra memory usage
                return '';
            }, 
            1024
        );

        // Run PHP inside a read-only Docker container; timeout returns error code 2
        // Output is gradually stored in $result until self::MAX_OUTPUT, then it is discarded
        passthru(
            'docker run'
                . ' --cpus="' . self::DOCKER_CPUS . '"'
                . ' --rm -v /var/www/html/code:/var/www/html/code:ro'
                    . ' php:7.4-cli'
                    . ' timeout -s 2 ' . self::DOCKER_TIMEOUT 
                    . ' sh -c "php /var/www/html/code/' . $page . '.txt" 2>&1', 
            $resultCode
        );

        // Stop the buffer
        ob_end_clean();

        // Remove optional new-line characters
        $result = trim($result);

        // Display the elapsed time - includes Docker startup
        echo number_format((microtime(true) - $start) * 1000, 3);

        // An error occured
        if (0 !== $resultCode) {
            throw new Exception(
                '[Error code ' . $resultCode . '] ' 
                . (
                    strlen($result) 
                        ? $result 
                        : 'Execution timeout'
                )
            );
        }
        
        // Pass the result
        return $result;
    }

}

new API();