const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const SVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#1e293b"/>
      <stop offset="100%" stop-color="#0f172a"/>
    </linearGradient>
    <linearGradient id="gold" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#fef08a"/>
      <stop offset="40%" stop-color="#eab308"/>
      <stop offset="100%" stop-color="#a16207"/>
    </linearGradient>
  </defs>
  <rect width="64" height="64" rx="13" fill="url(#bg)"/>
  <g stroke="url(#gold)" fill="none" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20 48 L32 14 L44 48" stroke-width="4.5"/>
    <path d="M24 37 L40 37" stroke-width="3"/>
    <circle cx="32" cy="14" r="3.5" fill="url(#gold)"/>
    <circle cx="20" cy="48" r="2" fill="url(#gold)" stroke="none"/>
    <circle cx="44" cy="48" r="2" fill="url(#gold)" stroke="none"/>
    <line x1="22" y1="47" x2="30" y2="19" stroke-width="2" stroke-dasharray="2 2" opacity="0.5"/>
    <line x1="42" y1="47" x2="34" y2="19" stroke-width="2" stroke-dasharray="2 2" opacity="0.5"/>
  </g>
</svg>`;

async function main() {
  const assetsDir = path.join(__dirname, '..', 'assets');

  // 256x256 PNG
  await sharp(Buffer.from(SVG))
    .resize(256, 256)
    .png()
    .toFile(path.join(assetsDir, 'icon.png'));

  // 16x16 PNG (for tray icon)
  await sharp(Buffer.from(SVG))
    .resize(16, 16)
    .png()
    .toFile(path.join(assetsDir, 'tray.png'));

  // ICO requires multiple sizes — generate 32x32 PNG first
  // electron-builder can convert PNG to ICO
  await sharp(Buffer.from(SVG))
    .resize(256, 256)
    .png()
    .toFile(path.join(assetsDir, 'icon-256.png'));

  console.log('Icons generated successfully');
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
