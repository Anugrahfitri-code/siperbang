import DOMPurify from 'dompurify';

export function sanitizeHtml(rawHtml: string): string {
  if (typeof window === "undefined") {
    // In SSR or non-browser environments, DOMPurify needs JSDOM,
    // but we'll fallback to a simple strip tags if DOMPurify is unavailable
    return rawHtml.replace(/<[^>]*>/g, "");
  }

  return DOMPurify.sanitize(rawHtml || "", {
    ALLOWED_TAGS: [
      "b", "strong", "i", "em", "u", "span", "div", "p", "br", "ul", "ol", "li", "font"
    ],
    ALLOWED_ATTR: [
      "style", "color"
    ]
  });
}
