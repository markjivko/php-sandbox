/**
 * PHP Sandbox
 * 
 * @copyright  (c) 2022, Mark Jivko
 * @author     Mark Jivko https://markjivko.com
 * @package    markjivko.com
 * @license    GPL v3+, https://gnu.org/licenses/gpl-3.0.txt
 */  
document.addEventListener("DOMContentLoaded", function() {
    // Ace Editor: Range
    var range = ace.require('ace/range').Range;

    // Ace Editor
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.setOptions({
        fontSize: 16,
        useSoftTabs: 4,
        wrap: 'free',
        keyboardHandler: 'ace/keyboard/sublime',
        enableBasicAutocompletion: true,
        enableSnippets: true,
        enableLiveAutocompletion: true
    });
    editor.session.setUseWrapMode(false);
    editor.session.setMode({path:"ace/mode/php", inline:false});

    // Page
    var editorPage = document.querySelector('[data-role="editor"]').getAttribute('data-page');

    // Worker
    var worker = {
        config: {
            delayStart: 100,
            delayEnd: 1000,
            delayStep: 20,
        },
        diff: new diff_match_patch(),
        executing: false,
        output: document.querySelector('[data-role="output"]'),
        bar: document.querySelector('[data-role="bar"]'),
        cache: null,
        htmlentities: (str) => {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        },
        log: (content, status, time) => {
            if ("undefined" === typeof status) {
                status = true;
            }

            // Write to console
            worker.output.innerHTML = `<span><i>linux@mint</i>:<b>~${("undefined" !== typeof time ? (time + 'ms') : '')}</b>$`
                    + ` <span class="${(status ? "success":"error")}">${worker.htmlentities(content)}</span>`
                + "</span>\n"
                + worker.output.innerHTML;
        },
        readTimeout: null,
        read: () => {
            fetch(`/code/${editorPage}.txt`, {
                method: 'GET',
                cache: 'no-cache',
                redirect: 'follow',
                headers: {
                    'Content-Type': 'text/plain'
                }
            }).then((response) => {
               if (!response.ok) {
                    worker.log(`You are not allowed to access '${editorPage}'`, false);
               }
               return response.ok ? response.text() : false;
            }).then((text) => {
                if ("string" === typeof text) {
                    if (text !== worker.cache) {
                        if (null !== text && null !== worker.cache) {
                            var diff = worker.diff.diff_main(worker.cache, text, true);
                            var offset = 0;
                            diff.forEach(function(chunk) {
                                var text = chunk[1];
                                switch (chunk[0]) {
                                    case 0:
                                        offset += text.length;
                                        break;

                                    case 1:
                                        editor.session.doc.insert(
                                            editor.session.doc.indexToPosition(offset), 
                                            text
                                        );
                                        offset += text.length;
                                        break;

                                    case -1:
                                        editor.session.doc.remove(range.fromPoints(
                                            editor.session.doc.indexToPosition(offset),
                                            editor.session.doc.indexToPosition(offset + text.length)
                                        ));
                                        break;
                                }
                            });
                        } else {
                            // Update document
                            editor.session.doc.setValue(text);
                        }

                        // Update local text cache
                        worker.cache = text;
                        
                        // Reset the timeout
                        worker.readTimeout = worker.config.delayStart;
                    }
                    
                    null === worker.readTimeout && (worker.readTimeout = worker.config.delayStart);
                    worker.readTimeout += worker.config.delayStep;
                    if (worker.readTimeout >= worker.config.delayEnd) {
                        worker.readTimeout = worker.config.delayEnd;
                    }
                    
                    window.setTimeout(worker.next, worker.readTimeout);
                }                
            });
        },
        write: () => {
            worker.cache = editor.getValue();
            fetch('/api.php', {
                method: 'POST',
                mode: 'no-cors',
                cache: 'no-cache',
                redirect: 'follow',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    'method': 'write', 
                    'page': editorPage,
                    'data': editor.getValue()
                })
            }).then((response) => {
               return response.json();
            }).then((json) => {
                !json.status && worker.log(json.result, false);
                window.setTimeout(worker.next, 50);
            });
        },
        next: () => {
            // Init or no local changes
            if (null === worker.cache || worker.cache === editor.getValue()) {
                worker.read();
            } else {
                worker.write();
            }
        },
        execute: () => {
            if (!worker.executing) {
                !worker.bar.classList.contains('loading') && worker.bar.classList.add('loading');
                worker.executing = true;
                
                // Prepare the source (outside of the read/write loop)
                var executeData = null;
                if (null !== worker.cache && worker.cache !== editor.getValue()) {
                    worker.cache = editor.getValue();
                    executeData = worker.cache
                }
                
                fetch('/api.php', {
                    method: 'POST',
                    mode: 'no-cors',
                    cache: 'no-cache',
                    redirect: 'follow',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        'method': 'execute', 
                        'page': editorPage,
                        'data': executeData
                    })
                }).then((response) => {
                   return response.json();
                }).then((json) => {
                    worker.log(json.result, json.status, json.content);
                    worker.bar.classList.contains('loading') && worker.bar.classList.remove('loading');
                    worker.executing = false;
                });
            }
        },
        start: () => {
            worker.log('A dockerized collaborative PHP sandbox my Mark Jivko');

            // Run
            document.querySelector('[data-role="bar"] button').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                worker.execute();
            }, false);

            // Key bindings
            document.addEventListener("keydown", (e) => {
                if (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey) {
                    if (e.key.match(/[sr]/gi)) {
                        e.preventDefault();
                    }
                    
                    switch (e.key) {
                        case 'r':
                            worker.execute();
                            break;
                    }
                }
            }, false);

            // No context menu
            editor.container.addEventListener("contextmenu", (e) => {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }, false);

            // Next step
            worker.next();
        }
    };

    // Start the state machine
    worker.start();
});

