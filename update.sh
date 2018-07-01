#!/usr/bin/env bash
MODULE_FOLDER="/home/robot"
ROOT_URL="https://raw.githubusercontent.com/bru02/ev3dev-cod3r/installer"
DIRECTORY="/var/www/html/cod3r"

# $1 => module name
function download_module() {
    pushd "$MODULE_FOLDER" >/dev/null
    wget -qN "$ROOT_URL/$1.sh"
    popd  >/dev/null
}
local=$(<"$MODULE_FOLDER/installer.sh")
git=$(wget "$MODULE_FOLDER/installer.sh" -q -O -)
if [ "$git" != "$local" ] ; then
    echo "Updating installer..."
    download_module "installer"
    echo "Done"
fi
source "$MODULE_FOLDER/$1.sh"

if [ -d "$DIRECTORY" ]; then
sudo rm -R -f "$DIRECTORY"
    echo "Updating cod3r..."
else
    echo "Installing cod3r..."
fi
sudo mkdir "$DIRECTORY"
sudo git clone https://github.com/bru02/ev3dev-cod3r.git "$DIRECTORY"
ip=$(ip addr | grep 'state UP' -A2 | tail -n1 | awk '{print $2}' | cut -f1  -d'/')
echo "Finished. Now goto http://$ip/cod3r to see cod3r."
