# ev3dev-cod3r
Code tool, that lets you program in your browser Python & Javascript, and it comes with a file manager.
Cod3r is  based on <a href="https://github.com/okanulas/Pathfind3r">Pathfind3r</a> & <a href="https://github.com/prasathmani/tinyfilemanager">Tiny File manager</a>
## Dependencies
1. Ev3 should be started using ev3dev debian linux distribution (https://www.ev3dev.org/docs/getting-started/).
2. Local git should be set up on the ev3dev. There is a good tutorial about setting up the development environment at this address -> http://www.ev3dev.org/docs/tutorials/setting-up-python-pycharm/

## Permissions
Super user permisions are needed to run python scripts from php server.  

1. Open sudoers using command sudo visudo.
2. Add following entry under "# User privilege specification" after root: 'www-data ALL=(ALL) NOPASSWD:ALL'
3. ![visudo_image](https://github.com/okanulas/Pathfind3r/blob/master/images/visudo.png)
4. Hit Ctrl+X, Accept changes and quit.

# Installing
Before you start make sure you have a local <a href='http://www.ev3dev.org/docs/tutorials/setting-up-python-pycharm/'>Git</a> installed!

### Run the following commands:
 ```bash 
 wget -N https://raw.githubusercontent.com/bru02/ev3dev-cod3r/installer/installer.sh
 chmod +x installer.sh
 sudo bash ./installer.sh -help
 sudo bash ./installer.sh -server -update

 ```
4. Open your browser and goto http://[ip_of_your_brick]/cod3r/test.php . If you see nothing, install PHP again by running ` sudo bash ./installer.sh -server`<br>
# Updating
Run one of the following commands:
```bash
 sudo bash ./installer.sh
```
or
```bash
 sudo bash /var/www/html/update.sh
```


