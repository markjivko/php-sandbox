# PHP Sandbox

<p align="center">
   <a href="https://github.com/markjivko/php-sandbox/blob/main/assets/preview.gif">
      <img src="https://repository-images.githubusercontent.com/460528694/c41b4a49-c4de-42a4-a8ea-18a7f80bef0e"/>
   </a>
</p>

This is a tiny PHP sandbox powered by Docker and Ace editor.

<a href="https://github.com/markjivko/php-sandbox/blob/main/assets/preview.gif">
   <img src="https://github.com/markjivko/php-sandbox/blob/main/assets/preview.gif?raw=true"/>
</a>

Use it for quick tests, pair programming or even online interviews.

## How to install

1. First install the **LAMP** stack (Apache, MySQL, PHP)

```
sudo apt-get install lamp-server
```

2. Clone the repo and copy the contents of **src** to **/var/www/html**

```
git clone https://github.com/markjivko/php-sandbox
sudo cp -R ./src /var/www/html
sudo chown ${USER}:${USER} /var/www/html
sudo chmod -R 644 /var/www/html
```

By default Apache runs as `www-data:www-data`, which means our php scripts have read-only access to the file system.

This is by design:
 * The only way to create new pages is to manually add text files in `/var/www/html/code/`
 * In order to make pages editable, manually change their permission to `666` (via cli or UI)

There is only 1 page available when you install this script, `two-sum.txt`;
 * In order to view it, open a browser at `http://localhost/two-sum/`
 * Make it editable with `sudo chmod 666 /var/www/html/code/two-sum.txt` or directly from your favorite file explorer

3. Last but not least, [install **Docker**](https://docs.docker.com/engine/install/ubuntu/)

## Security

The project is read-only for Apache with the expection of text files you selected.

Your text files are executed with PHP only inside of a Docker container.
* the container has read-only access to `/var/ww/html/code` only
* the script is killed automatically after 3 seconds

You can safely run this script on your laptop (with correct port forwarding).
