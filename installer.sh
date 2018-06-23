#!/usr/bin/env bash
MODULE_FOLDER="/var/www/html/"
ROOT_URL="https://raw.githubusercontent.com/bru02/ev3dev-cod3r/installer"

# $1 => module name
function download_module() {
    pushd "$MODULE_FOLDER" >/dev/null
    wget -qN "$ROOT_URL/$1.sh"
    popd  >/dev/null
}

# $1 => module name
function run_module() {
    download_module "$1"
    source "$MODULE_FOLDER/$1.sh"
}
function show_help() {
cat << EOF
Usage: ${0##*/} [-php] [-help] [-update]
Install cod3r.
    -update  Update the brick (takes a long time)
    -help    display this help and exit
    -php  installs PHP and Apache
    
EOF
}
function update() {
    sudo apt-get update
    sudo apt-get dist-upgrade
}
function confirm() {
    # call with a prompt string or use a default
    read -r -p "${1:-Are you sure? [y/N]} " response
    case "$response" in
        [yY][eE][sS]|[yY]) 
            true
            ;;
        *)
            false
            ;;
    esac
}
function install_server() {
    if ! [ -x "$(command -v php)" ]; then
        update
        sudo apt-get install apache2 php5 libapache2-mod-php5
        sudo /etc/init.d/apache2 restart
    else
        echo 'PHP is already installed!';
    fi
}
echo
echo "##############################"
echo "# Cod3r Installer            #"
echo "##############################"
echo "# Last update: 2018/06/18    #"
echo "##############################"
echo
if ! [ -x "$(command -v wget)" ]; then
  echo 'Error: wget is not installed.' >&2
  if [ confirm "Do you want to install it?" ]
  then
        sudo apt-get wget
  else
        echo "You are screwed"
        exit 1
  fi
fi
if ! [ -x "$(command -v git)" ]; then
  echo 'Error: git is not installed.' >&2
  if [ confirm "Do you want to install it?" ]
  then
        sudo apt-get git
  else
        echo "You are screwed"
        exit 1
  fi
fi



OPTIND=1
# Resetting OPTIND is necessary if getopts was used previously in the script.
# It is a good idea to make OPTIND local if you process options in a function.

if [ "$1" == "-h" ] || [ "$1" == "-help" ] ; then
    show_help
fi
shift "$((OPTIND-1))"

run_module update
sudo chown -R www-data /var/www/html/cod3r

exit 0