#!/bin/bash
source arduinopackager.cfg
TODAY=$(date +"%Y-%m-%d")

rm $tmpfolder -rf
mkdir $tmpfolder -p
mkdir $outputfolder -p

php identify.php -p $projectpath

find $projectpath/*/ -maxdepth 1 -name "librairies_active.packager" > $tmpfolder/packagerfiles.tmp

IFS=$'\r\n' packageFileList=($(cat $tmpfolder/packagerfiles.tmp))

for packageFileName in "${packageFileList[@]}"
do
   :
	IFS=$'\r\n' packageFile=($(cat $packageFileName))
	
	projectpath=${packageFileName%/*}
	projectname=${projectpath##*/}

	mkdir $tmpfolder/$projectname -p
	mkdir $tmpfolder/$projectname/libraries -p
	mkdir $tmpfolder/$projectname/sketch -p
	
	cp $projectpath $tmpfolder/$projectname/sketch -r
	
	for lib in "${packageFile[@]}"
	do
	   :
		libpath=$librairypath/$lib

		if [ -d $libpath ]
		then
		    cp $libpath $tmpfolder/$projectname/libraries -r		    
		fi		
	done
	cd $tmpfolder/
	tar -zcvf $outputfolder/$projectname-$TODAY.tar.gz $projectname
done
#tmpfolder