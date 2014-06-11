freshdns
========

FreshDNS AJAX based, PowerDNS administration system


# Installation
Upload the files to /var/www/freshdns/ and go to http://localhost/freshdns/install.php

Replace the values from installation with the default values of config.inc.php

    cp config.inc.default.php config.inc.php
    vi config.inc.php

Go to http://localhost/freshdns/install.php?p=install_db and create an admin user and finish the install.

Remove the installation files:

    rm class/class.install.php install.php
