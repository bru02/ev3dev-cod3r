#!/usr/bin/env bash
MODULE_FOLDER="/var/www/html/"
ROOT_URL="https://raw.githubusercontent.com/bru02/ev3dev-cod3r/installer/"

# $1 => module name
function download_module() {
    pushd "$MODULE_FOLDER" >/dev/null
    wget -qN "$ROOT_URL/$1.sh"
    popd  >/dev/null
}

# $1 => module name
function run_module() {
    createHeader "$1"
    download_module "$1"
    source "$MODULE_FOLDER/$1.sh"
}
function show_help() {
cat << EOF
Usage: ${0##*/} [-server] [-help] [-update]
Do stuff with FILE and write the result to standard output. With no FILE
or when FILE is -, read standard input.
    -update  Update the brick (takes a long time)
    -help    display this help and exit
    -server  installs PHP and Apache
    
EOF
}
function update() {
    sudo apt-get update
    sudo apt-get dist-upgrade
}
function install_server() {
    if ! [ -x "$(command -v php)" ]; then
        sudo apt-get install apache2 php5 libapache2-mod-php5
        sudo /etc/init.d/apache2 restart
    else
        echo 'PHP is already installed!';
    fi
    sudo chown -R www-data /var/www/html/cod3r
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
  exit 1
fi
if ! [ -x "$(command -v git)" ]; then
  echo 'Error: git is not installed.' >&2
  exit 1
fi



OPTIND=1
# Resetting OPTIND is necessary if getopts was used previously in the script.
# It is a good idea to make OPTIND local if you process options in a function.

while getopts hvf: opt; do
    case $opt in
        help)
            show_help
            exit 0
            ;;
        update)
            update
            ;;
        server)
            install_server  
            ;;
        *)
            show_help >&2
            exit 1
            ;;
    esac
done
shift "$((OPTIND-1))"

run_module update

exit 0