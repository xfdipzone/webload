#!/bin/bash

#连接数
site_connects=$(netstat -ant | grep $ip:80 | wc -l)
#当前连接数
site_cur_connects=$(netstat -ant | grep $ip:80 | grep EST | wc -l)

#apache
apache_speed=$(netstat -n | awk '/^tcp/ {++S[$NF]} END {for(a in S) print a, S[a]}')

printf "[#start#]\n$(date '+%Y-%m-%d %H:%M:%S')\n"
printf "connects:${site_connects}\n"
printf "cur connects:${site_cur_connects}\n"
printf "apache_speed:\n${apache_speed}\n[#end#]\n\n"

exit 0