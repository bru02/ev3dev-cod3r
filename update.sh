#!/usr/bin/env bash
DIRECTORY="/var/www/html/cod3r"
if [ -d "$DIRECTORY" ]; then
sudo rm -R -f "$DIRECTORY"
    echo "Updating cod3r..."
else
    echo "Installing cod3r via git"
fi
sudo mkdir "$DIRECTORY"
sudo git clone https://github.com/bru02/ev3dev-cod3r.git "$DIRECTORY"
echo "Finished. Now goto http://IpOfYourBrick/cod3r to see cod3r."
