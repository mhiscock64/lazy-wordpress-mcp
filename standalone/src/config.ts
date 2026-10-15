import { config as loadDotenv } from 'dotenv';
import { z } from 'zod';

loadDotenv();

const envSchema = z.object({
  WP_BASE_URL: z.string().url(),
  WP_USERNAME: z.string().min(1).optional(),
  WP_APPLICATION_PASSWORD: z.string().min(1).optional(),
  MCP_TRANSPORT: z.enum(['stdio', 'http']).default('stdio'),
  MCP_HTTP_HOST: z.string().default('127.0.0.1'),
  MCP_HTTP_PORT: z.coerce.number().int().positive().default(3847),
  MCP_HTTP_AUTH_TOKEN: z.string().optional().default(''),
  MCP_TOOL_ALLOWLIST: z.string().optional().default(''),
  MCP_RATE_LIMIT_PER_MINUTE: z.coerce.number().int().nonnegative().default(60),
  MCP_ENABLE_WRITES: z
    .string()
    .optional()
    .default('true')
    .transform((v) => v.toLowerCase() !== 'false' && v !== '0'),
  OAUTH_ACCESS_TOKEN: z.string().optional(),
});

export type AppConfig = z.infer<typeof envSchema> & {
  toolAllowlist: Set<string> | null;
  wpOrigin: string;
};

export function loadConfig(): AppConfig {
  const parsed = envSchema.safeParse(process.env);
  if (!parsed.success) {
    const msg = parsed.error.issues.map((i) => `${i.path.join('.')}: ${i.message}`).join('; ');
    throw new Error(`Invalid configuration: ${msg}`);
  }
  const data = parsed.data;
  if (!data.OAUTH_ACCESS_TOKEN && (!data.WP_USERNAME || !data.WP_APPLICATION_PASSWORD)) {
    throw new Error('Set WP_USERNAME + WP_APPLICATION_PASSWORD, or OAUTH_ACCESS_TOKEN');
  }
  const origin = new URL(data.WP_BASE_URL).origin;
  const allow = data.MCP_TOOL_ALLOWLIST.trim()
    ? new Set(
        data.MCP_TOOL_ALLOWLIST.split(',')
          .map((s) => s.trim())
          .filter(Boolean),
      )
    : null;
  return { ...data, toolAllowlist: allow, wpOrigin: origin };
}
