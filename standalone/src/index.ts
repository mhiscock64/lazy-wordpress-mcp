#!/usr/bin/env node
import { loadConfig } from './config.js';
import { createMcpServer } from './server.js';
import { startStdio } from './transport/stdio.js';
import { startHttp } from './transport/http.js';

async function main(): Promise<void> {
  const argvTransport = process.argv.includes('--http')
    ? 'http'
    : process.argv.includes('--stdio')
      ? 'stdio'
      : undefined;

  if (argvTransport) {
    process.env.MCP_TRANSPORT = argvTransport;
  }

  const cfg = loadConfig();
  if (cfg.MCP_TRANSPORT === 'http') {
    await startHttp(cfg);
  } else {
    const server = createMcpServer(cfg);
    await startStdio(server);
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
