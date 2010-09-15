execute()
{
	set +e
    $1 >> $HA_LOG 2>&1
    ret=$?
	set -e
    if [ $ret -eq 0 ]; then
        echo -e "\033[34;1m[OK] \033[0m $1";
    else
        echo -e "\033[31;1m[FAILED] \033[0m $1";
    fi
    return $ret
}

info()
{
	echo -e "\033[36;1m[INFO] \033[0m" "$1"
}


die()
{
	echo -e "\033[31;1m[ERROR] \033[0m" "$1"
	exit 1
}

valid_ip()
{
    local _ifs=$IFS
    IFS='.'
    local ip=($1)
    IFS=$_ifs

    if ! ( [[ "$1" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]] && \
           [[ ${ip[0]} -le 255 && ${ip[1]} -le 255 && \
              ${ip[2]} -le 255 && ${ip[3]} -le 255 ]] ); then
		echo "The ip address submitted is malformed"
        return 1
    fi
}

valid_vip()
{
	valid_ip "$3" || return $?

	local nic_mask=(`echo $1 | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`)
	local nic_addr=(`echo $2 | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`)
	local vip=(`echo $3 | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`)

	for i in {0..3}; do
		if [ $(expr ${nic_addr[$i]} \& ${nic_mask[$i]}) != \
		 	 $(expr ${vip[$i]} \& ${nic_mask[$i]}) ]; then
			echo "The ip address submitted is forbidden"
			return 1
		fi
	done
}
