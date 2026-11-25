// Wspólne żądanie do endpointów JSON (kapitan, dołączanie do wyprawy, ranking, panel admina).
// Zawsze Accept: application/json – serwer odpowiada wtedy JSON-em także przy błędach ({error}).
// Token CSRF idzie w nagłówku X-CSRF-Token (index.php sprawdza go dla każdej metody poza GET).
// Odrzuca obietnicę tylko przy błędzie sieci; błąd HTTP wywołujący sprawdza przez ok / status.
async function fetchJson(url, { method = 'GET', csrf = null, body } = {}) {
    const headers = { Accept: 'application/json' };
    if (csrf !== null) {
        headers['X-CSRF-Token'] = csrf;
    }
    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) });
    let data = null;
    try {
        data = await response.json();
    } catch {
        // 204 bez treści albo odpowiedź spoza naszego API (np. strona błędu serwera) – zostaje null.
    }

    return { ok: response.ok, status: response.status, data };
}
