<?php

    require 'vendor/autoload.php';

    function mysqlconn($query) {
        $dbservername = "193.192.100.78";
        $dbusername = "radius";
        $dbpassword = "Boss!*vLa34";
        $dbname = "radius";
        $connection = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
        return $connection->query($query);
    }

    # in progress
    function mysqlcheck($field, $value) {
        $query = "SELECT $field FROM radacct WHERE '$field' = '$value'";
        $row = mysqli_fetch_assoc(mysqlconn($query));

        if(is_null($row[$field])) {
            return 1;
        } else {
            return 0;
        }
    }

    function mysqlupdate ($mac) {
        $query = "UPDATE AuthInfo
                LEFT JOIN radacct ON AuthInfo.macaddress = radacct.callingstationid
                SET AuthInfo.sessionid = radacct.radacctid
                WHERE AuthInfo.logintime > radacct.acctstarttime
                AND AuthInfo.logintime < radacct.acctstoptime
                AND radacct.callingstationid = '$mac';";
        mysqlconn($query);
    }

    function mongoget() {
        $client = new MongoDB\Client("mongodb://localhost:27117");
        $collection = $client->ace_stat->stat_archive;
        #$result = $collection->find( [ 'mac' => '00:26:82:50:85:d4' ] );
        $result = $collection->find();

        foreach ($result as $entry) {
            $acctuniqueid = $entry['_id'];
            $acctsessionid = $entry['session'];
            $acctstarttime = $entry['assoc_time'];
            $acctstoptime = $entry['disassoc_time'];
            $acctsessiontime = $entry['duration'];
            $acctinputoctets = $entry['rx_bytes'];
            $acctoutputoctets = $entry['tx_bytes'];
            $calledstationid = $entry['ap_mac'];
            $callingstationid = $entry['mac'];
            $framedipaddress = $entry['ip'];

            $insertquery = "INSERT INTO radacct (acctuniqueid, acctsessionid, acctstarttime, acctstoptime, acctsessiontime, acctinputoctets, acctoutputoctets, calledstationid, callingstationid, framedipaddress)
                VALUES ('$acctuniqueid', '$acctsessionid', FROM_UNIXTIME('$acctstarttime'), FROM_UNIXTIME('$acctstoptime'), '$acctsessiontime', '$acctinputoctets', '$acctoutputoctets', '$calledstationid', '$callingstationid', '$framedipaddress')";

            if(mysqlcheck('acctuniqueid', $acctuniqueid)) {
                echo "null\n";
                mysqlput($insertquery);
            } else {
                echo "notNull\n";
            }
        }
    }

    mongoget();

?>
