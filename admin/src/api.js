export async function apiFetch(endpoint, options = {}) {
  return fetch(`${AcervoX.api}/${endpoint}`, {
    headers: {
      'X-WP-Nonce': AcervoX.nonce,
      'Content-Type': 'application/json'
    },
    ...options
  }).then(res => res.json());
}
