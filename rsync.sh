#!/bin/bash
rsync -arv /var/www/j5/public_html/plugins/authentication/emailx/ plugin/
rsync -arv /var/www/j5/public_html/plugins/system/emailx/ system/
