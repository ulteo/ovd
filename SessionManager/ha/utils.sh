
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

    for i in {0..$len}; do
        tmp=`ifconfig ${NICS[$i]} | grep "inet addr" | tr -s ' '| cut -d' ' -f3-`
        echo -e "\t\a[$i] ${NICS[$i]} ("$tmp")"
    done

    unset NIC_INFOS
    while [ -z "$NIC_INFOS" ]; do
        echo -n "Choose nic number: "
        read var_nic
        [ -z "$var_nic" ] && continue
        [[ $var_nic != [0-9] ]] && \
            [ $var_nic -ge 0 -a $var_nic -le $len ] && \
            NIC_INFOS=${NICS[$var_nic]}
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
    var_mask=`echo "$NIC_MASK" | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`
    var_addr=`echo "$NIC_ADDR" | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`

    i=0
    for n in $var_addr; do
        tab_ip[$i]=$n
        let i++
    done

    # Determine Address class of VIP
    i=0
    for n in $var_mask; do
        if [ $n == "0" ]; then
            tab_ip[$i]=-1
            break;
        fi
        let i++
    done

    # Select a VIP
    while [ 1 ]; do
        echo -n "Please, give the virtual IP you want: "
        read var_vip
        if [ -z "$var_vip" ]; then
            continue;
        fi
        valid_ip $var_vip
        if [[ $? == "0" ]]; then
            var_vip2=`echo "$var_vip" | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`
            i=0
            for n in $var_vip2; do
                if [ "${tab_ip[$i]} " -eq "-1" ]; then
                    tab_vip[$i]=$n
                elif [ "${tab_ip[$i]}" -eq "$n" ]; then
                    tab_vip[$i]=$n
                else
                    break;
                fi
                let i++
            done
            let i--
            if [ "$i" == "3" ]; then
                VIP=`echo ${tab_vip[0]}"."${tab_vip[1]}"."${tab_vip[2]}"."${tab_vip[3]}`
                [ "$VIP" != "$NIC_ADDR" ] && break
            fi
        fi
        echo "The ip address you have submit is forbidden !"
    done

    echo "You have selected" $VIP "as virtual IP"
}
