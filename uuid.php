
<?php

    //content
    $content = file_get_contents('zabbix_template_apt.yml');
    $content = preg_replace_callback("|uuid: (.*)\n|U", function ($matches) { return "uuid: ".str_replace('-', '', generateUUIDv4())."\n"; }, $content);
    
    $fp = fopen("new.yaml", "w");
    fwrite($fp, $content);
    fclose($fp);
        
    
    function generateUUIDv4() {
        $data = random_bytes(16);

        // Set version (4) and variant (2) bits
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Convert binary data to hexadecimal format
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return $uuid;
    }

    file_put_contents('zabbix_template_apt.yml', $content);
    
    

?>
