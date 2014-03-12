#!/bin/bash

CurVersion="0.1"
Module="procEye"

# We want to publish this into the Sming Lib Directory; so:
InstallDir="/var/lib/sming/standard/"

echo "Installing $Module into $InstallDir"

ThisDir=`pwd`
echo "Creating directory.. $InstallDir"
mkdir -p $InstallDir
cd ..
echo "Copying files to $InstallDir"
cp smCookieHandler.class.php $InstallDir$Module-$CurVersion.php
cd $InstallDir
echo "Removing any old symlink (if any) on $Module.php"
rm $Module.php
echo "Setting permissions on $InstallDir"
chown www-data:www-data $InstallDir$Module-$CurVersion.php
echo "Linking latest file as $Module.php"
ln -s $InstallDir$Module-$CurVersion.php $Module.php
echo "Returning home to $ThisDir"
cd $ThisDir
echo "Done"