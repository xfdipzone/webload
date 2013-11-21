#!/bin/bash

SITES=("http://web01.example.com" "http://web02.example.com") # 要监控的网站
NOTICE_EMAIL='me@example.com'                                 # 管理员电邮
MAXLOADTIME=10                                                # 访问超时时间设置
REMARKFILE='/tmp/monitor_load.remark'                         # 记录时否发送过通知电邮，如发送过则一小时内不再发送
ISSEND=0                                                      # 是否有发送电邮
EXPIRE=3600                                                   # 每次发送电邮的间隔秒数
NOW=$(date +%s)

if [ -f "$REMARKFILE" ] && [ -s "$REMARKFILE" ]; then
    REMARK=$(cat $REMARKFILE)
    
    # 删除过期的电邮发送时间记录文件
    if [ $(( $NOW - $REMARK )) -gt "$EXPIRE" ]; then
        rm -f ${REMARKFILE}
        REMARK=""
    fi
else
    REMARK=""
fi

# 循环判断每个site
for site in ${SITES[*]}; do

    printf "start to load ${site}\n"
    site_load_time=$(curl -o /dev/null -s -w "time_connect: %{time_connect}\ntime_starttransfer: %{time_starttransfer}\ntime_total: %{time_total}" "${site}")
    site_access=$(curl -o /dev/null -s -w %{http_code} "${site}")
    time_total=${site_load_time##*:}

    printf "$(date '+%Y-%m-%d %H:%M:%S')\n"
    printf "site load time\n${site_load_time}\n"
    printf "site access:${site_access}\n\n"

    # not send
    if [ "$REMARK" = "" ]; then
        # check access
        if [ "$time_total" = "0.000" ] || [ "$site_access" != "200" ]; then
            echo "Subject: ${site} can access $(date +%Y-%m-%d' '%H:%M:%S)" | sendmail ${NOTICE_EMAIL}
            ISSEND=1
        else
            # check load time
            if [ "${time_total%%.*}" -ge ${MAXLOADTIME} ]; then
                echo "Subject: ${site} load time total:${time_total} $(date +%Y-%m-%d' '%H:%M:%S)" | sendmail ${NOTICE_EMAIL}
                ISSEND=1
            fi
        fi
    fi

done

# 发送电邮后记录发送时间
if [ "$ISSEND" = "1" ]; then
    echo "$(date +%s)" > $REMARKFILE
fi

exit 0