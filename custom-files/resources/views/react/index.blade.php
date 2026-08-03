<!DOCTYPE html>
<html lang="en" class="h-full">
  <head>
    <title>Invoice Ninja</title>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#000000" />
    <meta
      name="description"
      content="Leading free invoice generator for freelancers and small businesses. Invoice clients, accept payments, track expenses &amp; time billable-tasks online."
    />

    <script>
      if (
        window.matchMedia &&
        window.matchMedia('(prefers-color-scheme: dark)').matches
      ) {
        document.documentElement.style.backgroundColor = '#18181b';
      }
    </script>

    <link rel="stylesheet" href="/rsms/inter.css" />

    <link rel="icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" href="/logo180.png" />
    <link rel="manifest" href="/manifest.json" />
    <script type="module" crossorigin src="/bundle.BBBwNLjN.js"></script>
    <link rel="stylesheet" crossorigin href="/index-CH3c91u5.css">
  </head>

  <body class="h-full">
    <noscript>You need to enable JavaScript to run this app.</noscript>
    <div id="root"></div>

    <!--
      Invoice Ninja PL: autouzupelnianie danych firmy po numerze NIP
      na formularzu klienta (/clients/create i /clients/{id}/edit)
      na podstawie Bialej Listy MF, przez endpoint /nip-lookup/{nip}.

      Skrypt jest osadzony inline, poniewaz katalog public/ jest w runtime
      wolumenem synchronizowanym przez entrypoint tylko przy zmianie wersji
      aplikacji - osobny plik .js nie trafilby do wolumenu serwowanego
      przez nginx.

      Formularz jest aplikacja React, dlatego:
      - wartosci pol ustawiamy natywnym setterem + zdarzeniem "input",
        inaczej React nadpisalby zmiane swoim stanem,
      - pola NIP i Nazwa nie maja atrybutu id, wiec znajdujemy je po
        tekscie etykiety wiersza,
      - kraj (react-select) wybieramy symulujac wpisanie tekstu i Enter.
    -->
    <script>
    (function () {
      'use strict';

      var NIP_LABELS = ['nip', 'numer nip', 'numer vat', 'vat number'];
      var NAME_LABELS = ['nazwa', 'name'];

      function isClientForm() {
        return /^\/clients\/(create|[^/]+\/edit)/.test(window.location.pathname);
      }

      function setReactValue(input, value) {
        if (!input) return;
        var setter = Object.getOwnPropertyDescriptor(
          window.HTMLInputElement.prototype,
          'value'
        ).set;
        setter.call(input, value);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }

      // InputField w InvoiceNinja UI przekazuje wartosc do stanu formularza
      // dopiero w onBlur (React mapuje go na natywne "focusout"), dlatego samo
      // zdarzenie "input" wypelnia pole tylko wizualnie - bez focusout
      // zapis wysyla stary (pusty) stan.
      function commitValue(input, value) {
        if (!input) return;
        setReactValue(input, value);
        input.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));
      }

      // Znajduje input po tekscie etykiety w tym samym wierszu formularza.
      function findInputByLabel(labels) {
        var inputs = document.querySelectorAll('input[type="text"]');
        for (var i = 0; i < inputs.length; i++) {
          var node = inputs[i].parentElement;
          for (var depth = 0; node && depth < 6; depth++) {
            var text = (node.innerText || '').split('\n')[0].trim().toLowerCase();
            if (labels.indexOf(text) !== -1) return inputs[i];
            node = node.parentElement;
          }
        }
        return null;
      }

      // react-select z krajem szukamy w tej samej karcie co pole #postal_code.
      function findCountryCombo() {
        var anchor = document.getElementById('postal_code');
        var node = anchor ? anchor.parentElement : null;
        while (node && node !== document.body) {
          var combo = node.querySelector('input[role="combobox"]');
          if (combo) return combo;
          node = node.parentElement;
        }
        return null;
      }

      function selectCountry(name) {
        var combo = findCountryCombo();
        if (!combo) return;
        combo.focus();
        setReactValue(combo, name);
        setTimeout(function () {
          combo.dispatchEvent(
            new KeyboardEvent('keydown', {
              key: 'Enter',
              keyCode: 13,
              which: 13,
              bubbles: true,
            })
          );
          combo.blur();
        }, 400);
      }

      function showStatus(anchor, message, isError) {
        var el = document.getElementById('nip-autofill-status');
        if (!el) {
          el = document.createElement('div');
          el.id = 'nip-autofill-status';
          el.style.cssText = 'font-size:0.75rem;margin-top:0.25rem;';
          anchor.parentElement.appendChild(el);
        }
        el.textContent = message;
        el.style.color = isError ? '#b91c1c' : '#15803d';
      }

      function fetchAndFill(nipInput) {
        var nip = (nipInput.value || '').replace(/[^0-9]/g, '');
        if (nip.length !== 10) {
          showStatus(nipInput, 'Podaj 10-cyfrowy numer NIP', true);
          return;
        }

        showStatus(nipInput, 'Pobieranie danych z Białej Listy…', false);

        fetch('/nip-lookup/' + nip, { headers: { Accept: 'application/json' } })
          .then(function (res) {
            return res.json().then(function (data) {
              if (!res.ok) throw new Error(data.error || 'Błąd pobierania danych');
              return data;
            });
          })
          .then(function (data) {
            commitValue(findInputByLabel(NAME_LABELS), data.name || '');
            commitValue(document.getElementById('address1'), data.street || '');
            commitValue(document.getElementById('address2'), data.number || '');
            commitValue(document.getElementById('city'), data.city || '');
            commitValue(
              document.getElementById('postal_code'),
              data.postal_code || ''
            );
            selectCountry('Polska');
            showStatus(nipInput, 'Uzupełniono dane: ' + data.name, false);
          })
          .catch(function (err) {
            showStatus(nipInput, err.message, true);
          });
      }

      function enhance() {
        if (!isClientForm()) return;

        var nipInput = findInputByLabel(NIP_LABELS);
        if (!nipInput || nipInput.dataset.nipAutofill) return;
        nipInput.dataset.nipAutofill = '1';

        var button = document.createElement('button');
        button.type = 'button';
        button.textContent = 'Pobierz dane z GUS';
        button.style.cssText =
          'margin-top:0.5rem;padding:0.25rem 0.75rem;font-size:0.75rem;' +
          'border:1px solid #09090b26;border-radius:0.375rem;cursor:pointer;' +
          'background:#fff;color:#2a303d;';
        button.addEventListener('click', function () {
          fetchAndFill(nipInput);
        });
        nipInput.parentElement.appendChild(button);

        nipInput.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            fetchAndFill(nipInput);
          }
        });
      }

      // SPA: formularz montuje sie dynamicznie, wiec obserwujemy zmiany DOM.
      var scheduled = null;
      new MutationObserver(function () {
        if (scheduled) return;
        scheduled = setTimeout(function () {
          scheduled = null;
          enhance();
        }, 300);
      }).observe(document.body, { childList: true, subtree: true });

      enhance();
    })();
    </script>
  </body>
</html>
