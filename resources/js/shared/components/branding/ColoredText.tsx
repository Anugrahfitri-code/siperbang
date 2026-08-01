import React from "react";

export function ColoredText({
  text,
  colorsJson,
  className = "",
}: {
  text: string;
  colorsJson?: string | null;
  className?: string;
}) {
  let colors: Record<number, string> = {};
  try {
    if (colorsJson) {
      colors = JSON.parse(colorsJson);
    }
  } catch (e) {}

  return (
    <span className={className}>
      {text.split("").map((char, i) => (
        <span key={i} style={{ color: colors[i] || undefined }}>
          {char}
        </span>
      ))}
    </span>
  );
}
