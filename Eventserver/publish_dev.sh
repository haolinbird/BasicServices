#!/bin/bash
set -e

log() {
	echo \[$(date '+%Y-%m-%d %H:%M:%S')\]  $1
}

log "当前分支：${CI_COMMIT_REF_NAME}"
log "当前项目：${CI_PROJECT_NAME}"
[[ $CI_COMMIT_REF_NAME = "online" ]] && publish_env="prod" || publish_env=${CI_COMMIT_REF_NAME}
log "发布环境：${publish_env}"
log "构建代码"
composer update

tarFile="${CI_PROJECT_NAME}_${publish_env}.tar"
log "打包代码到文件 ${tarFile}"
tar -czf ../${tarFile} --exclude .git ./*


codeFile="/home/www/code/${tarFile}"
publishDir="/home/www/${CI_PROJECT_NAME}"
ips=`python ~/get_ips.py ~/${publish_env}.yaml ${CI_PROJECT_NAME}`
log "即将发布到 ${ips}"

for ip in $ips
do
	log "发布ip: ${ip}"
	scp ../${tarFile} $ip:${codeFile}
	if !(ssh $ip test -e $publishDir); then
        ssh $ip "mkdir ${publishDir}"
    fi
    ssh $ip "tar -zxf ${codeFile} -C ${publishDir}"
    ssh $ip "sh /usr/local/bin/update_callback.sh"
done

log "finish"