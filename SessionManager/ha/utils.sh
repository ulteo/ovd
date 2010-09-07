
function valid_ip()
{
    local ip=$1
    local _ifs=$IFS

    if [[ $ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        IFS='.'
        ip=($ip)
        IFS=$_ifs
        [[ ${ip[0]} -le 255 && ${ip[1]} -le 255 \
            && ${ip[2]} -le 255 && ${ip[3]} -le 255 ]]
        return $?
    fi
    return 1
}

function execute()
{
    $1 2> /dev/null
    ret=$?
    if $ret; then
        echo -e "\033[34;1m[OK] \033[0m $1";
    else
        echo -e "\033[31;1m[FAILED] \033[0m $1";
    fi
    return $?
}

# LIST NETWORK INTERFACES & CHOOSE ONE
function set_netlink()
{
    NICS=(`ifconfig -s | tr -s ' ' | cut -d' ' -f1 | grep -E "eth[0-9]"`)
    len=${#NICS[*]}

    if [ $len != 0 ]; then
        echo -e "\nNIC detected :";
    else
        echo -e "\033[31;1m[FAILED] \033[0m No nic detected, please configure your network";
        exit 2;
    fi

    for i in $(seq 1 $len); do
        local nic="${NICS[$i-1]}"
        tmp=`ifconfig $nic | grep "inet addr" | tr -s ' '| cut -d' ' -f3-`
        [ -n "$tmp" ] && echo -e "\t\a[$i] $nic ("$tmp")"
    done

    unset NIC_INFOS
    while [ -z "$NIC_INFOS" ]; do
        echo -n "Choose nic number: "
        read var_nic
        [ -z "$var_nic" ] && continue

        [[ $var_nic == [0-9] ]] && \
            [ $var_nic -ge 0 -a $var_nic -le $len ] && \
            NIC_INFOS="${NICS[$var_nic-1]}"
    done

    NIC_NAME=${NICS[$NIC_INFOS]}
    NIC_ADDR=`ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $4}'`
    NIC_BCAST=`ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $6}'`
    NIC_MASK=`ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $8}'`

    echo -e "\033[36;1m[INFO] \033[0m You have selected NIC $NIC_NAME"
    echo "Addr:" $NIC_ADDR
    echo "Mask:" $NIC_MASK
    echo "Broadcast:" $NIC_BCAST
}

function set_virtual_ip()
{
    local nic_mask=(`echo $1 | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`)
    local nic_addr=(`echo $2 | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`)

    while [ -z "$VIP" ]; do
        echo -n "Please, give the virtual IP you want: " && read vip
        ([ -z "$vip" ] || ! valid_ip "$vip") && continue

        vip=(`echo $vip | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`)
        [ ${#vip[*]} != 4 ] && continue

        for i in {0..3}; do
            if [ $(expr ${nic_addr[$i]} \& ${nic_mask[$i]}) != \
                 $(expr ${vip[$i]} \& ${nic_mask[$i]}) ]; then
                echo "The ip address you have submit is forbidden !"
                unset vip && break
            fi
        done
        [ -n "$vip" ] && VIP=${vip[0]}.${vip[1]}.${vip[2]}.${vip[3]}
    done
    echo "You have selected" $VIP "as virtual IP"
}
