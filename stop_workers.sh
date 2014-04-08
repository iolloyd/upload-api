#!/bin/sh

ps u|grep resque.php|awk '{print $2}'|xargs kill -9
