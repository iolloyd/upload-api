#!/bin/sh

ps u|grep resque|awk '{print $2}'|xargs kill -9
