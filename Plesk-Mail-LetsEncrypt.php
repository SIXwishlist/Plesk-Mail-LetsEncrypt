<?php


############################################################
$password = trim(file_get_contents('/etc/psa/.psa.shadow'));
$db = mysql_connect('localhost', PLESK_ADMIN, "$password") or die("\nUsername or password incorrect!\n\n");
mysql_select_db('psa');

$res = mysql_query("SELECT name from domains where name != '".HOSTNAME."' order by name ASC");

$arr_mail = array(HOSTNAME);

echo "Fetching Domain DNS Records.\n";

$max = mysql_num_rows($res);
$i = 1;
while($row = mysql_fetch_assoc($res))
{
    echo "($i / $max) | " . $row['name']."\n";
    if(!file_exists(IP . "_" . MAIL_SUBDOMAIN))
    {
        $data = dns_get_record(MAIL_SUBDOMAIN . '.' . $row['name'], DNS_A);
        foreach($data as $record)
        {
            if($record['ip'] == IP)
            {
                $arr_mail[] = MAIL_SUBDOMAIN . '.' . $row['name'];
            }
        }
    }
    $i++;
}

if(!file_exists(IP . "_" . MAIL_SUBDOMAIN))
{
    echo "Saving mail domain list to file\n";
    $fh = fopen(IP . "_" . MAIL_SUBDOMAIN, 'w');
    fwrite($fh, implode(' -d ', $arr_mail));
    fclose($fh);
}



echo "\n\n";
shell_exec('/usr/local/psa/bin/extension --exec letsencrypt cli.php --secure-plesk -w "'.DEFAULT_IP_VHOST.'" -m "'.LEMAIL.'" -d ' . trim(file_get_contents(IP . "_" . MAIL_SUBDOMAIN)));
shell_exec('plesk bin certificate --update "Lets Encrypt certificate" -new-name "EMail"  -admin');
shell_exec('plesk bin mailserver --set-certificate "EMail"');

shell_exec('/usr/local/psa/bin/extension --exec letsencrypt cli.php --secure-plesk -w "'.DEFAULT_IP_VHOST.'" -m "'.LEMAIL.'" -d "'.HOSTNAME.'"');
shell_exec('plesk bin certificate --update "Lets Encrypt certificate" -new-name "Plesk"  -admin');