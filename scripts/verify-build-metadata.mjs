import { readFile } from "node:fs/promises";
import path from "node:path";
import {
  buildContainsSourceHash,
  buildDirectory,
  calculateBuildHash,
} from "./build-artifact-hash.mjs";
import { calculateSourceHash } from "./build-source-hash.mjs";

const metadataPath = path.join(buildDirectory, "build-meta.json");
let metadata;

try {
  metadata = JSON.parse(await readFile(metadataPath, "utf8"));
} catch {
  console.error("Build metadata is missing or invalid. Run `npm run build`.");
  process.exit(1);
}

const source = await calculateSourceHash();
if (metadata.source_hash !== source.hash || metadata.source_files !== source.files.length) {
  console.error("Frontend build is stale: source fingerprint does not match build metadata.");
  process.exit(1);
}

if (!(await buildContainsSourceHash(source.hash))) {
  console.error("Frontend assets do not embed the current source fingerprint.");
  process.exit(1);
}

const build = await calculateBuildHash();
if (metadata.build_hash !== build.hash || metadata.build_files !== build.files.length) {
  console.error("Frontend build files do not match their integrity metadata.");
  process.exit(1);
}

console.log(`Frontend build matches current source (${source.hash.slice(0, 12)}…).`);
