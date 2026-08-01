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
const allowedFontSizes = new Set(["12px", "13px", "14px", "15px", "16px", "17px", "18px", "20px", "24px", "28px", "32px", "36px", "40px", "44px", "48px", "56px"]);

const isSafeColor = (value: string): boolean => {
  const normalized = value.trim();
  return (
    /^#[0-9a-f]{3}([0-9a-f]{3})?$/i.test(normalized) ||
    /^rgba?\(\s*\d{1,3}%?\s*,\s*\d{1,3}%?\s*,\s*\d{1,3}%?(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i.test(normalized) ||
    ["black", "white", "gray", "grey", "red", "blue", "green", "navy", "indigo", "purple", "orange", "yellow", "teal", "maroon", "silver", "transparent"].includes(normalized.toLowerCase())
  );
};

export function sanitizeHtml(rawHtml: string): string {
  if (typeof document === "undefined") {
    return rawHtml.replace(/<[^>]*>/g, "");
  }

  const parser = new DOMParser();
  const doc = parser.parseFromString(rawHtml || "", "text/html");

  const sanitizeNode = (node: Node): void => {
    const children = Array.from(node.childNodes);

    for (const child of children) {
      if (child.nodeType === Node.COMMENT_NODE) {
        child.parentNode?.removeChild(child);
        continue;
      }

      if (child.nodeType !== Node.ELEMENT_NODE) {
        continue;
      }

      const element = child as HTMLElement;
      const tagName = element.tagName.toLowerCase();

      if (!allowedTags.has(tagName)) {
        sanitizeNode(element);
        const parent = element.parentNode;
        if (parent) {
          while (element.firstChild) parent.insertBefore(element.firstChild, element);
          parent.removeChild(element);
        }
        continue;
      }

      for (const attr of Array.from(element.attributes)) {
        const name = attr.name.toLowerCase();

        if (name === "style") {
          const declarations = element.style.cssText
            .split(";")
            .map((chunk) => chunk.trim())
            .filter(Boolean)
            .map((chunk) => {
              const separator = chunk.indexOf(":");
              if (separator < 1) return "";

              const property = chunk.slice(0, separator).trim().toLowerCase();
              const value = chunk.slice(separator + 1).trim();
              if (!allowedStyles.has(property)) return "";

              if (property === "color" && !isSafeColor(value)) return "";
              if (property === "font-size" && !allowedFontSizes.has(value)) return "";
              if (property === "font-weight" && !["normal", "bold", "500", "600", "700", "800", "900"].includes(value.toLowerCase())) return "";
              if (property === "font-style" && !["normal", "italic"].includes(value.toLowerCase())) return "";
              if (property === "text-decoration" && !["none", "underline"].includes(value.toLowerCase())) return "";

              return `${property}: ${value}`;
            })
            .filter(Boolean)
            .join("; ");

          if (declarations) element.setAttribute("style", declarations);
          else element.removeAttribute("style");
          continue;
        }

        if (tagName === "font" && name === "color" && isSafeColor(attr.value)) {
          continue;
        }

        element.removeAttribute(attr.name);
      }

      sanitizeNode(element);
    }
  };

  sanitizeNode(doc.body);
  return doc.body.innerHTML;
}
