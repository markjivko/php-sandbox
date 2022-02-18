<?php 
    // Trying to read inside the code repo
    if (preg_match('%^\/?code\/[\w\-]+\.txt$%i', $_SERVER['REQUEST_URI'])) {
        header('HTTP/1.0 404 Not Found', true, 404);
        exit();
    }

    // Prepare the page name
    $page = strtolower(
        preg_replace(
            '%[^\w\-]+%', 
            '', 
            $_SERVER['REQUEST_URI']
        )
    );

    // Check the file exists
    if (!is_file(__DIR__ . '/code/' . $page . '.txt')) {
        $page = '';
    }
?><!doctype html>
<!--
 * PHP Sandbox
 * 
 * @copyright  (c) 2022, Mark Jivko
 * @author     Mark Jivko https://markjivko.com
 * @package    markjivko.com
 * @license    GPL v3+, https://gnu.org/licenses/gpl-3.0.txt
-->
<html lang="en">
    <head>
    	<title>PHP Sandbox<?php echo (strlen($page) ? (' - ' . $page) : ' by Mark Jivko');?></title>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="/css/style.css">
        <link rel="icon" type="image/ico" href="/favicon.ico">
        <meta name="Author" content="Mark Jivko">
        <meta name="Description" content="Live coding tool">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="viewport" content="initial-scale=1.0, width=device-width">
        <?php if (strlen($page)):?>
            <script src="/js/ace/ace.js" charset="utf-8" defer></script>
            <script src="/js/ace/ext-language_tools.js" defer></script>
            <script src="/js/diff-match-patch.js" defer></script>
            <script src="/js/functions.js" defer></script>
        <?php endif;?>
    </head>
    <body>
        <div class="holder">
            <?php if (strlen($page)):?>
                <div data-role="editor" id="editor" data-page="<?php echo $page;?>"></div>
                <div data-role="output"></div>
                <div data-role="bar">
                	<button>&#9654; Run (Ctrl+R)</button>
                	<span>Running...</span>
                    <a data-role="repo" target="_blank" href="https://github.com/markjivko/php-sandbox">GitHub</a>
                </div>
            <?php else:?>
                <span data-role="empty">
                    <a target="_blank" href="https://github.com/markjivko/php-sandbox">PHP Sandbox on GitHub</a>
                </span>
            <?php endif;?>
        </div>
    </body>
</html>
