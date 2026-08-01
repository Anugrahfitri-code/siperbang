import { access, mkdir, writeFile } from "node:fs/promises";
import path from "node:path";
import {
  buildContainsSourceHash,
  buildDirectory,
  calculateBuildHash,
} from "./build-artifact-hash.mjs";
import { calculateSourceHash } from "./build-source-hash.mjs";

await access(path.join(buildDirectory, "manifest.json"));
const source = await calculateSourceHash();

if (!(await buildContainsSourceHash(source.hash))) {
  throw new Error(
    "Build assets do not contain the current source fingerprint. Run a clean Vite build.",
  );
}

const build = await calculateBuildHash();
await mkdir(buildDirectory, { recursive: true });
await writeFile(
  path.join(buildDirectory, "build-meta.json"),
  `${JSON.stringify(
    {
      schema: 1,
      source_hash: source.hash,
      source_files: source.files.length,
      build_hash: build.hash,
      build_files: build.files.length,
      generated_at_utc: new Date().toISOString(),
      node_version: process.version,
    },
    null,
    2,
  )}\n`,
  "utf8",
);

console.log(
  `Build metadata written (${source.hash.slice(0, 12)}…, ${build.files.length} build files).`,
);
