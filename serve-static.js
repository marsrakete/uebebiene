const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");

const root = __dirname;
const portArgIndex = process.argv.findIndex((arg) => arg === "--port" || arg === "-p");
const port = Number(portArgIndex >= 0 ? process.argv[portArgIndex + 1] : process.env.PORT) || 5004;

const contentTypes = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".webmanifest": "application/manifest+json; charset=utf-8",
  ".svg": "image/svg+xml",
  ".png": "image/png",
  ".pdf": "application/pdf",
  ".txt": "text/plain; charset=utf-8",
};

function sendText(response, statusCode, text) {
  const body = Buffer.from(text, "utf8");
  response.writeHead(statusCode, {
    "content-type": "text/plain; charset=utf-8",
    "content-length": body.length,
  });
  response.end(body);
}

function resolveRequestPath(requestUrl) {
  const url = new URL(requestUrl, `http://localhost:${port}`);
  const requestedPath = url.pathname === "/" ? "/index.html" : url.pathname;
  const decodedPath = decodeURIComponent(requestedPath);
  const normalizedPath = path.normalize(decodedPath).replace(/^([/\\]*(\.\.[/\\])+)+/, "");
  return path.join(root, normalizedPath);
}

const server = http.createServer((request, response) => {
  let filePath;
  try {
    filePath = resolveRequestPath(request.url || "/");
  } catch {
    sendText(response, 400, "400 - Ungueltige Anfrage");
    return;
  }

  if (!filePath.startsWith(root)) {
    sendText(response, 403, "403 - Zugriff verweigert");
    return;
  }

  fs.stat(filePath, (statError, stats) => {
    if (statError || !stats.isFile()) {
      sendText(response, 404, "404 - Datei nicht gefunden");
      return;
    }

    const extension = path.extname(filePath).toLowerCase();
    response.writeHead(200, {
      "content-type": contentTypes[extension] || "application/octet-stream",
      "content-length": stats.size,
    });
    fs.createReadStream(filePath).pipe(response);
  });
});

server.listen(port, "localhost", () => {
  console.log(`UebeBiene laeuft auf http://localhost:${port}/`);
  console.log("Zum Beenden Strg+C druecken.");
});

function shutdown() {
  server.close(() => process.exit(0));
}

process.on("SIGINT", shutdown);
process.on("SIGTERM", shutdown);
