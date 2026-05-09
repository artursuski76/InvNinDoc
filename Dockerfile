# FROM invoiceninja/invoiceninja:5.13.14
FROM invoiceninja/invoiceninja:5.13.22

# Przełączamy na root, żeby móc wykonać zmiany systemowe
USER root

# Instalacja Twoich narzędzi
RUN apk add --no-cache nano bash

# Kopiowanie Twoich plików z zachowaniem poprawnego UID (1500 zamiast www-data)
COPY --chown=1500:1500 custom-files/ /var/www/app/

# Poprawka uprawnień dla folderów, o które prosił Laravel w błędzie 500
# Ustawiamy właściciela 1500 (invoiceninja)
RUN mkdir -p /var/www/app/storage/framework/views \
    && chown -R 1500:1500 /var/www/app/storage \
    && chmod -R 775 /var/www/app/storage

# Zgodnie z linią 45 oficjalnego obrazu, wracamy do użytkownika 1500
USER 1500

# Nie musisz pisać ENTRYPOINT ani CMD - zostaną odziedziczone (docker-entrypoint + supervisord)
