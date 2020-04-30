#!/bin/sh

root_dir=`pwd`
dst_dir=$root_dir/Cfg/
cd $root_dir/Docs/Examples/Cfg/
for cfg in `ls`; do
    dst_file=$dst_dir/$cfg
    if [ ! -f $dst_file ]; then
        cp -v "$cfg" "$dst_file"
    fi
done
cd $root_dir

if [ `whoami` != "root" ]; then
    echo "You should run as root by hand:"
    exit 1
fi

dst_script=/etc/init.d/jmevent
if [ ! -f $dst_script ]; then
    cp Cli/Daemon/init.d/jmevent.example  $dst_script && \
    sed -i "s#@@BASE_DIR@@#$root_dir#g" $dst_script && \
    update-rc.d jmevent defaults && \
    echo "Done!"
    echo "Please fix configurations in dir Cfg and run 'service jmevent start'"
   
    exit 0
fi
echo "setup failed"

exit 1

