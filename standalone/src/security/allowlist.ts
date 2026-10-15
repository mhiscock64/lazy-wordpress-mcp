import type { AppConfig } from '../config.js';

export function isToolAllowed(cfg: AppConfig, toolName: string): boolean {
  if (!cfg.toolAllowlist) return true;
  return cfg.toolAllowlist.has(toolName);
}
