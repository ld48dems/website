import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const contentDir = join(root, 'assets', 'content');

function readArray(source, pattern, label) {
  const match = source.match(pattern);
  if (!match) throw new Error(`Could not find ${label} array.`);
  return Function(`"use strict"; return (${match[1]});`)();
}

await mkdir(contentDir, { recursive: true });

const endorsementsSource = await readFile(join(root, 'endorsements.dc.html'), 'utf8');
const groups = readArray(endorsementsSource, /groups:\s*(\[[\s\S]*?\]),\s*archives:/, 'endorsement groups');
const archives = readArray(endorsementsSource, /archives:\s*(\[[\s\S]*?\]),\s*\n\s*};/, 'endorsement archive');
await writeFile(
  join(contentDir, 'endorsements.json'),
  JSON.stringify({ cycle: '2026', groups, archives }, null, 2) + '\n',
  'utf8',
);

const resolutionsSource = await readFile(join(root, 'resolutions.dc.html'), 'utf8');
const resolutions = readArray(resolutionsSource, /resolutions:\s*(\[[\s\S]*?\]),\s*\n\s*};/, 'resolutions');
await writeFile(
  join(contentDir, 'resolutions.json'),
  JSON.stringify({ resolutions }, null, 2) + '\n',
  'utf8',
);

console.log(`Exported ${groups.reduce((count, group) => count + group.items.length, 0)} endorsement records.`);
console.log(`Exported ${resolutions.length} resolutions.`);