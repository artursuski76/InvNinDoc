<?php
/**
 * Invoice Ninja PL - custom overlay file.
 *
 * Proxy do API Wykazu podatnikow VAT (Biala Lista) Ministerstwa Finansow.
 * Potrzebny, poniewaz wl-api.mf.gov.pl nie wysyla naglowkow CORS,
 * wiec przegladarka nie moze odpytac tego API bezposrednio.
 */

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class NipLookupController extends Controller
{
    public function __invoke(string $nip): JsonResponse
    {
        $nip = preg_replace('/[^0-9]/', '', $nip);

        if (strlen($nip) !== 10 || !$this->isValidNip($nip)) {
            return response()->json(['error' => 'Nieprawidłowy numer NIP'], 422);
        }

        $response = Http::timeout(10)->get(
            "https://wl-api.mf.gov.pl/api/search/nip/{$nip}",
            ['date' => now()->format('Y-m-d')]
        );

        if (!$response->successful()) {
            return response()->json(['error' => 'Błąd usługi Białej Listy MF'], 502);
        }

        $subject = $response->json('result.subject');

        if (!$subject) {
            return response()->json(['error' => 'Nie znaleziono podmiotu w wykazie podatników VAT'], 404);
        }

        // workingAddress dla firm, residenceAddress dla JDG
        $address = $subject['workingAddress'] ?? $subject['residenceAddress'] ?? '';

        return response()->json(array_merge(
            ['name' => $subject['name'] ?? '', 'nip' => $nip],
            $this->parseAddress($address)
        ));
    }

    /**
     * Adres z Bialej Listy ma format "ULICA 7A/2, 09-411 PLOCK".
     */
    private function parseAddress(?string $address): array
    {
        $result = ['street' => '', 'number' => '', 'postal_code' => '', 'city' => ''];

        if (!$address) {
            return $result;
        }

        $parts = array_map('trim', explode(',', $address, 2));

        if (isset($parts[1]) && preg_match('/^(\d{2}-\d{3})\s+(.+)$/u', $parts[1], $m)) {
            $result['postal_code'] = $m[1];
            $result['city'] = mb_convert_case($m[2], MB_CASE_TITLE, 'UTF-8');
        }

        $street = preg_replace('/^UL\.\s*/iu', '', $parts[0]);

        // oddziel numer domu/lokalu od nazwy ulicy (np. "CHEMIKOW 7", "PROSTA 51/501")
        if (preg_match('/^(.*?)\s+(\d[\w\/\.\-]*)$/u', $street, $m)) {
            $result['street'] = mb_convert_case($m[1], MB_CASE_TITLE, 'UTF-8');
            $result['number'] = $m[2];
        } else {
            $result['street'] = mb_convert_case($street, MB_CASE_TITLE, 'UTF-8');
        }

        return $result;
    }

    private function isValidNip(string $nip): bool
    {
        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum = 0;

        foreach ($weights as $i => $weight) {
            $sum += $weight * (int) $nip[$i];
        }

        return ($sum % 11) === (int) $nip[9];
    }
}
