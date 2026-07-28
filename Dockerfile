FROM invoiceninja/invoiceninja:5.13.22

USER root

RUN apk add --no-cache nano bash

COPY --chown=1500:1500 custom-files/ /var/www/app/

USER 1500
