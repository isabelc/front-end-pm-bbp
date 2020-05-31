#! /bin/bash
# config
BACKUPDIR="${HOME}/Documents/LOG/" # destination folder for the backup .zip

CURRENTDIRNAME=${PWD##*/}
# zip a local backup of everything
echo "Making backup of everything ..."
cd ../
timestamp=$(date +%Y%m%d_%H%M%S)
zip -r ${CURRENTDIRNAME}.${timestamp}.zip $CURRENTDIRNAME
echo "Moving the backup out to $BACKUPDIR"
mv ${CURRENTDIRNAME}.${timestamp}.zip $BACKUPDIR
echo "*** FIN ***"
