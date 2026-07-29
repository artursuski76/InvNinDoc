# Invoice Ninja PL

**Invoice Ninja PL** is a community-driven customization of the official **Invoice Ninja** project, tailored for Polish businesses and accounting requirements.

This repository **does not contain a standalone application**. It provides a small set of files that are copied over the official Docker image of Invoice Ninja to add Polish-specific functionality while keeping modifications to the original codebase as small as possible.

## Project goals

The purpose of this project is to make Invoice Ninja easier to use in Poland by providing:

* Polish localization improvements,
* Polish VAT support,
* documents adapted to Polish accounting practices,
* integration with the Polish National e-Invoice System (KSeF),
* additional enhancements for Polish users.

The project aims to minimize modifications to the upstream application to simplify upgrades to newer Invoice Ninja releases.

## How it works

The Docker image is built directly on top of the official Invoice Ninja image:

```Dockerfile
FROM invoiceninja/invoiceninja:5.13.22

USER root

RUN apk add --no-cache nano bash

COPY --chown=1500:1500 custom-files/ /var/www/app/

USER 1500
```

Only the files contained in the `custom-files/` directory replace or extend the corresponding files in the official image.

## KSeF integration

The KSeF integration is implemented as a separate application communicating with Invoice Ninja through its public API.

Its responsibilities include:

* downloading invoices from Invoice Ninja,
* generating XML documents compliant with KSeF,
* submitting invoices to KSeF,
* monitoring processing status,
* downloading the Official Receipt Confirmation (UPO),
* attaching XML and UPO documents back to the corresponding Invoice Ninja invoice.

Keeping the KSeF functionality outside of Invoice Ninja minimizes changes to the upstream project and makes future upgrades significantly easier.

## Relationship to Invoice Ninja

This project is **not affiliated with, endorsed by, or maintained by the Invoice Ninja team**.

Invoice Ninja is an independent Open Source project developed by its respective authors.

Official project:

https://github.com/invoiceninja/invoiceninja

Official website:

https://invoiceninja.com

## License

This repository contains modifications intended to be used together with the official Invoice Ninja software.

Invoice Ninja is licensed under the **Elastic License 2.0 (ELv2)**. Users of this project must comply with the terms of that license when using the original software.

A copy of the applicable license is included in the `LICENSE` file.

## Contributing

Bug reports, feature requests and pull requests are welcome.

The goal of the project is to provide a high-quality Polish adaptation of Invoice Ninja while remaining as close as possible to the upstream codebase.
