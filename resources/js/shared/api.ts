const getCsrfToken = (): string | null => {
  return (
    document.querySelector<HTMLMetaElement>(
      'meta[name="csrf-token"]'
    )?.content ?? null
  );
};

export async function apiFetch(
  input: RequestInfo | URL,
  init: RequestInit = {}
): Promise<Response> {
  const method = (init.method ?? "GET").toUpperCase();
  const headers = new Headers(init.headers);

  if (!headers.has("Accept")) {
    headers.set("Accept", "application/json");
  }

  if (
    init.body &&
    !(init.body instanceof FormData) &&
    !headers.has("Content-Type")
  ) {
    headers.set("Content-Type", "application/json");
  }

  if (!["GET", "HEAD", "OPTIONS"].includes(method)) {
    const csrfToken = getCsrfToken();

    if (csrfToken) {
      headers.set("X-CSRF-TOKEN", csrfToken);
    }
  }

  return fetch(input, {
    ...init,
    headers,
    credentials: "same-origin",
  });
}