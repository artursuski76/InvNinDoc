FROM invoiceninja/invoiceninja:5.13.22

USER root

RUN apk add --no-cache nano bash

COPY --chown=1500:1500 custom-files/ /var/www/app/

# wyczysc cache tras i skompilowane widoki, aby podmienione routes/web.php
# i resources/views/react/index.blade.php zostaly faktycznie uzyte
RUN rm -f /var/www/app/bootstrap/cache/routes-v7.php \
    && rm -rf /var/www/app/storage/framework/views/*

USER 1500
