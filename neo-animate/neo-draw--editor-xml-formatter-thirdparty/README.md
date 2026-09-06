We use this folder to provide an XML formatter (NPM xml-formatter package) to neoDraw.
The XML formatter is used to beautify the exported neoDraw SVGs.

- To bundle it into `neo-draw--editor-xml-formatter.min.js` in the neoDraw folder, run `npm run build` in this folder.
- To update the `xml-formatter` package, update it in `package.json` and run `npm install` in this folder. Then run `npm run build` in this folder again.