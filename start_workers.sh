#!/bin/sh

COUNT=2 QUEUE=video_upload APP_INCLUDE=./bootstrap_workers.php vendor/chrisboulton/php-resque/bin/resque
