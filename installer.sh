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
    local=$(<"$MODULE_FOLDER/$1.sh")
    git=$(wget "$MODULE_FOLDER/$1.sh" -q -O -)
    if [ "$git" != "$local" ] || [ ! -e "$MODULE_FOLDER/$1.sh" ] ; then
        download_module "$1"
    fi
    source "$MODULE_FOLDER/$1.sh"
}
function show_help() {
    cat << EOF
    Usage: ${0##*/} [-php] [-help] [-update]
    Install cod3r.
        -update -u Updates and upgrades the brick (takes a long time)
        -help -h   Display this help and exit
        -php -p    Installs PHP and Apache
    
EOF
}
function updateBrick() {
    if confirm "Did you upgrade your brick?"; then
        echo ""
    else
        echo "Upgrading brick..."
        sudo apt-get update
        sudo apt-get dist-upgrade
        echo "Done"
    fi
}
function confirm() {
    # call with a prompt string or use a default
    read -r -p "${1:-Are you sure? [y/N]} " response
    case "$response" in
        [yY][eE][sS]|[yY]) 
            return 0
            ;;
        *)
            return 255 # Cause i shouldnt 
            ;;
    esac
}
function install_server() {
    if ! [ -x "$(command -v php)" ]; then
        echo 'Error: PHP is not installed.' >&2
        if confirm "Do you want to install it?"; then
             update
             echo "Installing Apache2 and PHP5..."
             sudo apt-get install apache2 php5 libapache2-mod-php5
             sudo /etc/init.d/apache2 restart
             echo "Done"
         else
               echo "You are screwed"
               exit 1
         fi
    fi
}
echo
echo "##############################"
echo "# Cod3r Installer            #"
echo "##############################"
echo "# Last update: 2018/06/24    #"
echo "##############################"
echo
if ! [ -x "$(command -v wget)" ]; then
  echo 'Error: wget is not installed.' >&2
  if confirm "Do you want to install it?"; then
        sudo apt-get wget
  else
        echo "You are screwed"
        exit 1
  fi
fi
if ! [ -x "$(command -v git)" ]; then
  echo 'Error: git is not installed.' >&2
  if confirm "Do you want to install it?"; then
        sudo apt-get git
  else
        echo "You are screwed"
        exit 1
  fi
fi



if [ "$1" == "-h" ] || [ "$1" == "-help" ] ; then
    show_help
fi

if [ "$1" == "-u" ] || [ "$1" == "-update" ] ; then
    updateBrick
fi

if ! [ -x "$(command -v php)" ] || [ "$1" == "-p" ] || [ "$1" == "-php" ] ; then
    install_server
fi
run_module update
sudo chown -R www-data /var/www/html/cod3r

exit 0