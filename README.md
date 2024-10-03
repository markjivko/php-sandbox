# PHP Sandbox

<p align="center">
   <img src="https://repository-images.githubusercontent.com/460528694/c41b4a49-c4de-42a4-a8ea-18a7f80bef0e"/>
</p>

This is a tiny PHP sandbox powered by Docker and [Ace editor](https://github.com/ajaxorg/ace).

Use it for quick tests, pair programming or even online interviews.

<a href="https://github.com/markjivko/php-sandbox/blob/main/assets/preview.gif">
   <img src="https://github.com/markjivko/php-sandbox/blob/main/assets/preview.gif?raw=true"/>
</a>

## How to install

1. First install the **LAMP** stack (Apache, MySQL, PHP)

```
sudo apt-get install lamp-server
```

2. Clone the repo and link `/var/www/html/` to `src/`

```
git clone https://github.com/markjivko/php-sandbox
sudo rm -rf /var/www/html
ln -s $(pwd)/php-sandbox/src /var/www/html
```

By default Apache runs as `www-data:www-data`, which means our php scripts have read-only access to the file system.

This is by design:
 * The only way to create new pages is to manually add text files in `/var/www/html/code/`
 * In order to make pages editable, manually change their permission to `666` (via cli or UI)

There is only 1 page available when you install this script, `index.txt`.
 * In order to view it, open a browser at `http://localhost/`
 * Make it editable with `chmod 666 /var/www/html/code/index.txt` or directly from your favorite file explorer
 
Page names contain only lower-case alpha-numeric characters and dashes (regex `[\w\-]{1,128}`).
Page URLs correspond to text files without the ".txt" extension.

3. Last but not least, [install **Docker**](https://docs.docker.com/engine/install/ubuntu/)

## Update

Updating is as simple as running the following commands:

```
git add -A
git reset --hard
git pull
```

Your work in `/var/www/html/code` is not affected.

## Security

The project is read-only for Apache with the exception of the text files you selected.

Your text files are executed with PHP only inside of a Docker container.

* the Docker container has read-only access to `/var/ww/html/code` only
* the script is killed automatically after 3 seconds
* output is limited to 512KB in length; OOM issues are prevented by forwarding `passthru` output to a custom output buffer handler
* total code size is limited to 512KB

Search functionality is missing by design. 
However, you could view all pages by running this PHP script:

```php
echo `ls -l /var/www/html/code`;
```

## Improvements

The current version uses a basic state machine to check for changes and regularly fetch updates. This model does not prevent race conditions. [diff-match-patch](https://github.com/google/diff-match-patch) is used to optimally update code changes for observers.

Websockets could be used to make the typing experience more fluid for all parties involved, however this approach is sensitive to network issues (lag, connection losses etc.)

The app is intentionally bare-bones; there are no users/roles/databases to worry about; you just manually add text files in `/var/www/html/code/` and set their permissions accordingly. 

> Simplicity is the ultimate sophistication.
> 
> -- Leonardo Da Vinci
