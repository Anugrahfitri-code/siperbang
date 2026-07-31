export function sanitizeHtml(rawHtml: string): string {
  if (typeof document === "undefined") {
    return rawHtml;
  }

  const parser = new DOMParser();
  const doc = parser.parseFromString(rawHtml, "text/html");

  const allowedTags = new Set([
    "b",
    "strong",
    "i",
    "em",
    "u",
    "span",
    "div",
    "p",
    "br",
    "ul",
    "ol",
    "li",
    "font",
  ]);

  const allowedStyles = new Set(["color", "font-size", "font-weight", "font-style", "text-decoration"]);

  const sanitizeNode = (node: Node) => {
    if (node.nodeType === Node.ELEMENT_NODE) {
      const element = node as HTMLElement;
      if (!allowedTags.has(element.tagName.toLowerCase())) {
        const parent = element.parentNode;
        if (parent) {
          while (element.firstChild) {
            parent.insertBefore(element.firstChild, element);
          }
          parent.removeChild(element);
        }
        return;
      }

      [...element.attributes].forEach((attr) => {
        const name = attr.name.toLowerCase();

        if (name.startsWith("on")) {
          element.removeAttribute(attr.name);
          return;
        }

        if (name === "style") {
          const styleValue = element.style.cssText
            .split(";")
            .map((chunk) => chunk.trim())
            .filter(Boolean)
            .map((chunk) => {
              const [property, value] = chunk.split(":").map((part) => part.trim());
              if (!property || !value) {
                return "";
              }
              if (!allowedStyles.has(property.toLowerCase())) {
                return "";
              }
              if (
                property.toLowerCase() === "color" &&
                !/^#[0-9A-Fa-f]{3,8}$/.test(value) &&
                !/^[a-zA-Z]+$/.test(value) &&
                !/^rgba?\(.*?\)$/i.test(value)
              ) {
                return "";
              }
              if (property.toLowerCase() === "font-size" && !/^\d+(px|em|rem|%)$/.test(value)) {
                return "";
              }
              return `${property}: ${value}`;
            })
            .filter(Boolean)
            .join("; ");

          if (styleValue) {
            element.setAttribute("style", styleValue);
          } else {
            element.removeAttribute("style");
          }
          return;
        }

        if (name !== "class") {
          element.removeAttribute(attr.name);
        }
      });
    }

    node.childNodes.forEach(sanitizeNode);
  };

  doc.body.childNodes.forEach(sanitizeNode);

  return doc.body.innerHTML;
}
