#!/bin/bash
### Copyright 1999-2020. Plesk International GmbH.

while : ; do
    curl -ksL https://plesk:8443/ | grep "<title>Plesk" > /dev/null
    [ $? -eq 0 ] && break
    sleep 5
done
