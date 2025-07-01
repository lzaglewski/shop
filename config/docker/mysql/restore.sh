#!/bin/bash

mariadb -uroot -p12345678 <<EOF
DROP DATABASE IF EXISTS kakawa;
CREATE DATABASE kakawa;
EOF

mariadb -uroot -p12345678 kakawa < /var/app/dump.sql