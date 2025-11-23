#!/usr/bin/env node

/**
 * Relocates Markdown files into docs/ subfolders and rewrites inter-doc links.
 *
 * - Categorization is done by a rule-based resolver (see resolveCategory).
 * - Generates a deterministic move plan (old -> new) before making changes.
 * - Updates Markdown links (and inline code references) to point to new paths.
 *
 * Safe to rerun: files already in the correct place are skipped.
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const repoRoot = path.resolve(__dirname, '..', '..');
const docsRoot = path.join(repoRoot, 'docs');

const skipRoots = new Set([
  '.git',
  'node_modules',
  'vendor',
  'storage',
  'bootstrap/cache',
  '.kiro',
  'public',
]);

const explicitPathCategories = new Map([
  ['app/notifications/readme.md', 'notifications'],
  ['resources/frontend.md', 'frontend'],
]);

const explicitBaseCategories = new Map([['readme.md', 'overview']]);
const defaultNameTargets = new Map([['readme.md', 'docs/overview/readme.md']]);

const keywordRules = [
  { dir: 'refactoring', keywords: ['refactoring', 'analysis'] },
  { dir: 'tasks', keywords: ['task'] },
  { dir: 'routes', keywords: ['route'] },
  { dir: 'guides', keywords: ['guide', 'setup'] },
  { dir: 'architecture', keywords: ['architecture'] },
  { dir: 'integration', keywords: ['integration'] },
  { dir: 'implementation', keywords: ['implementation'] },
  { dir: 'frontend', keywords: ['frontend'] },
  { dir: 'reference', keywords: ['reference'] },
  { dir: 'reviews', keywords: ['review'] },
  { dir: 'notifications', keywords: ['notification'] },
];

const toPosix = (p) => p.split(path.sep).join('/');
const normalizeRel = (absPath) => toPosix(path.relative(repoRoot, absPath));

const listMarkdown = (dir) => {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const results = [];

  for (const entry of entries) {
    if (entry.name.startsWith('.') && entry.name !== '.kiro') continue;
    const rel = normalizeRel(path.join(dir, entry.name));
    const rootFragment = rel.split('/')[0];
    if (skipRoots.has(rootFragment)) continue;

    const fullPath = path.join(repoRoot, rel);
    if (entry.isDirectory()) {
      results.push(...listMarkdown(fullPath));
      continue;
    }

    if (entry.isFile() && entry.name.toLowerCase().endsWith('.md')) {
      results.push(fullPath);
    }
  }

  return results;
};

const resolveCategory = (relPath) => {
  const normalized = relPath.toLowerCase();

  const parts = normalized.split('/');
  if (parts[0] === 'docs' && parts[1]) {
    return parts[1];
  }

  if (explicitPathCategories.has(normalized)) {
    return explicitPathCategories.get(normalized);
  }

  const baseName = path.posix.basename(normalized);
  if (explicitBaseCategories.has(baseName)) {
    return explicitBaseCategories.get(baseName);
  }

  for (const rule of keywordRules) {
    if (rule.keywords.some((keyword) => normalized.includes(keyword))) {
      return rule.dir;
    }
  }

  return 'misc';
};

const computeTargetRel = (relPath) => {
  const category = resolveCategory(relPath);
  return path.posix.join('docs', category, path.posix.basename(relPath));
};

const ensureDir = (dirPath) => {
  fs.mkdirSync(dirPath, { recursive: true });
};

const mdFiles = listMarkdown(repoRoot);
if (!mdFiles.length) {
  console.log('No Markdown files found.');
  process.exit(0);
}

const movePlan = mdFiles.map((absPath) => {
  const oldRel = normalizeRel(absPath);
  const newRel = computeTargetRel(oldRel);
  return {
    oldRel,
    newRel,
    oldAbs: absPath,
    newAbs: path.join(repoRoot, newRel),
  };
});

const seenDestinations = new Map();
for (const { oldRel, newRel } of movePlan) {
  const key = newRel.toLowerCase();
  if (seenDestinations.has(key) && seenDestinations.get(key) !== oldRel.toLowerCase()) {
    throw new Error(`Destination collision: ${newRel} for ${oldRel} and ${seenDestinations.get(key)}`);
  }
  seenDestinations.set(key, oldRel);
}

const oldToNew = new Map(movePlan.map(({ oldRel, newRel }) => [oldRel, newRel]));
const oldToNewLower = new Map(movePlan.map(({ oldRel, newRel }) => [oldRel.toLowerCase(), newRel]));
const newToOld = new Map(movePlan.map(({ oldRel, newRel }) => [newRel, oldRel]));

const baseNameToNew = movePlan.reduce((acc, { newRel }) => {
  const base = path.posix.basename(newRel).toLowerCase();
  if (!acc.has(base)) {
    acc.set(base, new Set());
  }
  acc.get(base).add(newRel);
  return acc;
}, new Map());

const needsMove = movePlan.filter(({ oldRel, newRel }) => oldRel !== newRel);
for (const item of needsMove) {
  ensureDir(path.dirname(item.newAbs));
  fs.renameSync(item.oldAbs, item.newAbs);
  console.log(`Moved: ${item.oldRel} -> ${item.newRel}`);
}

const linkPattern = /\[([^\]]+)]\(([^)]+)\)/g;
const inlineCodePattern = /`([^`]+\.md)`/g;

const updateLinks = ({ newRel, newAbs }) => {
  const oldRel = newToOld.get(newRel) ?? newRel;
  const original = fs.readFileSync(newAbs, 'utf8');
  let updated = original;
  let changed = false;

  const rewriteTarget = (targetPathRaw) => {
    const hashIndex = targetPathRaw.indexOf('#');
    const anchor = hashIndex >= 0 ? targetPathRaw.slice(hashIndex) : '';
    const targetPath = hashIndex >= 0 ? targetPathRaw.slice(0, hashIndex) : targetPathRaw;

    if (!targetPath || targetPath.startsWith('http://') || targetPath.startsWith('https://') || targetPath.startsWith('mailto:')) {
      return null;
    }

    const resolvedOld = toPosix(
      path.posix.normalize(path.posix.join(path.posix.dirname(oldRel), targetPath))
    );

    const normalizedTarget = toPosix(path.posix.normalize(targetPath));
    const baseName = path.posix.basename(normalizedTarget).toLowerCase();
    const mapped =
      oldToNew.get(resolvedOld) ||
      oldToNew.get(normalizedTarget) ||
      oldToNewLower.get(resolvedOld.toLowerCase()) ||
      oldToNewLower.get(normalizedTarget.toLowerCase()) ||
      (baseNameToNew.get(baseName)?.size === 1
        ? Array.from(baseNameToNew.get(baseName))[0]
        : undefined) ||
      defaultNameTargets.get(baseName);
    if (!mapped) {
      return null;
    }

    const relativeFromNew = toPosix(
      path.posix.relative(path.posix.dirname(newRel), mapped)
    );

    return { path: relativeFromNew || './' + path.posix.basename(mapped), anchor };
  };

  updated = updated.replace(linkPattern, (match, text, target) => {
    const rewritten = rewriteTarget(target);
    if (!rewritten) return match;
    const next = `[${text}](${rewritten.path}${rewritten.anchor})`;
    if (next !== match) {
      changed = true;
    }
    return next;
  });

  updated = updated.replace(inlineCodePattern, (match, target) => {
    const rewritten = rewriteTarget(target);
    if (!rewritten) return match;
    const next = `[${target}](${rewritten.path}${rewritten.anchor})`;
    if (next !== match) {
      changed = true;
    }
    return next;
  });

  if (changed) {
    fs.writeFileSync(newAbs, updated, 'utf8');
    console.log(`Updated links: ${newRel}`);
  }
};

movePlan.forEach(({ newRel, newAbs }) => updateLinks({ newRel, newAbs }));

console.log(`Processed ${movePlan.length} Markdown files.`);
