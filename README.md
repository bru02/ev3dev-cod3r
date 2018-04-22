# ev3dev-cod3r
Code tool, that lets you program in your browser Python & Javascript, and it comes with a file manager.
Cod3r is  based on <a href="https://github.com/jbenech/gnikrap">Gnikrnap</a> , <a href="https://github.com/okanulas/Pathfind3r">Pathfind3r</a> & <a href="https://github.com/prasathmani/tinyfilemanager">Tiny File manager</a>
## Dependencies
1. Ev3 should be started using ev3dev debian linux distribution.
2. Local git should be set up on the ev3dev. There is a good tutorial about setting up the development environment at this address -> http://www.ev3dev.org/docs/tutorials/setting-up-python-pycharm/
3. Apache & PHP 5 (For the server)
4. Java for Javascript. <a href="https://ev3dev-lang-java.github.io/docs/support/getting_started/brick.html">Tutorial</a><br>
   If you are on a EV3 and it says in the installer that platform is unknown, then change installer.sh to <a href="./docs/install/installer.sh.txt">this</a> and vars.sh (in folder module) to <a href="./docs/install/vars.sh.txt">this</a> and run ``` sudo ./installer.sh help ```

## Permissions
Super user permisions are needed to run python scripts from php server.  

1. Open sudoers using command sudo visudo.
2. Add following entry under "# User privilege specification" after root: 'www-data ALL=(ALL) NOPASSWD:ALL'
3. ![visudo_image](https://github.com/okanulas/Pathfind3r/blob/master/images/visudo.png)
4. Hit Ctrl+X, Accept changes and quit.


## Installation

1. Install Ev3dev from http://www.ev3dev.org/docs/getting-started/ . Latest tested and working version is ev3dev-jessie-ev3-generic-2017-02-11
2. Follow instructions at http://www.ev3dev.org/docs/tutorials/connecting-to-ev3dev-with-ssh/ and connect to ev3 brick using terminal.
3. Run Below commands
	* sudo apt-get update
	* sudo apt-get dist-upgrade
	* sudo apt-get install apache2 php5 libapache2-mod-php5
	* sudo /etc/init.d/apache2 restart
	* sudo mkdir /var/www/html/cod3r/
	* sudo git clone https://github.com/bru02/ev3dev-cod3r.git /var/www/html/cod3r/
4. Open your browser and goto http://[ip_of_your_brick]/cod3r/py/test.php . If you see nothing install PHP again, if you see something (usually www-data) copy that.<br>
    We need that to add permissions to PHP to be able to create files.
	* sudo chown -R [String from browser] /home/robot
	* sudo chown -R [String from browser] /var/www/html/cod3r

5. Open your browser and goto http://[ip_of_your_brick]/cod3r/py/manage.php . You should see a list of files in the home/robot folder



