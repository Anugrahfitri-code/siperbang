import { createHash } from "node:crypto";
import { readdir, readFile, stat } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const scriptsDirectory = path.dirname(fileURLToPath(import.meta.url));
export const projectRoot = path.resolve(scriptsDirectory, "..");

const sourceRoots = ["resources/js", "resources/css"];
const sourceFiles = [
  "package.json",
  "package-lock.json",
  "vite.config.js",
  "tsconfig.json",
  "eslint.config.js",
  "scripts/build-source-hash.mjs",
  "scripts/build-artifact-hash.mjs",
  "scripts/write-build-metadata.mjs",
  "scripts/verify-build-metadata.mjs",
];

const walk = async (relativeDirectory) => {
  const absoluteDirectory = path.join(projectRoot, relativeDirectory);
  const entries = await readdir(absoluteDirectory, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const relative = path.posix.join(relativeDirectory.replaceAll("\\", "/"), entry.name);
    if (entry.isDirectory()) files.push(...(await walk(relative)));
    else if (entry.isFile()) files.push(relative);
  }

  return files;
};

export const sourceFileList = async () => {
  const files = [...sourceFiles];

  for (const root of sourceRoots) {
    const absolute = path.join(projectRoot, root);
    try {
      if ((await stat(absolute)).isDirectory()) files.push(...(await walk(root)));
    } catch {
      // A missing optional source root contributes no files.
    }
  }

  return [...new Set(files)].sort();
};

export const calculateSourceHash = async () => {
  const hash = createHash("sha256");
  const files = await sourceFileList();

  for (const relative of files) {
    const content = await readFile(path.join(projectRoot, relative));
    hash.update(relative.replaceAll("\\", "/"));
    hash.update("\0");
    hash.update(content);
    hash.update("\0");
  }

  return { hash: hash.digest("hex"), files };
};
