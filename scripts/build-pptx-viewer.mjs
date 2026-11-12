import { readdirSync, existsSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

const root = process.cwd();
const platform = `${process.platform}-${process.arch}`;
const extension = process.platform === 'win32' ? '.exe' : '';

if (!process.env.ESBUILD_BINARY_PATH) {
  const store = join(root, 'node_modules', '.pnpm');
  if (existsSync(store)) {
    const prefix = `@esbuild+${platform}@`;
    const packageStore = readdirSync(store).filter((name) => name.startsWith(prefix)).sort().at(-1);
    if (packageStore) {
      const binary = join(store, packageStore, 'node_modules', '@esbuild', platform, `esbuild${extension}`);
      if (existsSync(binary)) process.env.ESBUILD_BINARY_PATH = binary;
    }
  }
}

const { build } = await import('esbuild');
await build({
  entryPoints: [resolve(root, 'public/assets/js/pptx-viewer.js')],
  bundle: true,
  minify: true,
  format: 'esm',
  platform: 'browser',
  target: ['es2022'],
  outfile: resolve(root, 'public/assets/js/pptx-viewer.bundle.js'),
  legalComments: 'eof',
  sourcemap: false,
});
console.log('Built public/assets/js/pptx-viewer.bundle.js and its stylesheet.');
