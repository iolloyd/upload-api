#!/bin/sh

COUNT=2 QUEUE=video_upload APP_INCLUDE=./workers_autoload.php php vendor/chrisboulton/php-resque/resque.php
