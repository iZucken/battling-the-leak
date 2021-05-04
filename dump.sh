rm -f /var/crash/_usr_bin_php7.4.1000.crash 2> /dev/null
php test.php
rm -rf ~/crash-dump 2> /dev/null
apport-unpack /var/crash/_usr_bin_php7.4.1000.crash ~/crash-dump
strings ~/crash-dump/CoreDump | grep uh-oh
strings ~/crash-dump/CoreDump | grep woops