#!/bin/bash

if [[ -z $1 ]]; then
  echo "no destination folder given"
  exit 1
fi

URL=https://events.ccc.de/congress/2013/Fahrplan/schedule.xml

DESTDIR=$1
TMPFILE=/tmp/schedule_`whoami`.xml

wget -N -q $URL -O $TMPFILE

if [[ $? -ne 0 ]]; then
  exit 0
fi

LAST_UPDATE=$(ls -t1 ${DESTDIR}*.xml 2>/dev/null | head -1)
if [[ -n $LAST_UPDATE ]]; then
	DIFF=$(diff -u $LAST_UPDATE $TMPFILE 2>/dev/null)
	if [[ $? -eq 0 ]]; then
		exit 0
	fi

	echo "Fahrplan update!"
	echo "================"
	echo "$DIFF"
else
	echo "Initial Fahrplan download!"
fi

VERSION=$(sed -n "s/.*version>\(.*\)<\/version.*/\1/p" $TMPFILE)
NEWFILE="fahrplan_${VERSION}_$(date +%s).xml"
echo "new fahrplan file: $NEWFILE"

mv $TMPFILE "${DESTDIR}/${NEWFILE}"
