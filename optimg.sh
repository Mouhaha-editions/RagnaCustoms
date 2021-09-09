#!/bin/bash
BASE_FILE=$(cd ${0%/*} && echo $PWD/${0##*/})
BASE_FOLDER=`dirname ${BASE_FILE}`

# Chemin des sites à partir du home de l'utilisateur
SITES="/var/www/woowine.com/web/media/cache/light_slider/uploads/sliders /var/www/woowine.com/web/uploads/creations /var/www/woowine.com/web/uploads/creations/cropped /var/www/woowine.com/web/uploads/shops /var/www/woowine.com/web/uploads/shops/cropped"
MTIME=700000
COMPR=7

function traiterSite() {
  echo "...Optimisation des images jpeg"
  for IMAGE in `find $1 -mtime -${MTIME} -type f -name '*.jp*' | grep "jpg$\|jpeg$\|jpe$\|JPG$\|JPEG$\|JPE$"`; do
     if [ `date -r ${IMAGE} +%s` -gt ${LASTRUN} ]; then
       echo "......${IMAGE}"
       jpegoptim -p --strip-all -m75 "${IMAGE}" > /dev/null
     fi
  done
  echo "...Optimisation des images JPEG"
   for IMAGE in `find $1 -mtime -${MTIME} -type f -name '*.JP*' | grep "jpg$\|jpeg$\|jpe$\|JPG$\|JPEG$\|JPE$"`; do
     if [ `date -r ${IMAGE} +%s` -gt ${LASTRUN} ]; then
       echo "......${IMAGE}"
       jpegoptim -p --strip-all -m75 "${IMAGE}" > /dev/null
     fi
  done
  # echo "...Optimisation des images PNG"
  # for IMAGE in `find $1 -mtime -${MTIME} -type f -name '*.png'`; do
  #    if [ `date -r ${IMAGE} +%s` -gt ${LASTRUN} ]; then
  #      echo "......${IMAGE}"
  #      optipng -o ${COMPR} "${IMAGE}" > /dev/null
  #    fi
  # done
}

function run() {
  echo "************************************************"
  echo "* Debut de l'optimisation : `date +"%Y-%m-%d %H:%M:%S"` *"
  echo "************************************************"
  LASTRUN=0
  if [ -f ${BASE_FOLDER}/.optimg_run ]; then
    LASTRUN=`cat ${BASE_FOLDER}/.optimg_run`
  fi
  for SITE in ${SITES}; do
    echo "Traitement du site ${SITE}"
    traiterSite ${SITE}
    echo "...OK"
  done
  echo "**********************************************"
  echo "* Fin de l'optimisation : `date +"%Y-%m-%d %H:%M:%S"` *"
  echo "**********************************************"
  date +%s > ${BASE_FOLDER}/.optimg_run
}

run 2>&1 | tee -a ${BASE_FOLDER}/optimg_`date +%s`.log