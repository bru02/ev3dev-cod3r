# ev3dev-cod3r
Code tool, that lets you program in your browser Python & Javascript, and it comes with a file manager & shell.
Cod3r is  based on <a href="https://github.com/jbenech/gnikrap">Gnikrap</a>, <a href="https://github.com/joni2back/angular-filemanager">Angular Filemanager</a>, <a href="https://github.com/RealTimeWeb/blockpy">Blockpy</a> & <a href="https://github.com/paradoxxxzero/butterfly">Butterfly</a>
## Dependencies
1. Ev3 should be started using ev3dev debian linux distribution (https://www.ev3dev.org/docs/getting-started/).
2. Local git should be set up on the ev3dev. There is a good tutorial about setting up the development environment at this address -> http://www.ev3dev.org/docs/tutorials/setting-up-python-pycharm/

### Run the following commands:
 ```bash 
 wget -N https://raw.githubusercontent.com/bru02/ev3dev-cod3r/installer/installer.sh
 chmod +x installer.sh
 sudo bash ./installer.sh -help
 sudo bash ./installer.sh -server -update

 ```
4. Open your browser and goto http://[ip_of_your_brick]/ .<br>
# Updating
Run one of the following commands:
```bash
 sudo bash ./installer.sh
```
or
```bash
 sudo bash /cod3r/update.sh
```