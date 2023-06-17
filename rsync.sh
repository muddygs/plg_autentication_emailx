#!/bin/bash
rsync -arv /var/www/eb/public_html/plugins/authentication/email/ plugin/
rsync -arv /var/www/eb/public_html/plugins/system/email/ system/
