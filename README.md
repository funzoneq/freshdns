freshdns
========

FreshDNS AJAX based, PowerDNS administration system


# Installation
Upload the files to /var/www/freshdns/ and go to http://localhost/freshdns/install.php

Replace the default values of config.inc.php with the values from installation 

    cp config.inc.default.php config.inc.php
    vi config.inc.php

Go to http://localhost/freshdns/install.php?p=install_db and create an admin user and finish the install.

Remove the installation files:

    rm class/class.install.php install.php


# Upgrade notes

## 1.0.3 or below --> 1.0.5 or above

  * Run the sql queries in sql_upgrade/mysql.103.104.sql and sql_upgrade/mysql.104.105.sql

  * Adapt your config.inc.php to the changes in config.inc.default.php, especially as follows:
    
    * Use the PDO Data Source Name syntax for the database connection
    
          //...
          $config['DB']['master_dsn']		= 'mysql:dbname=pdns;host=127.0.0.1';
          $config['DB']['slave_dsns']		= array('mysql:dbname=pdns;host=127.0.0.1','mysql:dbname=pdns;host=127.0.0.1');
          //...
        
    * remove the lines starting with 'include_once(...)' and all below



