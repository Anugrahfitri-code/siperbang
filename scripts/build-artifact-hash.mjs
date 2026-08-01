import { createHash } from "node:crypto";
import { readdir, readFile } from "node:fs/promises";
import path from "node:path";
import { projectRoot } from "./build-source-hash.mjs";

export const buildDirectory = path.join(projectRoot, "public/build");

const walk = async (absoluteDirectory, relativeDirectory = "") => {
  const entries = await readdir(absoluteDirectory, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const relative = path.posix.join(relativeDirectory, entry.name);
    const absolute = path.join(absoluteDirectory, entry.name);
    if (entry.isDirectory()) files.push(...(await walk(absolute, relative)));
    else if (entry.isFile() && relative !== "build-meta.json") files.push(relative);
  }

  return files;
};

export const buildFileList = async () => (await walk(buildDirectory)).sort();

export const calculateBuildHash = async () => {
  const hash = createHash("sha256");
  const files = await buildFileList();

  for (const relative of files) {
    hash.update(relative);
    hash.update("\0");
    hash.update(await readFile(path.join(buildDirectory, relative)));
    hash.update("\0");
  }

  return { hash: hash.digest("hex"), files };
};

export const buildContainsSourceHash = async (sourceHash) => {
  const files = await buildFileList();
  const javascriptFiles = files.filter((file) => file.endsWith(".js"));

  for (const relative of javascriptFiles) {
    const content = await readFile(path.join(buildDirectory, relative), "utf8");
    if (content.includes(sourceHash)) return true;
  }

  return false;
};
